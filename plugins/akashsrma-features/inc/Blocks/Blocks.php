<?php
/**
 * The plugin's blocks, built into build/blocks.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Blocks;

use Akashsrma\Features\Core\Config;
use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Registers each built block. Blocks work in any theme unless config/blocks.php names a theme feature for
 * them; those register only when the active theme declares that feature (add_theme_support).
 */
final class Blocks implements Module {

	/**
	 * Sets up the module.
	 *
	 * @param Config $config The plugin's configuration.
	 * @param string $build  Absolute path of build/blocks.
	 */
	public function __construct( private Config $config, private string $build ) {}

	/**
	 * Blocks are needed on the front end and in the editor.
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
	 * Registers the built blocks the active theme supports, reading their metadata from the manifest the build writes.
	 */
	public function register_blocks(): void {
		$manifest = $this->build . '/blocks-manifest.php';
		if ( ! is_readable( $manifest ) ) {
			return;
		}
		wp_register_block_metadata_collection( $this->build, $manifest );
		foreach ( array_keys( (array) require $manifest ) as $block ) {
			if ( self::allowed( $this->config->get( 'blocks' )[ $block ] ?? null ) ) {
				register_block_type( $this->build . '/' . $block );
			}
		}
	}

	/**
	 * Whether a block needing a theme feature (or none) can be used with the active theme.
	 *
	 * @param string|null $feature The theme feature the block needs, or null.
	 */
	public static function allowed( ?string $feature ): bool {
		return null === $feature || current_theme_supports( $feature );
	}
}
