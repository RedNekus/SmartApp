/**
 * Company Contact Form — Block logic (EDITOR ONLY)
 */
import './jsx-shim'; 
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.scss';
import './editor.scss';

/* ---------------------------------------------------------------------
 * Gutenberg block registration (EDITOR ONLY)
 * ------------------------------------------------------------------ */
if (typeof window !== 'undefined' && window.wp && window.wp.blockEditor) {
    registerBlockType(metadata.name, {
        ...metadata,
        edit: Edit,
        save,
    });
}
