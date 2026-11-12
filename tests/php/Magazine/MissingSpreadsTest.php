<?php
/**
 * 404s for spreads that don't exist.
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Magazine;

/**
 * Requests paged URLs of a two-spread Page.
 */
class MissingSpreadsTest extends \WP_UnitTestCase {

	/**
	 * The Page's URL.
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * A Page with four magazine pages: two spreads.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
		$break     = "\n<!-- wp:nextpage -->\n<!--nextpage-->\n<!-- /wp:nextpage -->\n";
		$this->url = get_permalink(
			self::factory()->post->create(
				array(
					'post_type'    => 'page',
					'post_name'    => 'story',
					'post_content' => implode( $break, array( 'One', 'Two', 'Three', 'Four' ) ),
				)
			)
		);
	}

	/**
	 * A spread that exists is served.
	 */
	public function test_existing_spread_is_served(): void {
		$this->go_to( $this->url . '2/' );
		$this->assertFalse( is_404() );
		$this->assertTrue( is_page() );
	}

	/**
	 * A spread past the last one is a 404, though WordPress counts four Page Break pages.
	 */
	public function test_missing_spread_is_a_404(): void {
		$this->go_to( $this->url . '3/' );
		$this->assertTrue( is_404() );
	}

	/**
	 * The missing spread is not redirected to the Page.
	 */
	public function test_missing_spread_is_not_redirected(): void {
		$this->go_to( $this->url . '3/' );
		$this->assertFalse( apply_filters( 'redirect_canonical', $this->url, $this->url . '3/' ) );
	}
}
