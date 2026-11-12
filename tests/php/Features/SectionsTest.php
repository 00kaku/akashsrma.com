<?php
/**
 * The site's sections (Content → Sections).
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Features;

use Akashsrma\Features\Content\Sections;
use Akashsrma\Features\Core\Config;

/**
 * Checks the starting sections, cleaning, the theme's post limit and the carry-over of old contents.
 */
class SectionsTest extends \WP_UnitTestCase {

	/**
	 * The sections under test.
	 *
	 * @var Sections
	 */
	private Sections $sections;

	/**
	 * A fresh Sections for each test, reading the plugin's real config.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->sections = new Sections( new Config( dirname( __DIR__, 3 ) . '/plugins/akashsrma-features/config' ) );
	}

	/**
	 * Before the screen is saved, the sections come from config/sections.php, with Pages found by slug.
	 */
	public function test_defaults_come_from_config(): void {
		$about = self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'about',
			)
		);
		$rows  = $this->sections->rows();

		$this->assertCount( 4, $rows );
		$this->assertSame(
			array(
				'page'  => $about,
				'label' => 'About',
			),
			$rows[0]
		);
		$this->assertSame(
			array(
				'post_type' => 'project',
				'label'     => 'Portfolio',
				'pick'      => false,
				'posts'     => array(),
			),
			$rows[1]
		);
	}

	/**
	 * Cleaning keeps real Pages and section post types, in order, and drops other keys (design options are a
	 * theme's business).
	 */
	public function test_sanitize_keeps_known_rows_and_keys(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$rows = $this->sections->sanitize(
			array(
				array(
					'post_type' => 'learning',
					'color'     => 'flag-blue',
				),
				array( 'post_type' => 'not-a-section' ),
				array( 'page' => 999999 ),
				array(
					'page'     => $page,
					'label'    => '<b>Hi</b>',
					'bookmark' => '1',
				),
			)
		);

		$this->assertCount( 2, $rows );
		$this->assertArrayNotHasKey( 'color', $rows[0] );
		$this->assertSame(
			array(
				'page'  => $page,
				'label' => 'Hi',
			),
			$rows[1]
		);
	}

	/**
	 * The theme sets how many posts a collection shows; without a theme setting it's DEFAULT_LIMIT.
	 */
	public function test_limit_comes_from_the_theme(): void {
		$this->assertSame( 5, Sections::limit(), 'The Magazine theme declares 5.' );

		add_theme_support( 'akashsrma-features', array( 'max_posts' => 2 ) );
		$this->assertSame( 2, Sections::limit() );

		remove_theme_support( 'akashsrma-features' );
		$this->assertSame( Sections::DEFAULT_LIMIT, Sections::limit() );
	}

	/**
	 * Chosen posts keep their order, belong to the collection, and stop at the theme's limit.
	 */
	public function test_chosen_posts_are_capped_and_typed(): void {
		add_theme_support( 'akashsrma-features', array( 'max_posts' => 3 ) );
		$projects = self::factory()->post->create_many( 5, array( 'post_type' => 'project' ) );
		$learning = self::factory()->post->create( array( 'post_type' => 'learning' ) );
		$row      = $this->sections->sanitize(
			array(
				array(
					'post_type' => 'project',
					'pick'      => '1',
					'posts'     => array_merge( array( $learning ), array_reverse( $projects ) ),
				),
			)
		)[0];

		$this->assertTrue( $row['pick'] );
		$this->assertSame( array_slice( array_reverse( $projects ), 0, 3 ), $row['posts'] );
	}

	/**
	 * Contents saved before the split carry over, without their design options.
	 */
	public function test_old_contents_carry_over(): void {
		update_option(
			'akashsrma_contents',
			array(
				array(
					'post_type' => 'project',
					'label'     => 'Work',
					'color'     => 'flag-blue',
					'index'     => 'thumbs',
				),
			)
		);
		$this->assertSame(
			array(
				array(
					'post_type' => 'project',
					'label'     => 'Work',
					'pick'      => false,
					'posts'     => array(),
				),
			),
			$this->sections->rows()
		);
	}
}
