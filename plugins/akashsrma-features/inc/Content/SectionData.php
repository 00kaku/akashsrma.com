<?php
/**
 * The sections a theme shows: the plugin's main output, through the `akashsrma_sections` filter.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Content;

use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Turns the Sections rows into plain data any theme can show. A theme reads it with
 * apply_filters( 'akashsrma_sections', array() ), so it still runs without the plugin.
 *
 * Each section has a stable `key` (`page-{ID}`, or the post type's name: it survives reordering and
 * relabelling, so themes can keep their own options against it), a `type` (`page` or `collection`), a
 * `label` and a `url`. A page section has its `post`; a collection has `collection`: its post type, name,
 * description, archive slug and posts.
 */
final class SectionData implements Module {

	/**
	 * Sets up the module.
	 *
	 * @param Sections $sections The site's sections.
	 */
	public function __construct( private Sections $sections ) {}

	/**
	 * Themes read sections on the front end and in the editor.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Answers the `akashsrma_sections` filter.
	 */
	public function register(): void {
		add_filter( 'akashsrma_sections', array( $this, 'sections' ) );
	}

	/**
	 * The sections, in order.
	 *
	 * @return list<array{key: string, type: string, label: string, url: string, post?: \WP_Post, collection?: array{post_type: string, name: string, description: string, slug: string, posts: list<\WP_Post>}}>
	 */
	public function sections(): array {
		$sections = array();
		$limit    = Sections::limit();

		foreach ( $this->sections->rows() as $row ) {
			if ( isset( $row['page'] ) ) {
				$page = get_post( (int) $row['page'] );
				if ( $page instanceof \WP_Post && 'publish' === $page->post_status ) {
					$sections[] = array(
						'key'   => 'page-' . $page->ID,
						'type'  => 'page',
						'label' => '' !== $row['label'] ? $row['label'] : get_the_title( $page ),
						'url'   => (string) get_permalink( $page ),
						'post'  => $page,
					);
				}
				continue;
			}

			$type = get_post_type_object( (string) $row['post_type'] );
			if ( ! $type ) {
				continue;
			}
			$sections[] = array(
				'key'        => $type->name,
				'type'       => 'collection',
				'label'      => '' !== $row['label'] ? $row['label'] : $type->labels->name,
				'url'        => (string) get_post_type_archive_link( $type->name ),
				'collection' => array(
					'post_type'   => $type->name,
					'name'        => $type->labels->name,
					'description' => $type->description,
					'slug'        => is_string( $type->has_archive ) ? $type->has_archive : $type->name,
					// The latest posts, or the chosen ones in the chosen order; never more than the theme allows.
					'posts'       => get_posts(
						empty( $row['pick'] )
							? array(
								'post_type'   => $type->name,
								'numberposts' => $limit,
								'orderby'     => 'date',
								'order'       => 'DESC',
							)
							: array(
								'post_type'   => $type->name,
								'numberposts' => $limit,
								'post__in'    => $row['posts'] ? $row['posts'] : array( 0 ),
								'orderby'     => 'post__in',
							)
					),
				),
			);
		}

		return $sections;
	}
}
