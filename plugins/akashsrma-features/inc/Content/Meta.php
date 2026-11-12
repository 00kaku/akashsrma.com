<?php
/**
 * The section post types' fields: each type's own (config/post-types.php) plus the shared ones
 * (config/fields.php).
 *
 * @package Akashsrma\Features
 */

namespace Akashsrma\Features\Content;

use Akashsrma\Features\Core\Config;
use Akashsrma\Features\Core\FieldTypes;
use Akashsrma\Features\Core\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every field as post meta, readable in the REST API (so blocks can edit it) and cleaned by its type.
 */
final class Meta implements Module {

	/**
	 * Sets up the module.
	 *
	 * @param Config $config The plugin's configuration.
	 */
	public function __construct( private Config $config ) {}

	/**
	 * Fields are needed everywhere.
	 */
	public function can_register(): bool {
		return true;
	}

	/**
	 * Hooks the registration to `init`.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Registers each post type's fields.
	 */
	public function register_meta(): void {
		foreach ( $this->config->get( 'post-types' ) as $name => $type ) {
			foreach ( $type['meta'] + $this->config->get( 'fields' ) as $key => $kind ) {
				[ $storage, $sanitize ] = FieldTypes::get( $kind );
				register_post_meta(
					$name,
					$key,
					array(
						'type'              => $storage,
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => $sanitize,
						'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
					)
				);
			}
		}
	}
}
