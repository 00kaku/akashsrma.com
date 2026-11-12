<?php
/**
 * The theme's blocks, built into build/blocks.
 *
 * @package Magazine
 */

namespace Magazine\Setup;

use Magazine\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every built block from the manifest the build writes. Each block.json declares its own scripts,
 * script modules and styles, which load only where the block is used.
 */
final class Blocks implements Module {

	/**
	 * Needed on the front end and in the editor.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Hooks the registration to `init`.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Registers the built blocks.
	 */
	public function register_blocks(): void {
		$build = get_theme_file_path( 'build/blocks' );
		if ( is_readable( $build . '/blocks-manifest.php' ) ) {
			wp_register_block_types_from_metadata_collection( $build, $build . '/blocks-manifest.php' );
		}
	}
}
