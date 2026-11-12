<?php
/**
 * The plugin's blocks.
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Features;

use Akashsrma\Features\Blocks\Blocks;

/**
 * Checks registration and the theme-feature check.
 */
class BlocksTest extends \WP_UnitTestCase {

	/**
	 * The Code & live links block is registered for every theme.
	 */
	public function test_project_links_block_is_registered(): void {
		$this->assertTrue( \WP_Block_Type_Registry::get_instance()->is_registered( 'akashsrma/project-links' ) );
	}

	/**
	 * A block that needs a theme feature is allowed only when the active theme declares it.
	 */
	public function test_theme_feature_check(): void {
		$this->assertTrue( Blocks::allowed( null ) );
		$this->assertTrue( Blocks::allowed( 'magazine' ), 'The Magazine theme declares the magazine feature.' );
		$this->assertFalse( Blocks::allowed( 'some-other-theme' ) );
	}
}
