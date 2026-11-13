<?php
/**
 * Crop focus on Image blocks.
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Magazine;

use Magazine\Setup\ImageFocus;

/**
 * Renders Image blocks with and without a focus point.
 */
class ImageFocusTest extends \WP_UnitTestCase {

	/**
	 * An Image block as the editor saves it, with the given attributes.
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 * @param string               $style The figure's style attribute, if any.
	 */
	private function image( array $attrs, string $style = '' ): string {
		$style = '' !== $style ? ' style="' . $style . '"' : '';
		return '<!-- wp:image ' . wp_json_encode( $attrs ) . ' --><figure class="wp-block-image is-style-bleed"' . $style . '><img src="https://example.org/a.jpg" alt=""/></figure><!-- /wp:image -->';
	}

	/**
	 * A focus point, as the editor stores it.
	 *
	 * @param mixed $x Across, from 0 to 1.
	 * @param mixed $y Down, from 0 to 1.
	 * @return array<string, mixed>
	 */
	private function point( mixed $x, mixed $y ): array {
		$point      = array();
		$point['x'] = $x;
		$point['y'] = $y;
		return $point;
	}

	/**
	 * A focus point becomes the --mz-focus custom property on the figure.
	 */
	public function test_focus_is_printed_on_the_figure(): void {
		$tags = new \WP_HTML_Tag_Processor( do_blocks( $this->image( array( 'magazineFocus' => $this->point( 0.5, 0.2 ) ) ) ) );
		$tags->next_tag( array( 'tag_name' => 'figure' ) );
		$this->assertSame( '--mz-focus: 50% 20%', $tags->get_attribute( 'style' ) );
	}

	/**
	 * A style the figure already has is kept.
	 */
	public function test_existing_style_is_kept(): void {
		$html = do_blocks( $this->image( array( 'magazineFocus' => $this->point( 0.25, 0.75 ) ), 'margin-top: 0;' ) );
		$this->assertStringContainsString( 'style="margin-top: 0; --mz-focus: 25% 75%"', $html );
	}

	/**
	 * Without a focus point the block renders unchanged.
	 */
	public function test_no_focus_leaves_the_block_alone(): void {
		$html = do_blocks( $this->image( array() ) );
		$this->assertStringNotContainsString( '--mz-focus', $html );
	}

	/**
	 * Values are clamped to the picture, and anything that isn't a point is ignored.
	 */
	public function test_position_is_clamped_and_validated(): void {
		$this->assertSame( '0% 100%', ImageFocus::position( $this->point( -0.4, 1.7 ) ) );
		$this->assertSame( '33% 67%', ImageFocus::position( $this->point( '0.333', 0.666 ) ) );
		$this->assertNull( ImageFocus::position( $this->point( 'left', 0.5 ) ) );
		$this->assertNull( ImageFocus::position( array( 'x' => 0.5 ) ) );
		$this->assertNull( ImageFocus::position( '50% 50%' ) );
	}
}
