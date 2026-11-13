/**
 * The page-turn physics (themes/akashsrma-magazine/src/blocks/magazine/physics.js): a turning page must land, fall back
 * and settle the way the engine relies on. Run with `npm run test:js`.
 */
import { describe, expect, it } from 'vitest';

import {
	stepSim,
	simSettled,
} from '../../themes/akashsrma-magazine/src/blocks/magazine/physics';

const sim = ( props ) => ( {
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
	...props,
} );

// Steps a sim at the engine's fixed 1/120 s until it settles, or gives up after `seconds`.
const run = ( s, seconds = 3 ) => {
	let t = 0;
	while ( t < seconds && ! simSettled( s ) ) {
		stepSim( s, 1 / 120 );
		t += 1 / 120;
	}
	return t;
};

describe( 'page physics', () => {
	it( 'a pushed page turns over and lands flat', () => {
		const s = sim( { kick: true } );
		const time = run( s );
		expect( simSettled( s ) ).toBe( true );
		expect( s.th ).toBeCloseTo( 180, 0 );
		expect( time ).toBeLessThan( 1.5 );
	} );

	it( 'a page pushed back from the far side lands where it started', () => {
		const s = sim( { th: 180, pushDir: -1, kick: true } );
		run( s );
		expect( simSettled( s ) ).toBe( true );
		expect( s.th ).toBeCloseTo( 0, 0 );
	} );

	it( 'a freshly pushed page is not settled before it moves', () => {
		expect( simSettled( sim( { kick: true } ) ) ).toBe( false );
	} );

	it( 'a lifted corner (peek) rests at its target angle', () => {
		const s = sim( { mode: 'peek', thT: 9, bT: 14 } );
		run( s );
		expect( simSettled( s ) ).toBe( true );
		expect( s.th ).toBeCloseTo( 9, 0 );
	} );

	it( 'a resting page does not move', () => {
		const s = sim( { mode: 'rest', th: 42 } );
		stepSim( s, 1 / 120 );
		expect( s.th ).toBe( 42 );
		expect( simSettled( s ) ).toBe( true );
	} );
} );
