<?php
/**
 * The site's sections: which Pages and collections it holds, in what order, under what labels.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Content;

use Akashsrma\Features\Core\Config;
use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Stores the sections in one option and cleans what the Sections screen sends. Nothing here is about how a
 * theme shows them: a theme reads them through SectionData and keeps its own design options.
 *
 * A row is a Page (`page`: its ID) or a collection (`post_type`: a section post type). Every row has a
 * `label`; a collection shows its latest posts, or (`pick`) only `posts`, in that order, never more than
 * the active theme allows (limit()).
 */
final class Sections implements Module {

	public const OPTION = 'akashsrma_sections';

	/**
	 * The settings group the Sections screen saves through.
	 */
	public const GROUP = 'akashsrma-sections';

	/**
	 * Posts a collection shows when the theme doesn't say.
	 */
	public const DEFAULT_LIMIT = 5;

	/**
	 * Before this plugin version, sections lived here, mixed with the magazine's design options.
	 */
	private const LEGACY_OPTION = 'akashsrma_contents';

	/**
	 * Sets up the sections.
	 *
	 * @param Config $config The plugin's configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * The option is needed everywhere: themes read it, the admin saves it.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Hooks the option's registration to `init`.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_option' ) );
	}

	/**
	 * Registers the option and how it is cleaned.
	 */
	public function register_option(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	/**
	 * The most posts a collection shows: what the active theme declares with
	 * add_theme_support( 'akashsrma-features', array( 'max_posts' => N ) ), or DEFAULT_LIMIT.
	 */
	public static function limit(): int {
		$support = get_theme_support( 'akashsrma-features' );
		$limit   = is_array( $support ) ? (int) ( $support[0]['max_posts'] ?? 0 ) : 0;
		return $limit > 0 ? $limit : self::DEFAULT_LIMIT;
	}

	/**
	 * The section post types a collection row can hold.
	 *
	 * @return list<string>
	 */
	public function types(): array {
		return array_keys( $this->config->get( 'post-types' ) );
	}

	/**
	 * The sections, in order: the saved rows; else the old contents, carried over; else config/sections.php.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function rows(): array {
		$saved = get_option( self::OPTION );
		if ( is_array( $saved ) ) {
			return $saved;
		}
		$legacy = get_option( self::LEGACY_OPTION );
		if ( is_array( $legacy ) ) {
			return $this->sanitize( $legacy );
		}
		$rows = array();
		foreach ( $this->config->get( 'sections' ) as $section ) {
			if ( isset( $section['page'] ) ) {
				$page   = get_page_by_path( $section['page'] );
				$rows[] = $this->row(
					array(
						'page'  => $page instanceof \WP_Post ? $page->ID : 0,
						'label' => $section['label'],
					)
				);
				continue;
			}
			$rows[] = $this->row( $section );
		}
		return $rows;
	}

	/**
	 * Cleans submitted rows: a Page that exists or a section post type, in the order sent; other keys dropped.
	 *
	 * @param mixed $input Submitted rows.
	 * @return list<array<string, mixed>>
	 */
	public function sanitize( mixed $input ): array {
		$rows = array();
		foreach ( (array) $input as $row ) {
			$row = wp_unslash( (array) $row );
			if ( isset( $row['post_type'] ) && in_array( $row['post_type'], $this->types(), true ) ) {
				$rows[] = $this->row(
					array(
						'post_type' => (string) $row['post_type'],
						'label'     => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
						'pick'      => ! empty( $row['pick'] ),
						'posts'     => array_slice(
							array_values(
								array_filter(
									wp_parse_id_list( (array) ( $row['posts'] ?? array() ) ),
									fn( $id ) => get_post_type( $id ) === $row['post_type']
								)
							),
							0,
							self::limit()
						),
					)
				);
			} elseif ( ! empty( $row['page'] ) && get_post( absint( $row['page'] ) ) ) {
				$rows[] = $this->row(
					array(
						'page'  => absint( $row['page'] ),
						'label' => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
					)
				);
			}
		}
		return $rows;
	}

	/**
	 * A row with every key it should have (also used for the screen's new rows).
	 *
	 * @param array<string, mixed> $row Values.
	 * @return array<string, mixed>
	 */
	public function row( array $row ): array {
		$row['label'] = (string) ( $row['label'] ?? '' );
		if ( isset( $row['post_type'] ) ) {
			$row['pick']  = (bool) ( $row['pick'] ?? false );
			$row['posts'] = $row['posts'] ?? array();
			return array_intersect_key( $row, array_flip( array( 'post_type', 'label', 'pick', 'posts' ) ) );
		}
		return array_intersect_key( $row, array_flip( array( 'page', 'label' ) ) );
	}
}
