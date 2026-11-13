<?php
/**
 * Front end of the Code & live links block.
 *
 * @package Akashsrma\Features
 *
 * @var WP_Block $block The block instance; postId comes from its context.
 */

defined( 'ABSPATH' ) || exit;

$akashsrma_post = $block->context['postId'] ?? get_the_ID();

if ( get_post_meta( $akashsrma_post, 'is_private', true ) ) {
	printf(
		'<div %s><p class="project-links__note">%s</p></div>',
		get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core.
		esc_html__( 'Code and site private — client work', 'akashsrma-features' )
	);
	return;
}

$akashsrma_links = array_filter(
	array(
		__( 'Code', 'akashsrma-features' ) => get_post_meta( $akashsrma_post, 'code_url', true ),
		( get_post_meta( $akashsrma_post, 'live_label', true ) ? get_post_meta( $akashsrma_post, 'live_label', true ) : __( 'Live', 'akashsrma-features' ) ) => get_post_meta( $akashsrma_post, 'live_url', true ),
	)
);

if ( ! $akashsrma_links ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<?php foreach ( $akashsrma_links as $akashsrma_label => $akashsrma_url ) : ?>
		<a class="project-links__a" href="<?php echo esc_url( $akashsrma_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $akashsrma_label ); ?> →</a>
	<?php endforeach; ?>
</div>
