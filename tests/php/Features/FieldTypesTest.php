<?php
/**
 * The config field types.
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Features;

use Akashsrma\Features\Core\FieldTypes;

/**
 * Checks the type map.
 */
class FieldTypesTest extends \WP_UnitTestCase {

	/**
	 * Each type maps to a storage type and a sanitizer.
	 */
	public function test_types_map_to_storage_and_sanitizer(): void {
		$this->assertSame( array( 'string', 'esc_url_raw' ), FieldTypes::get( 'url' ) );
		$this->assertSame( 'boolean', FieldTypes::get( 'boolean' )[0] );
	}
}
