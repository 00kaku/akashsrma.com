<?php
/**
 * Post types that become sections of the site. `slug` is the archive URL; `meta` lists the fields only that
 * type has, as name => type (see Core\FieldTypes). Loaded on `init`, so translations are available.
 *
 * @package Akashsrma\Features
 */

defined( 'ABSPATH' ) || exit;

return array(
	'project'  => array(
		'labels'      => array(
			'name'          => __( 'Portfolio', 'akashsrma-features' ),
			'singular_name' => __( 'Project', 'akashsrma-features' ),
		),
		'slug'        => 'portfolio',
		'icon'        => 'dashicons-portfolio',
		'description' => __( 'From platform scale down to a single slow query.', 'akashsrma-features' ),
		'meta'        => array(
			'role'     => 'string',
			'timeline' => 'string',
			'status'   => 'string',
			'client'   => 'string',
			'featured' => 'boolean',
		),
	),
	'learning' => array(
		'labels'      => array(
			'name'          => __( 'Learning', 'akashsrma-features' ),
			'singular_name' => __( 'Learning post', 'akashsrma-features' ),
		),
		'slug'        => 'learning',
		'icon'        => 'dashicons-welcome-learn-more',
		'description' => __( 'Things I build to learn — each links to its code and a place to try it.', 'akashsrma-features' ),
		'meta'        => array(
			'status'  => 'string',
			'started' => 'string',
		),
	),
);
