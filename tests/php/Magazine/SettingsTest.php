<?php
/**
 * The magazine's own settings (Appearance → Magazine).
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Magazine;

use Magazine\Settings\Settings;

/**
 * Checks defaults, cleaning, per-section options and the carry-over of old values.
 */
class SettingsTest extends \WP_UnitTestCase {

	/**
	 * Unsaved settings are the defaults; an empty value falls back too.
	 */
	public function test_defaults(): void {
		$this->assertSame( '01', Settings::values()['number'] );
		update_option( Settings::OPTION, array( 'masthead' => '  ' ) );
		$this->assertSame( 'AKASH SHARMA', Settings::values()['masthead'] );
	}

	/**
	 * Saving keeps known fields only, each cleaned by its type.
	 */
	public function test_settings_are_cleaned(): void {
		$clean = Settings::sanitize_settings(
			array(
				'number'   => '<b>02</b>',
				'cover_id' => '-5',
				'fillers'  => '3, x, 3, 4',
				'unknown'  => 'dropped',
			)
		);

		$this->assertSame( '02', $clean['number'] );
		$this->assertSame( 5, $clean['cover_id'] );
		$this->assertSame( '3,4', $clean['fillers'] );
		$this->assertArrayNotHasKey( 'unknown', $clean );
	}

	/**
	 * A section without saved options gets a bookmark in the next colour, a list index, a back link named
	 * after its collection, and the Page with its archive slug as opening page.
	 */
	public function test_section_defaults(): void {
		$opener  = self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'portfolio',
			)
		);
		$section = array(
			'key'        => 'project',
			'collection' => array(
				'name' => 'Portfolio',
				'slug' => 'portfolio',
			),
		);
		$options = Settings::section( $section, 5 );

		$this->assertTrue( $options['bookmark'] );
		$this->assertSame( Settings::COLORS[1], $options['color'] );
		$this->assertSame( 'list', $options['index'] );
		$this->assertSame( 'Portfolio', $options['back'] );
		$this->assertSame( $opener, $options['opener'] );
	}

	/**
	 * Saved section options are used, by section key.
	 */
	public function test_saved_section_options(): void {
		update_option( Settings::SECTIONS, Settings::sanitize_sections( array( 'page-7' => array( 'color' => 'flag-green' ) ) ) );
		$options = Settings::section( array( 'key' => 'page-7' ), 0 );

		$this->assertFalse( $options['bookmark'], 'Saved without the box ticked.' );
		$this->assertSame( 'flag-green', $options['color'] );
	}

	/**
	 * Values the content plugin stored before the split are read until the theme's options are saved.
	 */
	public function test_old_values_carry_over(): void {
		update_option( 'akashsrma_issue', array( 'city' => 'Pune' ) );
		update_option(
			'akashsrma_contents',
			array(
				array(
					'page'     => 12,
					'bookmark' => false,
					'color'    => 'flag-coral',
				),
			)
		);

		$this->assertSame( 'Pune', Settings::values()['city'] );
		$options = Settings::section( array( 'key' => 'page-12' ), 0 );
		$this->assertFalse( $options['bookmark'] );
		$this->assertSame( 'flag-coral', $options['color'] );
	}
}
