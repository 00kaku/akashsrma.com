<?php
/**
 * The content model: post types, their fields and the shared taxonomy.
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Features;

use Akashsrma\Features\Content\Meta;
use Akashsrma\Features\Core\Config;

/**
 * Checks what config/post-types.php, fields.php and taxonomies.php register.
 */
class PostTypesTest extends \WP_UnitTestCase {

	/**
	 * WordPress's test case clears registered meta after every test, so each test registers the fields again
	 * through the plugin's own Meta module.
	 */
	public function set_up(): void {
		parent::set_up();
		( new Meta( new Config( dirname( __DIR__, 3 ) . '/plugins/akashsrma-features/config' ) ) )->register_meta();
	}

	/**
	 * Portfolio and Learning exist, with their archive URLs.
	 */
	public function test_section_post_types_are_registered(): void {
		$this->assertSame( 'portfolio', get_post_type_object( 'project' )->has_archive );
		$this->assertSame( 'learning', get_post_type_object( 'learning' )->has_archive );
		$this->assertTrue( get_post_type_object( 'project' )->show_in_rest );
	}

	/**
	 * A type's own fields and the shared fields are registered, and readable in the REST API.
	 */
	public function test_fields_are_registered(): void {
		$fields = get_registered_meta_keys( 'post', 'project' );
		$this->assertArrayHasKey( 'role', $fields );
		$this->assertArrayHasKey( 'code_url', $fields );
		$this->assertTrue( (bool) $fields['code_url']['show_in_rest'] );
		$this->assertArrayNotHasKey( 'role', get_registered_meta_keys( 'post', 'learning' ) );
	}

	/**
	 * Fields are cleaned by their type.
	 */
	public function test_fields_are_cleaned_by_type(): void {
		$post = self::factory()->post->create( array( 'post_type' => 'project' ) );
		update_post_meta( $post, 'code_url', 'javascript:alert(1)' );
		$this->assertSame( '', get_post_meta( $post, 'code_url', true ) );
	}

	/**
	 * The Stack taxonomy is shared by both section post types.
	 */
	public function test_stack_taxonomy_is_shared(): void {
		$this->assertEqualsCanonicalizing( array( 'project', 'learning' ), get_taxonomy( 'stack' )->object_type );
	}
}
