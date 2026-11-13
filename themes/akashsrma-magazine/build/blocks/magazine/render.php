<?php
/**
 * Front end of the Magazine block. Every URL renders the whole magazine — the stage (header, intro, footer),
 * the cover, the bookmarks and every spread — opened at the spread the URL points to (closed on the front
 * page). Without JavaScript, or on narrow screens, it reads as one scrolling edition; view.js upgrades it to
 * a magazine with turning pages.
 *
 * @package Magazine
 */

use Magazine\Layout\Magazine;
use Magazine\Settings\Settings;

defined( 'ABSPATH' ) || exit;

$magazine_issue  = Settings::values();
$magazine_layout = Magazine::layout();
$magazine_name   = get_bloginfo( 'name' );
// A magazine setting left empty falls back, like one never set.
$magazine_get    = static fn( string $key, string $fallback = '' ): string => trim( (string) ( $magazine_issue[ $key ] ?? '' ) ) !== '' ? (string) $magazine_issue[ $key ] : $fallback;
$magazine_number = $magazine_get( 'number', '01' );
$magazine_label  = sprintf( /* translators: %s: issue number */ __( 'Issue %s', 'magazine' ), $magazine_number );
$magazine_open   = $magazine_layout['current'] >= 0;

// Bookmark colours come from the theme palette; each flag is a translucent film with a solid tip, stuck on at a small angle.
$magazine_palette = wp_list_pluck( wp_get_global_settings( array( 'color', 'palette', 'theme' ) ), 'color', 'slug' );
$magazine_tilts   = array( -1.4, 0.9, -0.6, 1.3 );

wp_interactivity_state(
	'magazine/magazine',
	array(
		'currentSection' => $magazine_open ? $magazine_layout['spreads'][ $magazine_layout['current'] ]['section'] : '',
		'isOpen'         => $magazine_open,
		'isDark'         => false,
	)
);
?>
<div
	<?php echo get_block_wrapper_attributes( array( 'class' => 'magazine' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>
	data-wp-interactive="magazine/magazine"
	data-wp-init="callbacks.init"
	data-wp-watch="callbacks.syncTheme"
	data-home="<?php echo esc_url( home_url( '/' ) ); ?>"
	aria-label="<?php echo esc_attr( $magazine_name . ' — ' . $magazine_label . '. ' . __( 'Use the left and right arrow keys to turn pages.', 'magazine' ) ); ?>"
>
	<header class="magazine__head">
		<button class="magazine__wordmark" type="button" data-wp-on--click="actions.close" aria-label="<?php echo esc_attr( $magazine_name . ' — ' . __( 'back to the cover', 'magazine' ) ); ?>"><?php echo esc_html( $magazine_name ); ?></button>
		<div class="magazine__actions">
			<button class="magazine__close" type="button" data-wp-on--click="actions.close" data-wp-class--is-visible="state.isOpen" data-wp-bind--aria-hidden="!state.isOpen" data-wp-bind--tabindex="state.closeTabindex">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>
				<?php esc_html_e( 'Close magazine', 'magazine' ); ?>
			</button>
			<button class="magazine__theme" type="button" data-wp-on--click="actions.toggleTheme" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'magazine' ); ?>">
				<svg class="magazine__icon-sun" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4.5"></circle><path d="M12 2.5v2.5M12 19v2.5M4.5 12H2M22 12h-2.5M5.6 5.6l1.8 1.8M16.6 16.6l1.8 1.8M18.4 5.6l-1.8 1.8M7.4 16.6l-1.8 1.8"></path></svg>
				<svg class="magazine__icon-moon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5z"></path></svg>
			</button>
		</div>
	</header>

	<div class="magazine__intro">
		<p class="magazine__intro-kicker"><?php echo esc_html( $magazine_label ); ?></p>
		<h1 class="magazine__intro-head"><?php echo esc_html( $magazine_get( 'headline', get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : $magazine_name ) ); ?></h1>
		<p class="magazine__intro-deck"><?php echo esc_html( $magazine_get( 'deck' ) ); ?></p>
		<?php if ( $magazine_layout['spreads'] ) : // Nothing to open until there are sections. ?>
			<p class="magazine__intro-hint"><?php esc_html_e( 'Pull a bookmark on the right, or', 'magazine' ); ?> <button class="magazine__intro-link" type="button" data-wp-on--click="actions.openMagazine"><?php esc_html_e( 'open the magazine →', 'magazine' ); ?></button></p>
		<?php endif; ?>
	</div>

	<footer class="magazine__foot">
		<span><?php echo esc_html( '© ' . gmdate( 'Y' ) . ' ' . $magazine_name . ( $magazine_get( 'city' ) ? ' · ' . $magazine_get( 'city' ) : '' ) ); ?></span>
		<?php if ( $magazine_layout['spreads'] ) : ?>
			<span><?php esc_html_e( 'Pull a bookmark, or use ← → to turn pages', 'magazine' ); ?></span>
		<?php endif; ?>
	</footer>

	<div class="magazine__cover" role="img" aria-label="<?php esc_attr_e( 'Magazine cover', 'magazine' ); ?>">
		<?php if ( ! empty( $magazine_issue['cover_id'] ) ) : ?>
			<?php
			echo wp_get_attachment_image(
				(int) $magazine_issue['cover_id'],
				'full',
				false,
				array(
					'class' => 'magazine__cover-photo',
				)
			);
			?>
		<?php endif; ?>
		<div class="magazine__cover-top">
			<div class="magazine__cover-date"><span><?php echo esc_html( $magazine_label ); ?></span><span><?php echo esc_html( $magazine_get( 'city' ) ); ?></span></div>
			<p class="magazine__masthead"><?php echo esc_html( $magazine_get( 'masthead', $magazine_name ) ); ?></p>
		</div>
		<div class="magazine__cover-issue"><span class="magazine__cover-issue-tag"><?php esc_html_e( 'Issue', 'magazine' ); ?></span><span class="magazine__cover-issue-no">N°<?php echo esc_html( $magazine_number ); ?></span></div>
		<div class="magazine__barcode"><svg viewBox="0 0 93 40" preserveAspectRatio="none" aria-hidden="true" fill="#111"><rect x="0" y="0" width="1" height="40"></rect><rect x="2" y="0" width="2" height="40"></rect><rect x="5" y="0" width="1" height="36"></rect><rect x="9" y="0" width="1" height="36"></rect><rect x="12" y="0" width="2" height="36"></rect><rect x="15" y="0" width="2" height="36"></rect><rect x="18" y="0" width="1" height="36"></rect><rect x="20" y="0" width="2" height="36"></rect><rect x="24" y="0" width="1" height="36"></rect><rect x="26" y="0" width="1" height="36"></rect><rect x="30" y="0" width="2" height="36"></rect><rect x="33" y="0" width="2" height="36"></rect><rect x="36" y="0" width="1" height="36"></rect><rect x="40" y="0" width="1" height="36"></rect><rect x="44" y="0" width="2" height="36"></rect><rect x="48" y="0" width="1" height="36"></rect><rect x="50" y="0" width="1" height="36"></rect><rect x="54" y="0" width="1" height="36"></rect><rect x="57" y="0" width="2" height="36"></rect><rect x="60" y="0" width="2" height="36"></rect><rect x="63" y="0" width="2" height="36"></rect><rect x="67" y="0" width="2" height="36"></rect><rect x="70" y="0" width="1" height="36"></rect><rect x="74" y="0" width="2" height="36"></rect><rect x="77" y="0" width="1" height="36"></rect><rect x="79" y="0" width="2" height="36"></rect><rect x="82" y="0" width="2" height="36"></rect><rect x="85" y="0" width="2" height="36"></rect><rect x="88" y="0" width="2" height="36"></rect></svg><span><?php echo esc_html( strtoupper( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ); ?></span></div>
		<div class="magazine__cover-fold"></div>
		<div class="magazine__cover-gloss"></div>
	</div>

	<nav class="magazine__tabs" aria-label="<?php esc_attr_e( 'Sections', 'magazine' ); ?>">
		<?php foreach ( $magazine_layout['tabs'] as $magazine_i => $magazine_tab ) : ?>
			<?php
			$magazine_hex = ltrim( (string) ( $magazine_palette[ $magazine_tab['color'] ] ?? '#F3D766' ), '#' );
			$magazine_rgb = implode( ',', array_map( 'hexdec', str_split( $magazine_hex, 2 ) ) );
			$magazine_bg  = "linear-gradient(to right, rgba({$magazine_rgb},0.42) 0, rgba({$magazine_rgb},0.42) 34px, rgba({$magazine_rgb},0.96) 34px), linear-gradient(to bottom, rgba(255,255,255,0.35), rgba(255,255,255,0) 45%)";
			?>
			<a
				class="magazine__tab"
				href="<?php echo esc_url( $magazine_tab['url'] ); ?>"
				style="<?php echo esc_attr( sprintf( 'background-image:%s;--magazine-tilt:%sdeg;--magazine-flag:#%s', $magazine_bg, $magazine_tilts[ $magazine_i % 4 ], $magazine_hex ) ); ?>"
				<?php echo wp_interactivity_data_wp_context( array( 'section' => $magazine_tab['key'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>
				data-wp-class--is-active="state.isActiveTab"
			><?php echo esc_html( $magazine_tab['label'] ); ?></a>
		<?php endforeach; ?>
	</nav>

	<div class="magazine__spreads">
		<div class="magazine__under magazine__under--left"></div>
		<div class="magazine__under magazine__under--right"></div>
		<div class="magazine__spine"></div>
		<?php
		// Each story's spreads share a row: in the scrolling edition its pages swipe sideways, with page dots
		// (the book ignores the wrappers).
		$magazine_stories = array();
		foreach ( $magazine_layout['spreads'] as $magazine_i => $magazine_spread ) {
			$magazine_stories[ $magazine_spread['unit'] ][ $magazine_i ] = $magazine_spread;
		}
		?>
		<?php foreach ( $magazine_stories as $magazine_story ) : ?>
			<?php $magazine_count = 2 * count( $magazine_story ); // Every spread holds two pages (a filler closes an odd story). ?>
			<div class="magazine__story">
				<div class="magazine__row">
					<?php foreach ( $magazine_story as $magazine_i => $magazine_spread ) : ?>
						<section class="magazine__spread" data-url="<?php echo esc_url( $magazine_spread['url'] ); ?>" data-section="<?php echo esc_attr( $magazine_spread['section'] ); ?>"<?php echo $magazine_i === $magazine_layout['current'] ? ' data-current' : ''; ?>>
							<?php foreach ( $magazine_spread['pages'] as $magazine_side => $magazine_page ) : ?>
								<div class="magazine__page magazine__page--<?php echo $magazine_side ? 'right' : 'left'; ?> magazine__page--<?php echo esc_attr( $magazine_page['kind'] ); ?>">
									<?php if ( $magazine_page['image'] ) : ?>
										<?php
										echo wp_get_attachment_image(
											$magazine_page['image'],
											'large',
											false,
											array(
												'class' => 'magazine__filler-image',
												'alt'   => '',
											)
										);
										?>
									<?php endif; ?>
									<?php if ( $magazine_page['runhead'] ) : ?>
										<p class="magazine__runhead"><?php echo esc_html( $magazine_side ? $magazine_label : $magazine_name ); ?></p>
									<?php endif; ?>
									<div class="magazine__page-body"><?php echo $magazine_page['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered post content / escaped markup from Magazine. ?></div>
									<p class="magazine__folio"><?php echo esc_html( $magazine_side ? $magazine_label . ' — ' . $magazine_page['folio'] : $magazine_page['folio'] . ' — ' . $magazine_name . ' · ' . $magazine_label ); ?></p>
									<div class="magazine__gloss"></div>
									<div class="magazine__gutter"></div>
								</div>
							<?php endforeach; ?>
						</section>
					<?php endforeach; ?>
				</div>
				<div class="magazine__dots" role="group" aria-label="<?php esc_attr_e( 'Pages', 'magazine' ); ?>">
					<?php for ( $magazine_n = 1; $magazine_n <= $magazine_count; $magazine_n++ ) : ?>
						<?php /* translators: 1: page number in the story, 2: pages in the story */ ?>
						<button class="magazine__dot" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Page %1$d of %2$d', 'magazine' ), $magazine_n, $magazine_count ) ); ?>"<?php echo 1 === $magazine_n ? ' aria-current="true"' : ''; ?>></button>
					<?php endfor; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
