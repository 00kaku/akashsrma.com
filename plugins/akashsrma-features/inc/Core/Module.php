<?php
/**
 * A feature of the plugin: it says whether it applies, then adds its own hooks.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Every module is listed in Plugin::modules(); the plugin calls register() on each one that can_register().
 */
interface Module {

	/**
	 * Whether this module applies to the current request (for example, admin screens only in wp-admin).
	 */
	public function can_register(): bool;

	/**
	 * Adds the module's hooks. Nothing else runs until WordPress calls them.
	 */
	public function register(): void;
}
