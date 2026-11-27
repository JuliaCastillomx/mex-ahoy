/**
 * Mex-Ahoy! — Reglas Editoriales Anti-IA y Configuración del Asistente
 * -------------------------------------------------------------------
 * Julia: puedes agregar o quitar palabras prohibidas en esta lista cuando
 * quieras sin tocar el resto del código del panel.
 *
 * Basado en Wikipedia:Signs of AI writing (WP:AISIGNS) + voz editorial
 * de Don Ricardo Castillo (periodismo en inglés, claro y directo).
 */

export const MEX_AHOY_EDITORIAL_CONFIG = {
  // Modelo predeterminado de Gemini (vía Firebase AI Logic)
  defaultModel: 'gemini-4-carbon',
  fallbackModel: 'gemini-4-neon',

  models: [
    {
      id: 'gemini-4-carbon',
      label: 'Gemini 4 Carbón (Recomendado • Redacción profunda y precisa)',
      shortLabel: 'Carbón'
    },
    {
      id: 'gemini-4-neon',
      label: 'Gemini 4 Neón (Más rápido • Ideal para consultas ágiles)',
      shortLabel: 'Neón'
    }
  ],

  // Firebase App Check (reCAPTCHA v3) — clave pública de sitio para proteger la cuota
  appCheckSiteKey: '6LfRjTQuAAAAAOErIFjkDBMuzRHqtbQ7F6sEy0_8',
  appCheckProvider: 'v3',

  /**
   * Lista de palabras y expresiones cliché de IA en inglés (WP:AISIGNS).
   * El Detector de Clichés revisa el artículo contra esta lista antes de publicar.
   */
  bannedVocabularyEn: [
    { phrase: 'delve', tip: 'Usar: examine, look into, explore' },
    { phrase: 'tapestry', tip: 'Usar: mix, history, fabric, variety' },
    { phrase: 'testament', tip: 'Usar: proof, sign, evidence' },
    { phrase: 'beacon', tip: 'Usar: example, symbol, model' },
    { phrase: 'cornerstone', tip: 'Usar: foundation, core, basis (salvo primera piedra literal)' },
    { phrase: 'pivotal', tip: 'Usar: key, decisive, major' },
    { phrase: 'crucial role', tip: 'Usar: major part, key role, or name the exact action' },
    { phrase: 'vital role', tip: 'Usar: key role, or state directly what it did' },
    { phrase: 'underscore', tip: 'Usar: show, confirm, point to' },
    { phrase: 'underscores', tip: 'Usar: shows, confirms, points to' },
    { phrase: 'underscoring', tip: 'Eliminar gerundio de cierre o usar verbo directo' },
    { phrase: 'highlight the importance', tip: 'Mostrar el hecho directo sin decir que es importante' },
    { phrase: 'highlighting', tip: 'Evitar cola con gerundio; usar oración nueva con verbo directo' },
    { phrase: 'foster', tip: 'Usar: encourage, build, support' },
    { phrase: 'fostering', tip: 'Usar: building, supporting, encouraging' },
    { phrase: 'showcase', tip: 'Usar: show, display, present' },
    { phrase: 'showcasing', tip: 'Usar: showing, displaying' },
    { phrase: 'vibrant', tip: 'Describir el color, sonido o dato concreto' },
    { phrase: 'bustling', tip: 'Usar: busy, crowded, packed' },
    { phrase: 'landscape', tip: 'Usar: scene, market, politics, field (salvo paisaje físico)' },
    { phrase: 'interplay', tip: 'Usar: relationship, balance, tension' },
    { phrase: 'intricate', tip: 'Usar: complex, detailed' },
    { phrase: 'nuanced', tip: 'Explicar los dos lados con hechos concretos' },
    { phrase: 'indelible mark', tip: 'Usar: lasting effect, long-term impact' },
    { phrase: 'paradigm', tip: 'Usar: model, system, approach' },
    { phrase: 'realm', tip: 'Usar: area, field, world' },
    { phrase: 'embark', tip: 'Usar: begin, start, launch' },
    { phrase: 'leverage', tip: 'Usar: use, rely on' },
    { phrase: 'navigate', tip: 'Usar: handle, deal with, face (salvo navegación real)' },
    { phrase: 'reshaping', tip: 'Usar: changing, altering' },
    { phrase: 'reshape', tip: 'Usar: change, alter' },
    { phrase: 'sobering reminder', tip: 'Dejar que el hecho hable por sí mismo' },
    { phrase: 'game-changer', tip: 'Usar: turning point, major shift' },
    { phrase: 'seismic shift', tip: 'Usar: major change, sharp turn' },
    { phrase: 'irreplaceable global treasure', tip: 'Evitar tono turístico promocional (puffery)' },
    { phrase: 'serves as a testament', tip: 'Usar: shows, proves' },
    { phrase: 'it is important to note', tip: 'Eliminar relleno e ir directo al dato' },
    { phrase: 'it is worth noting', tip: 'Eliminar relleno e ir directo al dato' },
    { phrase: 'in summary', tip: 'Evitar cierre de ensayo escolar' },
    { phrase: 'overall,', tip: 'Evitar cierre de ensayo escolar' },
    { phrase: 'only time will tell', tip: 'Evitar cierre cliché; cerrar con hecho o pregunta real' },
    { phrase: 'challenges remain', tip: 'Nombrar el obstáculo específico' },
    { phrase: 'more than just', tip: 'Evitar fórmula de paralelismo negativo' },
    { phrase: 'set the stage for', tip: 'Usar: led to, prepared, opened the way for' },
    { phrase: 'a deeper understanding', tip: 'Explicar concretamente qué se entiende mejor' },
    { phrase: 'rich tapestry', tip: 'Cliché #1 de IA en Wikipedia; eliminar' },
    { phrase: 'unprecedented', tip: 'Verificar si realmente nunca ocurrió; si no, usar rare o historic' }
  ],

  /**
   * Fuentes fantasma ("Weasel words" de Wikipedia): afirmaciones sin nombre ni institución.
   */
  weaselPhrasesEn: [
    'some critics argue',
    'critics argue',
    'observers point out',
    'observers say',
    'experts believe',
    'experts say',
    'many believe',
    'some analysts say',
    'analysts warn'
  ],

  /**
   * Reglas estructurales (regex) detectadas por el escáner.
   */
  structuralRules: [
    {
      id: 'no-dashes',
      label: 'Guion largo o medio (— / –) PROHIBIDO',
      pattern: /[—–]/g,
      tip: 'Regla estricta de Mex-Ahoy!: sustituir siempre por paréntesis (...), coma o punto.'
    },
    {
      id: 'spaced-hyphen',
      label: 'Guion suelto entre espacios ( - ) usado como inciso',
      pattern: /\s-\s/g,
      tip: 'Usar paréntesis (...), coma o punto en lugar de guion.'
    },
    {
      id: 'negative-parallelism',
      label: 'Paralelismo negativo típico de IA ("not only... but also" / "not just... it is")',
      pattern: /\b(not only\b.{1,90}\bbut also|it(?:'s| is) not just\b.{1,70}\bit(?:'s| is))\b/gi,
      tip: 'Reescribir en una oración afirmativa y directa.'
    },
    {
      id: 'trailing-ing',
      label: 'Cierre artificial con gerundio (", highlighting / underscoring / signaling...")',
      pattern: /,\s*(highlighting|underscoring|signaling|emphasizing|illustrating|reflecting|demonstrating|reinforcing|revealing|marking)\b/gi,
      tip: 'Cortar la oración con punto y empezar otra frase clara con verbo activo.'
    }
  ],

  /**
   * System Prompt oficial del Asistente Editorial de Mex-Ahoy!
   * Enforceable on every Gemini 4 Carbon / Neon call via Firebase AI Logic.
   */
  systemPrompt: `You are the dedicated Editorial Research & Writing Assistant for Don Ricardo Castillo, the 84-year-old Editor-in-Chief and Senior Political Analyst of "Mex-Ahoy!", an independent English-language digital newspaper about Mexico.

HOW TO COMMUNICATE WITH DON RICARDO:
- Don Ricardo prefers interacting with you in warm, respectful, clear Spanish (treat him with respect as "Don Ricardo", never condescending, always concise and easy to read).
- However, Mex-Ahoy! is published 100% IN ENGLISH. Therefore:
  1. Write your brief research notes, explanations, and historical/political briefings in clear, natural Spanish (unless he explicitly asks in English).
  2. Whenever you propose a paragraph, edit, translation, headline, or subtitle to insert into the newspaper, ALWAYS write that publishable text in crisp, authentic, natural journalistic ENGLISH inside a clearly marked block:
     === TEXTO LISTO PARA INSERTAR (INGLÉS) ===
     (your clean English newspaper prose here)
     === FIN ===

STRICT ANTI-AI ENGLISH WRITING RULES (BASED ON WIKIPEDIA WP:AISIGNS — ZERO EXCEPTIONS):
1. ABSOLUTELY NO DASHES: NEVER use em-dashes (—), en-dashes (–), or spaced hyphens ( - ) for parenthetical remarks or breaks. NOT EVEN ONE. Whenever you need a clarification or aside, ALWAYS use parentheses (...) or a comma or a period. Even for datelines, write "MEXICO CITY:" or "MEXICO CITY," instead of an em-dash.
2. BANNED AI CLICHÉ VOCABULARY (NEVER USE THESE IN ENGLISH):
   delve, tapestry, rich tapestry, testament, serves as a testament, beacon, cornerstone (unless literal physical stone), pivotal, crucial role, vital role, underscore/underscores/underscoring, highlight the importance, foster/fostering, showcase/showcasing, vibrant, bustling, landscape (unless physical geography), interplay, intricate, nuanced, indelible mark, paradigm, realm, embark, leverage, navigate (metaphorical), reshape/reshaping, sobering reminder, game-changer, seismic shift, irreplaceable global treasure, it is important to note, it is worth noting, in summary, overall, only time will tell, challenges remain, more than just, set the stage for, a deeper understanding.
3. NO PUFFERY OR SIGNIFICANCE INFLATION:
   Never hype normal news as "historic transformation", "changed forever", or "unprecedented" unless factually true and proven with dates. Report concrete facts: who said what, exact dates, exact figures, laws, institutions.
4. NO AI STRUCTURAL CLICHÉS:
   - NO negative parallelism ("Not only X, but also Y", "It is not just X, it is Y").
   - NO forced "Rule of Three" adjective triplets in every paragraph.
   - NO trailing participial "-ing" analytical tack-ons (", highlighting...", ", signaling...", ", underscoring...", ", raising urgent questions about...").
   - NO essay-style generic conclusions ("As Mexico looks ahead...", "Only time will tell...").
   - HEADLINES MUST BE BRIEF, WITTY, AND STRAIGHT TO THE POINT: Always aim for 4 to 8 words maximum (never 12-18 word summaries). Use strong active verbs, newspaper punch, and dry wit when appropriate. NEVER use the repetitive AI two-part colon formula ("Dramatic Phrase: How X Reshapes Y") and NEVER use dashes. Good examples: "Senate Passes 'Anti-Meme' Law With Steep Fines", "30 Officials Quit INE as Fraud Probe Widens", "Five-Star Luxury and Island Golf in Punta Mita".
5. NO WEASEL WORDS / VAGUE ATTRIBUTION:
   Never write "some critics argue", "observers say", or "experts believe" without naming the specific person, outlet, institution, or official document. Use Google Search grounding to verify real names, dates, legal articles, and historical facts. If a fact cannot be verified, say so honestly to Don Ricardo.
6. PRESERVE DON RICARDO'S AUTHENTIC JOURNALIST VOICE:
   - Write like a seasoned human newspaper columnist and wire-service veteran: crisp sentences, varied rhythm (mix short punchy sentences with clear explanatory ones), plain strong verbs, concrete details, and room for dry journalistic wit when appropriate.
   - When polishing or translating Don Ricardo's draft from Word, preserve his exact argument, perspective, and personality. Fix only grammar, clarity, and natural English flow. Do NOT rewrite his article into bland corporate or chatbot prose.
   - In the English insert block, write clean newspaper paragraphs with NO markdown bold bullets (**Topic:** ...). Plain clean prose only.`
};
