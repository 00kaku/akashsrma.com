<?php
/**
 * PHPUnit bootstrap: WordPress's test library (wp-env installs it and sets WP_TESTS_DIR), with the
 * Akashsrma Features plugin loaded and the Magazine theme active.
 *
 * @package Akashsrma\Tests
 */

$akashsrma_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/wordpress-phpunit';
$akashsrma_repo      = dirname( __DIR__, 2 );

require_once $akashsrma_repo . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
require_once $akashsrma_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () use ( $akashsrma_repo ) {
		require $akashsrma_repo . '/plugins/akashsrma-features/akashsrma-features.php';
		// The test site starts on a default theme; the theme under test is the Magazine theme.
		add_filter( 'stylesheet', fn() => 'magazine' );
		add_filter( 'template', fn() => 'magazine' );
	}
);

require $akashsrma_tests_dir . '/includes/bootstrap.php';
