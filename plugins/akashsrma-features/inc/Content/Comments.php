<?php
/**
 * Comments, switched off across the site.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Content;

use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * The site takes no comments, pingbacks or trackbacks, whatever the theme. Closes them on every post type,
 * hides any already stored, and removes their screens, admin bar item, blocks, feeds, REST routes and
 * XML-RPC methods.
 */
final class Comments implements Module {

	/**
	 * Admin screens that only manage comments.
	 */
	private const SCREENS = array( 'edit-comments.php', 'comment.php', 'options-discussion.php' );

	/**
	 * Comments are off everywhere.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		// Content: closed on every post type, none shown.
		add_action( 'init', array( $this, 'remove_support' ), 100 );
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'comments_array', '__return_empty_array', 20 );
		add_filter( 'get_comments_number', '__return_zero', 20 );
		add_filter( 'pre_option_default_comment_status', array( $this, 'closed' ) );
		add_filter( 'pre_option_default_ping_status', array( $this, 'closed' ) );

		// Blocks: none of the comment blocks in the editor.
		add_filter( 'allowed_block_types_all', array( $this, 'allowed_blocks' ) );

		// Feeds, REST, pingbacks.
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
		add_action( 'template_redirect', array( $this, 'no_comment_feeds' ) );
		add_filter( 'rest_endpoints', array( $this, 'rest_endpoints' ) );
		add_filter( 'xmlrpc_methods', array( $this, 'xmlrpc_methods' ) );
		add_filter( 'wp_headers', array( $this, 'headers' ) );

		// Admin: menus, screens, admin bar.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 100 );
		add_action( 'admin_init', array( $this, 'leave_comment_screens' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
	}

	/**
	 * Removes comment and trackback support from every post type.
	 */
	public function remove_support(): void {
		foreach ( get_post_types() as $post_type ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}

	/**
	 * New posts start with comments and pings closed.
	 */
	public function closed(): string {
		return 'closed';
	}

	/**
	 * Whether a block is a comment block.
	 *
	 * @param string $name Block name.
	 */
	public static function is_comment_block( string $name ): bool {
		return str_starts_with( $name, 'core/comment' )
			|| str_starts_with( $name, 'core/post-comments' )
			|| 'core/latest-comments' === $name;
	}

	/**
	 * Leaves the comment blocks out of the blocks the editor offers.
	 *
	 * @param bool|string[] $allowed Allowed block names, or true for all.
	 * @return bool|string[]
	 */
	public function allowed_blocks( $allowed ) {
		if ( false === $allowed ) {
			return false;
		}
		$names = is_array( $allowed ) ? $allowed : array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() );
		return array_values( array_filter( $names, fn( $name ) => ! self::is_comment_block( $name ) ) );
	}

	/**
	 * Comment feeds are not found.
	 */
	public function no_comment_feeds(): void {
		if ( is_comment_feed() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	/**
	 * Removes the comments routes from the REST API.
	 *
	 * @param array<string, mixed> $endpoints Routes and their handlers.
	 * @return array<string, mixed>
	 */
	public function rest_endpoints( array $endpoints ): array {
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( str_starts_with( $route, '/wp/v2/comments' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}

	/**
	 * Removes the pingback methods from XML-RPC.
	 *
	 * @param array<string, mixed> $methods XML-RPC methods.
	 * @return array<string, mixed>
	 */
	public function xmlrpc_methods( array $methods ): array {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
		return $methods;
	}

	/**
	 * Stops advertising a pingback address.
	 *
	 * @param array<string, string> $headers Response headers.
	 * @return array<string, string>
	 */
	public function headers( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/**
	 * Removes Comments and Settings → Discussion from the admin menu.
	 */
	public function admin_menu(): void {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	/**
	 * Sends anyone opening a comment screen directly to the dashboard.
	 */
	public function leave_comment_screens(): void {
		global $pagenow;
		if ( in_array( $pagenow, self::SCREENS, true ) ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * Removes the comments item from the admin bar.
	 *
	 * @param \WP_Admin_Bar $bar The admin bar.
	 */
	public function admin_bar( \WP_Admin_Bar $bar ): void {
		$bar->remove_node( 'comments' );
	}
}
