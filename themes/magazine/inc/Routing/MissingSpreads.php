<?php
/**
 * 404s for spreads that don't exist.
 *
 * @package Magazine
 */

namespace Magazine\Routing;

use Magazine\Core\Module;
use Magazine\Layout\Magazine;

defined( 'ABSPATH' ) || exit;

/**
 * A paged URL past a post's last spread (/about/6/ when About has five) is a 404. WordPress counts Page
 * Break pages, not the spreads they pair into, so it would otherwise serve the post, or redirect to it.
 */
final class MissingSpreads implements Module {

	/**
	 * Only front-end requests are routed.
	 */
	public function can_register(): bool {
		return ! is_admin();
	}

	/**
	 * Hooks the 404 decision and stops WordPress redirecting those 404s.
	 */
	public function register(): void {
		add_filter( 'pre_handle_404', array( $this, 'missing_spread' ), 10, 2 );
		add_filter( 'redirect_canonical', array( $this, 'keep_missing_spread' ) );
	}

	/**
	 * Makes a paged URL past the post's last spread a 404.
	 *
	 * @param bool      $handled Whether the 404 decision has been made already.
	 * @param \WP_Query $query   The main query.
	 */
	public function missing_spread( bool $handled, \WP_Query $query ): bool {
		$page = (int) $query->get( 'page' );
		$post = $query->get_queried_object();
		if ( $handled || $page < 2 || ! $query->is_singular() || ! $post instanceof \WP_Post || $page <= Magazine::spread_count( $post ) ) {
			return $handled;
		}
		$query->set_404();
		status_header( 404 );
		nocache_headers();
		return true;
	}

	/**
	 * Keeps a missing spread a 404: WordPress would otherwise redirect it to the post's first page.
	 *
	 * @param string|false $redirect_url Where WordPress would redirect.
	 * @return string|false
	 */
	public function keep_missing_spread( $redirect_url ) {
		return is_404() && (int) get_query_var( 'page' ) > 1 ? false : $redirect_url;
	}
}
