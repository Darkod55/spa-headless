import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
  Alpine.data('mpxCardsSlider', (config = {}) => ({
    pages: 1,
    active: 0,
    slidesPerView: 1,
    autoplay: Boolean(config?.autoplay),
    intervalMs: 5000,
    direction: 1,
    _paused: false,
    _timer: null,
    _ro: null,
    _mqlDesktop: null,
    _mqlTablet: null,
    _onPause: null,
    _onResume: null,
    _onPointerUp: null,

    init() {
      this._mqlDesktop = window.matchMedia('(min-width: 64rem)');
      this._mqlTablet = window.matchMedia('(min-width: 48rem)');
      const onBreakpoint = () => this.update();
      this._mqlDesktop.addEventListener?.('change', onBreakpoint);
      this._mqlTablet.addEventListener?.('change', onBreakpoint);

      this.$nextTick(() => {
        this.update();
        if (typeof ResizeObserver !== 'undefined' && this.$refs.track) {
          this._ro = new ResizeObserver(() => this.update());
          this._ro.observe(this.$refs.track);
        }
        this.setupAutoplay();
      });
    },

    destroy() {
      this.stopAutoplay();
      this._ro?.disconnect();
      if (this._onPause && this._onResume) {
        this.$el.removeEventListener('mouseenter', this._onPause);
        this.$el.removeEventListener('mouseleave', this._onResume);
        this.$el.removeEventListener('focusin', this._onPause);
        this.$el.removeEventListener('focusout', this._onResume);
        this.$refs.track?.removeEventListener('pointerdown', this._onPause);
        window.removeEventListener('pointerup', this._onPointerUp);
        window.removeEventListener('pointercancel', this._onPointerUp);
      }
    },

    setupAutoplay() {
      if (! this.autoplay) return;
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

      this._onPause = () => {
        this._paused = true;
      };
      this._onResume = () => {
        this._paused = false;
      };
      // Touch has no hover — resume after the gesture ends unless still hovered.
      this._onPointerUp = () => {
        if (! this.$el.matches(':hover')) {
          this._paused = false;
        }
      };

      this.$el.addEventListener('mouseenter', this._onPause);
      this.$el.addEventListener('mouseleave', this._onResume);
      this.$el.addEventListener('focusin', this._onPause);
      this.$el.addEventListener('focusout', (event) => {
        if (! this.$el.contains(event.relatedTarget)) {
          this._paused = false;
        }
      });
      this.$refs.track?.addEventListener('pointerdown', this._onPause);
      window.addEventListener('pointerup', this._onPointerUp);
      window.addEventListener('pointercancel', this._onPointerUp);

      this.startAutoplay();
    },

    startAutoplay() {
      this.stopAutoplay();
      if (! this.autoplay || this.pages <= 1) return;

      this._timer = window.setInterval(() => {
        if (! this._paused) {
          this.advance();
        }
      }, this.intervalMs);
    },

    stopAutoplay() {
      if (this._timer != null) {
        window.clearInterval(this._timer);
        this._timer = null;
      }
    },

    /**
     * Step forward/back; reverse direction at either end (ping-pong).
     * Avoids cloning slides for a seamless loop with scroll-snap.
     */
    advance() {
      if (this.pages <= 1) return;

      let next = this.active + this.direction;

      if (next >= this.pages) {
        this.direction = -1;
        next = this.active - 1;
      } else if (next < 0) {
        this.direction = 1;
        next = this.active + 1;
      }

      if (next !== this.active) {
        this.goTo(next);
      }
    },

    readSlidesPerView() {
      const root = this.$el;
      const styles = getComputedStyle(root);
      const desktop = parseInt(styles.getPropertyValue('--spa-slides-desktop'), 10) || 4;
      const tablet = parseInt(styles.getPropertyValue('--spa-slides-tablet'), 10) || 2;
      const mobile = parseInt(styles.getPropertyValue('--spa-slides-mobile'), 10) || 1;

      // lg ≥ 64rem → desktop; md ≥ 48rem (and below lg) → tablet; else mobile.
      if (this._mqlDesktop?.matches) {
        this.slidesPerView = Math.max(1, desktop);
      } else if (this._mqlTablet?.matches) {
        this.slidesPerView = Math.max(1, tablet);
      } else {
        this.slidesPerView = Math.max(1, mobile);
      }

      // Write the active count onto the element so card width calc updates.
      // Avoids Blockstudio CSS media-query reordering fighting the theme CSS.
      root.style.setProperty('--spa-slides', String(this.slidesPerView));
    },

    cards() {
      const track = this.$refs.track;
      if (! track) return [];
      return [...track.querySelectorAll(':scope > *')];
    },

    /**
     * Scroll distance for one card (always advance by a single slide,
     * even when several are visible).
     */
    slideStep() {
      const track = this.$refs.track;
      const card = track?.querySelector(':scope > *');
      if (! track || ! card) return 0;

      const styles = getComputedStyle(track);
      const gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
      return card.getBoundingClientRect().width + gap;
    },

    update() {
      const track = this.$refs.track;
      if (! track) return;

      this.readSlidesPerView();
      const count = this.cards().length;
      // Positions = how far you can shift by one card while keeping N visible.
      const pages = Math.max(1, count - this.slidesPerView + 1);
      const pagesChanged = pages !== this.pages;
      this.pages = pages;

      const step = this.slideStep();
      let active = step > 0 ? Math.round(track.scrollLeft / step) : 0;
      active = Math.max(0, Math.min(active, this.pages - 1));
      this.active = active;

      this.renderDots();

      if (pagesChanged && this.autoplay) {
        this.startAutoplay();
      }
    },

    goTo(index) {
      const track = this.$refs.track;
      if (! track) return;

      const target = Math.max(0, Math.min(index, this.pages - 1));
      track.scrollTo({ left: target * this.slideStep(), behavior: 'smooth' });
    },

    renderDots() {
      const nav = this.$refs.dots;
      if (! nav) return;

      // One page fills the track — no pagination chrome needed.
      if (this.pages <= 1) {
        nav.replaceChildren();
        nav.hidden = true;
        return;
      }

      nav.hidden = false;

      const existing = nav.querySelectorAll('.spa-cards-slider__dot');
      if (existing.length === this.pages) {
        existing.forEach((btn, i) => {
          const on = i === this.active;
          btn.classList.toggle('is-active', on);
          if (on) {
            btn.setAttribute('aria-current', 'true');
          } else {
            btn.removeAttribute('aria-current');
          }
        });
        return;
      }

      nav.replaceChildren();

      for (let i = 0; i < this.pages; i++) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'spa-cards-slider__dot' + (i === this.active ? ' is-active' : '');
        btn.setAttribute('aria-label', `Go to slide ${i + 1}`);
        if (i === this.active) {
          btn.setAttribute('aria-current', 'true');
        }
        btn.addEventListener('click', () => this.goTo(i));
        nav.appendChild(btn);
      }
    },
  }));
});

Alpine.start();

if (window.scrollY > 0) document.body.classList.add('scrolled');
window.addEventListener('scroll',() => window.scrollY > 0 ? document.body.classList.add('scrolled') : document.body.classList.remove('scrolled'));

/**
 * Preheader + main header height for full-viewport heroes (Hero V2).
 * Preheader is display:none below lg, so mobile only subtracts the sticky bar.
 */
function syncSiteChromeHeight() {
  const preheader = document.querySelector('.preheader');
  const header = document.getElementById('main-header');
  let height = 0;

  if (preheader && getComputedStyle(preheader).display !== 'none') {
    height += preheader.getBoundingClientRect().height;
  }

  if (header) {
    height += header.getBoundingClientRect().height;
  }

  document.documentElement.style.setProperty('--site-chrome-height', `${Math.round(height)}px`);
}

/**
 * Partners marquee: fill the viewport with cloned groups (avoids empty
 * teal when the logo set is narrower than the screen), then set pixel
 * shift + duration from the first group width.
 * Percentage translateX is unreliable with width:max-content on iOS.
 */
function syncPartnersMarqueeShift() {
  const pxPerSecond = window.matchMedia('(max-width: 767px)').matches ? 55 : 45;
  const minTrackWidth = Math.max(window.innerWidth, document.documentElement.clientWidth) * 2;

  document.querySelectorAll('.spa-partners-slider .wp-block-gallery[data-spa-marquee="true"]').forEach((gallery) => {
    const groups = [...gallery.querySelectorAll(':scope > .spa-partners-group')];
    const source = groups[0];
    if (!source) return;

    // Reset to a single source group, then rebuild clones for this viewport.
    groups.slice(1).forEach((group) => group.remove());

    const firstClone = source.cloneNode(true);
    firstClone.setAttribute('aria-hidden', 'true');
    gallery.appendChild(firstClone);

    // Keep cloning until the track covers 2× viewport (seamless + no gap).
    let guard = 0;
    while (gallery.scrollWidth < minTrackWidth && guard < 12) {
      const extra = source.cloneNode(true);
      extra.setAttribute('aria-hidden', 'true');
      gallery.appendChild(extra);
      guard += 1;
    }

    const width = source.getBoundingClientRect().width;
    if (width <= 0) return;

    gallery.style.setProperty('--spa-partners-shift', `-${width}px`);
    gallery.style.setProperty('--spa-partners-duration', `${Math.max(width / pxPerSecond, 12)}s`);
  });
}

function syncLayoutMetrics() {
  syncSiteChromeHeight();
  syncPartnersMarqueeShift();
}

function initTopicFilters() {
  document.querySelectorAll('[data-spa-topic-filter]').forEach((select) => {
    select.addEventListener('change', () => {
      const url = select.value;

      if (url) {
        window.location.assign(url);
      }
    });
  });
}

function decodeModulePayload(encoded) {
  try {
    const binary = atob(encoded);
    const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));
    return new TextDecoder().decode(bytes);
  } catch {
    return '';
  }
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text);
    return true;
  } catch {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'absolute';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    const ok = document.execCommand('copy');
    document.body.removeChild(ta);
    return ok;
  }
}

function initModuleCopy() {
  document.addEventListener('click', async (event) => {
    const btn = event.target.closest('[data-spa-copy]');
    if (!btn) return;

    const payload = (btn.getAttribute('data-spa-payload') || '').trim();
    const markup = decodeModulePayload(payload);
    if (!markup) return;

    const ok = await copyText(markup);
    const original = btn.dataset.originalText || btn.textContent;
    if (!btn.dataset.originalText) {
      btn.dataset.originalText = original;
    }

    btn.classList.remove('is-copied', 'is-failed');
    btn.classList.add(ok ? 'is-copied' : 'is-failed');
    btn.textContent = ok ? 'Block copied!' : 'Copy failed!';

    window.clearTimeout(btn._copyResetTimer);
    btn._copyResetTimer = window.setTimeout(() => {
      btn.classList.remove('is-copied', 'is-failed');
      btn.textContent = btn.dataset.originalText || 'Copy Block';
    }, 2000);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    syncLayoutMetrics();
    initTopicFilters();
    initModuleCopy();
  }, { once: true });
} else {
  syncLayoutMetrics();
  initTopicFilters();
  initModuleCopy();
}

window.addEventListener('load', syncLayoutMetrics);
window.addEventListener('resize', syncLayoutMetrics);
