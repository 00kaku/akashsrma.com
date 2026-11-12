<?php
/**
 * The features the theme declares.
 *
 * @package Magazine
 */

namespace Magazine\Setup;

use Magazine\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Declares the theme's features: `magazine`, so plugins register blocks made only for this theme; and, for the
 * Akashsrma Features plugin, how many posts a collection may show (as many as the index fits beside the
 * opening page).
 */
final class Supports implements Module {

	/**
	 * The most posts a collection's index shows beside its opening page.
	 */
	public const MAX_POSTS = 5;

	/**
	 * Needed on every request.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Hooks the declaration to `after_setup_theme`.
	 */
	public function register(): void {
		add_action( 'after_setup_theme', array( $this, 'add_supports' ) );
	}

	/**
	 * Declares the theme's features.
	 */
	public function add_supports(): void {
		add_theme_support( 'magazine' );
		add_theme_support( 'akashsrma-features', array( 'max_posts' => self::MAX_POSTS ) );
	}
}
