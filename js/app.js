/**
 * Mex-Ahoy! - Interactive Digital Newspaper
 * Client application script
 */

document.addEventListener('DOMContentLoaded', async () => {
  await loadArticlesData();
  initModals();
  initArticleReader();
  initCategoryNavigation();
  initPillarsNavigation();
  initMapExplorer();
  initPastEditions();
  initSearch();
  initNewsletter();
  initMobileNav();
  initTickerRates();
  checkInitialHash();
});

// --- Dynamic Data Sync (Local JSON + Firebase Cloud Firestore) ---
async function loadArticlesData() {
  try {
    const res = await fetch('data/articles.json?t=' + Date.now());
    if (res.ok) {
      const data = await res.json();
      if (data && data.articles) {
        Object.assign(ARTICLES, data.articles);
      }
    }
  } catch (e) {
    // Static fallback using pre-loaded ARTICLES object
  }

  // Also merge cloud-synced articles from Firebase Firestore if available
  if (typeof window.fetchFirestoreArticles === 'function') {
    try {
      const cloudArticles = await window.fetchFirestoreArticles();
      if (cloudArticles && Object.keys(cloudArticles).length > 0) {
        Object.assign(ARTICLES, cloudArticles);
      }
    } catch (err) {
      // Fallback to local articles
    }
  }

  refreshFrontpageHero();
}

function refreshFrontpageHero() {
  const articlesList = Object.values(ARTICLES);
  if (articlesList.length === 0) return;

  const heroArt = articlesList[0];
  const heroWrap = document.querySelector('.hero-article-wrapper');
  if (heroWrap && heroArt) {
    const heroFig = heroWrap.querySelector('.hero-figure');
    const heroImg = heroWrap.querySelector('.hero-figure img');
    const heroCap = heroWrap.querySelector('.hero-figure figcaption');
    const heroTag = heroWrap.querySelector('.eyebrow-tag');
    const heroH2 = heroWrap.querySelector('.hero-headline');
    const heroDeck = heroWrap.querySelector('.hero-deck');
    const heroByline = heroWrap.querySelector('.byline');
    const heroLead = heroWrap.querySelector('.hero-lead-text');
    const heroBtn = heroWrap.querySelector('.read-more-btn');

    if (heroFig) heroFig.setAttribute('data-article-id', heroArt.id);
    if (heroImg && heroArt.image) heroImg.src = heroArt.image;
    if (heroCap && heroArt.imageCaption) heroCap.textContent = heroArt.imageCaption;
    if (heroTag && heroArt.tag) heroTag.textContent = heroArt.tag;
    if (heroH2) {
      heroH2.textContent = heroArt.title;
      heroH2.setAttribute('data-article-id', heroArt.id);
    }
    if (heroDeck && heroArt.subtitle) heroDeck.textContent = heroArt.subtitle;
    if (heroByline && heroArt.author) heroByline.textContent = 'By ' + heroArt.author;
    if (heroLead && heroArt.lead) heroLead.textContent = heroArt.lead;
    if (heroBtn) heroBtn.setAttribute('data-article-id', heroArt.id);
  }

  const editorArt = ARTICLES['editor-worth-understanding'];
  const editorDesk = document.querySelector('.editor-desk-sidebar');
  if (editorDesk && editorArt) {
    const editH3 = editorDesk.querySelector('.editor-headline');
    const editByline = editorDesk.querySelector('.byline');
    if (editH3) editH3.textContent = editorArt.title;
    if (editByline && editorArt.author) editByline.textContent = 'By ' + editorArt.author;
  }
}

// --- Modal Management ---
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

function initModals() {
  // Close buttons
  document.querySelectorAll('.modal-close-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const modal = e.target.closest('.modal-overlay');
      if (modal) closeModal(modal.id);
    });
  });

  // Click outside modal card to close
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeModal(overlay.id);
      }
    });
  });

  // ESC key to close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(modal => {
        closeModal(modal.id);
      });
    }
  });
}

// --- Article Reader ---
function initArticleReader() {
  // Bind click on elements with data-article-id
  document.addEventListener('click', (e) => {
    const target = e.target.closest('[data-article-id]');
    if (target) {
      e.preventDefault();
      const articleId = target.getAttribute('data-article-id');
      loadArticleIntoReader(articleId);
    }
  });
}

function loadArticleIntoReader(articleId) {
  const article = ARTICLES[articleId];
  if (!article) return;

  const container = document.getElementById('article-reader-content');
  if (!container) return;

  // Build related articles list
  let relatedHtml = '';
  if (article.relatedIds && article.relatedIds.length > 0) {
    const relatedCards = article.relatedIds.map(relId => {
      const rel = ARTICLES[relId];
      if (!rel) return '';
      return `
        <div class="related-card-item" onclick="loadArticleIntoReader('${rel.id}')">
          <span class="related-item-tag">${rel.category}</span>
          <h4 class="related-item-title">${rel.title}</h4>
          <span style="font-size:0.75rem; color:var(--text-muted);">${rel.readTime}</span>
        </div>
      `;
    }).join('');

    if (relatedCards) {
      relatedHtml = `
        <div class="related-articles-section">
          <h4 class="related-section-title">Related Stories in this Edition</h4>
          <div class="related-grid">${relatedCards}</div>
        </div>
      `;
    }
  }

  container.innerHTML = `
    <div class="article-eyebrow ${article.categoryClass}" style="cursor:pointer;" onclick="closeModal('article-modal'); navigateToCategory('${getCategorySlug(article.category)}');">
      ${article.category} <span style="font-size:0.75rem; opacity:0.8;">(Explore Section &rarr;)</span>
    </div>
    <h1 class="article-title">${article.title}</h1>
    <p class="article-subtitle">${article.subtitle}</p>

    <div class="article-meta-line">
      <div class="article-author-info">
        <span class="article-author-name">${article.author}</span>
        <span>•</span>
        <span>${article.authorRole}</span>
      </div>
      <div>
        <span>${article.date}</span>
        <span>•</span>
        <span>${article.readTime}</span>
      </div>
    </div>

    <figure class="article-reader-img-wrap">
      <img src="${article.image}" alt="${article.title}">
      <figcaption>${article.imageCaption}</figcaption>
    </figure>

    <div class="article-content-body">
      ${article.body}
    </div>

    ${relatedHtml}

    <div class="article-actions-bar">
      <div class="article-share-group">
        <span>Share:</span>
        <button class="share-btn" onclick="copyArticleLink('${article.id}')">📋 Copy Link</button>
        <button class="share-btn" onclick="shareSocial('twitter', '${article.title}')">𝕏 Post</button>
        <button class="share-btn" onclick="shareSocial('whatsapp', '${article.title}')">WhatsApp</button>
      </div>
      <button class="share-btn" onclick="window.print()">🖨️ Print Article</button>
    </div>
  `;

  openModal('article-modal');
  window.location.hash = article.id;
}

window.copyArticleLink = function(id) {
  const url = `${window.location.origin}${window.location.pathname}#${id}`;
  navigator.clipboard.writeText(url).then(() => {
    showToast('Link copied to clipboard!');
  }).catch(() => {
    showToast('Link ready to share!');
  });
};

window.shareSocial = function(platform, title) {
  const url = encodeURIComponent(window.location.href);
  const text = encodeURIComponent(`Reading Mex-Ahoy!: "${title}"`);
  if (platform === 'twitter') {
    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
  } else if (platform === 'whatsapp') {
    window.open(`https://api.whatsapp.com/send?text=${text}%20${url}`, '_blank');
  }
};

// --- Category Navigation & Hub Pages ---
function initCategoryNavigation() {
  // Navigation bar tabs
  document.querySelectorAll('.nav-item a').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const href = link.getAttribute('href');
      if (href && href.startsWith('#')) {
        const catSlug = href.replace('#', '');
        navigateToCategory(catSlug);
      }
    });
  });

  // Mex-Ahoy! logo click returns to front page
  const logo = document.querySelector('.masthead-brand a');
  if (logo) {
    logo.addEventListener('click', (e) => {
      e.preventDefault();
      showFrontPage();
    });
  }
}

function getCategorySlug(categoryText) {
  const map = {
    'POLITICS': 'politics',
    'NEWS ANALYSIS': 'politics',
    'CULTURE & TRADITIONS': 'culture',
    'CULTURE': 'culture',
    'LIVING IN MEXICO': 'living',
    'TRAVEL': 'travel',
    'FOOD & DRINK': 'food',
    'OPINION': 'opinion',
    'EXPAT LIFE': 'expat',
    'HISTORY': 'history'
  };
  return map[categoryText] || 'politics';
}

window.navigateToCategory = function(slug) {
  // Update active state in nav bar
  document.querySelectorAll('.nav-item').forEach(item => {
    const a = item.querySelector('a');
    if (a && a.getAttribute('href') === `#${slug}`) {
      item.classList.add('active');
    } else {
      item.classList.remove('active');
    }
  });

  // Close mobile nav if open
  const mobileNav = document.getElementById('nav-links-list');
  if (mobileNav) mobileNav.classList.remove('open');

  // Render category view
  showCategoryHub(slug);
};

function showCategoryHub(slug) {
  const frontpageGrid = document.querySelector('.frontpage-grid');
  let hubView = document.getElementById('category-hub-container');

  if (!hubView) {
    hubView = document.createElement('div');
    hubView.id = 'category-hub-container';
    hubView.className = 'category-hub-view';
    frontpageGrid.parentNode.insertBefore(hubView, frontpageGrid);
  }

  const meta = CATEGORY_METADATA[slug] || {
    title: slug.toUpperCase(),
    subtitle: 'Explore journalism and dispatches in this section of Mex-Ahoy!.',
    categoryKey: slug.toUpperCase()
  };

  // Filter articles matching category
  const matchingArticles = Object.values(ARTICLES).filter(art => {
    const artSlug = getCategorySlug(art.category);
    return artSlug === slug || art.category.toUpperCase().includes(slug.toUpperCase());
  });

  hubView.innerHTML = `
    <div class="category-hub-header">
      <div class="category-breadcrumb">
        <a onclick="showFrontPage()">&larr; Front Page</a>
        <span>/</span>
        <span>${meta.title}</span>
      </div>
      <h2 class="category-hub-title">${meta.title}</h2>
      <p class="category-hub-desc">${meta.subtitle}</p>
      <button class="btn-back-frontpage" onclick="showFrontPage()">&larr; Return to Broadsheet Frontpage</button>
    </div>

    <div class="category-articles-grid">
      ${matchingArticles.map(art => `
        <article class="category-card" onclick="loadArticleIntoReader('${art.id}')">
          <figure class="category-card-figure">
            <img src="${art.image}" alt="${art.title}">
          </figure>
          <span class="eyebrow-tag ${art.categoryClass}" style="margin-bottom:4px;">${art.tag || art.category}</span>
          <h3 class="category-card-title">${art.title}</h3>
          <p class="category-card-subtitle">${art.subtitle}</p>
          <div class="category-card-meta">
            <span>By ${art.author}</span>
            <span>${art.readTime}</span>
          </div>
        </article>
      `).join('')}
    </div>
  `;

  // Hide frontpage broadsheet, show hub
  frontpageGrid.style.display = 'none';
  hubView.classList.add('active');
  window.scrollTo({ top: 120, behavior: 'smooth' });
  window.location.hash = slug;
}

window.showFrontPage = function() {
  const frontpageGrid = document.querySelector('.frontpage-grid');
  const hubView = document.getElementById('category-hub-container');
  if (frontpageGrid) frontpageGrid.style.display = '';
  if (hubView) hubView.classList.remove('active');

  // Reset nav active to first item
  document.querySelectorAll('.nav-item').forEach((item, idx) => {
    if (idx === 0) item.classList.add('active');
    else item.classList.remove('active');
  });

  window.scrollTo({ top: 0, behavior: 'smooth' });
  history.replaceState(null, null, ' ');
};

// --- Pillars Navigation ---
function initPillarsNavigation() {
  document.querySelectorAll('.masthead-pillars .pillar-item').forEach(pillar => {
    pillar.style.cursor = 'pointer';
    pillar.addEventListener('click', () => {
      const pillarName = pillar.textContent.trim();
      showPillarFilter(pillarName);
    });
  });
}

function showPillarFilter(pillarName) {
  const frontpageGrid = document.querySelector('.frontpage-grid');
  let hubView = document.getElementById('category-hub-container');

  if (!hubView) {
    hubView = document.createElement('div');
    hubView.id = 'category-hub-container';
    hubView.className = 'category-hub-view';
    frontpageGrid.parentNode.insertBefore(hubView, frontpageGrid);
  }

  const matchingArticles = Object.values(ARTICLES).filter(art => 
    art.pillar && art.pillar.toLowerCase() === pillarName.toLowerCase()
  );

  hubView.innerHTML = `
    <div class="category-hub-header">
      <div class="category-breadcrumb">
        <a onclick="showFrontPage()">&larr; Front Page</a>
        <span>/</span>
        <span>Editorial Pillar: ${pillarName}</span>
      </div>
      <h2 class="category-hub-title">${pillarName}</h2>
      <p class="category-hub-desc">Curated dispatches focusing on ${pillarName.toLowerCase()} across the Mexican republic.</p>
      <button class="btn-back-frontpage" onclick="showFrontPage()">&larr; Return to Broadsheet Frontpage</button>
    </div>

    <div class="category-articles-grid">
      ${matchingArticles.map(art => `
        <article class="category-card" onclick="loadArticleIntoReader('${art.id}')">
          <figure class="category-card-figure">
            <img src="${art.image}" alt="${art.title}">
          </figure>
          <span class="eyebrow-tag ${art.categoryClass}" style="margin-bottom:4px;">${art.category}</span>
          <h3 class="category-card-title">${art.title}</h3>
          <p class="category-card-subtitle">${art.subtitle}</p>
          <div class="category-card-meta">
            <span>By ${art.author}</span>
            <span>${art.readTime}</span>
          </div>
        </article>
      `).join('')}
    </div>
  `;

  frontpageGrid.style.display = 'none';
  hubView.classList.add('active');
  window.scrollTo({ top: 120, behavior: 'smooth' });
}

// --- Interactive Mexico Map Explorer ---
function initMapExplorer() {
  const mapElement = document.getElementById('masthead-mexico-map');
  const bannerBtn = document.getElementById('btn-explore-map');

  const handler = () => {
    renderMapExplorer();
    openModal('map-modal');
  };

  if (mapElement) mapElement.addEventListener('click', handler);
  if (bannerBtn) bannerBtn.addEventListener('click', handler);
}

function renderMapExplorer() {
  const container = document.getElementById('map-explorer-regions');
  if (!container) return;

  container.innerHTML = Object.entries(MEXICO_REGIONS).map(([key, region]) => `
    <div class="region-card" onclick="selectRegion('${key}')">
      <h3 class="region-title">${region.name}</h3>
      <div class="region-states">${region.states.join(' • ')}</div>
      <p class="region-highlights">${region.highlights}</p>
      <div style="margin-top:10px; font-family:var(--font-sans); font-size:0.75rem; font-weight:700; color:var(--color-red-mex);">
        Read Regional Story &rarr;
      </div>
    </div>
  `).join('');
}

window.selectRegion = function(regionKey) {
  const region = MEXICO_REGIONS[regionKey];
  if (!region) return;
  closeModal('map-modal');
  if (region.sampleArticleId) {
    loadArticleIntoReader(region.sampleArticleId);
  } else {
    showToast(`Exploring ${region.name}. Check our weekly regional features!`);
  }
};

// --- Past Editions Archive ---
function initPastEditions() {
  const btn = document.getElementById('btn-past-editions');
  if (btn) {
    btn.addEventListener('click', () => {
      renderPastEditions();
      openModal('editions-modal');
    });
  }
}

function renderPastEditions() {
  const list = document.getElementById('editions-list-content');
  if (!list) return;

  list.innerHTML = PAST_EDITIONS.map(ed => `
    <div class="edition-item">
      <div>
        <span class="edition-badge">${ed.volume} • ${ed.number} ${ed.isCurrent ? '• CURRENT ISSUE' : ''}</span>
        <h4 class="edition-headline">${ed.headline}</h4>
        <p class="edition-desc">${ed.coverStory}</p>
        <span style="font-size:0.75rem; color:var(--text-muted);">${ed.date}</span>
      </div>
      <div>
        <button class="btn-edition-view" onclick="openEdition('${ed.number}')">
          ${ed.isCurrent ? 'Read Online' : 'View Archive'}
        </button>
      </div>
    </div>
  `).join('');
}

window.openEdition = function(edNum) {
  closeModal('editions-modal');
  if (edNum.includes('01')) {
    showFrontPage();
    showToast('Viewing Current Issue: Vol. 1 • No. 01');
  } else {
    showToast(`Archive for ${edNum} loaded!`);
  }
};

// --- Real-time Search ---
function initSearch() {
  const searchBtn = document.getElementById('btn-search-nav');
  const searchInput = document.getElementById('newspaper-search-input');
  const searchResults = document.getElementById('search-results-list');

  if (searchBtn) {
    searchBtn.addEventListener('click', () => {
      openModal('search-modal');
      setTimeout(() => {
        if (searchInput) searchInput.focus();
      }, 100);
    });
  }

  if (searchInput && searchResults) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase().trim();
      if (!q) {
        searchResults.innerHTML = '<p style="color:var(--text-muted); font-size:0.85rem;">Type to search articles, authors, or topics...</p>';
        return;
      }

      const matches = Object.values(ARTICLES).filter(art => 
        art.title.toLowerCase().includes(q) ||
        art.subtitle.toLowerCase().includes(q) ||
        art.author.toLowerCase().includes(q) ||
        art.category.toLowerCase().includes(q) ||
        art.lead.toLowerCase().includes(q)
      );

      if (matches.length === 0) {
        searchResults.innerHTML = `<p style="color:var(--text-muted); font-size:0.85rem;">No stories found matching "${q}".</p>`;
      } else {
        searchResults.innerHTML = matches.map(art => `
          <div class="edition-item" style="cursor:pointer;" onclick="closeModal('search-modal'); loadArticleIntoReader('${art.id}');">
            <div>
              <span class="eyebrow-tag ${art.categoryClass}" style="margin:0;">${art.category}</span>
              <h4 style="font-family:var(--font-serif-headline); font-size:1.1rem; margin:4px 0;">${art.title}</h4>
              <p style="font-size:0.8rem; color:var(--text-muted);">${art.subtitle}</p>
            </div>
            <span style="color:var(--color-red-mex); font-weight:700;">&rarr;</span>
          </div>
        `).join('');
      }
    });
  }
}

// --- Check Initial URL Hash on page load ---
function checkInitialHash() {
  const hash = window.location.hash.replace('#', '');
  if (!hash) return;

  if (ARTICLES[hash]) {
    loadArticleIntoReader(hash);
  } else if (CATEGORY_METADATA[hash]) {
    navigateToCategory(hash);
  }
}

// --- Newsletter Subscription (Firebase Cloud Firestore + Hostinger Hybrid) ---
function initNewsletter() {
  const form = document.getElementById('newsletter-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const input = form.querySelector('.newsletter-input');
      const submitBtn = form.querySelector('.newsletter-submit');
      if (!input || !input.value.trim()) return;

      const email = input.value.trim();
      const originalText = submitBtn ? submitBtn.textContent : 'Subscribe Free';

      try {
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = 'Subscribing...';
        }

        if (typeof window.subscribeToMexAhoy === 'function') {
          await window.subscribeToMexAhoy(email, 'frontpage_newsletter');
        }

        showToast(`¡Gracias! ${email} is now subscribed to Mex-Ahoy!`);
        input.value = '';
      } catch (err) {
        showToast(err.message || 'Please enter a valid email address.');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      }
    });
  }
}

// --- Mobile Navigation ---
function initMobileNav() {
  const toggle = document.getElementById('nav-toggle-btn');
  const links = document.getElementById('nav-links-list');

  if (toggle && links) {
    toggle.addEventListener('click', () => {
      links.classList.toggle('open');
    });
  }
}

// --- Currency & Weather Ticker ---
function initTickerRates() {
  const rates = {
    usd: (18.42 + (Math.random() * 0.1 - 0.05)).toFixed(2),
    eur: (20.15 + (Math.random() * 0.1 - 0.05)).toFixed(2)
  };

  const usdEl = document.getElementById('rate-usd');
  const eurEl = document.getElementById('rate-eur');
  if (usdEl) usdEl.textContent = `$${rates.usd} MXN`;
  if (eurEl) eurEl.textContent = `$${rates.eur} MXN`;
}

// --- Toast Message Helper ---
function showToast(text) {
  let toast = document.getElementById('toast-notification');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.className = 'toast-msg';
    document.body.appendChild(toast);
  }
  toast.textContent = text;
  toast.classList.add('show');
  setTimeout(() => {
    toast.classList.remove('show');
  }, 3500);
}
