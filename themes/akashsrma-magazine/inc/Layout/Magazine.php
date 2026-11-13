<?php
/**
 * Lays the magazine out: sections become pages, pages pair into spreads, every spread gets its URL,
 * folios, running heads and page links.
 *
 * @package Magazine
 */

namespace Magazine\Layout;

use Magazine\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the magazine the Magazine block prints. Pure layout: the sections come from Sections (the content
 * plugin's sections, with the magazine's options), and no hooks are added here.
 */
final class Magazine {

	/**
	 * Splits post content into magazine pages at each Page Break block.
	 */
	private const PAGE_BREAK = '/<!-- wp:nextpage -->.*?<!-- \/wp:nextpage -->|<!--nextpage-->/s';

	/**
	 * A page that opens with a picture bleeding off its top edge has no running head.
	 */
	private const BLEEDS = '/^\s*<figure[^>]*\bis-style-bleed\b/';

	/**
	 * Lays out the whole magazine.
	 *
	 * @return array{spreads: list<array{url: string, title: string, section: string, unit: int, part: int, pages: array{0: array<string, mixed>, 1: array<string, mixed>}}>, tabs: list<array{key: string, label: string, color: string, url: string}>, current: int, home_title: string}
	 */
	public static function layout(): array {
		$sections = Sections::all();
		$fillers  = self::fillers();

		// 1. Every section becomes units — a Page; or an archive opener followed by its posts.
		$units = array();
		foreach ( $sections as $s => $section ) {
			if ( isset( $section['post'] ) ) {
				$units[] = self::post_unit( $section['post'], $s );
				continue;
			}
			$units[] = self::archive_unit( $section, $s );
			foreach ( $section['archive']['posts'] as $post ) {
				$units[] = self::post_unit( $post, $s ) + array( 'back' => $section['archive']['back'] );
			}
		}

		// 2. Units pair their pages into spreads; every unit opens on a fresh spread, a filler page closing an odd one.
		$spreads = array();
		$owners  = array(); // Spread index of each post / archive, to find the current spread.
		$filled  = 0; // Filler pages so far.
		foreach ( $units as $u => &$unit ) {
			$owners[ $unit['owner'] ] = count( $spreads );
			$unit['first']            = count( $spreads );
			if ( count( $unit['pages'] ) % 2 ) {
				// Fillers take the illustrations in turn, so neighbouring fillers differ.
				$unit['pages'][] = self::page( '', 'filler', $fillers ? $fillers[ $filled % count( $fillers ) ] : 0 );
				++$filled;
			}
			foreach ( array_chunk( $unit['pages'], 2 ) as $k => $pair ) {
				$spreads[] = array(
					// Continuation spreads use WordPress's own paged URLs: /about/2/.
					'url'     => $k ? trailingslashit( $unit['url'] ) . user_trailingslashit( (string) ( $k + 1 ) ) : $unit['url'],
					'title'   => $unit['title'],
					'section' => $sections[ $unit['section'] ]['key'],
					'unit'    => $u,
					'part'    => $k,
					'pages'   => $pair,
				);
			}
			$unit['count'] = count( $spreads ) - $unit['first'];
		}
		unset( $unit );

		// 3. Folios count from the cover (the first spread is pages 2 and 3); then the furniture that needs them.
		foreach ( $spreads as $i => &$spread ) {
			foreach ( $spread['pages'] as $side => &$page ) {
				$page['folio']   = 2 + 2 * $i + $side;
				$page['runhead'] = 'content' === $page['kind'] && ! preg_match( self::BLEEDS, $page['html'] );
			}
			unset( $page );

			$unit = $units[ $spread['unit'] ];
			if ( $spread['part'] ) {
				$spread['pages'][0]['html'] = self::continued( $spread['pages'][0]['folio'] - 1 ) . $spread['pages'][0]['html'];
			}
			// Posts always get page links; a Page only when it runs over more than one spread.
			if ( isset( $unit['back'] ) || $unit['count'] > 1 ) {
				// The page links close the last page that has content: the right page, or the left when the right is a filler.
				$side                              = 'filler' === $spread['pages'][1]['kind'] ? 0 : 1;
				$spread['pages'][ $side ]['html'] .= self::page_links( $spread, $i, $units, $sections );
			}
		}
		unset( $spread );

		// Only sections with a bookmark get a tab (Appearance → Magazine).
		$tabs = array_map(
			fn( $section ) => array_intersect_key( $section, array_flip( array( 'key', 'label', 'color', 'url' ) ) ),
			array_filter( $sections, fn( $section ) => $section['bookmark'] )
		);

		return array(
			'spreads'    => $spreads,
			'tabs'       => array_values( $tabs ),
			'current'    => self::current( $owners, count( $spreads ) ),
			'home_title' => self::document_title( array( 'id' => 0 ) ),
		);
	}

	/**
	 * The browser title for a URL the magazine shows, so the tab can follow the page as it turns. With The SEO
	 * Framework active it is that plugin's title; otherwise WordPress's own pattern ("Title - Site").
	 *
	 * @param array{id?: int, pta?: string} $args A post ID (0 for the home page), or a post type for its archive.
	 */
	public static function document_title( array $args ): string {
		$seo = function_exists( 'tsf' ) ? tsf() : null; // The SEO Framework, when that plugin is active.
		if ( null !== $seo && method_exists( $seo, 'get_title' ) ) {
			$title = (string) $seo->get_title( $args );
		} else {
			$site = get_bloginfo( 'name', 'display' );
			$sep  = (string) apply_filters( 'document_title_separator', '-' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress's own filter.
			if ( ! empty( $args['pta'] ) ) {
				$type  = get_post_type_object( $args['pta'] );
				$title = $type ? $type->labels->name . " $sep $site" : $site;
			} elseif ( ! empty( $args['id'] ) ) {
				$title = get_the_title( $args['id'] ) . " $sep $site";
			} else {
				$tagline = get_bloginfo( 'description', 'display' );
				$title   = $tagline ? "$site $sep $tagline" : $site;
			}
		}
		return html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * How many spreads a post fills: its Page Break pages, two to a spread.
	 *
	 * @param \WP_Post $post The post.
	 */
	public static function spread_count( \WP_Post $post ): int {
		return (int) ceil( count( (array) preg_split( self::PAGE_BREAK, $post->post_content ) ) / 2 );
	}

	/**
	 * The filler illustrations (Appearance → Magazine), in a fixed shuffle: it looks random, but every filler
	 * page shows the same picture on every visit, which page caching would force anyway.
	 *
	 * @return list<int> Attachment IDs.
	 */
	private static function fillers(): array {
		$ids = wp_parse_id_list( (string) Settings::values()['fillers'] );
		usort( $ids, fn( $a, $b ) => crc32( "filler-$a" ) <=> crc32( "filler-$b" ) );
		return $ids;
	}

	/**
	 * A post's pages: its content split at Page Break blocks.
	 *
	 * @param \WP_Post $post    The post.
	 * @param int      $section Index of its section.
	 * @return array{owner: string, url: string, title: string, section: int, short: string, pages: list<array<string, mixed>>}
	 */
	private static function post_unit( \WP_Post $post, int $section ): array {
		return array(
			'owner'   => 'post:' . $post->ID,
			'url'     => (string) get_permalink( $post ),
			'title'   => self::document_title( array( 'id' => $post->ID ) ),
			'section' => $section,
			'short'   => (string) ( get_post_meta( $post->ID, 'short_title', true ) ? get_post_meta( $post->ID, 'short_title', true ) : self::title( $post ) ),
			'pages'   => self::render_pages( $post ),
		);
	}

	/**
	 * An archive's opening spread: its opener Page's content, with the index of its posts at the end.
	 *
	 * @param array{key: string, label: string, url: string, archive: array{title: string, description: string, opener: \WP_Post|null, index: string, back: string, posts: list<\WP_Post>}} $section The section.
	 * @param int                                                                                                                                                                           $index Index of the section.
	 * @return array{owner: string, url: string, title: string, section: int, short: string, pages: list<array<string, mixed>>}
	 */
	private static function archive_unit( array $section, int $index ): array {
		$archive = $section['archive'];
		$pages   = $archive['opener']
			? self::render_pages( $archive['opener'] )
			: array(
				self::page(
					sprintf(
						'<p class="magazine-kicker magazine-push">%s</p><h2 class="magazine-title is-size-l">%s</h2><p class="magazine-deck">%s</p>',
						esc_html( $archive['title'] ),
						esc_html( $archive['title'] ),
						esc_html( $archive['description'] )
					)
				),
			);
		if ( count( $pages ) < 2 ) {
			$pages[] = self::page( '' );
		}
		$pages[1]['html'] .= self::index( $archive['posts'], $archive['index'] );

		return array(
			'owner'   => 'archive:' . $section['key'],
			'url'     => $section['url'],
			'title'   => self::document_title( array( 'pta' => $section['key'] ) ),
			'section' => $index,
			'short'   => $section['label'],
			'pages'   => $pages,
		);
	}

	/**
	 * Renders a post's content, one magazine page per Page Break.
	 *
	 * @param \WP_Post $post The post.
	 * @return list<array<string, mixed>>
	 */
	private static function render_pages( \WP_Post $post ): array {
		$parts = preg_split( self::PAGE_BREAK, $post->post_content );

		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- blocks read the post being rendered.
		setup_postdata( $post );
		$pages = array_map( fn( $part ) => self::page( trim( (string) apply_filters( 'the_content', $part ) ) ), $parts ? $parts : array( '' ) );
		wp_reset_postdata();

		return $pages;
	}

	/**
	 * The list of an archive's posts; every row links to (and turns the magazine to) its post.
	 *
	 * @param \WP_Post[] $posts The posts.
	 * @param string     $style `thumbs` (with featured images) or `list`.
	 */
	private static function index( array $posts, string $style ): string {
		if ( ! $posts ) {
			return '<p class="magazine-empty">' . esc_html__( 'Nothing here yet.', 'magazine' ) . '</p>';
		}
		$rows = '';
		foreach ( $posts as $i => $post ) {
			$thumb = '';
			if ( 'thumbs' === $style ) {
				// No featured image: a tile with the title's first letter keeps the row's shape.
				$thumb = get_the_post_thumbnail( $post, 'thumbnail', array( 'class' => 'magazine-index__thumb' ) );
				$thumb = $thumb ? $thumb : sprintf( '<span class="magazine-index__thumb magazine-index__thumb--empty" aria-hidden="true">%s</span>', esc_html( mb_substr( self::title( $post ), 0, 1 ) ) );
			}
			$rows .= sprintf(
				'<a class="magazine-index__item" href="%s">%s<span class="magazine-index__num">%02d</span><span class="magazine-index__body"><span class="magazine-index__head">%s</span><span class="magazine-index__text">%s</span></span><span class="magazine-index__arrow" aria-hidden="true">→</span></a>',
				esc_url( (string) get_permalink( $post ) ),
				$thumb, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup, or escaped above.
				$i + 1,
				esc_html( self::title( $post ) ),
				esc_html( get_the_excerpt( $post ) )
			);
		}
		return sprintf( '<nav class="magazine-index magazine-index--%s">%s</nav>', esc_attr( $style ), $rows );
	}

	/**
	 * "← All work · Next: … →" at the foot of a spread; continued spreads link back one spread and on to the
	 * next. Book only: the scrolling edition swipes through a story's pages instead.
	 *
	 * @param array<string, mixed>       $spread   The spread.
	 * @param int                        $i        Its index.
	 * @param list<array<string, mixed>> $units    All units.
	 * @param list<array<string, mixed>> $sections All sections.
	 */
	private static function page_links( array $spread, int $i, array $units, array $sections ): string {
		$unit = $units[ $spread['unit'] ];

		$back = null;
		if ( $spread['part'] ) {
			// One spread back: the unit's first URL, or its previous paged URL (/about/2/).
			$prev = $spread['part'] - 1;
			/* translators: %d: page number */
			$back = array( sprintf( __( '← Page %d', 'magazine' ), 2 + 2 * ( $i - 1 ) ), $prev ? trailingslashit( $unit['url'] ) . user_trailingslashit( (string) ( $prev + 1 ) ) : $unit['url'] );
		} elseif ( isset( $unit['back'] ) ) {
			$back = array( '← ' . $unit['back'], $sections[ $unit['section'] ]['url'] );
		}

		if ( $spread['part'] < $unit['count'] - 1 ) {
			/* translators: %d: page number */
			$next = array( sprintf( __( 'Continued on page %d →', 'magazine' ), 2 + 2 * ( $i + 1 ) ), trailingslashit( $unit['url'] ) . user_trailingslashit( (string) ( $spread['part'] + 2 ) ) );
		} else {
			$after = $units[ $spread['unit'] + 1 ] ?? null;
			$next  = null;
			if ( $after && $after['section'] === $unit['section'] ) {
				/* translators: %s: short title of the next post */
				$next = array( sprintf( __( 'Next: %s →', 'magazine' ), $after['short'] ), $after['url'] );
			} elseif ( $after ) {
				$next = array( $sections[ $after['section'] ]['label'] . ' →', $sections[ $after['section'] ]['url'] );
			}
		}

		$link = fn( $l ) => $l ? sprintf( '<a class="magazine-pnav__a" href="%s">%s</a>', esc_url( $l[1] ), esc_html( $l[0] ) ) : '<span></span>';
		return '<nav class="magazine-pnav">' . $link( $back ) . $link( $next ) . '</nav>';
	}

	/**
	 * A post's title, or "Untitled" when it has none.
	 *
	 * @param \WP_Post $post The post.
	 */
	private static function title( \WP_Post $post ): string {
		$title = get_the_title( $post );
		return '' !== trim( $title ) ? $title : __( 'Untitled', 'magazine' );
	}

	/**
	 * The "Continued from page N" label at the top of a continued spread.
	 *
	 * @param int $folio The page it continues from.
	 */
	private static function continued( int $folio ): string {
		return sprintf(
			'<p class="magazine-kicker magazine-cont">%s</p>',
			/* translators: %d: page number */
			esc_html( sprintf( __( 'Continued from page %d', 'magazine' ), $folio ) )
		);
	}

	/**
	 * One magazine page.
	 *
	 * @param string $html  Page content (already escaped / rendered).
	 * @param string $kind  `content` or `filler`.
	 * @param int    $image Attachment ID of a filler illustration, if any.
	 * @return array{html: string, kind: string, image: int, folio: int, runhead: bool}
	 */
	private static function page( string $html, string $kind = 'content', int $image = 0 ): array {
		return array(
			'html'    => $html,
			'kind'    => $kind,
			'image'   => $image,
			'folio'   => 0,
			'runhead' => false,
		);
	}

	/**
	 * The spread the requested URL opens at; -1 keeps the magazine closed (front page, 404, anything else).
	 *
	 * @param array<string, int> $owners Spread index of each post and archive.
	 * @param int                $count  Number of spreads.
	 */
	private static function current( array $owners, int $count ): int {
		if ( is_singular() ) {
			$start = $owners[ 'post:' . get_queried_object_id() ] ?? null;
			$page  = max( 1, (int) get_query_var( 'page' ) );
			return null === $start ? -1 : min( $count - 1, $start + $page - 1 );
		}
		if ( is_post_type_archive() ) {
			$type = get_query_var( 'post_type' );
			return $owners[ 'archive:' . ( is_array( $type ) ? reset( $type ) : $type ) ] ?? -1;
		}
		return -1;
	}
}
