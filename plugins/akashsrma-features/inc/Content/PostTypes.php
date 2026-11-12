<?php
/**
 * The section post types (Portfolio, Learning), from config/post-types.php.
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Content;

use Akashsrma\Features\Core\Config;
use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Registers each configured post type, with its archive URL and the template new posts start from.
 */
final class PostTypes implements Module {

	/**
	 * Sets up the module.
	 *
	 * @param Config $config The plugin's configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * Post types are needed everywhere.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Hooks the registration to `init`.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
	}

	/**
	 * Registers every post type in config/post-types.php.
	 */
	public function register_post_types(): void {
		foreach ( $this->config->get( 'post-types' ) as $name => $type ) {
			register_post_type(
				$name,
				array(
					'labels'       => $type['labels'],
					'description'  => $type['description'],
					'public'       => true,
					'show_in_rest' => true,
					'has_archive'  => $type['slug'],
					'rewrite'      => array(
						'slug'       => $type['slug'],
						'with_front' => false,
					),
					'menu_icon'    => $type['icon'],
					'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ),
					'template'     => $this->config->get( 'templates' ),
				)
			);
		}
	}
}
