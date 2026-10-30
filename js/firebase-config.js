/**
 * Mex-Ahoy! — Firebase Cloud Configuration (Hybrid Architecture)
 * Project ID: mex-ahoy
 * Connects Hostinger frontend & editorial panel with Google Cloud Firestore
 */

const MEX_AHOY_FIREBASE_CONFIG = {
  apiKey: "AIzaSyD8iPxqJ1kNCKgV5YClz3Hvl3YPKNLN7RU",
  authDomain: "mex-ahoy.firebaseapp.com",
  projectId: "mex-ahoy",
  storageBucket: "mex-ahoy.firebasestorage.app",
  messagingSenderId: "377653714683",
  appId: "1:377653714683:web:62c03456301aafaa051a5e",
  measurementId: "G-B64CT8FCF4"
};

const FIRESTORE_BASE_URL = `https://firestore.googleapis.com/v1/projects/${MEX_AHOY_FIREBASE_CONFIG.projectId}/databases/(default)/documents`;

/**
 * Helper: Convert JS value to Firestore REST Value
 */
function toFirestoreValue(val) {
  if (val === null || val === undefined) return { nullValue: null };
  if (typeof val === 'boolean') return { booleanValue: val };
  if (typeof val === 'number') {
    return Number.isInteger(val) ? { integerValue: String(val) } : { doubleValue: val };
  }
  if (Array.isArray(val)) {
    return { arrayValue: { values: val.map(toFirestoreValue) } };
  }
  if (typeof val === 'object') {
    const fields = {};
    for (const [k, v] of Object.entries(val)) {
      fields[k] = toFirestoreValue(v);
    }
    return { mapValue: { fields } };
  }
  return { stringValue: String(val) };
}

/**
 * Helper: Convert Firestore REST Document to plain JS Object
 */
function fromFirestoreValue(valObj) {
  if (!valObj) return null;
  if ('stringValue' in valObj) return valObj.stringValue;
  if ('booleanValue' in valObj) return Boolean(valObj.booleanValue);
  if ('integerValue' in valObj) return parseInt(valObj.integerValue, 10);
  if ('doubleValue' in valObj) return parseFloat(valObj.doubleValue);
  if ('timestampValue' in valObj) return valObj.timestampValue;
  if ('arrayValue' in valObj) {
    return (valObj.arrayValue.values || []).map(fromFirestoreValue);
  }
  if ('mapValue' in valObj) {
    const res = {};
    const fields = valObj.mapValue.fields || {};
    for (const [k, v] of Object.entries(fields)) {
      res[k] = fromFirestoreValue(v);
    }
    return res;
  }
  return null;
}

function parseFirestoreDoc(doc) {
  if (!doc || !doc.fields) return null;
  const obj = {};
  for (const [k, v] of Object.entries(doc.fields)) {
    obj[k] = fromFirestoreValue(v);
  }
  if (doc.name) {
    const parts = doc.name.split('/');
    obj._docId = parts[parts.length - 1];
  }
  return obj;
}

/**
 * Save a new subscriber to Firebase Firestore (`subscribers` collection)
 * Also sends a backup copy to local PHP endpoint on Hostinger if available.
 */
window.subscribeToMexAhoy = async function(rawEmail, source = 'frontpage_newsletter') {
  const email = String(rawEmail || '').trim().toLowerCase();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    throw new Error('Please enter a valid email address.');
  }

  // Deterministic document ID from email so we never create duplicates
  const docId = email.replace(/[^a-z0-9]/g, '_');
  const nowIso = new Date().toISOString();

  const payload = {
    fields: {
      email: { stringValue: email },
      subscribedAt: { stringValue: nowIso },
      source: { stringValue: source },
      status: { stringValue: 'active' },
      domain: { stringValue: window.location.hostname || 'mex-ahoy.com' }
    }
  };

  let firebaseSaved = false;

  // 1. Save to Firebase Cloud Firestore
  try {
    const url = `${FIRESTORE_BASE_URL}/subscribers/${encodeURIComponent(docId)}?key=${MEX_AHOY_FIREBASE_CONFIG.apiKey}`;
    const res = await fetch(url, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    if (res.ok) {
      firebaseSaved = true;
    }
  } catch (err) {
    console.warn('Firebase Firestore sync warning:', err);
  }

  // 2. Also save to Hostinger PHP backup endpoint (`subscribe.php`)
  try {
    const phpRes = await fetch('subscribe.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, subscribedAt: nowIso, source })
    });
    if (phpRes.ok) {
      firebaseSaved = true;
    }
  } catch (err) {
    // Expected if running on static preview without PHP
  }

  return { ok: firebaseSaved, email, subscribedAt: nowIso };
};

/**
 * Fetch all subscribers from Firebase Firestore (used in /admin panel)
 */
window.fetchFirestoreSubscribers = async function() {
  try {
    const url = `${FIRESTORE_BASE_URL}/subscribers?pageSize=500&key=${MEX_AHOY_FIREBASE_CONFIG.apiKey}`;
    const res = await fetch(url);
    if (!res.ok) return [];
    const data = await res.json();
    const docs = data.documents || [];
    return docs
      .map(parseFirestoreDoc)
      .filter(Boolean)
      .sort((a, b) => String(b.subscribedAt || '').localeCompare(String(a.subscribedAt || '')));
  } catch (e) {
    console.warn('Error fetching subscribers from Firestore:', e);
    return [];
  }
};

/**
 * Delete subscriber from Firebase Firestore (used in /admin panel)
 */
window.deleteFirestoreSubscriber = async function(docId) {
  const url = `${FIRESTORE_BASE_URL}/subscribers/${encodeURIComponent(docId)}?key=${MEX_AHOY_FIREBASE_CONFIG.apiKey}`;
  const res = await fetch(url, { method: 'DELETE' });
  return res.ok;
};

/**
 * Sync an article to Firebase Firestore (`articles` collection)
 * Protects editorial articles from being overwritten during Git deployments
 */
window.syncArticleToFirestore = async function(article, isHero = false) {
  if (!article || !article.id) return false;
  try {
    const fields = {};
    for (const [k, v] of Object.entries(article)) {
      fields[k] = toFirestoreValue(v);
    }
    fields.updatedAt = { stringValue: new Date().toISOString() };
    if (isHero) {
      fields.isHero = { booleanValue: true };
    }

    const url = `${FIRESTORE_BASE_URL}/articles/${encodeURIComponent(article.id)}?key=${MEX_AHOY_FIREBASE_CONFIG.apiKey}`;
    const res = await fetch(url, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ fields })
    });
    return res.ok;
  } catch (e) {
    console.warn('Error syncing article to Firestore:', e);
    return false;
  }
};

/**
 * Delete an article from Firebase Firestore (`articles` collection)
 */
window.deleteArticleFromFirestore = async function(articleId) {
  if (!articleId) return false;
  try {
    const url = `${FIRESTORE_BASE_URL}/articles/${encodeURIComponent(articleId)}?key=${MEX_AHOY_FIREBASE_CONFIG.apiKey}`;
    const res = await fetch(url, { method: 'DELETE' });
    return res.ok;
  } catch (e) {
    return false;
  }
};

/**
 * Fetch cloud articles from Firebase Firestore
 */
window.fetchFirestoreArticles = async function() {
  try {
    const url = `${FIRESTORE_BASE_URL}/articles?pageSize=100&key=${MEX_AHOY_FIREBASE_CONFIG.apiKey}`;
    const res = await fetch(url);
    if (!res.ok) return null;
    const data = await res.json();
    if (!data.documents || data.documents.length === 0) return null;

    const map = {};
    const parsed = data.documents.map(parseFirestoreDoc).filter(Boolean);
    // Put hero article first if marked
    parsed.sort((a, b) => (b.isHero ? 1 : 0) - (a.isHero ? 1 : 0));
    for (const art of parsed) {
      if (art.id) {
        delete art._docId;
        map[art.id] = art;
      }
    }
    return map;
  } catch (e) {
    return null;
  }
};
