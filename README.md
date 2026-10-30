# Mex-Ahoy! — Your Weekly Look Into Mexico 🗞️🇲🇽

> **Vol. 1 • No. 01 • October 8, 2026**
> *A Weekly Digital Newspaper bringing clear, thoughtful, and insightful stories about the Mexico that matters.*

---

## 🧭 Overview

**Mex-Ahoy!** is an interactive, responsive digital newspaper created according to the official design specifications:
- **Cabecera (Masthead):**
  - Horizontal Logotype (`assets/images/mex_ahoy_logo_horizontal.png`) featuring the nautical helm, telescope, and open newspaper with "Mex-Ahoy! - Your Weekly Look Into Mexico".
  - **Mapa de México Aparte** (`assets/images/mexico_map.png`): Distinct and separate Mexican territorial silhouette with internal state borders and interactive regional exploration.
  - Core editorial pillars: *People*, *Places*, *Culture*, *The Real Mexico*.
- **Top Bar:** Live currency ticker (`USD/MXN`, `EUR/MXN`) and city weather forecast (`CDMX`, `Cancún`, `San Miguel`).
- **Primary Navigation:** Politics, Culture, Living in Mexico, Travel, Food & Drink, Opinion, Expat Life, History, and a dedicated **Past Editions** archive button.
- **Lead Story:** *"What Comes After Claudia?"* with hero photograph of the National Palace and Zócalo.
- **The Editor's Desk:** Column by Ricardo Castillo: *"A Mexico Worth Understanding"*.
- **Secondary Features (3 Columns):**
  1. *Living in Mexico:* What Americans Should Know Before Moving to Mexico.
  2. *Culture & Traditions:* The Mexico Beyond the Tourist Trail.
  3. *Travel:* Hidden Gems Along Mexico's Caribbean Coast.
- **Interactive Reading:** Click any headline or "READ MORE →" to open the full article in a distraction-free, print-worthy editorial reader with drop-caps and pull quotes.
- **Print Broadsheet:** Native `@media print` stylesheet formatted for printing or exporting to PDF.

---

## 📁 Project Structure

```
mex-ahoy/
├── assets/
│   └── images/
│       ├── mex_ahoy_logo_horizontal.png   # Main horizontal logo
│       ├── mexico_map.png                 # Isolated Mexico map for masthead
│       ├── mex_ahoy_logo_vertical.jpg     # Vertical emblem / favicon
│       ├── mex_ahoy_mockup.jpg            # Original layout reference
│       ├── hero_palacio.jpg               # Lead story photo
│       ├── article_san_miguel.jpg         # Living in Mexico photo
│       ├── article_market.jpg             # Culture & Traditions photo
│       └── article_caribbean.jpg          # Travel photo
├── js/
│   ├── app.js                             # Interactivity & modal controllers
│   └── articles-data.js                   # Article repository & metadata
├── styles/
│   └── main.css                           # Broadsheet styling & typography
├── index.html                             # Frontpage digital edition
├── package.json
└── README.md
```

---

## 🚀 How to Run

You can view the newspaper immediately by simply opening `index.html` in any web browser, or by starting a local development server:

```powershell
# Option 1: Using Python
cd C:\Users\ADMIN\.gemini\antigravity\scratch\mex-ahoy
python -m http.server 3000

# Option 2: Using Node / npx
npx serve -l 3000 .
```

Then open `http://localhost:3000` in your browser.
