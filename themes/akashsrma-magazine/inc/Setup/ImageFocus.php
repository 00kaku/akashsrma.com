<?php
/**
 * Crop focus for Image blocks.
 *
 * @package Magazine
 */

namespace Magazine\Setup;

use Magazine\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Prints an Image block's crop focus (its `magazineFocus` attribute, set in the editor) as the --mz-focus
 * custom property on the block's figure. The magazine's image styles use it as the picture's
 * object-position, so a cropped picture keeps its subject in view at every size.
 */
final class ImageFocus implements Module {

	/**
	 * Needed wherever blocks render.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Filters each Image block's output.
	 */
	public function register(): void {
		add_filter( 'render_block_core/image', array( $this, 'add_focus' ), 10, 2 );
	}

	/**
	 * A focus point as a CSS position, or null when it isn't a usable point.
	 *
	 * @param mixed $focus The attribute: x and y, each from 0 to 1.
	 * @return string|null For example "50% 20%".
	 */
	public static function position( mixed $focus ): ?string {
		if ( ! is_array( $focus ) || ! isset( $focus['x'], $focus['y'] ) || ! is_numeric( $focus['x'] ) || ! is_numeric( $focus['y'] ) ) {
			return null;
		}
		$percent = static fn( $value ): int => (int) round( min( 1, max( 0, (float) $value ) ) * 100 );
		return sprintf( '%d%% %d%%', $percent( $focus['x'] ), $percent( $focus['y'] ) );
	}

	/**
	 * Adds the focus to the block's figure.
	 *
	 * @param string               $content The block's HTML.
	 * @param array<string, mixed> $block   The parsed block.
	 */
	public function add_focus( string $content, array $block ): string {
		$position = self::position( $block['attrs']['magazineFocus'] ?? null );
		if ( null === $position ) {
			return $content;
		}
		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( ! $tags->next_tag( array( 'tag_name' => 'figure' ) ) ) {
			return $content;
		}
		$style = $tags->get_attribute( 'style' );
		$style = is_string( $style ) ? rtrim( trim( $style ), ';' ) : '';
		$tags->set_attribute( 'style', ( '' !== $style ? $style . '; ' : '' ) . '--mz-focus: ' . $position );
		return $tags->get_updated_html();
	}
}
