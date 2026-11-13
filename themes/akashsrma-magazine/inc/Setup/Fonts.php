<?php
/**
 * Font preloading.
 *
 * @package Magazine
 */

namespace Magazine\Setup;

use Magazine\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Preloads the fonts the first screen needs, so the masthead doesn't flash in a fallback face. The font
 * faces themselves are declared in theme.json.
 */
final class Fonts implements Module {

	/**
	 * Fonts are preloaded on the front end only.
	 */
	public function can_register(): bool {
		return ! is_admin();
	}

	/**
	 * Hooks the preload links early into the head.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'preload' ), 1 );
	}

	/**
	 * Prints the preload links.
	 */
	public function preload(): void {
		foreach ( array( 'bodoni-moda-latin-wght-normal', 'source-serif-4-latin-wght-normal' ) as $font ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( get_theme_file_uri( "assets/fonts/{$font}.woff2" ) )
			);
		}
	}
}
