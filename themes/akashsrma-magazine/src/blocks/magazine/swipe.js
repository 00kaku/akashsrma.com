/**
 * The scrolling edition's story rows: each story's pages side by side, swiped through one at a time. The
 * browser's scroll snapping does the swiping (style.scss); this keeps the page dots in step with it, and
 * turns to a page on a dot or a tap on a neighbouring page.
 */

const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

/**
 * @param {HTMLElement}                                     root           The Magazine block.
 * @param {Object}                                          options
 * @param {(story: HTMLElement, page: HTMLElement) => void} options.onPage Called when a row settles on another page.
 * @return {{show: (page: HTMLElement, smooth?: boolean) => void, read: (story: HTMLElement) => void, current: (story: HTMLElement) => HTMLElement|undefined}} Controls for the engine.
 */
export function createSwipe( root, { onPage } ) {
	const behavior = ( smooth ) =>
		smooth && ! reduceMotion.matches ? 'smooth' : 'auto';

	const rows = [ ...root.querySelectorAll( '.magazine__story' ) ].map(
		( story ) => ( {
			story,
			row: story.querySelector( '.magazine__row' ),
			pages: [ ...story.querySelectorAll( '.magazine__page' ) ],
			dots: [ ...story.querySelectorAll( '.magazine__dot' ) ],
			at: -1,
		} )
	);

	// The page whose centre is nearest the row's centre.
	const nearest = ( r ) => {
		const centre = r.row.scrollLeft + r.row.clientWidth / 2;
		let best = 0;
		r.pages.forEach( ( page, i ) => {
			const d = ( p ) =>
				Math.abs( p.offsetLeft + p.offsetWidth / 2 - centre );
			if ( d( page ) < d( r.pages[ best ] ) ) {
				best = i;
			}
		} );
		return best;
	};

	const update = ( r ) => {
		const i = nearest( r );
		if ( i === r.at ) {
			return;
		}
		r.at = i;
		r.dots.forEach( ( dot, k ) =>
			k === i
				? dot.setAttribute( 'aria-current', 'true' )
				: dot.removeAttribute( 'aria-current' )
		);
		onPage( r.story, r.pages[ i ] );
	};

	const turnTo = ( r, i, smooth = true ) => {
		const page =
			r.pages[ Math.max( 0, Math.min( r.pages.length - 1, i ) ) ];
		r.row.scrollTo( {
			left:
				page.offsetLeft - ( r.row.clientWidth - page.offsetWidth ) / 2,
			behavior: behavior( smooth ),
		} );
	};

	for ( const r of rows ) {
		let frame = 0;
		r.row.addEventListener(
			'scroll',
			() => {
				window.cancelAnimationFrame( frame );
				frame = window.requestAnimationFrame( () => update( r ) );
			},
			{ passive: true }
		);
		r.dots.forEach( ( dot, k ) =>
			dot.addEventListener( 'click', () => turnTo( r, k ) )
		);
		// A tap on a neighbouring page's edge turns to it (instead of following a link on it).
		r.row.addEventListener( 'click', ( e ) => {
			const k = r.pages.indexOf( e.target.closest( '.magazine__page' ) );
			if (
				k >= 0 &&
				k !== r.at &&
				! root.classList.contains( 'is-book' )
			) {
				e.preventDefault();
				e.stopPropagation();
				turnTo( r, k );
			}
		} );
		update( r );
	}
	root.classList.add( 'has-swipe' );

	const rowOf = ( el ) => rows.find( ( r ) => r.story.contains( el ) );

	return {
		// Brings a page into view: its story scrolled to, then the row turned to the page.
		show( page, smooth = false ) {
			const r = rowOf( page );
			if ( r ) {
				r.story.scrollIntoView( {
					block: 'start',
					behavior: behavior( smooth ),
				} );
				turnTo( r, r.pages.indexOf( page ), smooth );
			}
		},
		// Marks the story being read: only its page dots show.
		read( story ) {
			rows.forEach( ( r ) =>
				r.story.classList.toggle( 'is-reading', r.story === story )
			);
		},
		// The page a story's row is showing.
		current: ( story ) => {
			const r = rowOf( story );
			return r?.pages[ Math.max( 0, r.at ) ];
		},
	};
}
