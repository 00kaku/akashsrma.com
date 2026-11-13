<?php
/**
 * Appearance → Magazine: the theme's own settings.
 *
 * @package Magazine
 */

namespace Magazine\Admin;

use Magazine\Core\Module;
use Magazine\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * One form, two parts: the magazine settings (issue, homepage text, cover, fillers), and how each section
 * from the content plugin appears in the magazine (bookmark, colour; for a collection its opening page,
 * index and back link). The sections themselves, their order and posts are edited in the plugin.
 */
final class MagazinePage implements Module {

	private const SLUG = 'magazine';

	/**
	 * Admin only.
	 */
	public function can_register(): bool {
		return is_admin();
	}

	/**
	 * Adds the menu entry and the screen's styles.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds Appearance → Magazine.
	 */
	public function add_menu(): void {
		add_theme_page( __( 'Magazine', 'magazine' ), __( 'Magazine', 'magazine' ), 'edit_theme_options', self::SLUG, array( $this, 'render' ) );
	}

	/**
	 * Loads the screen's styles, on this screen only.
	 *
	 * @param string $hook The admin screen being loaded.
	 */
	public function enqueue( string $hook ): void {
		if ( 'appearance_page_' . self::SLUG === $hook ) {
			wp_enqueue_style( 'magazine-admin', get_theme_file_uri( 'assets/admin/magazine.css' ), array(), (string) filemtime( get_theme_file_path( 'assets/admin/magazine.css' ) ) );
		}
	}

	/**
	 * Draws the screen.
	 */
	public function render(): void {
		$values   = Settings::values();
		$sections = array_values( (array) apply_filters( 'akashsrma_sections', array() ) );
		$pages    = get_pages( array( 'post_status' => 'publish' ) );
		?>
		<div class="wrap magazine-admin">
			<h1><?php esc_html_e( 'Magazine', 'magazine' ); ?></h1>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( Settings::GROUP ); ?>

				<h2><?php esc_html_e( 'Issue and homepage', 'magazine' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( Settings::fields() as $key => $field ) : ?>
						<?php $id = 'magazine-' . $key; ?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
							<td>
								<input class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( Settings::OPTION . '[' . $key . ']' ); ?>" type="<?php echo 'image' === $field['type'] ? 'number' : 'text'; ?>" value="<?php echo esc_attr( (string) $values[ $key ] ); ?>">
								<?php if ( 'text' !== $field['type'] && $values[ $key ] ) : ?>
									<p>
										<?php foreach ( wp_parse_id_list( (string) $values[ $key ] ) as $image ) : ?>
											<?php echo wp_get_attachment_image( $image, 'thumbnail' ); ?>
										<?php endforeach; ?>
									</p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2><?php esc_html_e( 'Sections in the magazine', 'magazine' ); ?></h2>
				<?php if ( ! has_filter( 'akashsrma_sections' ) ) : ?>
					<p><?php esc_html_e( 'The sections come from the Akashsrma Features plugin, which is not active.', 'magazine' ); ?></p>
				<?php elseif ( ! $sections ) : ?>
					<p><?php esc_html_e( 'No sections yet: add them under Content → Sections.', 'magazine' ); ?></p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'How each section appears in the magazine. Add, remove or reorder sections, and choose their posts, under Content → Sections.', 'magazine' ); ?></p>
					<ol class="magazine-admin__sections">
						<?php foreach ( $sections as $i => $section ) : ?>
							<?php $this->render_section( $section, $i, $pages ); ?>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * One section's magazine options.
	 *
	 * @param array<string, mixed> $section A section from the content plugin.
	 * @param int                  $i       Its position.
	 * @param \WP_Post[]           $pages   Published Pages, for the opening page.
	 */
	private function render_section( array $section, int $i, array $pages ): void {
		$options    = Settings::section( $section, $i );
		$name       = fn( string $key ): string => Settings::SECTIONS . '[' . $section['key'] . '][' . $key . ']';
		$collection = isset( $section['collection'] );
		?>
		<li class="magazine-admin__section">
			<p class="magazine-admin__head">
				<strong><?php echo esc_html( (string) $section['label'] ); ?></strong>
				<span class="magazine-admin__badge"><?php echo $collection ? esc_html__( 'Collection', 'magazine' ) : esc_html__( 'Page', 'magazine' ); ?></span>
			</p>
			<p class="magazine-admin__row">
				<label><input type="checkbox" name="<?php echo esc_attr( $name( 'bookmark' ) ); ?>" value="1" <?php checked( $options['bookmark'] ); ?>> <?php esc_html_e( 'Bookmark', 'magazine' ); ?></label>
				<span class="magazine-admin__colors" role="radiogroup" aria-label="<?php esc_attr_e( 'Bookmark colour', 'magazine' ); ?>">
					<?php foreach ( Settings::COLORS as $color ) : ?>
						<label class="magazine-admin__color magazine-admin__color--<?php echo esc_attr( $color ); ?>">
							<input type="radio" name="<?php echo esc_attr( $name( 'color' ) ); ?>" value="<?php echo esc_attr( $color ); ?>" <?php checked( $options['color'], $color ); ?>>
							<span class="screen-reader-text"><?php echo esc_html( $color ); ?></span>
						</label>
					<?php endforeach; ?>
				</span>
			</p>
			<?php if ( $collection ) : ?>
				<p class="magazine-admin__row">
					<label><?php esc_html_e( 'Opening page', 'magazine' ); ?>
						<select name="<?php echo esc_attr( $name( 'opener' ) ); ?>">
							<option value="0"><?php esc_html_e( 'Automatic (from the collection)', 'magazine' ); ?></option>
							<?php foreach ( $pages as $page ) : ?>
								<option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( $options['opener'], $page->ID ); ?>><?php echo esc_html( get_the_title( $page ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label><?php esc_html_e( 'Index of posts', 'magazine' ); ?>
						<select name="<?php echo esc_attr( $name( 'index' ) ); ?>">
							<option value="thumbs" <?php selected( $options['index'], 'thumbs' ); ?>><?php esc_html_e( 'With pictures', 'magazine' ); ?></option>
							<option value="list" <?php selected( $options['index'], 'list' ); ?>><?php esc_html_e( 'List', 'magazine' ); ?></option>
						</select>
					</label>
					<label><?php esc_html_e( 'Back link', 'magazine' ); ?>
						<input type="text" name="<?php echo esc_attr( $name( 'back' ) ); ?>" value="<?php echo esc_attr( $options['back'] ); ?>">
					</label>
				</p>
			<?php endif; ?>
		</li>
		<?php
	}
}
