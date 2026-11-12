<?php
/**
 * The magazine's own settings: everything about how this theme shows the site, none of what the site holds.
 *
 * @package Magazine
 */

namespace Magazine\Settings;

use Magazine\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Two options, edited under Appearance → Magazine:
 * - `magazine_settings`: the issue (number, city, masthead), the homepage text, the cover and the filler
 *   illustrations;
 * - `magazine_sections`: for each section the content plugin provides (by its stable key), its bookmark,
 *   bookmark colour, index style, back-link label and opening page.
 *
 * Until first saved, both read the values the content plugin used to store, so nothing is re-entered.
 */
final class Settings implements Module {

	public const OPTION   = 'magazine_settings';
	public const SECTIONS = 'magazine_sections';
	public const GROUP    = 'magazine';

	/**
	 * The Post-it colours a bookmark can take (theme.json palette slugs). New sections take them in turn.
	 */
	public const COLORS = array( 'flag-yellow', 'flag-blue', 'flag-coral', 'flag-green' );

	/**
	 * The options are read on the front end and saved in the admin.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Registers the options on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_options' ) );
	}

	/**
	 * Registers both options and how they are cleaned.
	 */
	public function register_options(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_settings' ),
			)
		);
		register_setting(
			self::GROUP,
			self::SECTIONS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_sections' ),
			)
		);
	}

	/**
	 * The magazine settings' fields: label, type (`text`, `image` or `images`) and default.
	 *
	 * @return array<string, array{label: string, type: string, default: string|int}>
	 */
	public static function fields(): array {
		return array(
			'number'   => array(
				'label'   => __( 'Issue number', 'magazine' ),
				'type'    => 'text',
				'default' => '01',
			),
			'city'     => array(
				'label'   => __( 'City', 'magazine' ),
				'type'    => 'text',
				'default' => 'New Delhi',
			),
			'masthead' => array(
				'label'   => __( 'Masthead', 'magazine' ),
				'type'    => 'text',
				'default' => 'AKASH SHARMA',
			),
			'headline' => array(
				'label'   => __( 'Homepage headline', 'magazine' ),
				'type'    => 'text',
				'default' => 'Senior Web Engineer',
			),
			'deck'     => array(
				'label'   => __( 'Homepage introduction', 'magazine' ),
				'type'    => 'text',
				'default' => 'Building fast, well-crafted web experiences — from WordPress VIP at scale to the front end.',
			),
			'cover_id' => array(
				'label'   => __( 'Cover image (attachment ID)', 'magazine' ),
				'type'    => 'image',
				'default' => 0,
			),
			'fillers'  => array(
				'label'   => __( 'Filler illustrations (attachment IDs, comma-separated)', 'magazine' ),
				'type'    => 'images',
				'default' => '',
			),
		);
	}

	/**
	 * The magazine settings, with defaults for anything unset or left empty.
	 *
	 * @return array<string, mixed>
	 */
	public static function values(): array {
		$saved  = get_option( self::OPTION );
		$saved  = is_array( $saved ) ? $saved : (array) get_option( 'akashsrma_issue', array() );
		$values = array();
		foreach ( self::fields() as $key => $field ) {
			$value          = $saved[ $key ] ?? '';
			$values[ $key ] = '' === trim( (string) $value ) ? $field['default'] : $value;
		}
		return $values;
	}

	/**
	 * Cleans the magazine settings: known fields only, each by its type.
	 *
	 * @param mixed $input Submitted values.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings( mixed $input ): array {
		$input = wp_unslash( (array) $input );
		$clean = array();
		foreach ( self::fields() as $key => $field ) {
			$value         = $input[ $key ] ?? '';
			$clean[ $key ] = match ( $field['type'] ) {
				'image'  => absint( $value ),
				'images' => implode( ',', array_filter( wp_parse_id_list( (string) $value ) ) ),
				default  => sanitize_text_field( (string) $value ),
			};
		}
		return $clean;
	}

	/**
	 * A section's magazine options, with defaults: a bookmark in the next colour in turn; for a collection,
	 * a list index, a back link named after the collection, and as opening page the Page whose slug is the
	 * collection's archive slug (if there is one).
	 *
	 * @param array<string, mixed> $section A section from the content plugin.
	 * @param int                  $index   Its position, for the default colour.
	 * @return array{bookmark: bool, color: string, index: string, back: string, opener: int}
	 */
	public static function section( array $section, int $index ): array {
		$all   = self::saved_sections();
		$saved = $all[ $section['key'] ] ?? array();
		$name  = $section['collection']['name'] ?? '';
		$page  = isset( $section['collection'] ) ? get_page_by_path( $section['collection']['slug'] ) : null;

		return array(
			'bookmark' => (bool) ( $saved['bookmark'] ?? true ),
			'color'    => in_array( $saved['color'] ?? '', self::COLORS, true ) ? $saved['color'] : self::COLORS[ $index % count( self::COLORS ) ],
			'index'    => 'thumbs' === ( $saved['index'] ?? '' ) ? 'thumbs' : 'list',
			'back'     => '' !== (string) ( $saved['back'] ?? '' ) ? (string) $saved['back'] : $name,
			'opener'   => (int) ( $saved['opener'] ?? ( $page instanceof \WP_Post && 'publish' === $page->post_status ? $page->ID : 0 ) ),
		);
	}

	/**
	 * Cleans the section options: one entry per section key.
	 *
	 * @param mixed $input Submitted options, by section key.
	 * @return array<string, array{bookmark: bool, color: string, index: string, back: string, opener: int}>
	 */
	public static function sanitize_sections( mixed $input ): array {
		$clean = array();
		foreach ( wp_unslash( (array) $input ) as $key => $options ) {
			$options                       = (array) $options;
			$clean[ sanitize_key( $key ) ] = array(
				'bookmark' => ! empty( $options['bookmark'] ),
				'color'    => in_array( $options['color'] ?? '', self::COLORS, true ) ? $options['color'] : self::COLORS[0],
				'index'    => 'thumbs' === ( $options['index'] ?? '' ) ? 'thumbs' : 'list',
				'back'     => sanitize_text_field( (string) ( $options['back'] ?? '' ) ),
				'opener'   => absint( $options['opener'] ?? 0 ),
			);
		}
		return $clean;
	}

	/**
	 * The saved section options; until first saved, the design options the content plugin used to store
	 * alongside its sections, keyed the same way the plugin now keys sections.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function saved_sections(): array {
		$saved = get_option( self::SECTIONS );
		if ( is_array( $saved ) ) {
			return $saved;
		}
		$legacy = array();
		foreach ( (array) get_option( 'akashsrma_contents', array() ) as $row ) {
			$key = isset( $row['page'] ) ? 'page-' . (int) $row['page'] : (string) ( $row['post_type'] ?? '' );
			if ( '' !== $key ) {
				$legacy[ $key ] = $row;
			}
		}
		return $legacy;
	}
}
