<?php
/**
 * A feature of the theme: it says whether it applies, then adds its own hooks.
 *
 * @package Magazine
 */

namespace Magazine\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Every module is listed in Theme::modules(); the theme calls register() on each one that can_register().
 */
interface Module {

	/**
	 * Whether this module applies to the current request.
	 */
	public function can_register(): bool;

	/**
	 * Adds the module's hooks.
	 */
	public function register(): void;
}
