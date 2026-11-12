<?php
/**
 * The site's starting sections, used until Content → Sections is first saved (from then on that screen
 * decides): a Page (by slug) or a collection (a section post type), in order, each with its label.
 *
 * @package Akashsrma\Features
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'page'  => 'about',
		'label' => __( 'About', 'akashsrma-features' ),
	),
	array(
		'post_type' => 'project',
		'label'     => __( 'Portfolio', 'akashsrma-features' ),
	),
	array(
		'post_type' => 'learning',
		'label'     => __( 'Learning', 'akashsrma-features' ),
	),
	array(
		'page'  => 'contact',
		'label' => __( 'Contact', 'akashsrma-features' ),
	),
);
