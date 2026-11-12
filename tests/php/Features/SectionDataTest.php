<?php
/**
 * The sections themes receive (the `akashsrma_sections` filter).
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Features;

use Akashsrma\Features\Content\Sections;

/**
 * Checks the data shape and which posts a collection carries.
 */
class SectionDataTest extends \WP_UnitTestCase {

	/**
	 * The sections, as a theme sees them.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function sections(): array {
		return apply_filters( 'akashsrma_sections', array() );
	}

	/**
	 * A Page section: stable key, type, label (or the Page title), URL and the post.
	 */
	public function test_page_section(): void {
		$page = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Contact',
			)
		);
		update_option(
			Sections::OPTION,
			array(
				array(
					'page'  => $page,
					'label' => '',
				),
			)
		);
		$section = $this->sections()[0];

		$this->assertSame( 'page-' . $page, $section['key'] );
		$this->assertSame( 'page', $section['type'] );
		$this->assertSame( 'Contact', $section['label'] );
		$this->assertSame( $page, $section['post']->ID );
	}

	/**
	 * A collection: keyed by post type, with its name, archive slug and latest posts, newest first, up to the
	 * theme's limit.
	 */
	public function test_collection_shows_latest_posts(): void {
		$ids = array();
		foreach ( range( 1, 7 ) as $day ) {
			$ids[] = self::factory()->post->create(
				array(
					'post_type' => 'project',
					'post_date' => "2026-01-0{$day} 10:00:00",
				)
			);
		}
		update_option( Sections::OPTION, array( array( 'post_type' => 'project' ) ) );
		$section = $this->sections()[0];

		$this->assertSame( 'project', $section['key'] );
		$this->assertSame( 'collection', $section['type'] );
		$this->assertSame( 'portfolio', $section['collection']['slug'] );
		$this->assertSame( array_slice( array_reverse( $ids ), 0, Sections::limit() ), wp_list_pluck( $section['collection']['posts'], 'ID' ) );
	}

	/**
	 * Chosen posts appear in the chosen order.
	 */
	public function test_chosen_posts_keep_their_order(): void {
		$ids = self::factory()->post->create_many( 3, array( 'post_type' => 'project' ) );
		update_option(
			Sections::OPTION,
			array(
				array(
					'post_type' => 'project',
					'pick'      => true,
					'posts'     => array( $ids[2], $ids[0] ),
				),
			)
		);

		$this->assertSame( array( $ids[2], $ids[0] ), wp_list_pluck( $this->sections()[0]['collection']['posts'], 'ID' ) );
	}

	/**
	 * Unpublished Pages are left out.
	 */
	public function test_unpublished_pages_are_skipped(): void {
		$draft = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		update_option( Sections::OPTION, array( array( 'page' => $draft ) ) );
		$this->assertSame( array(), $this->sections() );
	}
}
