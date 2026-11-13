<?php
/**
 * The sections, as the magazine shows them.
 *
 * @package Magazine
 */

namespace Magazine\Layout;

use Magazine\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the sections from the content plugin (the `akashsrma_sections` filter; none without the plugin) and
 * adds the magazine's own options for each: its bookmark and colour and, for a collection, its opening page,
 * index style and back link.
 */
final class Sections {

	/**
	 * The sections, in order.
	 *
	 * @return list<array{key: string, label: string, url: string, color: string, bookmark: bool, post?: \WP_Post, archive?: array{title: string, description: string, opener: \WP_Post|null, index: string, back: string, posts: list<\WP_Post>}}>
	 */
	public static function all(): array {
		$sections = array();
		foreach ( array_values( (array) apply_filters( 'akashsrma_sections', array() ) ) as $i => $section ) {
			$options = Settings::section( $section, $i );
			$item    = array(
				'key'      => (string) $section['key'],
				'label'    => (string) $section['label'],
				'url'      => (string) $section['url'],
				'color'    => $options['color'],
				'bookmark' => $options['bookmark'],
			);
			if ( isset( $section['post'] ) ) {
				$sections[] = $item + array( 'post' => $section['post'] );
				continue;
			}
			$opener     = $options['opener'] ? get_post( $options['opener'] ) : null;
			$sections[] = $item + array(
				'archive' => array(
					'title'       => (string) $section['collection']['name'],
					'description' => (string) $section['collection']['description'],
					'opener'      => $opener instanceof \WP_Post && 'publish' === $opener->post_status ? $opener : null,
					'index'       => $options['index'],
					'back'        => $options['back'],
					'posts'       => array_values( $section['collection']['posts'] ),
				),
			);
		}
		return $sections;
	}
}
