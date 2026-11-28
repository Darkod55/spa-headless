import domReady from '@wordpress/dom-ready';
import { select, subscribe } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import '../css/editor-canvas.css';

const buttonTypes = ['primary', 'light', 'dark', 'outline-light', 'text'];

/**
 * BlockStudio `set` only writes a class when the value is truthy, so an
 * Arrow toggle saved as false never marks the wrapper. Missing means on.
 * The classes sit on the wrapper; spa-button.css paints the inner link.
 */
addFilter('editor.BlockListBlock', 'spa/button-arrow', (BlockListBlock) => {
  return (props) => {
    if (props.name !== 'core/button') {
      return createElement(BlockListBlock, props);
    }

    const fields = props.attributes?.blockstudio?.attributes ?? {};
    const raw = fields.button_type;
    const value = raw && typeof raw === 'object' ? raw.value : raw;
    const type = buttonTypes.includes(value) ? value : 'primary';
    const off = fields.arrow === false || fields.arrow === 'false' || fields.arrow === 0;
    const className = [props.className, 'cta', `cta--${type}`, off ? 'cta--no-arrow' : 'cta--arrow']
      .filter(Boolean)
      .join(' ');

    return createElement(BlockListBlock, { ...props, className });
  };
});

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
