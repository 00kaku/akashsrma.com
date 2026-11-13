<?php
/**
 * Stubs for static analysis: functions from plugins the theme can use when they're active, but which aren't
 * part of this repository. PHPStan reads this file; WordPress never loads it.
 *
 * @package Akashsrma\Tests
 */

/**
 * The SEO Framework's API (wordpress.org plugin `autodescription`), used for page titles.
 */
function tsf(): object {} // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- mirrors the plugin's own function name.
