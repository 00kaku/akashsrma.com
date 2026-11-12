<?php
/**
 * The plugin's bootstrap.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features;

use Akashsrma\Features\Core\Config;
use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the plugin's modules, giving each what it needs, and registers the ones that apply. Adding a
 * feature is adding a class under inc/ and a line in modules(). Every hook lives in a module's register().
 */
final class Plugin {

	/**
	 * Sets up the plugin.
	 *
	 * @param string $file Absolute path of the main plugin file.
	 */
	public function __construct( private string $file ) {}

	/**
	 * Registers every module that applies to this request.
	 */
	public function boot(): void {
		foreach ( $this->modules() as $module ) {
			if ( $module->can_register() ) {
				$module->register();
			}
		}
	}

	/**
	 * The plugin's modules.
	 *
	 * @return list<Module>
	 */
	private function modules(): array {
		$dir      = dirname( $this->file );
		$config   = new Config( $dir . '/config' );
		$sections = new Content\Sections( $config );

		return array(
			// Content: what the site holds, and in what sections.
			new Content\PostTypes( $config ),
			new Content\Meta( $config ),
			new Content\Taxonomies( $config ),
			$sections,
			new Content\SectionData( $sections ),
			new Content\Comments(),

			// Blocks, for every theme or only those that declare the feature a block needs.
			new Blocks\Blocks( $config, $dir . '/build/blocks' ),

			// Admin screens.
			new Admin\SectionsPage( $sections, plugin_dir_url( $this->file ) ),
		);
	}
}
