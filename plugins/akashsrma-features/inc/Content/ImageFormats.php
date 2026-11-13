<?php
/**
 * WebP copies of uploaded pictures.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Content;

use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress saves the sizes it makes of an uploaded JPEG or PNG (thumbnail, medium, large…) as WebP, which is
 * several times smaller for the same look. The uploaded original is kept as it is.
 */
final class ImageFormats implements Module {

	/**
	 * Uploads happen anywhere: the editor, the media screens, WP-CLI.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Hooks the output format.
	 */
	public function register(): void {
		add_filter( 'image_editor_output_format', array( $this, 'formats' ) );
	}

	/**
	 * Maps JPEG and PNG to WebP when the server's image editor can write WebP.
	 *
	 * @param array<string, string> $formats Input MIME type => output MIME type.
	 * @return array<string, string>
	 */
	public function formats( array $formats ): array {
		if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			return $formats;
		}
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';
		return $formats;
	}
}
