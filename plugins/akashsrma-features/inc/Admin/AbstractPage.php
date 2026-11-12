<?php
/**
 * The shared shape of the plugin's admin screens.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Admin;

use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * An admin screen: registered only in wp-admin, it adds its menu entry and, if it has any, its assets.
 * Subclasses say where the entry goes (add_menu) and draw the screen (render).
 */
abstract class AbstractPage implements Module {

	/**
	 * Admin screens exist only in wp-admin.
	 */
	public function can_register(): bool {
		return is_admin();
	}

	/**
	 * Hooks the menu entry and the assets.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds the screen's menu entry.
	 */
	abstract public function add_menu(): void;

	/**
	 * Draws the screen.
	 */
	abstract public function render(): void;

	/**
	 * Loads the screen's assets. None by default.
	 *
	 * @param string $hook The admin screen being loaded.
	 */
	public function enqueue( string $hook ): void {}
}
