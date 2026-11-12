<?php
/**
 * The theme's bootstrap.
 *
 * @package Magazine
 */

namespace Magazine;

use Magazine\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the theme's modules that apply to this request. Adding a feature is adding a class under inc/
 * and a line in modules(). Every hook lives in a module's register().
 */
final class Theme {

	/**
	 * Registers every module that applies.
	 */
	public function boot(): void {
		foreach ( $this->modules() as $module ) {
			if ( $module->can_register() ) {
				$module->register();
			}
		}
	}

	/**
	 * The theme's modules.
	 *
	 * @return list<Module>
	 */
	private function modules(): array {
		return array(
			new Setup\Supports(),
			new Setup\Blocks(),
			new Setup\BlockStyles(),
			new Setup\Patterns(),
			new Setup\Fonts(),
			new Routing\MissingSpreads(),

			// The magazine's own settings, and their screen (Appearance → Magazine).
			new Settings\Settings(),
			new Admin\MagazinePage(),
		);
	}
}
