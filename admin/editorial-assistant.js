/**
 * Mex-Ahoy! — Asistente Editorial Real (Firebase AI Logic + Gemini 4 Carbon/Neon)
 * Incluye:
 *  - Conexión directa a Firebase AI Logic (GoogleAIBackend + Google Search Grounding)
 *  - Streaming en tiempo real
 *  - Historial dedicado por artículo
 *  - Sanitizador estricto Cero-Guiones (convierte — / – en paréntesis o comas)
 *  - Detector de Clichés de IA en inglés (WP:AISIGNS)
 *  - Limpiador inteligente de texto pegado desde Microsoft Word
 */

import { initializeApp, getApps, getApp } from 'https://www.gstatic.com/firebasejs/13.4.0/firebase-app.js';
import { getAI, getGenerativeModel, GoogleAIBackend } from 'https://www.gstatic.com/firebasejs/13.4.0/firebase-ai.js';
import { MEX_AHOY_EDITORIAL_CONFIG } from './editorial-rules.js';

let firebaseAppInstance = null;
let aiInstance = null;
let appCheckInitialized = false;

function getSelectedModelId() {
  const saved = localStorage.getItem('mexahoy_ai_model');
  const valid = MEX_AHOY_EDITORIAL_CONFIG.models.some(m => m.id === saved);
  return valid ? saved : MEX_AHOY_EDITORIAL_CONFIG.defaultModel;
}

function setSelectedModelId(modelId) {
  localStorage.setItem('mexahoy_ai_model', modelId);
}

async function ensureFirebaseAI() {
  if (aiInstance) return aiInstance;

  const fbConfig = window.MEX_AHOY_FIREBASE_CONFIG || (typeof MEX_AHOY_FIREBASE_CONFIG !== 'undefined' ? MEX_AHOY_FIREBASE_CONFIG : null);
  if (!fbConfig) {
    throw new Error('No se encontró la configuración de Firebase (js/firebase-config.js).');
  }

  firebaseAppInstance = getApps().length > 0
    ? getApp()
    : initializeApp(fbConfig);

  // Activar App Check automáticamente si Julia configuró su Site Key en editorial-rules.js
  if (!appCheckInitialized && MEX_AHOY_EDITORIAL_CONFIG.appCheckSiteKey) {
    try {
      const appCheckMod = await import('https://www.gstatic.com/firebasejs/13.4.0/firebase-app-check.js');
      const ProviderClass = MEX_AHOY_EDITORIAL_CONFIG.appCheckProvider === 'v3'
        ? appCheckMod.ReCaptchaV3Provider
        : appCheckMod.ReCaptchaEnterpriseProvider;
      appCheckMod.initializeAppCheck(firebaseAppInstance, {
        provider: new ProviderClass(MEX_AHOY_EDITORIAL_CONFIG.appCheckSiteKey),
        isTokenAutoRefreshEnabled: true
      });
      appCheckInitialized = true;
    } catch (err) {
      console.warn('App Check initialization skipped:', err);
    }
  }

  aiInstance = getAI(firebaseAppInstance, { backend: new GoogleAIBackend() });
  return aiInstance;
}

/**
 * Sanitizador estricto Cero-Guiones ("como Dios manda": paréntesis o comas).
 * Elimina cualquier em-dash (—), en-dash (–) o guion suelto entre espacios ( - ).
 */
export function enforceNoDashes(text) {
  if (!text) return '';
  let out = String(text);

  // 1. Datelines clásicos al inicio de párrafo: "MEXICO CITY — " -> "MEXICO CITY: "
  out = out.replace(/^([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s.,]{1,32}?)\s*[—–]\s*/gm, '$1: ');

  // 2. Pares de guiones largos/medios usados como inciso: " —texto— " -> " (texto) "
  out = out.replace(/\s*[—–]\s*([^—–\n]{2,140}?)\s*[—–]\s*/g, ' ($1) ');

  // 3. Cualquier guion largo/medio restante al final de una cláusula -> coma
  out = out.replace(/\s*[—–]\s*/g, ', ');

  // 4. Guion simple usado como inciso entre espacios: " - " -> ", " (conservando palabras compuestas como well-known)
  out = out.replace(/\s+-\s+/g, ', ');

  // Limpiar dobles comas o espacios accidentales
  out = out.replace(/,\s*,/g, ',').replace(/\s{2,}/g, ' ').replace(/ \)/g, ')').replace(/\( /g, '(');
  return out.trim();
}

/**
 * Extrae el bloque de texto listo para insertar en inglés, si la IA lo incluyó.
 */
export function parseAssistantOutput(rawText) {
  const fullText = String(rawText || '').trim();
  const markerRegex = /===\s*TEXTO LISTO PARA INSERTAR(?:\s*\(INGLÉS\))?\s*===([\s\S]*?)===\s*FIN\s*===/i;
  const match = fullText.match(markerRegex);

  if (match) {
    const cleanInsert = enforceNoDashes(match[1].trim());
    const explanation = fullText.replace(markerRegex, '').trim();
    return {
      explanation: explanation || 'Aquí tiene la propuesta lista para su artículo:',
      insertText: cleanInsert
    };
  }

  return {
    explanation: fullText,
    insertText: null
  };
}

/**
 * Envía una consulta real a Gemini 4 Carbón / Neón vía Firebase AI Logic
 * con búsqueda en Google en tiempo real (googleSearch) y streaming.
 */
export async function askArticleAssistant({
  article,
  actionType,
  userQuestion,
  history = [],
  onStreamUpdate = null
}) {
  const ai = await ensureFirebaseAI();
  const modelId = getSelectedModelId();

  const model = getGenerativeModel(ai, {
    model: modelId,
    systemInstruction: MEX_AHOY_EDITORIAL_CONFIG.systemPrompt,
    tools: [{ googleSearch: {} }],
    generationConfig: {
      temperature: 0.65,
      topP: 0.92,
      maxOutputTokens: 2400
    }
  });

  const articleContextBlock = [
    `CURRENT ARTICLE IN MEX-AHOY! EDITOR:`,
    `- Title: ${article.title || '(Untitled draft)'}`,
    `- Subtitle: ${article.subtitle || '(None yet)'}`,
    `- Category: ${article.category || 'POLITICS'}`,
    `- Author: ${article.author || 'Ricardo Castillo'}`,
    `- Date: ${article.date || ''}`,
    `- Current Draft Body:`,
    `${(article.body || '').slice(0, 9000)}`
  ].join('\n');

  const taskPrompts = {
    historical: `Investiga el CONTEXTO HISTÓRICO real y verificable relacionado con este artículo en particular (fechas exactas, antecedentes en México o en la relación bilateral, reformas o leyes históricas relevantes).
1) Primero presenta un resumen claro y breve en español para Don Ricardo (máximo 2-3 párrafos cortos con fechas y datos exactos).
2) Después incluye el bloque === TEXTO LISTO PARA INSERTAR (INGLÉS) === ... === FIN === con 1 o 2 párrafos breves en inglés periodístico natural listos para añadir al artículo, respetando TODAS las reglas Anti-IA y CERO guiones largos o medios.`,

    political: `Investiga el CONTEXTO POLÍTICO ACTUAL relacionado con este artículo en particular (actores involucrados, partidos políticos, postura de Palacio Nacional, Congreso o Washington, e implicaciones concretas).
1) Primero presenta un análisis directo y claro en español para Don Ricardo (con nombres propios y fuentes verificadas, sin especulaciones vagas).
2) Después incluye el bloque === TEXTO LISTO PARA INSERTAR (INGLÉS) === ... === FIN === con 1 o 2 párrafos en inglés periodístico natural listos para añadir al artículo, respetando TODAS las reglas Anti-IA y CERO guiones.`,

    facts: `Verifica los DATOS, FECHAS, CIFRAS, CARGOS OFICIALES Y MARCO LEGAL que aparecen o faltan en este artículo usando búsqueda en Google.
1) Presenta a Don Ricardo en español una lista muy clara de datos verificados y, si detectas algún error o dato que conviene confirmar, avísale con respeto.
2) Incluye el bloque === TEXTO LISTO PARA INSERTAR (INGLÉS) === ... === FIN === con un breve párrafo factual de contexto en inglés (sin guiones ni clichés de IA) si aporta valor al artículo.`,

    polish: `Revisa y pule la redacción en INGLÉS del borrador actual de Don Ricardo (o tradúcelo al inglés periodístico natural si hay partes escritas en español).
IMPORTANTE:
- Conserva intacta la voz, el criterio y el estilo personal de Don Ricardo.
- Corrige gramática, fluidez y elimina cualquier cliché de IA de Wikipedia.
- CERO guiones largos (—) ni medios (–): usa solo paréntesis (...), comas o puntos.
1) En español, explica en 2 o 3 líneas muy breves qué mejoraste.
2) Coloca el texto pulido completo en inglés dentro del bloque === TEXTO LISTO PARA INSERTAR (INGLÉS) === ... === FIN ===.`,

    headlines: `Propón exactamente 3 titulares periodísticos excelentes en INGLÉS para este artículo de Mex-Ahoy! y 1 subtítulo (deck) breve.
REGLAS PARA LOS TITULARES:
- Estilo de periódico real, directo y natural (como los titulares clásicos de Don Ricardo).
- PROHIBIDO usar guiones largos (—) o repetir la fórmula robótica de IA "Frase dramática: Cómo X transforma Y".
- Responde únicamente en este formato exacto para que el panel pueda mostrar botones de 1 clic:
HEADLINE_1: ...
HEADLINE_2: ...
HEADLINE_3: ...
SUBTITLE: ...`,

    fix_cliches: `El Detector de Clichés encontró expresiones que suenan a IA o guiones prohibidos en este artículo:
${userQuestion || 'Revisa clichés de IA y guiones largos.'}

Reescribe ÚNICAMENTE las partes afectadas del artículo en inglés periodístico humano y natural, conservando fielmente las ideas y la voz de Don Ricardo, con CERO guiones largos/medios (usa paréntesis, comas o puntos) y CERO palabras prohibidas de Wikipedia WP:AISIGNS.
1) Explica brevemente en español qué limpiaste.
2) Entrega el cuerpo limpio completo en inglés dentro de === TEXTO LISTO PARA INSERTAR (INGLÉS) === ... === FIN ===.`,

    custom: `Don Ricardo pregunta lo siguiente sobre este artículo en particular:
"${userQuestion}"

Responde con claridad y respeto en español (usando búsqueda en Google si requiere investigar datos históricos o políticos actualizados). Si tu respuesta incluye un párrafo o dato que le convenga agregar al periódico, inclúyelo también en inglés periodístico limpio dentro del bloque === TEXTO LISTO PARA INSERTAR (INGLÉS) === ... === FIN === (con CERO guiones y CERO clichés de IA).`
  };

  // Incluir memoria reciente de ESTE artículo (últimos 6 turnos para mantener foco sin saturar)
  const recentHistory = history
    .slice(-6)
    .filter(m => m && (m.role === 'user' || m.role === 'assistant') && m.rawText)
    .map(m => `${m.role === 'user' ? 'Don Ricardo' : 'Asistente'}: ${m.rawText.slice(0, 900)}`)
    .join('\n\n');

  const finalPrompt = [
    articleContextBlock,
    recentHistory ? `\nCONVERSACIÓN PREVIA SOBRE ESTE MISMO ARTÍCULO:\n${recentHistory}` : '',
    `\nTAREA ACTUAL:\n${taskPrompts[actionType] || taskPrompts.custom}`
  ].join('\n');

  const streamResult = await model.generateContentStream(finalPrompt);
  let accumulated = '';

  for await (const chunk of streamResult.stream) {
    const piece = chunk.text();
    if (piece) {
      accumulated += piece;
      if (typeof onStreamUpdate === 'function') {
        onStreamUpdate(accumulated);
      }
    }
  }

  const finalResponse = await streamResult.response;
  const finalText = accumulated || finalResponse.text() || '';

  // Extraer fuentes reales de Google Search Grounding si están disponibles
  const sources = [];
  try {
    const candidate = finalResponse.candidates && finalResponse.candidates[0];
    const chunks = candidate?.groundingMetadata?.groundingChunks || [];
    const seen = new Set();
    for (const ch of chunks) {
      if (ch.web && ch.web.uri && !seen.has(ch.web.uri)) {
        seen.add(ch.web.uri);
        sources.push({
          title: ch.web.title || new URL(ch.web.uri).hostname,
          url: ch.web.uri
        });
      }
    }
  } catch (e) {
    // Grounding metadata optional
  }

  // Parseo especial para 3 titulares
  if (actionType === 'headlines') {
    const h1 = (finalText.match(/HEADLINE_1:\s*(.+)/i) || [])[1];
    const h2 = (finalText.match(/HEADLINE_2:\s*(.+)/i) || [])[1];
    const h3 = (finalText.match(/HEADLINE_3:\s*(.+)/i) || [])[1];
    const sub = (finalText.match(/SUBTITLE:\s*(.+)/i) || [])[1];
    const headlines = [h1, h2, h3].filter(Boolean).map(h => enforceNoDashes(h.replace(/^["']|["']$/g, '').trim()));
    return {
      rawText: finalText,
      explanation: headlines.length ? 'Aquí tiene 3 opciones de titular natural para su artículo:' : finalText,
      headlines,
      suggestedSubtitle: sub ? enforceNoDashes(sub.replace(/^["']|["']$/g, '').trim()) : null,
      insertText: null,
      sources,
      modelUsed: modelId
    };
  }

  const parsed = parseAssistantOutput(finalText);
  return {
    rawText: finalText,
    explanation: parsed.explanation,
    insertText: parsed.insertText,
    headlines: null,
    sources,
    modelUsed: modelId
  };
}

/**
 * Detector de Clichés de IA en Inglés (basado en Wikipedia WP:AISIGNS + Cero Guiones).
 */
export function scanArticleCliches(title = '', subtitle = '', body = '') {
  const combined = [title, subtitle, body].filter(Boolean).join('\n\n');
  const findings = [];

  // 1. Reglas estructurales (guiones prohibidos, paralelismo negativo, colas -ing)
  for (const rule of MEX_AHOY_EDITORIAL_CONFIG.structuralRules) {
    const matches = combined.match(rule.pattern);
    if (matches && matches.length > 0) {
      findings.push({
        severity: rule.id === 'no-dashes' || rule.id === 'spaced-hyphen' ? 'high' : 'medium',
        category: 'Estructura / Puntuación',
        found: rule.label,
        count: matches.length,
        examples: [...new Set(matches.slice(0, 3))],
        tip: rule.tip
      });
    }
  }

  // 2. Vocabulario prohibido de IA en inglés
  for (const item of MEX_AHOY_EDITORIAL_CONFIG.bannedVocabularyEn) {
    const escaped = item.phrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`\\b${escaped}\\b`, 'gi');
    const matches = combined.match(regex);
    if (matches && matches.length > 0) {
      findings.push({
        severity: 'medium',
        category: 'Palabra cliché de IA (Wikipedia)',
        found: `"${item.phrase}"`,
        count: matches.length,
        examples: [item.phrase],
        tip: item.tip
      });
    }
  }

  // 3. Fuentes fantasma (Weasel words)
  for (const phrase of MEX_AHOY_EDITORIAL_CONFIG.weaselPhrasesEn) {
    const escaped = phrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`\\b${escaped}\\b`, 'gi');
    const matches = combined.match(regex);
    if (matches && matches.length > 0) {
      findings.push({
        severity: 'low',
        category: 'Fuente vaga (Weasel word)',
        found: `"${phrase}"`,
        count: matches.length,
        examples: [phrase],
        tip: 'Nombrar exactamente quién lo dijo (persona, institución o medio).'
      });
    }
  }

  return findings;
}

/**
 * Limpiador inteligente para textos copiados y pegados desde Microsoft Word.
 * Quita XML invisible, estilos mso-*, fuentes rotas, espacios raros y guiones si se elige.
 */
export function cleanWordContent(htmlInput = '', plainInput = '', removeDashes = true) {
  let text = '';

  if (htmlInput && htmlInput.trim()) {
    // Quitar comentarios condicionales de Word <!--[if gte mso 9]>...<![endif]--> y etiquetas <style>/<xml>
    let cleanedHtml = htmlInput
      .replace(/<!--\[if[\s\S]*?endif\]-->/gi, '')
      .replace(/<!--[\s\S]*?-->/g, '')
      .replace(/<style[\s\S]*?<\/style>/gi, '')
      .replace(/<xml[\s\S]*?<\/xml>/gi, '')
      .replace(/<head[\s\S]*?<\/head>/gi, '');

    // Parsear en DOM seguro (sin ejecutar scripts)
    const parser = new DOMParser();
    const doc = parser.parseFromString(cleanedHtml, 'text/html');
    const blocks = [];

    const walk = (node) => {
      if (!node) return;
      if (node.nodeType === Node.ELEMENT_NODE) {
        const tag = node.tagName.toUpperCase();
        if (['SCRIPT', 'STYLE', 'META', 'LINK', 'TITLE'].includes(tag)) return;

        if (['H1', 'H2', 'H3', 'H4'].includes(tag)) {
          const headingText = normalizeWhitespace(node.textContent);
          if (headingText) blocks.push(`### ${headingText}`);
          return;
        }
        if (tag === 'BLOCKQUOTE') {
          const qText = normalizeWhitespace(node.textContent);
          if (qText) blocks.push(`> ${qText}`);
          return;
        }
        if (tag === 'LI') {
          const liText = normalizeWhitespace(node.textContent);
          if (liText) blocks.push(`• ${liText}`);
          return;
        }
        if (tag === 'P' || tag === 'DIV') {
          // Si contiene otros bloques <p>/<div>, bajar recursivamente
          const hasChildBlocks = Array.from(node.children).some(ch =>
            ['P', 'DIV', 'H1', 'H2', 'H3', 'H4', 'UL', 'OL', 'BLOCKQUOTE'].includes(ch.tagName.toUpperCase())
          );
          if (hasChildBlocks) {
            Array.from(node.childNodes).forEach(walk);
          } else {
            const pText = normalizeWhitespace(node.textContent);
            if (pText) blocks.push(pText);
          }
          return;
        }
        Array.from(node.childNodes).forEach(walk);
      }
    };

    walk(doc.body);
    text = blocks.join('\n\n');
  }

  // Respaldo con texto plano si el HTML venía vacío o no tenía bloques
  if (!text.trim() && plainInput) {
    text = String(plainInput)
      .replace(/\r\n/g, '\n')
      .replace(/\r/g, '\n')
      .split(/\n{2,}/)
      .map(p => normalizeWhitespace(p))
      .filter(Boolean)
      .join('\n\n');
  }

  // Normalizar comillas raras de Word y espacios invisibles
  text = text
    .replace(/\u00A0/g, ' ')
    .replace(/[\u2018\u2019]/g, "'")
    .replace(/[\u201C\u201D]/g, '"')
    .replace(/\u2026/g, '...')
    .replace(/\n{3,}/g, '\n\n')
    .trim();

  if (removeDashes) {
    text = text
      .split('\n\n')
      .map(block => enforceNoDashes(block))
      .join('\n\n');
  }

  const paragraphs = text ? text.split('\n\n').filter(Boolean) : [];
  return {
    cleanedText: text,
    paragraphCount: paragraphs.length,
    wordCount: text ? text.split(/\s+/).filter(Boolean).length : 0
  };
}

function normalizeWhitespace(str) {
  return String(str || '')
    .replace(/\u00A0/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

/**
 * Convierte el HTML almacenado en articles.json a texto editorial limpio y legible
 * para que Don Ricardo no vea etiquetas <p class="drop-cap"> confusas.
 */
export function htmlBodyToCleanText(html = '') {
  if (!html) return '';
  if (!/<[a-z][\s\S]*>/i.test(html)) return html.trim();

  const parser = new DOMParser();
  const doc = parser.parseFromString(html, 'text/html');
  const lines = [];

  Array.from(doc.body.children).forEach(el => {
    const tag = el.tagName.toUpperCase();
    if (tag === 'H2' || tag === 'H3' || tag === 'H4') {
      const t = normalizeWhitespace(el.textContent);
      if (t) lines.push(`### ${t}`);
    } else if (tag === 'BLOCKQUOTE') {
      const citeEl = el.querySelector('cite');
      const citeText = citeEl ? normalizeWhitespace(citeEl.textContent) : '';
      if (citeEl) citeEl.remove();
      const quoteText = normalizeWhitespace(el.textContent);
      if (quoteText) {
        lines.push(`> ${quoteText}${citeText ? ' ' + citeText : ''}`);
      }
    } else if (tag === 'UL' || tag === 'OL') {
      const items = Array.from(el.querySelectorAll('li'))
        .map(li => `• ${normalizeWhitespace(li.textContent)}`)
        .filter(Boolean);
      if (items.length) lines.push(items.join('\n'));
    } else {
      const t = normalizeWhitespace(el.textContent);
      if (t) lines.push(t);
    }
  });

  return lines.join('\n\n');
}

/**
 * Convierte el texto limpio del editor de vuelta al HTML exacto de Mex-Ahoy!
 * (<p class="drop-cap">, <h3>, <blockquote class="newspaper-quote">, <ul><li>).
 */
export function cleanTextToNewspaperHtml(text = '') {
  const raw = String(text || '').trim();
  if (!raw) return '';
  // Si el usuario pegó HTML deliberadamente, respetarlo
  if (/^\s*<(p|h3|blockquote|div|ul)/i.test(raw)) return raw;

  const blocks = raw.replace(/\r\n/g, '\n').split(/\n{2,}/).map(b => b.trim()).filter(Boolean);
  let firstParagraphDone = false;

  return blocks.map(block => {
    if (block.startsWith('### ')) {
      return `<h3>${escapeHtml(block.slice(4).trim())}</h3>`;
    }
    if (block.startsWith('> ')) {
      const content = block.slice(2).trim();
      return `<blockquote class="newspaper-quote">\n  <p>${escapeHtml(content)}</p>\n</blockquote>`;
    }
    const bulletLines = block.split('\n').map(l => l.trim()).filter(Boolean);
    if (bulletLines.every(l => l.startsWith('• ') || l.startsWith('- '))) {
      const lis = bulletLines.map(l => `  <li>${escapeHtml(l.slice(2).trim())}</li>`).join('\n');
      return `<ul>\n${lis}\n</ul>`;
    }
    const joined = escapeHtml(block).replace(/\n/g, '<br>');
    if (!firstParagraphDone) {
      firstParagraphDone = true;
      return `<p class="drop-cap">${joined}</p>`;
    }
    return `<p>${joined}</p>`;
  }).join('\n\n');
}

function escapeHtml(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

// Exponer API en window para uso inmediato desde admin/index.php
window.MexAhoyAI = {
  config: MEX_AHOY_EDITORIAL_CONFIG,
  getSelectedModelId,
  setSelectedModelId,
  askArticleAssistant,
  enforceNoDashes,
  scanArticleCliches,
  cleanWordContent,
  htmlBodyToCleanText,
  cleanTextToNewspaperHtml
};
