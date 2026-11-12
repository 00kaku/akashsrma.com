<?php
/**
 * The value types config files use for fields and settings.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Maps each config field type to how WordPress stores it and the function that cleans it.
 */
final class FieldTypes {

	/**
	 * Config type => [ storage type, sanitizer ].
	 */
	private const TYPES = array(
		'string'  => array( 'string', 'sanitize_text_field' ),
		'url'     => array( 'string', 'esc_url_raw' ),
		'boolean' => array( 'boolean', 'rest_sanitize_boolean' ),
		'integer' => array( 'integer', 'absint' ),
	);

	/**
	 * A config type's storage type and sanitizer.
	 *
	 * @param string $kind A config type: string, url, boolean or integer.
	 * @return array{0: string, 1: callable-string}
	 */
	public static function get( string $kind ): array {
		return self::TYPES[ $kind ];
	}
}
