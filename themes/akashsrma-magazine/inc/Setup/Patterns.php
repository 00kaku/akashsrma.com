<?php
/**
 * The category the theme's patterns sit in.
 *
 * @package Magazine
 */

namespace Magazine\Setup;

use Magazine\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Magazine pattern category. The patterns themselves (patterns/) are registered by WordPress
 * from their file headers.
 */
final class Patterns implements Module {

	/**
	 * Needed in the editor.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Hooks the registration to `init`.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_category' ) );
	}

	/**
	 * Registers the category.
	 */
	public function register_category(): void {
		register_block_pattern_category( 'magazine', array( 'label' => __( 'Magazine', 'magazine' ) ) );
	}
}
