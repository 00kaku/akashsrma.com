<?php
/**
 * Content → Sections: the site's sections, edited as an ordered list.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Admin;

use Akashsrma\Features\Content\Sections;

defined( 'ABSPATH' ) || exit;

/**
 * Opens the Content menu. Each row is a Page or a collection with its label; a collection also says which
 * posts it shows. Rows are reordered by dragging or with the arrows, and saving posts them in the order
 * shown (Sections cleans them). How a theme shows the sections is the theme's own business.
 */
final class SectionsPage extends AbstractPage {

	/**
	 * Sets up the screen.
	 *
	 * @param Sections $sections The site's sections.
	 * @param string   $url      URL of the plugin directory, for the screen's script and styles.
	 */
	public function __construct( private Sections $sections, private string $url ) {}

	/**
	 * Adds the Content menu, opening on Sections.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'Sections', 'akashsrma-features' ),
			__( 'Content', 'akashsrma-features' ),
			'edit_theme_options',
			Sections::GROUP,
			array( $this, 'render' ),
			'dashicons-index-card',
			58
		);
		add_submenu_page( Sections::GROUP, __( 'Sections', 'akashsrma-features' ), __( 'Sections', 'akashsrma-features' ), 'edit_theme_options', Sections::GROUP, array( $this, 'render' ) );
	}

	/**
	 * Loads the screen's script (reordering, adding and removing rows, searching posts) and styles, on this
	 * screen only.
	 *
	 * @param string $hook The admin screen being loaded.
	 */
	public function enqueue( string $hook ): void {
		if ( 'toplevel_page_' . Sections::GROUP !== $hook ) {
			return;
		}
		$version = (string) filemtime( dirname( __DIR__, 2 ) . '/assets/sections.js' );
		wp_enqueue_style( 'akashsrma-sections', $this->url . 'assets/sections.css', array(), $version );
		wp_enqueue_script( 'akashsrma-sections', $this->url . 'assets/sections.js', array( 'jquery-ui-sortable', 'jquery-ui-autocomplete', 'wp-api-fetch', 'wp-html-entities', 'wp-url' ), $version, true );
	}

	/**
	 * Draws the screen: the sections as an ordered list, plus templates for new rows.
	 */
	public function render(): void {
		$pages = get_pages( array( 'post_status' => 'publish' ) );
		$types = $this->sections->types();
		?>
		<div class="wrap akashsrma-sections">
			<h1><?php esc_html_e( 'Sections', 'akashsrma-features' ); ?></h1>
			<p class="description"><?php esc_html_e( 'The sections of the site, in order: a page, or a collection of posts. Drag a section by its handle (or use the arrows) to move it. The theme decides how sections look and are navigated.', 'akashsrma-features' ); ?></p>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( Sections::GROUP ); ?>
				<input type="hidden" name="<?php echo esc_attr( Sections::OPTION ); ?>" value="">
				<ol class="akashsrma-sections__list" data-routes="<?php echo esc_attr( (string) wp_json_encode( array_combine( $types, array_map( 'rest_get_route_for_post_type_items', $types ) ) ) ); ?>">
					<?php foreach ( $this->sections->rows() as $i => $row ) : ?>
						<?php $this->render_row( (string) $i, $row, $pages ); ?>
					<?php endforeach; ?>
				</ol>
				<p class="akashsrma-sections__add">
					<button type="button" class="button" data-add="page"><?php esc_html_e( '+ Add a page', 'akashsrma-features' ); ?></button>
					<button type="button" class="button" data-add="collection"><?php esc_html_e( '+ Add a collection', 'akashsrma-features' ); ?></button>
				</p>
				<?php submit_button( __( 'Save sections', 'akashsrma-features' ) ); ?>
			</form>
			<template data-row="page"><?php $this->render_row( '__i__', $this->sections->row( array( 'page' => 0 ) ), $pages ); ?></template>
			<template data-row="collection"><?php $this->render_row( '__i__', $this->sections->row( array( 'post_type' => $types[0] ?? '' ) ), $pages ); ?></template>
		</div>
		<?php
	}

	/**
	 * One section's row.
	 *
	 * @param string               $i     The row's key in the form.
	 * @param array<string, mixed> $row   Its values.
	 * @param \WP_Post[]           $pages Published Pages, for the picker.
	 */
	private function render_row( string $i, array $row, array $pages ): void {
		$name       = fn( string $key ): string => Sections::OPTION . "[$i][$key]";
		$collection = isset( $row['post_type'] );
		$post       = $collection ? null : get_post( (int) $row['page'] );
		?>
		<li class="akashsrma-sections__row">
			<span class="akashsrma-sections__handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to move', 'akashsrma-features' ); ?>"></span>
			<div class="akashsrma-sections__main">
				<p class="akashsrma-sections__head">
					<span class="akashsrma-sections__badge"><?php echo $collection ? esc_html__( 'Collection', 'akashsrma-features' ) : esc_html__( 'Page', 'akashsrma-features' ); ?></span>
					<?php if ( $collection ) : ?>
						<select name="<?php echo esc_attr( $name( 'post_type' ) ); ?>" aria-label="<?php esc_attr_e( 'Collection', 'akashsrma-features' ); ?>">
							<?php foreach ( $this->sections->types() as $type ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $row['post_type'], $type ); ?>><?php echo esc_html( get_post_type_object( $type )->labels->name ?? $type ); ?></option>
							<?php endforeach; ?>
						</select>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $row['post_type'] ) ); ?>"><?php esc_html_e( 'Show posts', 'akashsrma-features' ); ?></a>
					<?php else : ?>
						<select name="<?php echo esc_attr( $name( 'page' ) ); ?>" aria-label="<?php esc_attr_e( 'Page', 'akashsrma-features' ); ?>">
							<option value=""><?php esc_html_e( '— Choose a page —', 'akashsrma-features' ); ?></option>
							<?php foreach ( $pages as $page ) : ?>
								<option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( (int) $row['page'], $page->ID ); ?>><?php echo esc_html( get_the_title( $page ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php if ( $post ) : ?>
							<a href="<?php echo esc_url( (string) get_edit_post_link( $post ) ); ?>"><?php esc_html_e( 'Edit', 'akashsrma-features' ); ?></a>
							<a href="<?php echo esc_url( (string) get_permalink( $post ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'akashsrma-features' ); ?></a>
						<?php endif; ?>
					<?php endif; ?>
				</p>
				<p class="akashsrma-sections__label">
					<label><?php esc_html_e( 'Label', 'akashsrma-features' ); ?>
						<input type="text" class="regular-text" name="<?php echo esc_attr( $name( 'label' ) ); ?>" value="<?php echo esc_attr( (string) $row['label'] ); ?>" placeholder="<?php esc_attr_e( 'The page or collection name', 'akashsrma-features' ); ?>">
					</label>
				</p>
				<?php if ( $collection ) : ?>
					<?php $this->render_picker( $name, $row ); ?>
				<?php endif; ?>
			</div>
			<div class="akashsrma-sections__actions">
				<button type="button" class="button-link" data-move="-1" aria-label="<?php esc_attr_e( 'Move up', 'akashsrma-features' ); ?>"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
				<button type="button" class="button-link" data-move="1" aria-label="<?php esc_attr_e( 'Move down', 'akashsrma-features' ); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
				<button type="button" class="button-link akashsrma-sections__remove" data-remove><?php esc_html_e( 'Remove', 'akashsrma-features' ); ?></button>
			</div>
		</li>
		<?php
	}

	/**
	 * A collection's posts: the latest few, or up to that many chosen by searching, in the order dragged to.
	 * The theme sets how many (Sections::limit()).
	 *
	 * @param \Closure             $name Field name for a key of the row.
	 * @param array<string, mixed> $row  The collection's row.
	 */
	private function render_picker( \Closure $name, array $row ): void {
		$limit  = Sections::limit();
		$chosen = $row['posts'] ? get_posts(
			array(
				'post_type'   => $row['post_type'],
				'post__in'    => $row['posts'],
				'orderby'     => 'post__in',
				'numberposts' => $limit,
			)
		) : array();
		?>
		<fieldset class="akashsrma-sections__posts">
			<legend><?php esc_html_e( 'Posts', 'akashsrma-features' ); ?></legend>
			<label><input type="radio" name="<?php echo esc_attr( $name( 'pick' ) ); ?>" value="" <?php checked( ! $row['pick'] ); ?>> <?php /* translators: %d: how many posts */ printf( esc_html__( 'The latest %d (a new post replaces the oldest)', 'akashsrma-features' ), (int) $limit ); ?></label>
			<label><input type="radio" name="<?php echo esc_attr( $name( 'pick' ) ); ?>" value="1" <?php checked( $row['pick'] ); ?>> <?php esc_html_e( 'Choose posts', 'akashsrma-features' ); ?></label>
			<div class="akashsrma-sections__picker" data-max="<?php echo esc_attr( (string) $limit ); ?>" data-field="<?php echo esc_attr( $name( 'posts' ) ); ?>[]">
				<input type="search" class="regular-text akashsrma-sections__search" placeholder="<?php esc_attr_e( 'Search posts to add…', 'akashsrma-features' ); ?>" aria-label="<?php esc_attr_e( 'Search posts to add', 'akashsrma-features' ); ?>">
				<p class="description akashsrma-sections__count"></p>
				<ol class="akashsrma-sections__picks">
					<?php foreach ( $chosen as $post ) : ?>
						<li>
							<span class="akashsrma-sections__pick-handle dashicons dashicons-menu" aria-hidden="true"></span>
							<span class="akashsrma-sections__pick-title"><?php echo esc_html( get_the_title( $post ) ); ?></span>
							<input type="hidden" name="<?php echo esc_attr( $name( 'posts' ) ); ?>[]" value="<?php echo esc_attr( (string) $post->ID ); ?>">
							<button type="button" class="button-link akashsrma-sections__unpick" aria-label="<?php esc_attr_e( 'Remove', 'akashsrma-features' ); ?>"><span class="dashicons dashicons-no-alt"></span></button>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</fieldset>
		<?php
	}
}
