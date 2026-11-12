<?php
/**
 * Taxonomies shared by every section post type, from config/taxonomies.php.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Content;

use Akashsrma\Features\Core\Config;
use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Registers each configured taxonomy for all the section post types.
 */
final class Taxonomies implements Module {

	/**
	 * Sets up the module.
	 *
	 * @param Config $config The plugin's configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * Taxonomies are needed everywhere.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Hooks the registration to `init`.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Registers every taxonomy in config/taxonomies.php.
	 */
	public function register_taxonomies(): void {
		$post_types = array_keys( $this->config->get( 'post-types' ) );
		foreach ( $this->config->get( 'taxonomies' ) as $name => $taxonomy ) {
			register_taxonomy(
				$name,
				$post_types,
				array(
					'labels'            => $taxonomy['labels'],
					'show_in_rest'      => true,
					'show_admin_column' => true,
					'rewrite'           => array( 'with_front' => false ),
				)
			);
		}
	}
}
