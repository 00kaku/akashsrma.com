<?php
/**
 * The magazine's layout: pages, spreads, URLs and folios.
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Magazine;

use Magazine\Layout\Magazine;

/**
 * Lays out sections given directly through the filter, so each test controls its content.
 */
class LayoutTest extends \WP_UnitTestCase {

	/**
	 * A Page Break block, as the editor saves it.
	 */
	private const BREAK = "\n<!-- wp:nextpage -->\n<!--nextpage-->\n<!-- /wp:nextpage -->\n";

	/**
	 * Pretty permalinks, as the site uses.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
	}

	/**
	 * A published Page with the given number of pages.
	 *
	 * @param int $pages How many magazine pages.
	 */
	private function page_with( int $pages ): \WP_Post {
		$parts = array_map( fn( $n ) => "<!-- wp:paragraph --><p>Page {$n}</p><!-- /wp:paragraph -->", range( 1, $pages ) );
		return self::factory()->post->create_and_get(
			array(
				'post_type'    => 'page',
				'post_name'    => 'story',
				'post_content' => implode( self::BREAK, $parts ),
			)
		);
	}

	/**
	 * Lays out one Page section.
	 *
	 * @param \WP_Post $post The Page.
	 * @return array<string, mixed>
	 */
	private function layout_of( \WP_Post $post ): array {
		$section = array(
			'key'   => 'page-' . $post->ID,
			'type'  => 'page',
			'label' => 'Story',
			'url'   => get_permalink( $post ),
			'post'  => $post,
		);
		add_filter( 'akashsrma_sections', fn() => array( $section ), 20 );
		return Magazine::layout();
	}

	/**
	 * Pages pair into spreads; an odd count is closed by a filler page.
	 */
	public function test_pages_pair_into_spreads_with_a_filler(): void {
		$spreads = $this->layout_of( $this->page_with( 3 ) )['spreads'];

		$this->assertCount( 2, $spreads );
		$this->assertSame( 'content', $spreads[1]['pages'][0]['kind'] );
		$this->assertSame( 'filler', $spreads[1]['pages'][1]['kind'] );
	}

	/**
	 * Continuation spreads get paged URLs; folios count from the cover.
	 */
	public function test_urls_and_folios(): void {
		$post    = $this->page_with( 4 );
		$spreads = $this->layout_of( $post )['spreads'];

		$this->assertSame( get_permalink( $post ), $spreads[0]['url'] );
		$this->assertSame( trailingslashit( get_permalink( $post ) ) . '2/', $spreads[1]['url'] );
		$this->assertSame( array( 2, 3 ), array( $spreads[0]['pages'][0]['folio'], $spreads[0]['pages'][1]['folio'] ) );
		$this->assertSame( 5, $spreads[1]['pages'][1]['folio'] );
	}

	/**
	 * A continued spread opens with "Continued from page N".
	 */
	public function test_continued_spreads_are_labelled(): void {
		$spreads = $this->layout_of( $this->page_with( 4 ) )['spreads'];
		$this->assertStringContainsString( 'Continued from page 3', $spreads[1]['pages'][0]['html'] );
	}

	/**
	 * Every spread carries the title WordPress gives its URL, continuation spreads included, so the browser tab
	 * can follow the page as it turns.
	 */
	public function test_spreads_carry_their_titles(): void {
		$post    = $this->page_with( 3 );
		$spreads = $this->layout_of( $post )['spreads'];
		$title   = get_the_title( $post ) . ' - ' . get_bloginfo( 'name' );

		$this->assertSame( $title, $spreads[0]['title'] );
		$this->assertSame( $title, $spreads[1]['title'] );
	}

	/**
	 * The home title is the site name and tagline, as on the front page.
	 */
	public function test_home_title(): void {
		update_option( 'blogdescription', 'Web Engineer' );
		$this->assertSame( get_bloginfo( 'name' ) . ' - Web Engineer', $this->layout_of( $this->page_with( 1 ) )['home_title'] );
	}

	/**
	 * A sectionless site lays out nothing, and the magazine stays closed.
	 */
	public function test_no_sections(): void {
		add_filter( 'akashsrma_sections', fn() => array(), 20 );
		$layout = Magazine::layout();
		$this->assertSame( array(), $layout['spreads'] );
		$this->assertSame( -1, $layout['current'] );
	}

	/**
	 * Spread count is pages over two, rounded up.
	 */
	public function test_spread_count(): void {
		$this->assertSame( 1, Magazine::spread_count( $this->page_with( 1 ) ) );
		$this->assertSame( 2, Magazine::spread_count( $this->page_with( 3 ) ) );
		$this->assertSame( 2, Magazine::spread_count( $this->page_with( 4 ) ) );
	}
}
