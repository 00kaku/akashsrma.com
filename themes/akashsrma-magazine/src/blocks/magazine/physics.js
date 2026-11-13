/**
 * Page physics. Each turning page is N hinged vertical strips: strip k bends by b * PROFILE[k] on top of
 * the base angle th, so the spine side follows th and the free edge trails.
 * A page is pushed past its tipping point, then gravity, air drag and springy paper take it.
 */
// N hinged strips per leaf; strip k bends by b * PROFILE[k] on top of the base angle th,
// so the spine side follows th and the free edge trails.
export const N = 7;
const PROFILE = Array.from( { length: N }, ( _, k ) =>
	Math.pow( k / ( N - 1 ), 1.6 )
);
// Tuned offline: lands ~610ms after a push, trails its free edge up to ~26°, two small flutters on landing.
const PHYS = {
	G: 1500,
	push: 3400,
	c: 2.8,
	e: 0.16,
	A: 0.058,
	kb: 380,
	cb: 17,
	imp: 0.9,
	kp: 170,
	cp: 24,
};
// The simulation runs at this share of real time: a turn lands in ~760ms instead of the tuned ~610ms,
// which reads as heavier paper and gives the GPU more time to draw each frame.
export const PACE = 0.8;
export const STAGGER_MS = 70;
// strips overlap their neighbour by a few px so anti-aliased joints never expose the strip backing
const OVERLAP = 3;

export function stepSim( s, dt ) {
	const P = PHYS;
	if ( s.mode === 'rest' ) {
		return;
	}
	if ( s.mode === 'peek' ) {
		s.w += ( P.kp * ( s.thT - s.th ) - P.cp * s.w ) * dt;
		s.th = Math.max( 0, s.th + s.w * dt );
		s.bv += ( P.kb * ( s.bT - s.b ) - P.cb * s.bv ) * dt;
		s.b += s.bv * dt;
		return;
	}
	// auto: pushed over by a hand, then gravity and air take it; paper bends against its motion
	if ( s.kick && s.th > 0.5 && s.th < 179.5 ) {
		s.kick = false;
	}
	const rad = ( s.th * Math.PI ) / 180;
	let a = P.G * -Math.cos( rad ) - P.c * s.w;
	if ( s.pushDir > 0 && s.th < 70 ) {
		a += P.push;
	}
	if ( s.pushDir < 0 && s.th > 110 ) {
		a -= P.push;
	}
	s.w += a * dt;
	s.th += s.w * dt;
	if ( s.th >= 180 ) {
		const v = s.w;
		s.th = 180;
		if ( v > 0 ) {
			if ( v < 90 ) {
				s.w = 0;
			} else {
				s.w = -v * P.e;
				s.bv -= v * P.imp;
			}
		}
	}
	if ( s.th <= 0 ) {
		const v = -s.w;
		s.th = 0;
		if ( v > 0 ) {
			if ( v < 90 ) {
				s.w = 0;
			} else {
				s.w = v * P.e;
				s.bv += v * P.imp;
			}
		}
	}
	s.bv += ( P.kb * ( -P.A * s.w - s.b ) - P.cb * s.bv ) * dt;
	s.b += s.bv * dt;
}

export function simSettled( s ) {
	if ( s.delay > 0 ) {
		return false;
	}
	// a freshly pushed page hasn't left its starting edge yet — that is not "finished"
	if ( s.mode === 'auto' && s.kick ) {
		return false;
	}
	if ( s.mode === 'rest' ) {
		return true;
	}
	if ( s.mode === 'peek' ) {
		return (
			Math.abs( s.th - s.thT ) < 0.3 &&
			Math.abs( s.w ) < 3 &&
			Math.abs( s.b - s.bT ) < 0.3 &&
			Math.abs( s.bv ) < 3
		);
	}
	return (
		( s.th >= 179.9 || s.th <= 0.1 ) &&
		Math.abs( s.w ) < 5 &&
		Math.abs( s.b ) < 1 &&
		Math.abs( s.bv ) < 20
	);
}

// strip boundaries snapped to whole pixels, so text inside a moving page sits on the same pixel grid
// as the static page it replaces — otherwise glyphs twitch by a pixel at every hand-off
export function layoutSim( s, W ) {
	if ( s.W === W ) {
		return;
	}
	s.W = W;
	const fwd = s.dir === 'fwd';
	const B = [];
	for ( let k = 0; k <= N; k++ ) {
		B.push( Math.round( ( k * W ) / N ) );
	}
	s.B = B;
	s.strips.forEach( ( st, k ) => {
		const near = `${ -B[ k ] }px`;
		const far = `${ -( W - B[ k + 1 ] - OVERLAP ) }px`;
		st.el.style.width = `${ B[ k + 1 ] - B[ k ] + OVERLAP }px`;
		st.el.style.left = fwd ? `${ B[ k ] }px` : 'auto';
		st.el.style.right = fwd ? 'auto' : `${ B[ k ] }px`;
		if ( st.slF ) {
			st.slF.style.width = `${ W }px`;
			st.slF.style.left = fwd ? near : far;
		}
		if ( st.slB ) {
			st.slB.style.width = `${ W }px`;
			st.slB.style.left = fwd ? far : near;
		}
	} );
}

export function applySim( s ) {
	if ( ! s.el ) {
		return;
	}
	const W = s.el.offsetWidth;
	if ( ! W ) {
		return;
	}
	layoutSim( s, W );
	const B = s.B;
	const fwd = s.dir === 'fwd';
	const phs = [];
	const shade = [];
	for ( let k = 0; k < N; k++ ) {
		phs[ k ] = Math.max( 0, Math.min( 180, s.th + s.b * PROFILE[ k ] ) );
		// a strip tilted away from the page plane catches less light
		shade[ k ] =
			0.34 * ( 1 - Math.abs( Math.cos( ( phs[ k ] * Math.PI ) / 180 ) ) );
	}
	// shade values at each crease, blended across every strip so the curl reads as one smooth surface
	const J = [ shade[ 0 ] ];
	for ( let k = 1; k < N; k++ ) {
		J.push( ( shade[ k - 1 ] + shade[ k ] ) / 2 );
	}
	J.push( shade[ N - 1 ] );
	const grad = ( dir, a, b ) =>
		`linear-gradient(to ${ dir }, rgba(0,0,0,${ a.toFixed( 3 ) }), rgba(0,0,0,${ b.toFixed( 3 ) }))`;
	let X = 0;
	let Z = 0;
	for ( let k = 0; k < N; k++ ) {
		const st = s.strips[ k ];
		if ( ! st ) {
			continue;
		}
		const ph = phs[ k ];
		const r = ( ph * Math.PI ) / 180;
		const dx = fwd ? X - B[ k ] : -X + B[ k ];
		st.el.style.transform = `translate3d(${ dx.toFixed( 2 ) }px,0,${ Z.toFixed( 2 ) }px) rotateY(${ ( fwd ? -ph : ph ).toFixed( 3 ) }deg)`;
		// cull in JS rather than trusting backface-visibility, which GPU compositing can leak through
		const front = ph < 90;
		if ( st.fF ) {
			st.fF.style.visibility = front ? 'visible' : 'hidden';
		}
		if ( st.fB ) {
			st.fB.style.visibility = front ? 'hidden' : 'visible';
		}
		if ( st.shF ) {
			st.shF.style.background = grad(
				fwd ? 'right' : 'left',
				J[ k ],
				J[ k + 1 ]
			);
			st.shF.style.opacity = '1';
		}
		if ( st.shB ) {
			st.shB.style.background = grad(
				fwd ? 'left' : 'right',
				J[ k ],
				J[ k + 1 ]
			);
			st.shB.style.opacity = '1';
		}
		X += ( B[ k + 1 ] - B[ k ] ) * Math.cos( r );
		Z += ( B[ k + 1 ] - B[ k ] ) * Math.sin( r );
	}
}
