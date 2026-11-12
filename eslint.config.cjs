/**
 * ESLint: the @wordpress/scripts defaults, plus one exception — @wordpress/* packages are provided
 * by WordPress at runtime (webpack externals), so they are never installed locally.
 */
const defaults = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...defaults,
	{
		settings: {
			// Treated like internal modules, so they aren't expected in package.json.
			'import/internal-regex': '^@wordpress/',
		},
		rules: {
			'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/' ] } ],
		},
	},
];
