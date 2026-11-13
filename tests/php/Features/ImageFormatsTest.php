<?php
/**
 * WebP copies of uploaded pictures.
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Features;

/**
 * Checks the image editor's output formats.
 */
class ImageFormatsTest extends \WP_UnitTestCase {

	/**
	 * JPEG and PNG sizes come out as WebP, when the server can write WebP.
	 */
	public function test_jpeg_and_png_become_webp(): void {
		if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$this->markTestSkipped( 'This image editor cannot write WebP.' );
		}
		$formats = apply_filters( 'image_editor_output_format', array(), '', 'image/png' );
		$this->assertSame( 'image/webp', $formats['image/png'] );
		$this->assertSame( 'image/webp', $formats['image/jpeg'] );
	}

	/**
	 * Other formats keep whatever they had.
	 */
	public function test_other_formats_are_untouched(): void {
		$formats = apply_filters( 'image_editor_output_format', array( 'image/gif' => 'image/gif' ), '', 'image/gif' );
		$this->assertSame( 'image/gif', $formats['image/gif'] );
	}
}
