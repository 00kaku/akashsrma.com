<?php
/**
 * Block styles editors pick for magazine pages.
 *
 * @package Magazine
 */

namespace Magazine\Setup;

use Magazine\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the image Bleed styles and the table Facts style. Their CSS ships with the Magazine block.
 */
final class BlockStyles implements Module {

	/**
	 * Needed in the editor and on the front end.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Hooks the registration to `init`.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_styles' ) );
	}

	/**
	 * Registers the styles.
	 */
	public function register_styles(): void {
		$styles = array(
			array( 'core/image', 'bleed', __( 'Bleed (magazine)', 'magazine' ) ),
			array( 'core/image', 'bleed-short', __( 'Bleed, short (magazine)', 'magazine' ) ),
			array( 'core/table', 'facts', __( 'Facts (magazine)', 'magazine' ) ),
		);
		foreach ( $styles as [ $block, $name, $label ] ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
