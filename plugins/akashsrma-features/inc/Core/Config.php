<?php
/**
 * The plugin's configuration: one PHP file per topic in config/, each returning an array.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Loads config files on first use. Modules read config from their hooks (after `init`), so translated
 * labels in the files work.
 */
final class Config {

	/**
	 * Files already loaded, by name.
	 *
	 * @var array<string, array<mixed>>
	 */
	private array $loaded = array();

	/**
	 * Sets up the config.
	 *
	 * @param string $dir Absolute path of the config directory.
	 */
	public function __construct( private string $dir ) {}

	/**
	 * A config file's array, by file name without `.php` (for example `post-types`).
	 *
	 * @param string $name The file's name.
	 * @return array<mixed>
	 */
	public function get( string $name ): array {
		$this->loaded[ $name ] ??= (array) require $this->dir . '/' . $name . '.php';
		return $this->loaded[ $name ];
	}
}
