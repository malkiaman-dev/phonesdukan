/**
 * AI SEO Assistant ,  Frontend Module
 * PhonesDukan Admin Panel
 *
 * Usage (call at bottom of page):
 *   AISeo.init({
 *     prefix:    'seo',          // field ID prefix ('' for add-product, 'ep_' for edit)
 *     productId: 0,              // 0 for new products
 *     apiUrl:    '../api/ai-seo.php',
 *   });
 */

'use strict';

window.AISeo = (function () {
  /* ── State ──────────────────────────────────────────────────────────────── */
  let cfg = {};
  let credits        = { ok: true, daily: 0, daily_limit: 200, monthly: 0, monthly_limit: 3000 };
  let suggestTimers  = {};
  let scoreTimer     = null;
  let slowTimer      = null;       // fires "taking longer than usual" warning
  let fallbackActive = false;      // true when AI is completely disabled
  let lastRequest    = null;       // stores context for retry
  let fixCtxStore    = {};         // safe storage for Fix with AI context (avoids HTML-in-onclick)

  const SCORE_DEBOUNCE    = 500;   // reduced for snappier real-time feedback
  const SUGGEST_DEBOUNCE  = 1800;
  const SLOW_WARN_MS      = 9000;

  // ── FA Icon helpers ──────────────────────────────────────────────────────
  function icon(cls, extra) {
    return '<i class="' + cls + (extra ? ' ' + extra : '') + '" aria-hidden="true"></i>';
  }

  /* ── Field Map (built from prefix) ──────────────────────────────────────── */
  function fieldId(name) {
    return cfg.prefix + name;
  }
  function $id(id) { return document.getElementById(id); }
  function val(name) {
    const el = $id(fieldId(name));
    return el ? el.value : '';
  }

  /* ── Init ─────────────────────────────────────────────────────────────────*/
  function init(options) {
    cfg = Object.assign({
      prefix:    '',
      productId: 0,
      apiUrl:    'api/ai-seo.php',
    }, options);

    injectToastContainer();
    buildScoreSection();
    attachFieldListeners();

    // Fire score immediately (no debounce) so existing data shows on page load
    var data   = collectData();
    var result = calculateScore(data);
    renderScore(result);

    fetchCredits();

    // Close dropdowns on outside click
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.ai-btn-group')) closeAllDropdowns();
    });
  }

  /* ── API Call Helper ─────────────────────────────────────────────────────*/
  async function api(action, data) {
    const body = Object.assign({ action, product_id: cfg.productId }, data);
    try {
      const controller = new AbortController();
      // Hard client-side timeout at 40s
      const hardTimeout = setTimeout(() => controller.abort(), 40000);

      const res  = await fetch(cfg.apiUrl, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(body),
        signal:  controller.signal,
      });
      clearTimeout(hardTimeout);

      // Try to parse JSON ,  never crash on malformed response
      let json;
      try {
        json = await res.json();
      } catch (_) {
        return {
          success:       false,
          error:         'AI response could not be read. Please try again.',
          error_type:    'invalid_response',
          retry:         true,
          fallback_mode: true,
        };
      }

      // Handle server-level auth error (session expired)
      if (res.status === 401) {
        return { success: false, error: 'Session expired. Please refresh the page.', error_type: 'auth', retry: false, fallback_mode: true };
      }

      // Credits / fallback ,  update UI state
      if (json.limit_reached || json.error_type === 'credits') {
        setCreditExhaustedState(json.error || 'AI credits exhausted.');
      }

      return json;

    } catch (err) {
      // AbortController fired (client-side timeout)
      if (err.name === 'AbortError') {
        return {
          success:       false,
          error:         'The AI request timed out on the client side. Please check your connection and try again.',
          error_type:    'timeout',
          retry:         true,
          retry_after:   3,
          fallback_mode: true,
        };
      }
      // Any other network failure
      return {
        success:       false,
        error:         'Unable to reach the AI service. Please check your internet connection.',
        error_type:    'network',
        retry:         true,
        retry_after:   5,
        fallback_mode: true,
      };
    }
  }

  /* ── Slow Response Timer ─────────────────────────────────────────────────*/
  function startSlowTimer(fieldName) {
    clearTimeout(slowTimer);
    slowTimer = setTimeout(function () {
      toastAction(
        'AI is taking longer than usual. Please wait a moment or dismiss to continue manually.',
        'warning',
        'Dismiss',
        function () { clearTimeout(slowTimer); },
        8000
      );
    }, SLOW_WARN_MS);
  }
  function clearSlowTimer() {
    clearTimeout(slowTimer);
  }

  /* ── Fetch Credits ────────────────────────────────────────────────────────*/
  async function fetchCredits() {
    const res = await api('get_credits', {});
    if (res.success && res.credits) {
      credits = Object.assign(credits, res.credits);
      updateCreditBar();
    }
  }

  /* ── Toast ────────────────────────────────────────────────────────────────*/
  function injectToastContainer() {
    if ($id('ai-toast-container')) return;
    const div = document.createElement('div');
    div.id = 'ai-toast-container';
    document.body.appendChild(div);
  }

  function toast(msg, type = 'default', duration = 4000) {
    const icons = {
      success: '<i class="fas fa-check"></i>', error: '<i class="fas fa-times"></i>', warning: '<i class="fas fa-exclamation-triangle"></i>', default: '<i class="fas fa-robot"></i>',
      limit: '<i class="fas fa-lock"></i>', info: '<i class="fas fa-info-circle"></i>', timeout: '<i class="fas fa-clock"></i>', network: '<i class="fas fa-wifi"></i>',
    };
    const c = document.createElement('div');
    c.className = `ai-toast ${type}`;
    c.innerHTML = `
      <span class="ai-toast-icon">${icons[type] || '<i class="fas fa-robot"></i>'}</span>
      <span class="ai-toast-text">${escHtml(msg)}</span>
      <button class="ai-toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    `;
    const container = $id('ai-toast-container');
    if (container) container.appendChild(c);
    if (duration > 0) {
      setTimeout(function () {
        c.classList.add('removing');
        setTimeout(function () { c.remove(); }, 260);
      }, duration);
    }
    return c;
  }

  /** Toast with an action button (Retry / Dismiss / Continue) */
  function toastAction(msg, type, actionLabel, actionFn, duration = 0) {
    const icons = { success: '<i class="fas fa-check"></i>', error: '<i class="fas fa-times"></i>', warning: '<i class="fas fa-exclamation-triangle"></i>', default: '<i class="fas fa-robot"></i>', info: '<i class="fas fa-info-circle"></i>', limit: '<i class="fas fa-lock"></i>' };
    const c = document.createElement('div');
    c.className = `ai-toast ${type} ai-toast-action`;
    const actionId = 'tai_' + Date.now();
    c.innerHTML = `
      <span class="ai-toast-icon">${icons[type] || '<i class="fas fa-robot"></i>'}</span>
      <div class="ai-toast-body">
        <span class="ai-toast-text">${escHtml(msg)}</span>
        <div class="ai-toast-actions">
          ${actionLabel ? `<button id="${actionId}" class="ai-toast-btn">${escHtml(actionLabel)}</button>` : ''}
          <button class="ai-toast-btn-dim" onclick="this.closest('.ai-toast').remove()">Dismiss</button>
        </div>
      </div>
    `;
    const container = $id('ai-toast-container');
    if (container) container.appendChild(c);
    if (actionLabel && actionFn) {
      const btn = c.querySelector('#' + actionId);
      if (btn) btn.addEventListener('click', function () { c.remove(); actionFn(); });
    }
    if (duration > 0) {
      setTimeout(function () {
        c.classList.add('removing');
        setTimeout(function () { c.remove(); }, 260);
      }, duration);
    }
    return c;
  }

  /** Handle any AI error response and show the right UI */
  function handleAiError(res, fieldEl, retryFn) {
    clearSlowTimer();
    const type      = res.error_type || 'unknown';
    const msg       = res.error      || 'An unexpected AI error occurred.';
    const canRetry  = res.retry === true;
    const retryAfter= (res.retry_after || 0) * 1000;

    // Choose toast type from error type
    const toastType = {
      credits:          'limit',
      rate_limit:       'warning',
      timeout:          'timeout',
      auth:             'error',
      network:          'network',
      server:           'warning',
      model_unavailable:'warning',
      invalid_response: 'warning',
      empty_response:   'warning',
      token_limit:      'warning',
      invalid_request:  'error',
      unknown:          'error',
    }[type] || 'error';

    // Show the error panel in the score section
    showErrorPanel(type, msg);

    if (type === 'credits') {
      // Credits exhausted: disable AI buttons, keep manual working
      setCreditExhaustedState(msg);
      toastAction(msg, 'limit', null, null, 0);
      return;
    }

    if (type === 'rate_limit' && retryAfter > 0) {
      toastAction(msg, 'warning', `Auto-retry in ${Math.ceil(retryAfter / 1000)}s`, function () {
        if (retryFn) retryFn();
      }, 0);
      // Auto-retry after cooldown
      setTimeout(function () {
        document.querySelectorAll('.ai-toast.warning').forEach(function (t) { t.remove(); });
        if (retryFn) retryFn();
      }, retryAfter);
      return;
    }

    if (canRetry && retryFn) {
      toastAction(msg, toastType, 'Retry', function () {
        setTimeout(retryFn, retryAfter);
      }, 0);
    } else {
      toastAction(msg, toastType, 'Continue Manually', function () {
        if (fieldEl) { fieldEl.focus(); }
      }, 8000);
    }
  }

  /** Show the error panel inside the AI SEO score section */
  function showErrorPanel(errorType, msg) {
    const panel = $id('ai-error-panel');
    if (!panel) return;
    const icons = {
      credits: '<i class="fas fa-lock"></i>', rate_limit: '<i class="fas fa-hourglass-half"></i>', timeout: '<i class="fas fa-clock"></i>', auth: '<i class="fas fa-key"></i>',
      network: '<i class="fas fa-wifi"></i>', server: '<i class="fas fa-cog"></i>', model_unavailable: '<i class="fas fa-robot"></i>', default: '<i class="fas fa-exclamation-triangle"></i>',
    };
    panel.innerHTML = `
      <div class="ai-error-card ai-error-${errorType}">
        <span class="ai-error-icon">${icons[errorType] || icons.default}</span>
        <div class="ai-error-body">
          <strong class="ai-error-title">${getErrorTitle(errorType)}</strong>
          <p class="ai-error-desc">${escHtml(msg)}</p>
        </div>
        <button class="ai-error-dismiss" onclick="AISeo.clearErrorPanel()"><i class="fas fa-times"></i></button>
      </div>
    `;
    panel.classList.add('visible');
  }

  function getErrorTitle(type) {
    const titles = {
      credits:          'AI Credits Exhausted',
      rate_limit:       'Request Limit Reached',
      timeout:          'AI Request Timed Out',
      auth:             'Authentication Failed',
      network:          'Connection Error',
      server:           'AI Service Error',
      model_unavailable:'AI Model Unavailable',
      invalid_response: 'Response Error',
      empty_response:   'Empty AI Response',
      token_limit:      'Content Too Long',
      invalid_request:  'Invalid Request',
      unknown:          'AI Error',
    };
    return titles[type] || 'AI Error';
  }

  function clearErrorPanel() {
    const panel = $id('ai-error-panel');
    if (panel) { panel.innerHTML = ''; panel.classList.remove('visible'); }
  }

  /** Disable AI buttons when credits are exhausted */
  function setCreditExhaustedState(msg) {
    fallbackActive = true;
    const banner = $id('ai-fallback-banner');
    if (banner) {
      banner.querySelector('.ai-fallback-msg').textContent = msg || 'AI credits exhausted. Manual editing is fully available.';
      banner.classList.add('visible');
    }
    // Dim AI buttons (don't remove them ,  just disable)
    document.querySelectorAll('.ai-refine-btn, .ai-gen-btn.primary, .ai-gen-btn.ghost').forEach(function (btn) {
      btn.style.opacity = '0.45';
      btn.style.cursor  = 'not-allowed';
      btn.title         = 'AI credits exhausted. Recharge API credits to re-enable.';
      btn.dataset.creditDisabled = '1';
    });
    updateCreditBar();
  }

  /** Re-enable AI buttons (called after successful retry or credit refresh) */
  function clearCreditExhaustedState() {
    fallbackActive = false;
    const banner = $id('ai-fallback-banner');
    if (banner) banner.classList.remove('visible');
    document.querySelectorAll('[data-credit-disabled="1"]').forEach(function (btn) {
      btn.style.opacity = '';
      btn.style.cursor  = '';
      btn.title         = '';
      delete btn.dataset.creditDisabled;
    });
  }

  /* ── Fallback Banner (legacy + credits) ───────────────────────────────────*/
  function showFallbackBanner(msg) {
    const el = $id('ai-fallback-banner');
    if (el) {
      el.querySelector('.ai-fallback-msg').textContent = msg;
      el.classList.add('visible');
    }
  }

  /* ── Credit Bar ───────────────────────────────────────────────────────────*/
  function updateCreditBar() {
    const fill    = document.querySelector('.ai-credit-fill');
    const count   = document.querySelector('.ai-credit-count');
    if (!fill || !count) return;
    const pct = credits.daily_limit > 0
      ? Math.min(100, Math.round((credits.daily / credits.daily_limit) * 100))
      : 0;
    fill.style.width = pct + '%';
    fill.classList.remove('warn', 'crit');
    if (pct > 90) fill.classList.add('crit');
    else if (pct > 70) fill.classList.add('warn');
    count.textContent = (credits.daily || 0) + ' / ' + (credits.daily_limit || 200) + ' today';
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     SHARED KEYWORD MATCHING — used by both calculateScore() and verifyFix()
     so the score panel and the fix verification always agree.
     ═══════════════════════════════════════════════════════════════════════════ */

  /**
   * Returns true if `text` contains the keyword (or its significant words).
   * 3-tier matching:
   *   Tier 1 — exact phrase:            "login l 274 price pakistan" found verbatim
   *   Tier 2 — all significant words:   login ✓  274 ✓  price ✓  pakistan ✓
   *             (handles AI adding "in": "price IN pakistan", hyphens "L-274" vs "L 274")
   *   Tier 3 — first 3 sig words:       "login 274 price" found as phrase
   */
  function kwFoundIn(text, kw) {
    if (!kw || !text) return false;
    const lText = text.toLowerCase().replace(/-/g, ' '); // normalise hyphens to spaces
    const lKw   = kw.toLowerCase().trim().replace(/-/g, ' ');

    // Tier 1: exact phrase (after hyphen normalisation)
    if (lText.includes(lKw)) return true;

    // Tier 2: every significant word (>2 chars) is present somewhere in the text
    const sigWords = lKw.split(/\s+/).filter(function(w) { return w.length > 2; });
    if (sigWords.length >= 2 && sigWords.every(function(w) { return lText.includes(w); })) return true;

    // Tier 3: first 3 significant words as a contiguous phrase
    const phrase = sigWords.slice(0, 3).join(' ');
    return Boolean(phrase && lText.includes(phrase));
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     SEO SCORING ENGINE
     ═══════════════════════════════════════════════════════════════════════════ */
  function collectData() {
    // Try named field first (add-product.php), then fall back to
    // the primary image's img_alt_* field (edit-product.php)
    let altText = val('primary_alt_text');
    if (!altText) {
      const primaryRadio = document.querySelector('input[name="primary_image_id"]:checked');
      if (primaryRadio) {
        const altEl = document.getElementById('img_alt_' + primaryRadio.value);
        if (altEl) altText = altEl.value.trim();
      }
      // Fallback: pick the first alt text field found on the page
      if (!altText) {
        const firstAlt = document.querySelector('[id^="img_alt_"]');
        if (firstAlt) altText = firstAlt.value.trim();
      }
    }
    return {
      productName:        val('product_name'),
      seoTitle:           val('seo_title'),
      metaDescription:    val('seo_description'),
      focusKeyword:       val('focus_keyword'),
      productDescription: val('product_description'),
      shortDescription:   val('short_description'),
      altText:            altText,
      tags:               val('product_tag'),
      secondaryKeywords:  val('secondary_keywords'),
    };
  }

  function scoreColor(s) {
    if (s >= 70) return 'good';
    if (s >= 40) return 'average';
    return 'poor';
  }

  function calculateScore(data) {
    let score    = 0;
    let readScore = 0, kwScore = 0, contentScore = 0, metaScore = 0;
    const checks = [];

    const title   = data.seoTitle        || '';
    const meta    = data.metaDescription || '';
    const kw      = (data.focusKeyword   || '').toLowerCase().trim();
    const desc    = data.productDescription || '';
    const short   = data.shortDescription   || '';
    const alt     = data.altText            || '';
    const tags    = data.tags               || '';
    const name    = data.productName        || '';

    /* ── SEO Title (20 pts) ────────────────────────────────────────────── */
    const tLen = title.length;
    if (tLen >= 50 && tLen <= 60) {
      score += 20; metaScore += 20;
      checks.push({ type:'success', title:'SEO title length is perfect', desc:'Between 50-60 characters. Google shows this fully in search results.' });
    } else if (tLen >= 40 && tLen <= 70) {
      score += 12; metaScore += 12;
      checks.push({ type:'warning', title:'SEO title length is acceptable', desc:'Aim for exactly 50-60 chars for best CTR.',
        fix: fieldId('seo_title'), issue:'title_length', issueCtx:{ problem:'Title is '+tLen+' chars, ideal is 50-60', keyword: kw } });
    } else if (tLen > 0) {
      score += 5; metaScore += 5;
      const isTooLong = tLen > 60;
      checks.push({ type:'error',
        title: isTooLong ? 'SEO title too long' : 'SEO title too short',
        desc: isTooLong ? 'Current: '+tLen+' chars. Maximum is 60. Google truncates longer titles.' : 'Current: '+tLen+' chars. Minimum is 50 for proper SERP display.',
        fix: fieldId('seo_title'), issue: isTooLong ? 'title_too_long' : 'title_too_short',
        issueCtx: { problem: isTooLong ? 'Title is '+tLen+' chars, max 60' : 'Title is '+tLen+' chars, min 50', keyword: kw } });
    } else {
      checks.push({ type:'error', title:'SEO title is missing', desc:'Add an SEO title to appear in Google search results.',
        fix: fieldId('seo_title'), issue:'title_missing', issueCtx:{ keyword: kw } });
    }

    /* ── Meta Description (20 pts) ────────────────────────────────────── */
    const mLen = meta.length;
    if (mLen >= 140 && mLen <= 160) {
      score += 20; metaScore += 20;
      checks.push({ type:'success', title:'Meta description is perfect length', desc:'Between 140-160 characters. Fully visible in Google SERPs.' });
    } else if (mLen >= 100 && mLen <= 170) {
      score += 12; metaScore += 12;
      checks.push({ type:'warning', title:'Meta description length is acceptable', desc:'Aim for 140-160 chars for best display. Current: '+mLen+'.',
        fix: fieldId('seo_description'), issue:'meta_length', issueCtx:{ problem:'Meta is '+mLen+' chars', keyword: kw } });
    } else if (mLen > 0) {
      score += 5; metaScore += 5;
      const isMetaLong = mLen > 160;
      checks.push({ type:'error',
        title: isMetaLong ? 'Meta description too long' : 'Meta description too short',
        desc: isMetaLong ? 'Current: '+mLen+' chars. Google truncates at 160.' : 'Current: '+mLen+' chars. Min 140 for full visibility.',
        fix: fieldId('seo_description'), issue: isMetaLong ? 'meta_too_long' : 'meta_too_short',
        issueCtx: { problem: isMetaLong ? 'Meta is '+mLen+' chars, max 160' : 'Meta is '+mLen+' chars, min 140', keyword: kw } });
    } else {
      checks.push({ type:'error', title:'Meta description is missing', desc:'Required for Google SERP display and CTR.',
        fix: fieldId('seo_description'), issue:'meta_missing', issueCtx:{ keyword: kw } });
    }

    /* ── Focus Keyword (25 pts) ───────────────────────────────────────── */
    if (kw) {
      // Use kwFoundIn() — same 3-tier logic as verifyFix() so score and fix agree
      if (kwFoundIn(title, kw)) {
        score += 10; kwScore += 10;
        checks.push({ type:'success', title:'Focus keyword found in SEO title', desc:'Good keyword placement for Google ranking.' });
      } else {
        checks.push({ type:'error', title:'Focus keyword missing from SEO title', desc:'Include "'+kw+'" naturally in the title.',
          fix: fieldId('seo_title'), issue:'keyword_missing_title',
          issueCtx:{ problem:'Keyword "'+kw+'" not found in SEO title', keyword: kw } });
      }
      if (kwFoundIn(meta, kw)) {
        score += 8; kwScore += 8;
        checks.push({ type:'success', title:'Focus keyword found in meta description', desc:'Keyword placement improves SERP relevance.' });
      } else {
        checks.push({ type:'error', title:'Focus keyword missing from meta description', desc:'Add "'+kw+'" naturally to the meta description.',
          fix: fieldId('seo_description'), issue:'keyword_missing_meta',
          issueCtx:{ problem:'Keyword "'+kw+'" not in meta description', keyword: kw } });
      }
      if (kwFoundIn(desc, kw) || kwFoundIn(short, kw)) {
        score += 7; kwScore += 7;
        checks.push({ type:'success', title:'Focus keyword found in product description', desc:'Good on-page keyword coverage.' });
      } else {
        checks.push({ type:'warning', title:'Focus keyword not in product description', desc:'Add "'+kw+'" naturally to the product description.',
          fix: fieldId('product_description'), issue:'keyword_missing_desc',
          issueCtx:{ problem:'Keyword "'+kw+'" not in description', keyword: kw } });
      }
    } else {
      checks.push({ type:'error', title:'Focus keyword is missing', desc:'Set a primary keyword to optimize all SEO fields.',
        fix: fieldId('focus_keyword'), issue:'keyword_missing', issueCtx:{} });
    }

    /* ── Product Description (15 pts) ────────────────────────────────── */
    const dLen = desc.length;
    if (dLen >= 300) {
      score += 15; contentScore += 15;
      checks.push({ type:'success', title:'Product description is detailed', desc:dLen+' characters. Good depth for ranking.' });
    } else if (dLen >= 150) {
      score += 8; contentScore += 8;
      checks.push({ type:'warning', title:'Product description could be longer', desc:'Current: '+dLen+' chars. Aim for 300+ for best ranking.',
        fix: fieldId('product_description'), issue:'desc_too_short',
        issueCtx:{ problem:'Description is only '+dLen+' chars, aim for 300+', keyword: kw } });
    } else if (dLen > 0) {
      score += 3; contentScore += 3;
      checks.push({ type:'error', title:'Product description too short', desc:'Current: '+dLen+' chars. Need at least 300 for SEO depth.',
        fix: fieldId('product_description'), issue:'desc_too_short',
        issueCtx:{ problem:'Description is only '+dLen+' chars', keyword: kw } });
    } else {
      checks.push({ type:'error', title:'Product description is missing', desc:'Required for ranking. Google needs content to understand this product.',
        fix: fieldId('product_description'), issue:'desc_missing', issueCtx:{ keyword: kw } });
    }

    /* ── Readability (10 pts) ────────────────────────────────────────── */
    if (name && meta.toLowerCase().includes(name.toLowerCase().split(' ')[0].toLowerCase())) {
      score += 5; readScore += 5;
      checks.push({ type:'success', title:'Product name in meta description', desc:'Improves relevance and CTR.' });
    }
    if (title && (title.includes(',') && title.toLowerCase().includes('phones dukan'))) {
      score += 5; readScore += 5;
      checks.push({ type:'success', title:'Brand name in SEO title', desc:'"Phones Dukan" brand signal detected.' });
    } else if (title) {
      checks.push({ type:'warning', title:'Add brand name to SEO title', desc:'Append ", Phones Dukan" to strengthen brand recognition.',
        fix: fieldId('seo_title'), issue:'no_brand_separator',
        issueCtx:{ problem:'No brand name in title', keyword: kw } });
    }

    /* ── Alt Text (5 pts) ────────────────────────────────────────────── */
    if (alt.trim()) {
      score += 5; contentScore += 5;
      checks.push({ type:'success', title:'Image alt text is set', desc:'Good for image SEO and accessibility.' });
    } else {
      checks.push({ type:'error', title:'Image alt text is missing', desc:'Required for Google Image Search ranking.',
        fix: fieldId('primary_alt_text'), issue:'alt_missing', issueCtx:{ keyword: kw }, fixAction:'generate' });
    }

    /* ── Tags (5 pts) ────────────────────────────────────────────────── */
    if (tags.trim()) {
      score += 5; contentScore += 5;
      checks.push({ type:'success', title:'Product tags are defined', desc:'Helps with product discoverability and internal linking.' });
    } else {
      checks.push({ type:'warning', title:'Product tags are missing', desc:'Tags improve product discoverability on Google Shopping.',
        fix: fieldId('product_tag'), issue:'tags_missing', issueCtx:{ keyword: kw } });
    }

    score       = Math.min(score, 100);
    readScore   = Math.min(Math.round((readScore / 10) * 100), 100);
    kwScore     = Math.min(Math.round((kwScore   / 25) * 100), 100);
    contentScore= Math.min(Math.round((contentScore / 25) * 100), 100);
    metaScore   = Math.min(Math.round((metaScore / 40) * 100), 100);

    return { score, readScore, kwScore, contentScore, metaScore, checks };
  }

  /* ── Build the Score Section HTML ────────────────────────────────────── */
  function buildScoreSection() {
    const wrap = $id('ai-seo-score-section');
    if (!wrap) return;

    wrap.innerHTML = `
      <!-- Fallback / credits exhausted banner -->
      <div id="ai-fallback-banner" class="ai-fallback-banner">
        ${icon('fas fa-exclamation-triangle')}
        <span class="ai-fallback-msg">AI unavailable. Manual editing still works perfectly.</span>
        <button onclick="this.parentElement.classList.remove('visible');AISeo.clearErrorPanel()" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:1rem;color:inherit">${icon('fas fa-times')}</button>
      </div>

      <!-- Error panel -->
      <div id="ai-error-panel" class="ai-error-panel"></div>

      <!-- AI Credit bar -->
      <div class="ai-credit-bar">
        <span class="ai-credit-label">${icon('fas fa-robot')} AI Credits</span>
        <div class="ai-credit-track"><div class="ai-credit-fill" style="width:0%"></div></div>
        <span class="ai-credit-count">Loading...</span>
      </div>

      <!-- Score widget -->
      <div class="ai-seo-score-section" style="margin-top:14px">
        <div class="ai-score-header">
          <h3>${icon('fas fa-chart-bar')} Live SEO Score</h3>
          <div style="display:flex;align-items:center;gap:8px">
            <span id="ai-score-badge" class="ai-score-badge poor">0/100</span>
            <button class="ai-refresh-score-btn" id="ai-refresh-btn" onclick="AISeo.runScoreNow()" title="Refresh SEO score">
              ${icon('fas fa-sync-alt')} Refresh
            </button>
          </div>
        </div>
        <div class="ai-score-donut-wrap">
          <div class="ai-score-donut">
            <svg width="80" height="80" viewBox="0 0 80 80">
              <circle class="ai-score-donut-bg"   cx="40" cy="40" r="32"/>
              <circle class="ai-score-donut-fill donut-poor" id="ai-donut-fill"
                      cx="40" cy="40" r="32"
                      stroke-dasharray="201.06" stroke-dashoffset="201.06"/>
            </svg>
            <div class="ai-score-donut-text">
              <span class="ai-score-donut-num score-poor" id="ai-score-num">0</span>
              <span class="ai-score-donut-label">/ 100</span>
            </div>
          </div>
          <div class="ai-subscores">
            ${renderSubscoreRow('Readability',  'read')}
            ${renderSubscoreRow('Keyword Opt.', 'kw')}
            ${renderSubscoreRow('Content',      'content')}
            ${renderSubscoreRow('Meta Tags',    'meta')}
          </div>
        </div>

        <!-- Analysis panel -->
        <div class="ai-suggestions-panel">
          <div class="ai-suggestions-title">${icon('fas fa-tasks')} SEO Analysis</div>
          <div class="ai-check-list" id="ai-check-list">
            <div style="color:#9ca3af;font-size:.82rem;padding:8px 4px">
              Fill in product details to see live SEO analysis...
            </div>
          </div>
        </div>
      </div>
    `;

    updateCreditBar();
  }

  function renderSubscoreRow(label, key) {
    return `
      <div class="ai-subscore-row">
        <span class="ai-subscore-label">${label}</span>
        <div class="ai-subscore-bar">
          <div class="ai-subscore-fill fill-poor" id="ai-fill-${key}" style="width:0%"></div>
        </div>
        <span class="ai-subscore-val score-poor" id="ai-val-${key}">0</span>
      </div>
    `;
  }

  /* ── Run / Update Score ───────────────────────────────────────────────── */

  /** Debounced score update (triggered on field input) */
  function runScore() {
    clearTimeout(scoreTimer);
    scoreTimer = setTimeout(function () {
      const data   = collectData();
      const result = calculateScore(data);
      renderScore(result);

      // Persist to DB if product already exists
      if (cfg.productId) {
        api('save_score', {
          overall_score: result.score,
          readability:   result.readScore,
          keyword_score: result.kwScore,
          content_score: result.contentScore,
          meta_score:    result.metaScore,
          checks:        result.checks,
        });
      }
    }, SCORE_DEBOUNCE);
  }

  /** Immediate score update ,  called by Refresh button and after AI apply */
  function runScoreNow() {
    clearTimeout(scoreTimer);
    const btn = $id('ai-refresh-btn');
    if (btn) btn.classList.add('spinning');
    const data   = collectData();
    const result = calculateScore(data);
    renderScore(result);
    if (cfg.productId) {
      api('save_score', {
        overall_score: result.score, readability: result.readScore,
        keyword_score: result.kwScore, content_score: result.contentScore,
        meta_score: result.metaScore, checks: result.checks,
      });
    }
    setTimeout(function () { if (btn) btn.classList.remove('spinning'); }, 600);
  }

  function renderScore(result) {
    const { score, readScore, kwScore, contentScore, metaScore, checks } = result;
    const cls   = scoreColor(score);
    const circ  = 2 * Math.PI * 32; // ~201.06

    // Donut ,  SVG elements require setAttribute, not .className
    const fill = $id('ai-donut-fill');
    if (fill) {
      fill.style.strokeDashoffset = circ - (circ * score / 100);
      fill.setAttribute('class', `ai-score-donut-fill donut-${cls}`);
    }

    // Number
    const numEl = $id('ai-score-num');
    if (numEl) {
      numEl.textContent = score;
      numEl.setAttribute('class', `ai-score-donut-num score-${cls}`);
    }

    // Badge
    const badge = $id('ai-score-badge');
    if (badge) {
      badge.textContent = score + '/100';
      badge.className   = `ai-score-badge ${cls}`;
    }

    // Sub-scores
    [['read', readScore], ['kw', kwScore], ['content', contentScore], ['meta', metaScore]].forEach(function ([k, v]) {
      const c = scoreColor(v);
      const f = $id('ai-fill-' + k);
      const n = $id('ai-val-'  + k);
      if (f) { f.style.width = v + '%'; f.className = `ai-subscore-fill fill-${c}`; }
      if (n) { n.textContent = v; n.className = `ai-subscore-val score-${c}`; }
    });

    // Checks list
    const list = $id('ai-check-list');
    if (!list) return;
    if (!checks.length) {
      list.innerHTML = '<div style="color:#9ca3af;font-size:.82rem;padding:8px 4px">Fill in product details to see live SEO analysis...</div>';
      return;
    }
    list.innerHTML = checks.map(function (c) {
      // FA icons instead of emojis
      const faIcon = {
        success: 'fas fa-check',
        warning: 'fas fa-exclamation',
        error:   'fas fa-times',
        info:    'fas fa-info',
      }[c.type] || 'fas fa-circle';

      // Fix button — use data-* attributes to avoid HTML corruption from special chars
      // Never put HTML content (field values) in onclick attributes
      const fixId = 'fix_' + Date.now() + '_' + Math.random().toString(36).slice(2,6);
      if (c.fix && c.type !== 'success') {
        // Store context safely in module-level map
        AISeo._storeFixCtx(fixId, c.fix, c.issue || '', c.issueCtx || {});
      }
      // Pass `this` so we can show loading state on the button itself
      const fixBtn = (c.fix && c.type !== 'success')
        ? `<button class="ai-check-fix-btn" id="fixbtn_${fixId}" onclick="AISeo._runFix('${fixId}', this)">
             <i class="fas fa-wrench" style="font-size:.65rem"></i> Fix with AI
           </button>`
        : '';

      // Title + description layout
      const title = c.title || c.msg;
      const desc  = c.desc  || '';

      return `
        <div class="ai-check-item ${c.type}">
          <span class="ai-check-icon"><i class="${faIcon}"></i></span>
          <div class="ai-check-body">
            <span class="ai-check-title">${escHtml(title)}</span>
            ${desc ? `<span class="ai-check-desc">${escHtml(desc)}</span>` : ''}
          </div>
          ${fixBtn}
        </div>
      `;
    }).join('');
  }

  /* ── Attach Listeners ─────────────────────────────────────────────────── */
  function attachFieldListeners() {
    const watchFields = [
      'product_name', 'product_description', 'short_description',
      'seo_title', 'seo_description', 'focus_keyword', 'product_tag',
      'primary_alt_text', 'secondary_keywords',
    ];
    watchFields.forEach(function (name) {
      const el = $id(fieldId(name));
      if (!el) return;
      el.addEventListener('input', runScore);
    });
  }

  /* ── Dropdown helpers ─────────────────────────────────────────────────── */
  function closeAllDropdowns() {
    document.querySelectorAll('.ai-dropdown-menu.open').forEach(function (m) {
      m.classList.remove('open');
    });
  }

  function toggleDropdown(arrowBtn) {
    const menu = arrowBtn.parentElement.querySelector('.ai-dropdown-menu');
    if (!menu) return;
    const wasOpen = menu.classList.contains('open');
    closeAllDropdowns();
    if (!wasOpen) menu.classList.add('open');
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     AI ACTIONS (called from HTML onclick)
     ═══════════════════════════════════════════════════════════════════════════ */

  /** Refine a field using AI */
  async function refineField(fieldName, style) {
    style = style || 'seo';
    if (fallbackActive) { toast('AI credits exhausted. Please use manual editing.', 'limit', 5000); return; }
    const el = $id(fieldId(fieldName));
    if (!el) return;
    const value = el.value.trim();
    if (!value) {
      toast('Please write some content first, then refine it with AI.', 'warning');
      return;
    }

    const btn = document.querySelector(`[data-ai-refine="${fieldId(fieldName)}"]`)
             || document.querySelector(`[data-ai-refine="${fieldName}"]`);
    setLoading(btn, true);
    showFieldGenerating(el);
    clearErrorPanel();
    startSlowTimer(fieldName);

    // Store for retry
    const reqData = { field: fieldName, value, context: buildContext(), style };
    lastRequest   = { type: 'refine', fieldName, style, el, btn };

    const res = await api('refine', reqData);

    clearSlowTimer();
    setLoading(btn, false);
    hideFieldGenerating(el);

    if (!res.success) {
      handleAiError(res, el, function () { refineField(fieldName, style); });
      return;
    }

    clearErrorPanel();
    toast('AI suggestion ready. Scroll down to review and apply.', 'info', 5000);

    api('save_history', {
      field: fieldName, old_value: value, new_value: res.result,
      applied_action: 'refine:' + style, applied: 0,
    });

    showApplyPopup(el, res.result, fieldName, style);
  }

  /** Generate content for a single field */
  async function generateField(target, btn) {
    if (fallbackActive) { toast('AI credits exhausted. Please use manual editing.', 'limit', 5000); return; }
    if (btn) setLoading(btn, true);
    clearErrorPanel();

    var fieldMap2 = {
      seo_title:         fieldId('seo_title'),
      meta_description:  fieldId('seo_description'),
      focus_keyword:     fieldId('focus_keyword'),
      description:       fieldId('product_description'),
      short_description: fieldId('short_description'),
      tags:              fieldId('product_tag'),
    };
    var targetEl = $id(fieldMap2[target] || '');
    if (targetEl) showFieldGenerating(targetEl);
    startSlowTimer(target);

    const data = collectData();
    const reqPayload = {
      target,
      product_name:     data.productName,
      brand:            getSelectText('brand_id'),
      category:         getSelectText('category_id'),
      tags:             data.tags,
      existing_content: data.productDescription || data.shortDescription,
      price:            val('regular_price') || val('sale_price'),
    };
    lastRequest = { type: 'generate', target, btn, reqPayload };

    const res = await api('generate', reqPayload);

    clearSlowTimer();
    if (targetEl) hideFieldGenerating(targetEl);
    if (btn) setLoading(btn, false);

    if (!res.success) {
      handleAiError(res, targetEl, function () { generateField(target, btn); });
      return;
    }

    clearErrorPanel();

    const fieldMap = {
      seo_title:         fieldId('seo_title'),
      meta_description:  fieldId('seo_description'),
      focus_keyword:     fieldId('focus_keyword'),
      description:       fieldId('product_description'),
      short_description: fieldId('short_description'),
      tags:              fieldId('product_tag'),
    };

    const el = $id(fieldMap[target] || '');
    if (el) {
      showApplyPopup(el, res.result, target, 'generate');
    }
  }

  /**
   * Generate tags and directly insert into the secondary_keywords / Tags field.
   * No popup — applies immediately like Fix with AI.
   */
  async function generateTagsDirect(btn) {
    if (fallbackActive) { toast('AI credits exhausted. Please use manual editing.', 'limit', 5000); return; }

    // Target the secondary_keywords field (labelled "Tags" in the UI)
    const el = $id(fieldId('secondary_keywords'));
    if (!el) { toast('Tags field not found. Please refresh the page.', 'warning', 3000); return; }

    if (btn) setLoading(btn, true);
    showFieldGenerating(el);
    clearErrorPanel();
    startSlowTimer('tags');

    const data = collectData();
    const res  = await api('generate', {
      target:           'tags',
      product_name:     data.productName,
      brand:            getSelectText('brand_id'),
      category:         getSelectText('category_id'),
      tags:             data.tags,
      existing_content: data.productDescription || data.shortDescription,
      price:            val('regular_price') || val('sale_price'),
    });

    clearSlowTimer();
    hideFieldGenerating(el);
    if (btn) setLoading(btn, false);

    if (!res.success) {
      handleAiError(res, el, function () { generateTagsDirect(btn); });
      return;
    }

    clearErrorPanel();
    applyToField(el, res.result, 'secondary_keywords');

    // Flash green border
    el.style.outline       = '3px solid #16a34a';
    el.style.outlineOffset = '2px';
    setTimeout(function () { el.style.outline = ''; el.style.outlineOffset = ''; }, 2200);

    toast('Tags generated and applied!', 'success', 4000);
    setTimeout(runScoreNow, 300);

    api('save_history', {
      field: 'secondary_keywords', old_value: '', new_value: res.result,
      applied_action: 'generate:tags', applied: 1,
    });
  }

  /** Generate product description (HTML) + specifications (Label: Value) together */
  async function generateDescriptions(btn) {
    if (fallbackActive) { toast('AI credits exhausted. Please use manual editing.', 'limit', 5000); return; }

    const data = collectData();
    if (!data.productName) {
      toast('Enter a product name first, then generate description and specifications.', 'warning');
      return;
    }

    if (btn) setLoading(btn, true);
    clearErrorPanel();
    toast('Generating description and specifications with AI...', 'info', 3000);

    const descEl = $id(fieldId('product_description'));
    const specEl = $id(fieldId('short_description'));
    if (descEl) showFieldGenerating(descEl);
    if (specEl) showFieldGenerating(specEl);
    startSlowTimer('descriptions');

    const reqPayload = {
      target:           'descriptions',
      product_name:     data.productName,
      brand:            getSelectText('brand_id'),
      category:         getSelectText('category_id'),
      tags:             data.tags,
      existing_content: data.productDescription || data.shortDescription,
      price:            val('regular_price') || val('sale_price'),
    };
    lastRequest = { type: 'generateDescriptions', btn, reqPayload };

    const res = await api('generate', reqPayload);

    clearSlowTimer();
    if (descEl) hideFieldGenerating(descEl);
    if (specEl) hideFieldGenerating(specEl);
    if (btn) setLoading(btn, false);

    if (!res.success) {
      handleAiError(res, descEl, function () { generateDescriptions(btn); });
      return;
    }

    clearErrorPanel();

    const r = res.result || {};
    let applied = 0;

    if (r.product_description && descEl) {
      applyToField(descEl, r.product_description, 'product_description');
      applied++;
    }
    if (r.short_description && specEl) {
      applyToField(specEl, r.short_description, 'short_description');
      applied++;
    }

    if (applied) {
      toast('AI generated description and specifications. Review before saving.', 'success', 5000);
      runScore();
    } else {
      handleAiError({
        success: false,
        error: 'AI response could not be parsed into description fields. Try again.',
        error_type: 'invalid_response',
        retry: true,
      }, descEl, function () { generateDescriptions(btn); });
    }
  }

  /** Auto-fill ALL SEO fields with one click */
  async function generateAll(btn) {
    if (fallbackActive) { toast('AI credits exhausted. Please use manual editing.', 'limit', 5000); return; }
    if (btn) setLoading(btn, true);
    clearErrorPanel();
    toast('Generating all SEO fields with AI...', 'info', 3000);

    ['seo_title','seo_description','focus_keyword','product_tag','product_description','short_description'].forEach(function(n) {
      var el = $id(fieldId(n)); if (el) showFieldGenerating(el);
    });
    startSlowTimer('all');

    const data = collectData();
    const reqPayload = {
      target:           'all',
      product_name:     data.productName,
      brand:            getSelectText('brand_id'),
      category:         getSelectText('category_id'),
      tags:             data.tags,
      existing_content: data.productDescription || data.shortDescription,
      price:            val('regular_price') || val('sale_price'),
    };
    lastRequest = { type: 'generateAll', btn, reqPayload };

    const res = await api('generate', reqPayload);

    clearSlowTimer();
    if (btn) setLoading(btn, false);
    ['seo_title','seo_description','focus_keyword','product_tag','product_description','short_description'].forEach(function(n) {
      var el = $id(fieldId(n)); if (el) hideFieldGenerating(el);
    });

    if (!res.success) {
      handleAiError(res, null, function () { generateAll(btn); });
      return;
    }
    clearErrorPanel();

    const r = res.result || {};

    // Apply to fields
    const appliedFields = [];
    const applies = [
      ['seo_title',          r.seo_title],
      ['seo_description',    r.meta_description],
      ['focus_keyword',      r.focus_keyword],
      ['product_description',r.product_description],
      ['short_description',  r.short_description],
      ['product_tag',        r.tags],
    ];

    applies.forEach(function ([fieldName, content]) {
      if (!content) return;
      const el = $id(fieldId(fieldName));
      if (!el) return;
      applyToField(el, content, fieldName);
      appliedFields.push(fieldName);
    });

    if (appliedFields.length) {
      toast('AI filled ' + appliedFields.length + ' SEO fields successfully. Review before saving.', 'success', 5000);
      runScore();
    } else {
      handleAiError({
        success: false, error: 'AI response could not be parsed into fields. Try generating each field individually.',
        error_type: 'invalid_response', retry: true,
      }, null, function () { generateAll(btn); });
    }
  }

  /** Smart suggestions (live typing) */
  async function smartSuggest(fieldName) {
    const el = $id(fieldId(fieldName));
    if (!el) return;
    const value = el.value.trim();
    if (value.length < 8) {
      closeSuggestions(fieldName);
      return;
    }

    clearTimeout(suggestTimers[fieldName]);
    suggestTimers[fieldName] = setTimeout(async function () {
      const res = await api('smart_suggest', {
        field:   fieldName,
        value:   value,
        context: buildContext(),
      });
      if (res.success && res.suggestions && res.suggestions.length) {
        showInlineSuggestions(el, fieldName, res.suggestions);
      }
    }, SUGGEST_DEBOUNCE);
  }

  /**
   * Fix with AI — RankMath-style. Detects the exact issue, calls AI with
   * a laser-targeted instruction, auto-applies the result to the field, and
   * scrolls the admin to see it change in real-time.
   *
   * @param {string} targetFieldId  - Element ID of the field to fix (e.g. ep_seo_title)
   * @param {string} issueType      - The specific SEO issue key
   * @param {string} issueCtxJson   - JSON string with problem/keyword context
   * @param {Element} fixBtnEl      - The Fix button element (for loading state)
   */
  async function fixIssue(targetFieldId, issueType, issueCtxJson, fixBtnEl) {
    if (fallbackActive) {
      toast('AI credits exhausted. Please use manual editing.', 'limit', 5000);
      return;
    }

    const el = $id(targetFieldId);
    if (!el) {
      toast('Could not find the field to fix. Please refresh the page.', 'warning', 4000);
      return;
    }

    // Decode issue context (safe — no HTML in the ctx anymore)
    let issueCtx = {};
    try { issueCtx = JSON.parse(issueCtxJson || '{}'); } catch(_) {}

    const fieldName = targetFieldId.replace(cfg.prefix, '');
    const value     = el.value.trim();

    // ── 1. Show loading state on the Fix button immediately ───────────────
    if (fixBtnEl) {
      fixBtnEl.disabled = true;
      fixBtnEl.dataset.origHtml = fixBtnEl.innerHTML;
      fixBtnEl.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:.65rem"></i> Fixing...';
      fixBtnEl.style.opacity = '0.75';
    }

    // ── 2. Scroll to the target field BEFORE the AI call ──────────────────
    // Admin sees the field immediately and watches it change
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // ── 3. Show generating overlay on the field ───────────────────────────
    showFieldGenerating(el);
    clearErrorPanel();
    startSlowTimer(fieldName);

    // ── 4. For missing/empty fields → generate fresh content ─────────────
    const generateIssues = ['title_missing', 'meta_missing', 'desc_missing', 'alt_missing', 'tags_missing'];
    if (generateIssues.includes(issueType)) {
      hideFieldGenerating(el);
      if (fixBtnEl) { fixBtnEl.disabled = false; fixBtnEl.innerHTML = fixBtnEl.dataset.origHtml || 'Fix with AI'; fixBtnEl.style.opacity = ''; }

      // Special handling: alt_missing → find the primary image's alt text field
      if (issueType === 'alt_missing') {
        // Try primary image radio first
        let altEl = null;
        const primaryRadio = document.querySelector('input[name="primary_image_id"]:checked');
        if (primaryRadio) altEl = document.getElementById('img_alt_' + primaryRadio.value);
        // Fallback: first alt field on the page
        if (!altEl) altEl = document.querySelector('[id^="img_alt_"]');

        if (altEl) {
          // Extract image ID from element ID (img_alt_123 → 123)
          const imgId = altEl.id.replace('img_alt_', '');
          altEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return await generateImageMeta(imgId, 'alt_text', null);
        }
        // No image fields found — fall through to generic generate
      }

      const generateMap = {
        title_missing: 'seo_title',
        meta_missing:  'meta_description',
        desc_missing:  'description',
        tags_missing:  'tags',
      };
      const target = generateMap[issueType] || fieldName;
      return await generateField(target, null);
    }

    // ── 5. Map issue type to the exact style instruction ──────────────────
    const issueToStyle = {
      title_too_long:        'shorten_title',
      title_too_short:       'expand_title',
      title_length:          'optimize_title_length',
      keyword_missing_title: 'add_keyword_to_title',
      keyword_missing_meta:  'add_keyword_to_meta',
      keyword_missing_desc:  'add_keyword_to_desc',
      meta_too_short:        'expand_meta',
      meta_too_long:         'shorten_meta',
      meta_length:           'optimize_meta_length',
      desc_too_short:        'expand_desc',
      no_brand_separator:    'add_brand_to_title',
      readability_low:       'readability',
    };
    const style   = issueToStyle[issueType] || 'seo';
    // Clean the keyword — strip old |-format, take the first segment
    const rawKw   = issueCtx.keyword || '';
    const keyword = rawKw.includes('|') ? rawKw.split('|')[0].trim() : rawKw.trim();

    // ── 6. Build API payload with keyword as a mandatory explicit field ───
    const context   = buildContext();
    const issueNote = issueCtx.problem ? ' ISSUE: ' + issueCtx.problem + '.' : '';

    // When fixing a length issue AND a keyword is present, PHP will automatically
    // upgrade the style to add_keyword_to_title / add_keyword_to_meta (DUAL REQUIREMENT).
    // We pass the keyword explicitly so PHP knows to apply the upgrade.
    lastRequest = { type: 'refine', fieldName, style, el, btn: fixBtnEl };

    const res = await api('refine', {
      field:      fieldName,
      value:      value || '(empty — please generate fresh content)',
      context:    context + issueNote,
      style:      style,
      issue_type: issueType,
      keyword:    keyword,            // triggers DUAL REQUIREMENT in PHP when present
    });

    // ── 7. Clear loading states ───────────────────────────────────────────
    clearSlowTimer();
    hideFieldGenerating(el);

    if (fixBtnEl) {
      fixBtnEl.disabled  = false;
      fixBtnEl.innerHTML = fixBtnEl.dataset.origHtml || '<i class="fas fa-wrench" style="font-size:.65rem"></i> Fix with AI';
      fixBtnEl.style.opacity = '';
    }

    // ── 8. Handle API error ───────────────────────────────────────────────
    if (!res.success) {
      handleAiError(res, el, function () { fixIssue(targetFieldId, issueType, issueCtxJson, fixBtnEl); });
      return;
    }

    clearErrorPanel();
    let fixedContent = res.result;

    // ── 9. VERIFY the fix actually solved the issue ───────────────────────
    // Retry ONCE if:  (a) keyword was supposed to be injected but isn't, OR
    //                 (b) length constraint wasn't met (meta/title length issues)
    const isKwIssue  = ['keyword_missing_title','keyword_missing_meta','keyword_missing_desc'].includes(issueType);
    const isLenIssue = ['title_too_short','title_too_long','title_length','optimize_title_length',
                        'meta_too_short','meta_too_long','meta_length','optimize_meta_length'].includes(issueType);
    const needsVerify = isKwIssue || isLenIssue;
    let verified = true;

    if (needsVerify) {
      verified = verifyFix(issueType, fixedContent, keyword);

      if (!verified) {
        // ── Auto-retry with ultra-explicit instruction ─────────────────
        const retryMsg = isLenIssue
          ? 'Length not in range yet. Trying again with stricter instructions...'
          : 'Keyword not found in first attempt. Trying again...';
        toast(retryMsg, 'warning', 3000);
        showFieldGenerating(el);

        // On retry, always pass keyword so PHP applies DUAL REQUIREMENT regardless of style.
        // Use the original field value (not fixedContent) on length retries — the first
        // attempt may have produced something partially correct but still wrong length/keyword.
        const retryValue = isLenIssue ? (value || fixedContent) : fixedContent;
        const retryRes = await api('refine', {
          field:      fieldName,
          value:      retryValue,
          context:    context,
          style:      style,
          issue_type: issueType,
          keyword:    keyword,
          forced:     true,           // tells PHP this is a retry — use ultra-explicit prompt
        });

        hideFieldGenerating(el);

        if (retryRes.success) {
          const retryVerified = verifyFix(issueType, retryRes.result, keyword);
          if (retryVerified) {
            fixedContent = retryRes.result;
            verified = true;
          } else {
            // Both attempts failed — apply best result and warn
            const warnMsg = isLenIssue
              ? 'AI could not reach the exact length target. Applied closest result. You can trim/expand manually.'
              : 'AI could not add "' + keyword + '" naturally. Applied best result. You can edit manually.';
            toast(warnMsg, 'warning', 8000);
            // Still apply the result (better than nothing)
          }
        }
      }
    }

    // ── 10. Apply the fix to the field ────────────────────────────────────
    applyToField(el, fixedContent, fieldName);

    // ── 11. Show button state based on verification ───────────────────────
    const checkItem = fixBtnEl ? fixBtnEl.closest('.ai-check-item') : null;

    if (verified) {
      if (fixBtnEl) {
        fixBtnEl.innerHTML = '<i class="fas fa-check" style="font-size:.65rem"></i> Fixed!';
        fixBtnEl.style.background  = '#16a34a';
        fixBtnEl.style.color       = '#fff';
        fixBtnEl.style.borderColor = '#16a34a';
        fixBtnEl.disabled = true;
        setTimeout(function () {
          fixBtnEl.innerHTML = fixBtnEl.dataset.origHtml || '<i class="fas fa-wrench" style="font-size:.65rem"></i> Fix with AI';
          fixBtnEl.style.background = fixBtnEl.style.color = fixBtnEl.style.borderColor = '';
          fixBtnEl.disabled = false;
        }, 6000);
      }
      if (checkItem) {
        checkItem.style.transition  = 'background .4s, border-color .4s';
        checkItem.style.background  = '#f0fdf4';
        checkItem.style.borderColor = '#16a34a';
      }
    } else {
      // Verification failed — show warning state on button
      if (fixBtnEl) {
        fixBtnEl.innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size:.65rem"></i> Review';
        fixBtnEl.style.background  = '#f59e0b';
        fixBtnEl.style.color       = '#111';
        fixBtnEl.style.borderColor = '#f59e0b';
        setTimeout(function () {
          fixBtnEl.innerHTML = fixBtnEl.dataset.origHtml || '<i class="fas fa-wrench" style="font-size:.65rem"></i> Fix with AI';
          fixBtnEl.style.background = fixBtnEl.style.color = fixBtnEl.style.borderColor = '';
          fixBtnEl.disabled = false;
        }, 6000);
      }
    }

    // ── 12. Log history ───────────────────────────────────────────────────
    api('save_history', {
      field: fieldName, old_value: value, new_value: fixedContent,
      applied_action: 'fix:' + issueType, applied: 1,
    });

    // ── 13. Scroll to field and flash border ──────────────────────────────
    setTimeout(function () {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      el.focus();
      const borderColor = verified ? '#16a34a' : '#f59e0b';
      el.style.outline       = '3px solid ' + borderColor;
      el.style.outlineOffset = '2px';
      setTimeout(function () { el.style.outline = ''; el.style.outlineOffset = ''; }, 2500);
    }, 150);

    // ── 14. Refresh score ─────────────────────────────────────────────────
    setTimeout(runScoreNow, 500);

    // ── 15. Toast ─────────────────────────────────────────────────────────
    if (verified) {
      toast('Issue fixed! Click "Update Product" to save.', 'success', 5000);
    }
  }

  /**
   * Verify that a fix actually resolved the specific SEO issue.
   * Returns true if the issue appears resolved, false if still present.
   *
   * For ALL length fixes: also checks keyword is still present (if provided),
   * because PHP instructions now enforce DUAL REQUIREMENTS (length + keyword).
   */
  function verifyFix(issueType, content, keyword) {
    if (!content) return false;
    const lower = content.toLowerCase().replace(/<[^>]+>/g, '').trim(); // strip HTML
    const len   = lower.length;

    // Use the shared kwFoundIn() helper (same logic as calculateScore)
    function kwPresent() {
      return !keyword || kwFoundIn(lower, keyword);
    }

    switch (issueType) {
      // ── Keyword presence issues ───────────────────────────────────────────
      case 'keyword_missing_title':
      case 'keyword_missing_meta':
      case 'keyword_missing_desc':
        return kwPresent();

      // ── Title length issues (also verify keyword not lost) ────────────────
      case 'title_too_long':
        return len <= 65 && kwPresent();
      case 'title_too_short':
        return len >= 45 && kwPresent();
      case 'title_length':
      case 'optimize_title_length':
        return len >= 45 && len <= 66 && kwPresent();

      // ── Meta description length issues (also verify keyword not lost) ─────
      case 'meta_too_long':
        return len <= 200 && kwPresent();
      case 'meta_too_short':
        return len >= 100 && kwPresent();
      case 'meta_length':
      case 'optimize_meta_length':
        // Allow a ±10-char slack on each side for HTML-stripped text
        return len >= 130 && len <= 175 && kwPresent();

      default:
        return true; // non-verifiable issues pass by default
    }
  }

  /** Legacy wrapper */
  async function fixField(targetFieldId, fixAction) {
    return fixIssue(targetFieldId, fixAction === 'generate' ? (targetFieldId.includes('alt') ? 'alt_missing' : 'desc_missing') : 'seo', '{}', null);
  }

  /* ── Image Meta Generation ───────────────────────────────────────────────── */

  /**
   * Generate AI content for an image field (alt_text, title, description, caption).
   * @param {number} imageId   - The image's database ID (used in field element IDs)
   * @param {string} fieldType - 'alt_text' | 'title' | 'description' | 'caption'
   * @param {Element} btn      - The clicked button (for loading state)
   */
  async function generateImageMeta(imageId, fieldType, btn) {
    if (fallbackActive) { toast('AI credits exhausted. Please use manual editing.', 'limit', 4000); return; }

    // Locate the input/textarea — IDs follow the pattern img_alt_123, img_title_123, etc.
    const pfxMap  = { alt_text: 'alt', title: 'title', description: 'desc', caption: 'caption' };
    const prefix  = pfxMap[fieldType] || fieldType;
    const fieldEl = document.getElementById('img_' + prefix + '_' + imageId);
    if (!fieldEl) { toast('Field not found. Please refresh the page.', 'warning', 3000); return; }

    if (btn) setLoading(btn, true);
    showFieldGenerating(fieldEl);
    clearErrorPanel();
    startSlowTimer(fieldType);

    const data = collectData();
    const res  = await api('generate_image_meta', {
      image_field:   fieldType,
      product_name:  data.productName || '',
      brand:         getSelectText('brand_id'),
      category:      getSelectText('category_id'),
      current_value: fieldEl.value.trim(),
    });

    clearSlowTimer();
    hideFieldGenerating(fieldEl);
    if (btn) setLoading(btn, false);

    if (!res.success) {
      handleAiError(res, fieldEl, function () { generateImageMeta(imageId, fieldType, btn); });
      return;
    }

    applyToField(fieldEl, res.result, fieldType);

    // Flash green border on the field
    fieldEl.style.outline       = '3px solid #16a34a';
    fieldEl.style.outlineOffset = '2px';
    setTimeout(function () { fieldEl.style.outline = ''; fieldEl.style.outlineOffset = ''; }, 2200);

    const labelMap = { alt_text: 'Alt text', title: 'Image title', description: 'Image description', caption: 'Caption' };
    toast((labelMap[fieldType] || fieldType) + ' generated! Click Update Product to save.', 'success', 4000);

    // If this is the primary image alt text → refresh SEO score immediately
    const primaryRadio = document.querySelector('input[name="primary_image_id"]:checked');
    if (fieldType === 'alt_text' && primaryRadio && String(primaryRadio.value) === String(imageId)) {
      setTimeout(runScoreNow, 300);
    }
  }

  /**
   * Generate AI content for a PRIMARY image field on add-product.php
   * (also used for gallery fields which have explicit element IDs).
   * @param {string} fieldElId  - Element ID of the input/textarea
   * @param {string} fieldType  - 'alt_text' | 'title' | 'description' | 'caption'
   * @param {Element} btn       - The clicked button
   */
  async function generatePrimaryImageMeta(fieldElId, fieldType, btn) {
    if (fallbackActive) { toast('AI credits exhausted. Please use manual editing.', 'limit', 4000); return; }

    const fieldEl = $id(fieldElId);
    if (!fieldEl) { toast('Field not found. Please refresh the page.', 'warning', 3000); return; }

    if (btn) setLoading(btn, true);
    showFieldGenerating(fieldEl);
    clearErrorPanel();
    startSlowTimer(fieldType);

    const data = collectData();
    const res  = await api('generate_image_meta', {
      image_field:   fieldType,
      product_name:  data.productName || '',
      brand:         getSelectText('brand_id'),
      category:      getSelectText('category_id'),
      current_value: fieldEl.value.trim(),
    });

    clearSlowTimer();
    hideFieldGenerating(fieldEl);
    if (btn) setLoading(btn, false);

    if (!res.success) {
      handleAiError(res, fieldEl, function () { generatePrimaryImageMeta(fieldElId, fieldType, btn); });
      return;
    }

    applyToField(fieldEl, res.result, fieldType);

    // Flash green border
    fieldEl.style.outline       = '3px solid #16a34a';
    fieldEl.style.outlineOffset = '2px';
    setTimeout(function () { fieldEl.style.outline = ''; fieldEl.style.outlineOffset = ''; }, 2200);

    const labelMap = { alt_text: 'Alt text', title: 'Image title', description: 'Image description', caption: 'Caption' };
    toast((labelMap[fieldType] || fieldType) + ' generated!', 'success', 3500);

    // Refresh SEO score if alt text for primary image changed
    if (fieldType === 'alt_text' && (fieldElId === 'primary_alt_text' || fieldElId.startsWith('gallery_alt_'))) {
      setTimeout(runScoreNow, 300);
    }
  }

  /* ── Slug Generation ──────────────────────────────────────────────────── */
  /** Manual: convert product name to clean slug */
  function generateSlugManual() {
    const nameEl = $id(fieldId('product_name'));
    const slugEl = $id(fieldId('product_slug'));
    if (!slugEl) return;

    const name = (nameEl ? nameEl.value : slugEl.value).trim();
    if (!name) { toast('Enter a product name first to generate slug.', 'warning', 3000); return; }

    // Stop words to remove
    const stopWords = /\b(the|a|an|and|or|but|in|on|at|to|for|of|with|by|from|is|are|was|were|be|been|new|best|buy|get|top|great|good|my|your|our)\b/gi;
    const slug = name
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')     // remove special chars
      .replace(stopWords, ' ')            // remove stop words
      .trim()
      .replace(/\s+/g, '-')              // spaces to hyphens
      .replace(/-+/g, '-')               // collapse multiple hyphens
      .replace(/^-|-$/g, '');            // trim hyphens

    slugEl.value = slug;
    slugEl.dispatchEvent(new Event('input', { bubbles: true }));
    // Highlight
    slugEl.classList.add('ai-writing-done');
    setTimeout(function () { slugEl.classList.remove('ai-writing-done'); }, 1200);
    toast('Slug generated from product name.', 'success', 2500);
  }

  /** AI-powered: generate SEO-optimized slug */
  async function generateSlugAI(btn) {
    if (fallbackActive) { toast('AI credits exhausted. Use manual slug generation.', 'limit', 4000); return; }
    const nameEl = $id(fieldId('product_name'));
    const slugEl = $id(fieldId('product_slug'));
    if (!slugEl) return;

    const name = nameEl ? nameEl.value.trim() : '';
    if (!name) { toast('Enter a product name first.', 'warning', 3000); return; }

    if (btn) setLoading(btn, true);
    startSlowTimer('product_slug');

    const res = await api('generate_slug', {
      product_name: name,
      brand:        getSelectText('brand_id'),
      category:     getSelectText('category_id'),
    });

    clearSlowTimer();
    if (btn) setLoading(btn, false);

    if (!res.success) {
      handleAiError(res, slugEl, function () { generateSlugAI(btn); });
      return;
    }

    // Clean the slug just in case
    const rawSlug = (res.result || '').toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    slugEl.value = rawSlug;
    slugEl.dispatchEvent(new Event('input', { bubbles: true }));
    slugEl.classList.add('ai-writing-done');
    setTimeout(function () { slugEl.classList.remove('ai-writing-done'); }, 1200);
    toast('AI SEO slug generated.', 'success', 3000);
  }

  /* ── Apply popup (confirm before applying) ───────────────────────────── */
  function showApplyPopup(fieldEl, newContent, fieldName, action) {
    // Remove any existing popup
    document.querySelectorAll('.ai-apply-popup').forEach(function (p) { p.remove(); });

    const truncated = newContent.length > 120 ? newContent.substring(0, 120) + '...' : newContent;

    const popup = document.createElement('div');
    popup.className = 'ai-apply-popup';
    popup.style.cssText = `
      position: absolute;
      top: calc(100% + 6px);
      left: 0;
      right: 0;
      background: #fff;
      border: 2px solid #facc15;
      border-radius: 10px;
      box-shadow: 0 8px 32px rgba(17,17,17,.16);
      z-index: 9999;
      overflow: hidden;
      animation: aiToastIn .2s ease;
    `;

    popup.innerHTML = `
      <div style="padding:8px 12px;background:#fef9c3;border-bottom:1px solid #facc15;font-size:.73rem;font-weight:700;color:#111111;display:flex;align-items:center;gap:6px">
        <i class="fas fa-robot" style="color:#111;font-size:.8rem"></i>
        <span>AI Suggestion</span>
        <button onclick="this.closest('.ai-apply-popup').remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#6b7280;font-size:.9rem;line-height:1;padding:0">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div style="padding:12px;font-size:.84rem;line-height:1.5;color:#111111;max-height:130px;overflow-y:auto;word-break:break-word">
        ${escHtml(newContent)}
      </div>
      <div style="display:flex;gap:8px;padding:10px 12px;background:#f8fafc;border-top:1px solid #e5e7eb;align-items:center">
        <button class="ai-gen-btn primary" onclick="AISeo._applyContent('${safeFieldId(fieldEl.id)}', this)" data-content="${escAttr(newContent)}" data-field="${escAttr(fieldName)}" data-action="${escAttr(action)}" style="font-size:.78rem">
          <i class="fas fa-check" style="font-size:.7rem"></i> Apply
        </button>
        <button class="ai-gen-btn ghost" onclick="AISeo.refineMore('${safeFieldId(fieldEl.id)}', '${escAttr(newContent)}', '${escAttr(fieldName)}')" style="font-size:.78rem;background:#f1f5f9;color:#111;border:1px solid #e5e7eb">
          <i class="fas fa-sync-alt" style="font-size:.65rem"></i> Refine More
        </button>
        <button class="ai-gen-btn ghost" onclick="this.closest('.ai-apply-popup').remove()" style="font-size:.78rem;background:#fff;color:#6b7280;border:1px solid #e5e7eb;margin-left:auto">
          Dismiss
        </button>
      </div>
    `;

    // Position relative to field
    const parent = fieldEl.closest('.ai-field-relative') || fieldEl.parentElement;
    if (parent) {
      parent.style.position = 'relative';
      parent.appendChild(popup);
    } else {
      fieldEl.parentElement.appendChild(popup);
    }

    // Auto-scroll so user can see the popup
    setTimeout(function () {
      popup.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);

    // Auto-remove after 45s
    setTimeout(function () { if (popup.parentElement) popup.remove(); }, 45000);
  }

  function _applyContent(fieldElId, btn) {
    const content   = btn.dataset.content;
    const fieldName = btn.dataset.field;
    const action    = btn.dataset.action;
    const el        = $id(fieldElId);
    if (!el) return;

    const oldVal = el.value;
    applyToField(el, content, fieldName);
    btn.closest('.ai-apply-popup').remove();
    toast('Applied. Now click "Update Product" to save to database.', 'success', 5000);

    // Save history
    api('save_history', {
      field: fieldName, old_value: oldVal, new_value: content,
      applied_action: action, applied: 1,
    });

    // Auto-refresh score immediately after applying
    setTimeout(runScoreNow, 200);
  }

  async function refineMore(fieldElId, currentContent, fieldName) {
    document.querySelectorAll('.ai-apply-popup').forEach(function (p) { p.remove(); });
    const el = $id(fieldElId);
    if (!el) return;
    el.value = currentContent;
    await refineField(fieldName, 'seo');
  }

  function applyToField(el, content, fieldName) {
    el.value = content;
    el.classList.add('ai-writing-done');
    setTimeout(function () { el.classList.remove('ai-writing-done'); }, 1200);
    // Trigger native events
    el.dispatchEvent(new Event('input',  { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  /* ── Inline suggestions (below field) ───────────────────────────────── */
  function showInlineSuggestions(el, fieldName, suggestions) {
    closeSuggestions(fieldName);

    const container = el.closest('.ai-field-relative') || el.parentElement;
    container.style.position = 'relative';

    const popEl = document.createElement('div');
    popEl.className  = 'ai-inline-suggestions visible';
    popEl.id         = 'ai-sugg-' + fieldName;

    const items = suggestions.map(function (s, i) {
      return `
        <div class="ai-inline-sugg-item" onclick="AISeo._pickSuggestion('${safeFieldId(el.id)}', '${escAttr(s)}', '${escAttr(fieldName)}')">
          <span class="ai-inline-sugg-item-num">${i + 1}</span>
          <span class="ai-inline-sugg-text">${escHtml(s)}</span>
        </div>
      `;
    }).join('');

    popEl.innerHTML = `
      <div class="ai-inline-sugg-header">
        <span><i class="fas fa-robot"></i> AI Suggestions</span>
        <button class="ai-inline-sugg-close" onclick="AISeo.closeSuggestions('${escAttr(fieldName)}')"><i class="fas fa-times"></i></button>
      </div>
      ${items}
    `;

    container.appendChild(popEl);
  }

  function closeSuggestions(fieldName) {
    const el = $id('ai-sugg-' + fieldName);
    if (el) el.remove();
  }

  function _pickSuggestion(fieldElId, content, fieldName) {
    const el = $id(fieldElId);
    if (!el) return;
    applyToField(el, content, fieldName);
    closeSuggestions(fieldName);
    toast('Suggestion applied!', 'success', 2500);
    runScore();
  }

  /* ── Helpers ─────────────────────────────────────────────────────────── */
  function buildContext() {
    const data = collectData();
    return [
      data.productName     ? 'Product: ' + data.productName : '',
      getSelectText('brand_id')    ? 'Brand: '    + getSelectText('brand_id') : '',
      getSelectText('category_id') ? 'Category: ' + getSelectText('category_id') : '',
    ].filter(Boolean).join(', ');
  }

  function getSelectText(id) {
    // Try by ID first, then by name attribute
    let el = $id(id);
    if (!el) el = document.querySelector(`select[name="${id}"]`);
    if (!el) return '';
    if (el.tagName === 'SELECT') {
      return el.options[el.selectedIndex]?.textContent?.trim() || '';
    }
    return el.value || '';
  }

  function setLoading(btn, loading) {
    if (!btn) return;
    if (loading) {
      btn.dataset.origText = btn.innerHTML;
      // Use FA spinner ,  visible on both dark and light buttons
      btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:.8rem"></i> Generating...';
      btn.classList.add('loading');
      btn.disabled = true;
    } else {
      if (btn.dataset.origText) btn.innerHTML = btn.dataset.origText;
      btn.classList.remove('loading');
      btn.disabled = false;
    }
  }

  /** Show "Generating with AI..." overlay on top of a field */
  function showFieldGenerating(el) {
    if (!el) return;
    // Remove any existing overlay
    var existing = el.parentElement && el.parentElement.querySelector('.ai-generating-overlay');
    if (existing) existing.remove();

    var overlay = document.createElement('div');
    overlay.className = 'ai-generating-overlay';
    overlay.innerHTML =
      '<span style="display:inline-flex;align-items:center;gap:10px">' +
        '<i class="fas fa-spinner fa-spin" style="color:#111111;font-size:1rem"></i>' +
        '<span class="ai-gen-label">Generating with AI...</span>' +
      '</span>';

    var parent = el.closest('.ai-field-relative') || el.parentElement;
    if (parent) {
      parent.style.position = 'relative';
      parent.appendChild(overlay);
    }

    // Also visually dim the field
    el.style.opacity = '0.4';
    el.style.pointerEvents = 'none';
  }

  /** Remove the generating overlay from a field */
  function hideFieldGenerating(el) {
    if (!el) return;
    el.style.opacity = '';
    el.style.pointerEvents = '';
    var parent = el.closest('.ai-field-relative') || el.parentElement;
    if (parent) {
      var overlay = parent.querySelector('.ai-generating-overlay');
      if (overlay) overlay.remove();
    }
  }

  function escHtml(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function escAttr(s) {
    return String(s || '').replace(/'/g, '\\\'').replace(/"/g, '&quot;').replace(/\n/g, ' ');
  }
  function safeFieldId(id) {
    return (id || '').replace(/'/g, '').replace(/"/g, '');
  }

  /** Retry the last AI request that failed */
  function retryLastRequest() {
    if (!lastRequest) {
      toast('Nothing to retry.', 'info', 3000);
      return;
    }
    clearErrorPanel();
    if (lastRequest.type === 'refine')       return refineField(lastRequest.fieldName, lastRequest.style);
    if (lastRequest.type === 'generate')     return generateField(lastRequest.target, lastRequest.btn);
    if (lastRequest.type === 'generateAll')         return generateAll(lastRequest.btn);
    if (lastRequest.type === 'generateDescriptions') return generateDescriptions(lastRequest.btn);
  }

  /* ── Fix Context Store — avoids HTML injection in onclick attributes ──── */
  /**
   * Store fix context keyed by a unique ID.
   * Called by renderScore() before rendering the Fix button.
   */
  function _storeFixCtx(id, fieldId, issue, issueCtx) {
    fixCtxStore[id] = { fieldId, issue, issueCtx };
    // Auto-clean entries older than 5 minutes
    setTimeout(function () { delete fixCtxStore[id]; }, 300000);
  }

  /**
   * Called from the Fix with AI button onclick.
   * btn is passed via `this` so we can show a loading state on it.
   */
  function _runFix(id, btn) {
    const ctx = fixCtxStore[id];
    if (!ctx) {
      toast('Fix context expired. Click "Refresh Score" and try again.', 'warning', 4000);
      return;
    }
    return fixIssue(ctx.fieldId, ctx.issue, JSON.stringify(ctx.issueCtx || {}), btn);
  }

  /* ── Public API ──────────────────────────────────────────────────────── */
  return {
    init,
    runScore,
    runScoreNow,
    refineField,
    generateField,
    generateDescriptions,
    generateAll,
    smartSuggest,
    closeSuggestions,
    fixField,
    fixIssue,
    _runFix,
    _storeFixCtx,
    generateTagsDirect,
    generateImageMeta,
    generatePrimaryImageMeta,
    generateSlugManual,
    generateSlugAI,
    toggleDropdown,
    _applyContent,
    refineMore,
    _pickSuggestion,
    clearErrorPanel,
    retryLastRequest,
    clearCreditExhaustedState,
  };
})();
