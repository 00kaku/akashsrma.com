<?php
/**
 * Fields every section post type has, as name => type: a short title for "Next: …" links, and the Code / Live
 * links shown by the Code & live links block.
 *
 * @package Akashsrma\Features
 */

defined( 'ABSPATH' ) || exit;

return array(
	'short_title' => 'string',
	'code_url'    => 'url',
	'live_url'    => 'url',
	'live_label'  => 'string',
	'is_private'  => 'boolean',
);
