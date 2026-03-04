/**
 * JSX Runtime Shim for WordPress
 * Maps automatic JSX runtime (jsx/jsxs) to classic runtime (createElement)
 * Required when using JSX with wp-element in Gutenberg blocks
 */
if (typeof window !== 'undefined' && window.wp && window.wp.element) {
    const el = window.wp.element;
    if (!el.jsx) {
        el.jsx = el.createElement;
    }
    if (!el.jsxs) {
        el.jsxs = el.createElement;
    }
}
