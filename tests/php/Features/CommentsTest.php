<?php
/**
 * Comments, switched off.
 *
 * @package Akashsrma\Tests
 */

namespace Akashsrma\Tests\Features;

use Akashsrma\Features\Content\Comments;

/**
 * Checks that comments are closed, hidden and gone from blocks, REST and XML-RPC.
 */
class CommentsTest extends \WP_UnitTestCase {

	/**
	 * No post type supports comments, and posts are closed even when stored as open.
	 */
	public function test_comments_are_closed(): void {
		$this->assertSame( array(), get_post_types_by_support( 'comments' ) );

		$post = self::factory()->post->create(
			array(
				'comment_status' => 'open',
				'ping_status'    => 'open',
			)
		);
		self::factory()->comment->create( array( 'comment_post_ID' => $post ) );

		$this->assertFalse( comments_open( $post ) );
		$this->assertFalse( pings_open( $post ) );
		$this->assertSame( 0, (int) get_comments_number( $post ) );
		$this->assertSame( 'closed', get_option( 'default_comment_status' ) );
	}

	/**
	 * The editor offers no comment blocks.
	 */
	public function test_comment_blocks_are_not_allowed(): void {
		$allowed = apply_filters( 'allowed_block_types_all', true, new \WP_Block_Editor_Context() );

		$this->assertContains( 'core/paragraph', $allowed );
		$this->assertNotContains( 'core/comments', $allowed );
		$this->assertNotContains( 'core/latest-comments', $allowed );
		$this->assertNotContains( 'core/post-comments-form', $allowed );
		$this->assertTrue( Comments::is_comment_block( 'core/comment-template' ) );
	}

	/**
	 * REST has no comments routes, and XML-RPC no pingbacks.
	 */
	public function test_comment_routes_are_gone(): void {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayNotHasKey( '/wp/v2/comments', $routes );
		$this->assertArrayHasKey( '/wp/v2/posts', $routes );

		$methods = apply_filters( 'xmlrpc_methods', array( 'pingback.ping' => 'x' ) );
		$this->assertArrayNotHasKey( 'pingback.ping', $methods );
	}
}
