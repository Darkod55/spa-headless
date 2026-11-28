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
});
