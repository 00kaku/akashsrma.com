/**
 * The Magazine block's Interactivity store: the small, shared pieces of state (is the magazine open, which
 * section is showing, light or dark) and the controls bound to them. The page-turn engine in book.js does
 * the rest.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

import { createBook } from './book';

// Dark mode is a per-reader preference, so it lives in the browser (pages are cached for everyone).
const THEME_KEY = 'magazine-color-scheme';

let book;

const { state } = store( 'magazine/magazine', {
	state: {
		// The bookmark of the section on screen stands out; `section` is each bookmark's context.
		get isActiveTab() {
			return getContext().section === state.currentSection;
		},
		// The close control is only reachable while the magazine is open.
		get closeTabindex() {
			return state.isOpen ? '0' : '-1';
		},
	},
	actions: {
		close() {
			book?.close();
		},
		openMagazine() {
			book?.open();
		},
		toggleTheme() {
			state.isDark = ! state.isDark;
			try {
				window.localStorage.setItem(
					THEME_KEY,
					state.isDark ? 'dark' : 'light'
				);
			} catch {}
		},
	},
	callbacks: {
		// The wrapper's classes also belong to the engine, so the dark class is toggled, never rendered.
		syncTheme() {
			getElement().ref.classList.toggle( 'is-dark', state.isDark );
		},
		init() {
			try {
				state.isDark =
					window.localStorage.getItem( THEME_KEY ) === 'dark';
			} catch {}
			book = createBook( getElement().ref, {
				onChange( { open, section } ) {
					state.isOpen = open;
					state.currentSection = section;
				},
			} );
		},
	},
} );
