/**
 * The page-turn engine. It works on the server-rendered spreads: static pages are shown or hidden, and a
 * turning page is built from clones of the two pages it carries, cut into hinged strips (see physics.js).
 *
 * The engine owns the book's DOM and timing; it reports what is open through `onChange`, and the
 * Interactivity store in view.js reflects that in the bookmarks and the close button.
 */
import { N, PACE, STAGGER_MS, applySim, simSettled, stepSim } from './physics';
import { createSwipe } from './swipe';

// Layout: header and footer bands, room on the right for the bookmarks, magazine proportions.
const HEAD = 76;
const FOOT = 56;
const GAP = 168;
const SIDE = 16;
const ASPECT = 0.77;
const MAX_PAGE_H = 860;
const FILL = 0.78;

// The two-page magazine needs a wide, landscape window; anything else reads as the scrolling edition.
export const BOOK_QUERY = '(min-width: 1024px) and (min-aspect-ratio: 21/20)';

const el = ( className, parent ) => {
	const node = document.createElement( 'div' );
	node.className = className;
	if ( parent ) {
		parent.append( node );
	}
	return node;
};

const sameUrl = ( a, b ) => {
	const clean = ( url ) => {
		const u = new URL( url, window.location.href );
		return u.origin + u.pathname.replace( /\/?$/, '/' );
	};
	return clean( a ) === clean( b );
};

/**
 * Turns a Magazine block's markup into a book.
 *
 * @param {HTMLElement}                                        root             The block wrapper.
 * @param {Object}                                             options
 * @param {(change: {open: boolean, section: string}) => void} options.onChange Called whenever what's open changes.
 * @return {{ close: () => void, open: () => void, destroy: () => void }} Controls for the store.
 */
export function createBook( root, { onChange } ) {
	// With no spreads (no sections yet) it is still a book on wide screens: the cover, closed, that doesn't open.
	const spreads = [ ...root.querySelectorAll( '.magazine__spread' ) ];
	const pages = spreads.map( ( s ) => [
		...s.querySelectorAll( ':scope > .magazine__page' ),
	] );
	const coverEl = root.querySelector( '.magazine__cover' );
	// Every page is already in the document and any of them can turn into view, so none of their
	// pictures may wait for lazy loading.
	root.querySelectorAll( 'img[loading="lazy"]' ).forEach( ( img ) => {
		img.loading = 'eager';
	} );
	const box = root.querySelector( '.magazine__spreads' );
	const leavesEl = el( 'magazine__leaves', box );
	const casts = [
		el( 'magazine__cast magazine__cast--left', box ),
		el( 'magazine__cast magazine__cast--right', box ),
	];
	const home = root.dataset.home;
	const media = window.matchMedia( BOOK_QUERY );
	const reduceMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	const serverCurrent = spreads.findIndex( ( s ) =>
		s.hasAttribute( 'data-current' )
	);
	let phase = serverCurrent >= 0 ? 'open' : 'closed';
	let cur = Math.max( 0, serverCurrent );
	let turn = null;
	let pending = null;
	let pendingClose = false;
	let fromHistory = false;
	let raf = null;
	let last = null;
	const sims = new Map();

	// ---- what's on screen ----

	const show = ( left, right ) => {
		pages.flat().forEach( ( p ) => p.classList.remove( 'is-shown' ) );
		left?.classList.add( 'is-shown' );
		right?.classList.add( 'is-shown' );
		root.classList.toggle( 'has-left', !! left );
	};

	const L = ( i ) => pages[ i ]?.[ 0 ];
	const R = ( i ) => pages[ i ]?.[ 1 ];

	// Stage state for the CSS (intro, spine, underlays) and the store (bookmarks, close button).
	const report = () => {
		const closedLike = phase === 'closed' || phase === 'closing';
		root.classList.toggle( 'is-closed', closedLike );
		root.classList.toggle( 'is-closing', phase === 'closing' );
		root.classList.toggle( 'is-spread', phase === 'open' );
		grips.next.hidden = ! ( phase === 'open' && cur < spreads.length - 1 );
		grips.prev.hidden = ! ( phase === 'open' && cur > 0 );
		grips.cover.hidden = phase !== 'closed' || ! spreads.length;
		// The bookmark of where the magazine is heading lights up as soon as a turn starts.
		const showing = turn && ! turn.peek ? turn.to : cur;
		onChange( {
			open: phase === 'opening' || phase === 'open',
			section: closedLike ? '' : spreads[ showing ].dataset.section,
		} );
	};

	const pushUrl = ( url ) => {
		if ( ! fromHistory && ! sameUrl( url, window.location.href ) ) {
			window.history.pushState( null, '', url );
		}
	};

	// ---- geometry: magazine proportions, centred in the space left of the bookmarks ----

	const layout = () => {
		const vw = window.innerWidth;
		const vh = window.innerHeight;
		const availH = vh - HEAD - FOOT;
		let H = Math.min( availH, vh * FILL, MAX_PAGE_H );
		let P = Math.min( H * ASPECT, ( vw - 2 * SIDE - GAP ) / 2 );
		H = Math.round( Math.min( H, P / ASPECT ) );
		P = Math.round( P );
		const left = Math.round( SIDE + ( vw - 2 * SIDE - GAP - 2 * P ) / 2 );
		const top = Math.round( HEAD + ( availH - H ) / 2 );
		const vars = { left, top, page: P, height: H };
		for ( const [ k, v ] of Object.entries( vars ) ) {
			root.style.setProperty( `--magazine-${ k }`, `${ v }px` );
		}
		for ( const s of sims.values() ) {
			s.W = 0;
			applySim( s );
		}
		markOverflow();
	};

	// Logged-in editors see which book pages their content runs past (the page clips it): add a Page Break there.
	const markOverflow = () => {
		if ( ! document.body.classList.contains( 'logged-in' ) ) {
			return;
		}
		const isBook = root.classList.contains( 'is-book' );
		root.querySelectorAll(
			'.magazine__page:not(.is-clone) > .magazine__page-body'
		).forEach( ( body ) =>
			body.classList.toggle(
				'is-overflowing',
				isBook && body.scrollHeight > body.clientHeight + 1
			)
		);
	};

	// ---- leaves: a turning page, cut into N hinged strips ----

	const clonePage = ( page ) => {
		if ( ! page ) {
			return el( 'magazine__page magazine__page--blank is-clone' );
		}
		const copy = page.cloneNode( true );
		// A turning page must paint its pictures immediately, never after it has started to move.
		copy.querySelectorAll( 'img' ).forEach( ( img ) => {
			img.loading = 'eager';
			img.decoding = 'sync';
		} );
		copy.classList.remove( 'is-shown', 'is-lifted' );
		copy.classList.add( 'is-clone' );
		copy.removeAttribute( 'id' );
		copy.setAttribute( 'aria-hidden', 'true' );
		return copy;
	};

	const makeLeaf = ( key, dir, front, back, opts = {} ) => {
		// Built off the page; warm() puts it on once its copies are ready.
		const leaf = el( `magazine__leaf magazine__leaf--${ dir }` );
		const strips = [];
		for ( let k = 0; k < N; k++ ) {
			const strip = el( 'magazine__strip', leaf );
			strip.style.transformOrigin =
				dir === 'fwd' ? 'left center' : 'right center';
			const fF = el( 'magazine__face', strip );
			const slF = el( 'magazine__slice', fF );
			slF.append( clonePage( front ) );
			const shF = el( 'magazine__shade', fF );
			const fB = el( 'magazine__face magazine__face--back', strip );
			const slB = el( 'magazine__slice', fB );
			slB.append( clonePage( back ) );
			const shB = el( 'magazine__shade', fB );
			strips.push( { el: strip, fF, fB, slF, slB, shF, shB } );
		}
		const sim = {
			key,
			back,
			dir,
			th: 0,
			w: 0,
			b: 0,
			bv: 0,
			thT: 0,
			bT: 0,
			mode: 'auto',
			pushDir: 1,
			delay: 0,
			kick: false,
			W: 0,
			el: leaf,
			strips,
			...opts,
		};
		if ( key === 'cover' ) {
			leaf.classList.add( 'magazine__leaf--cover' );
		}
		sims.set( key, sim );
		applySim( sim );
		return sim;
	};

	// A leaf goes on the page only when its copies are ready, so a turn never shows a blank or half-drawn
	// page: its pictures are decoded first (off the page), then it is laid flat over the identical real page,
	// `place` switches the real pages underneath while they are covered, and two frames later, once all of
	// that has been drawn, the motion starts.
	const warm = ( list, place ) => {
		list.forEach( ( s ) => ( s.delay = Infinity ) );
		const live = () => list.every( ( s ) => sims.get( s.key ) === s );
		const pictures = list.flatMap( ( s ) => [
			...s.el.querySelectorAll( 'img' ),
		] );
		Promise.race( [
			Promise.all(
				pictures.map( ( img ) => img.decode().catch( () => {} ) )
			),
			new Promise( ( done ) => setTimeout( done, 150 ) ),
		] ).then( () => {
			// A turn replaced or dropped meanwhile has nothing left to start.
			if ( ! live() ) {
				return;
			}
			list.forEach( ( s ) => {
				leavesEl.append( s.el );
				applySim( s );
			} );
			place();
			window.requestAnimationFrame( () =>
				window.requestAnimationFrame( () => {
					if ( live() ) {
						list.forEach( ( s, i ) => {
							s.delay = s.mode === 'auto' ? i * STAGGER_MS : 0;
						} );
						loop();
					}
				} )
			);
		} );
		loop();
	};

	// A finished leaf leaves the sims at once but stays on the page two more frames, over the real page that
	// replaces it (callers show that page straight after), so the real page is drawn before it is uncovered.
	const dropLeaf = ( key ) => {
		const s = sims.get( key );
		if ( ! s ) {
			return;
		}
		sims.delete( key );
		window.requestAnimationFrame( () =>
			window.requestAnimationFrame( () => s.el.remove() )
		);
	};

	const resetCasts = () => casts.forEach( ( c ) => ( c.style.opacity = '' ) );

	// ---- the loop ----

	const loop = () => {
		if ( ! raf ) {
			raf = window.requestAnimationFrame( tick );
		}
	};

	function tick( now ) {
		raf = null;
		let dt =
			last === null ? 1 / 60 : Math.min( 0.05, ( now - last ) / 1000 );
		last = now;
		dt *= PACE;
		if ( reduceMotion ) {
			dt *= 3;
		}
		let busy = false;
		for ( const s of sims.values() ) {
			if ( s.delay > 0 ) {
				s.delay -= dt * 1000;
				busy = true;
				continue;
			}
			// Fixed 1/120 s steps, however fast the screen refreshes.
			for ( let left = dt; left > 0; left -= 1 / 120 ) {
				stepSim( s, Math.min( left, 1 / 120 ) );
			}
			applySim( s );
			if ( ! simSettled( s ) ) {
				busy = true;
			}
		}
		updateDepth();
		resolve();
		if ( busy ) {
			loop();
		} else {
			last = null;
		}
	}

	// The lifted page shades the page it uncovers; leaves stack in the order they would land.
	function updateDepth() {
		// A riffled leaf landing on top holds the ones beneath it down: their landing bounce would otherwise
		// lift their (blank) edges up through it, in 3D, and flash over the page that just arrived.
		for ( let j = 1; j < 3; j++ ) {
			const above = sims.get( `t${ j }` );
			for ( let i = 0; above && above.th >= 150 && i < j; i++ ) {
				const below = sims.get( `t${ i }` );
				if (
					below &&
					below.th > 170 &&
					( below.th < 180 || below.b )
				) {
					Object.assign( below, { th: 180, w: 0, b: 0, bv: 0 } );
					applySim( below );
				}
			}
		}
		const lead = sims.get( 't0' ) || sims.get( 'cover' );
		let o = 0;
		if ( lead && lead.delay <= 0 && lead.th < 90 ) {
			o = Math.min( 1, lead.th / 10 ) * ( 1 - lead.th / 90 ) * 0.9;
		}
		casts[ 0 ].style.opacity =
			lead?.dir === 'bwd' && o ? o.toFixed( 3 ) : '';
		casts[ 1 ].style.opacity =
			lead?.dir === 'fwd' && o ? o.toFixed( 3 ) : '';
		// Riffled leaves lying flat on the same side share one 3D plane, where z-index doesn't apply and the GPU
		// may draw any of them on top: a blank in-between leaf would flicker over the page that landed. A half
		// pixel of depth each keeps the order: the first leaf on top before it lifts, the last after landing.
		for ( let i = 0; i < 3; i++ ) {
			const s = sims.get( `t${ i }` );
			if ( s ) {
				const landed = s.th >= 90;
				s.el.style.zIndex = String( landed ? 40 + i : 30 - i );
				s.el.style.transform = `translateZ(${ ( landed ? i : -i ) * 0.5 }px)`;
			}
		}
	}

	// What a finished motion means: the magazine opened or closed, a turn committed or cancelled.
	function resolve() {
		const cover = sims.get( 'cover' );
		if ( cover && cover.mode === 'auto' && simSettled( cover ) ) {
			const opened = cover.th > 90;
			dropLeaf( 'cover' );
			resetCasts();
			coverEl.classList.toggle( 'is-lifted', opened );
			if ( opened ) {
				phase = 'open';
				show( L( cur ), R( cur ) );
				pushUrl( spreads[ cur ].dataset.url );
			} else {
				// A closed magazine opens again at its first spread, whichever one was open before.
				phase = 'closed';
				cur = 0;
				show( null, R( cur ) );
				pushUrl( home );
			}
			fromHistory = false;
			report();
			runPending();
		}
		if ( turn ) {
			const list = [];
			for ( let i = 0; i < turn.n; i++ ) {
				const s = sims.get( `t${ i }` );
				if ( s ) {
					list.push( s );
				}
			}
			const done = list.every(
				( s ) =>
					( s.mode === 'auto' && simSettled( s ) ) ||
					( s.mode === 'peek' && s.thT === 0 && simSettled( s ) )
			);
			if ( list.length !== turn.n || ! done ) {
				return;
			}
			const completed = list.every( ( s ) => s.th > 90 );
			list.forEach( ( s ) => dropLeaf( s.key ) );
			resetCasts();
			if ( completed ) {
				cur = turn.to;
				pushUrl( spreads[ cur ].dataset.url );
			}
			fromHistory = false;
			turn = null;
			show( L( cur ), R( cur ) );
			report();
			runPending();
		}
	}

	function runPending() {
		if ( pendingClose ) {
			pendingClose = false;
			close();
			return;
		}
		const to = pending;
		pending = null;
		if ( to !== null ) {
			go( to );
		}
	}

	// ---- actions ----

	function open( i ) {
		if ( phase !== 'closed' || ! spreads.length ) {
			return;
		}
		phase = 'opening';
		cur = i;
		// A cover already lifted by the pointer carries straight on; one still being prepared starts over.
		let lifted = sims.get( 'cover' );
		if ( lifted && lifted.delay === Infinity ) {
			dropLeaf( 'cover' );
			lifted = null;
		}
		// The pointer lifts the cover with the default spread behind it; a bookmark may open another one.
		// The back can't be seen before the cover passes upright, so it is swapped now.
		if ( lifted && lifted.back !== L( i ) ) {
			lifted.back = L( i );
			lifted.strips.forEach( ( strip ) =>
				strip.slB.replaceChildren( clonePage( L( i ) ) )
			);
		}
		const s = lifted || makeLeaf( 'cover', 'fwd', coverEl, L( i ) );
		Object.assign( s, { mode: 'auto', pushDir: 1, kick: true } );
		const place = () => {
			show( null, R( i ) );
			coverEl.classList.add( 'is-lifted' );
		};
		report();
		if ( lifted ) {
			place();
			loop();
		} else {
			warm( [ s ], place );
		}
	}

	function close() {
		if (
			phase === 'opening' ||
			( phase === 'open' && turn && ! turn.peek )
		) {
			pendingClose = true;
			pending = null;
			return;
		}
		if ( phase !== 'open' ) {
			return;
		}
		if ( turn ) {
			dropLeaf( 't0' );
			turn = null;
		}
		phase = 'closing';
		const s = makeLeaf( 'cover', 'fwd', coverEl, L( cur ), {
			th: 180,
			pushDir: -1,
			kick: true,
		} );
		report();
		warm( [ s ], () => show( null, R( cur ) ) );
	}

	function startTurn( to, { peek = false, mode = 'auto' } = {} ) {
		const d = to - cur;
		if ( ! d ) {
			return;
		}
		const dir = d > 0 ? 'fwd' : 'bwd';
		const n = peek ? 1 : Math.min( Math.abs( d ), 3 );
		turn = { from: cur, to, dir, n, peek };
		const fwd = dir === 'fwd';
		const leaves = [];
		for ( let i = 0; i < n; i++ ) {
			const first = i === 0;
			const lastLeaf = i === n - 1;
			const front = fwd ? first && R( cur ) : first && L( cur );
			const back = fwd ? lastLeaf && L( to ) : lastLeaf && R( to );
			leaves.push(
				makeLeaf( `t${ i }`, dir, front || null, back || null, {
					mode,
					kick: mode === 'auto',
				} )
			);
		}
		report();
		warm( leaves, () =>
			show( fwd ? L( cur ) : L( to ), fwd ? R( to ) : R( cur ) )
		);
	}

	function go( to ) {
		if ( to < 0 || to >= spreads.length ) {
			return;
		}
		if ( phase === 'closed' ) {
			open( to );
			return;
		}
		if ( phase === 'opening' || phase === 'closing' ) {
			pending = to;
			return;
		}
		if ( to === cur && ! turn ) {
			return;
		}
		if ( turn ) {
			if ( ! turn.peek ) {
				pending = to;
				return;
			}
			const s = sims.get( 't0' );
			if ( turn.to === to && s ) {
				Object.assign( s, { mode: 'auto', pushDir: 1, kick: true } );
				turn.peek = false;
				report();
				loop();
				return;
			}
			dropLeaf( 't0' );
			resetCasts();
			turn = null;
			show( L( cur ), R( cur ) );
		}
		startTurn( to );
	}

	const next = () => ( phase === 'closed' ? open( cur ) : go( cur + 1 ) );
	const prev = () => {
		if ( phase !== 'open' ) {
			return;
		}
		if ( cur > 0 ) {
			go( cur - 1 );
		} else if ( ! turn ) {
			close();
		}
	};

	// ---- grab zones: hover lifts a corner, click turns the page ----

	const grips = {
		next: el( 'magazine__grip magazine__grip--next', box ),
		prev: el( 'magazine__grip magazine__grip--prev', box ),
		cover: el( 'magazine__grip magazine__grip--cover', box ),
	};
	const target = ( kind ) => ( kind === 'next' ? cur + 1 : cur - 1 );
	const simFor = ( kind ) => sims.get( kind === 'cover' ? 'cover' : 't0' );

	const gripEnter = ( kind ) => {
		if ( kind === 'cover' ) {
			if ( phase !== 'closed' ) {
				return;
			}
			let s = sims.get( 'cover' );
			if ( ! s ) {
				s = makeLeaf( 'cover', 'fwd', coverEl, L( cur ), {
					mode: 'rest',
				} );
				warm( [ s ], () => coverEl.classList.add( 'is-lifted' ) );
			}
			Object.assign( s, { mode: 'peek', thT: 7, bT: 16 } );
			loop();
			return;
		}
		if ( phase !== 'open' || turn ) {
			return;
		}
		startTurn( target( kind ), { peek: true, mode: 'peek' } );
		Object.assign( sims.get( 't0' ), { thT: 9, bT: 14 } );
	};

	const gripLeave = ( kind ) => {
		const s = simFor( kind );
		if ( s && s.mode === 'peek' ) {
			s.thT = 0;
			s.bT = 0;
			loop();
		}
	};

	// A click turns the page (or opens the cover); go() carries on from a lifted corner.
	const gripClick = ( kind ) =>
		kind === 'cover' ? open( cur ) : go( target( kind ) );

	for ( const [ kind, grip ] of Object.entries( grips ) ) {
		grip.addEventListener( 'pointerenter', () => gripEnter( kind ) );
		grip.addEventListener( 'pointerleave', () => gripLeave( kind ) );
		grip.addEventListener( 'click', () => gripClick( kind ) );
	}

	// ---- router: links to a spread turn the magazine (or scroll the edition) instead of reloading ----

	const spreadFor = ( url ) =>
		spreads.findIndex( ( s ) => sameUrl( s.dataset.url, url ) );

	const onClick = ( e ) => {
		const a = e.target.closest?.( 'a[href]' );
		if (
			! a ||
			! root.contains( a ) ||
			a.target === '_blank' ||
			e.metaKey ||
			e.ctrlKey ||
			e.shiftKey ||
			e.button !== 0
		) {
			return;
		}
		const i = spreadFor( a.href );
		if ( i < 0 ) {
			return;
		}
		e.preventDefault();
		if ( media.matches ) {
			go( i );
		} else {
			swipe.show( firstPage( i ), true );
			window.history.pushState( null, '', spreads[ i ].dataset.url );
		}
	};

	const onPop = () => {
		const i = spreadFor( window.location.href );
		if ( ! media.matches ) {
			if ( i >= 0 ) {
				swipe.show( firstPage( i ) );
			}
			return;
		}
		fromHistory = true;
		if ( i < 0 ) {
			close();
		} else {
			go( i );
		}
	};

	const onKey = ( e ) => {
		if (
			! media.matches ||
			/^(INPUT|TEXTAREA|SELECT)$/.test( e.target.tagName )
		) {
			return;
		}
		if ( e.key === 'ArrowRight' || e.key === 'PageDown' ) {
			e.preventDefault();
			next();
		} else if ( e.key === 'ArrowLeft' || e.key === 'PageUp' ) {
			e.preventDefault();
			prev();
		} else if ( e.key === 'Escape' ) {
			close();
		}
	};

	// ---- the scrolling edition (narrow or portrait screens): the address follows the reader ----

	// The address and bookmark follow the page on show in the story crossing the middle of the screen.
	const firstPage = ( i ) => spreads[ i ].querySelector( '.magazine__page' );
	let reading = null;
	const follow = ( page ) => {
		const spread = page?.closest( '.magazine__spread' );
		if ( ! spread || media.matches ) {
			return;
		}
		window.history.replaceState( null, '', spread.dataset.url );
		onChange( { open: false, section: spread.dataset.section } );
	};
	const swipe = createSwipe( root, {
		onPage: ( story, page ) => story === reading && follow( page ),
	} );
	const stories = [ ...root.querySelectorAll( '.magazine__story' ) ];
	const observer = new window.IntersectionObserver(
		( entries ) => {
			for ( const entry of entries ) {
				if ( entry.isIntersecting ) {
					reading = entry.target;
					swipe.read( reading );
					follow( swipe.current( reading ) );
				}
			}
		},
		{ rootMargin: '-40% 0px -55% 0px' }
	);

	// ---- switching between the book and the scrolling edition ----

	const setMode = () => {
		const isBook = media.matches;
		root.classList.toggle( 'is-book', isBook );
		document.body.classList.toggle( 'has-magazine-book', isBook );
		if ( isBook ) {
			// The cover joins the book's own stacking context, so its grab zone can sit above it.
			box.append( coverEl );
			stories.forEach( ( s ) => observer.unobserve( s ) );
			layout();
			show( phase === 'closed' ? null : L( cur ), R( cur ) );
			coverEl.classList.toggle( 'is-lifted', phase !== 'closed' );
			report();
		} else {
			box.before( coverEl );
			[ ...sims.keys() ].forEach( dropLeaf );
			turn = null;
			stories.forEach( ( s ) => observer.observe( s ) );
			markOverflow();
			if ( serverCurrent >= 0 ) {
				swipe.show( firstPage( serverCurrent ) );
			}
		}
	};

	window.addEventListener( 'resize', layout );
	window.addEventListener( 'popstate', onPop );
	document.addEventListener( 'keydown', onKey );
	root.addEventListener( 'click', onClick );
	media.addEventListener( 'change', setMode );
	setMode();
	document.fonts?.ready.then( markOverflow );

	return {
		close,
		open: () => go( cur ),
		destroy() {
			window.removeEventListener( 'resize', layout );
			window.removeEventListener( 'popstate', onPop );
			document.removeEventListener( 'keydown', onKey );
			root.removeEventListener( 'click', onClick );
			media.removeEventListener( 'change', setMode );
			observer.disconnect();
		},
	};
}
