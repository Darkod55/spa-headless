import domReady from '@wordpress/dom-ready';
import { select, subscribe } from '@wordpress/data';
import '../css/editor-canvas.css';

/**
 * Flag selected core blocks so editor-canvas.css can hide inspector UI
 * that is hardcoded in the block with no theme.json opt-out
 * (button Width panel; image Width/Height fields).
 */
domReady(() => {
  const syncSelectedCoreBlock = () => {
    const selected = select('core/block-editor')?.getSelectedBlock?.();
    document.body.classList.toggle(
      'ecoa--core-button-selected',
      selected?.name === 'core/button'
    );
    document.body.classList.toggle(
      'ecoa--core-image-selected',
      selected?.name === 'core/image'
    );
  };

  syncSelectedCoreBlock();
  subscribe(syncSelectedCoreBlock);
  watchFlodeskCanvases();
});

/**
 * Custom HTML saves the Flodesk container, then the editor drops the
 * inline script. Load the universal script inside the canvas and mount
 * each #fd-form-* so the preview matches the front end.
 */
const FLODESK_BASE = 'https://assets.flodesk.com/universal';

function installFlodesk(win) {
  if (!win || win.fd) {
    return;
  }

  const doc = win.document;
  win.FlodeskObject = 'fd';
  win.fd = function flodeskQueue() {
    (win.fd.q = win.fd.q || []).push(arguments);
  };

  const version = '?v=' + Math.floor(Date.now() / (120 * 1000)) * 60;
  const head = doc.head || doc.documentElement;

  const moduleScript = doc.createElement('script');
  moduleScript.async = true;
  moduleScript.type = 'module';
  moduleScript.src = FLODESK_BASE + '.mjs' + version;
  head.appendChild(moduleScript);

  const fallbackScript = doc.createElement('script');
  fallbackScript.async = true;
  fallbackScript.noModule = true;
  fallbackScript.src = FLODESK_BASE + '.js' + version;
  head.appendChild(fallbackScript);
}

function mountFlodeskForms(doc, win) {
  installFlodesk(win);

  doc.querySelectorAll('[id^="fd-form-"]').forEach((container) => {
    if (
      container.querySelector('[data-ff-el="root"]') ||
      container.getAttribute('data-fd-editor-mounted') === container.id
    ) {
      return;
    }

    const formId = container.id.slice('fd-form-'.length);
    if (!formId) {
      return;
    }

    container.setAttribute('data-fd-editor-mounted', container.id);
    win.fd('form', {
      formId,
      containerEl: '#' + container.id,
    });
  });
}

function watchFlodeskDocument(doc, win) {
  if (!doc?.body || doc.documentElement.dataset.flodeskWatch === '1') {
    return;
  }

  doc.documentElement.dataset.flodeskWatch = '1';

  const scan = () => mountFlodeskForms(doc, win);
  scan();
  new MutationObserver(scan).observe(doc.body, {
    childList: true,
    subtree: true,
  });
}

function watchFlodeskCanvases() {
  const seen = new WeakSet();

  const scanFrames = () => {
    document.querySelectorAll('iframe[name="editor-canvas"]').forEach((frame) => {
      if (seen.has(frame)) {
        return;
      }

      seen.add(frame);

      const boot = () => {
        try {
          watchFlodeskDocument(frame.contentDocument, frame.contentWindow);
        } catch {
          // Canvas iframe is not readable until it finishes loading.
        }
      };

      boot();
      frame.addEventListener('load', boot);
    });
  };

  scanFrames();
  new MutationObserver(scanFrames).observe(document.documentElement, {
    childList: true,
    subtree: true,
  });
}
