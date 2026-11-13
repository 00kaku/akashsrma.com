<?php
/**
 * Title: Opening spread
 * Slug: magazine/opening-spread
 * Categories: magazine
 * Post Types: page, project, learning
 * Block Types: core/post-content
 * Description: A story's first spread: a full picture page, then the title page.
 *
 * @package Magazine
 */

?>
<!-- wp:image {"className":"is-style-bleed"} -->
<figure class="wp-block-image is-style-bleed"><img alt=""/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"magazine-kicker"} -->
<p class="magazine-kicker">Caption label</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"magazine-deck"} -->
<p class="magazine-deck">A line about the picture.</p>
<!-- /wp:paragraph -->

<!-- wp:nextpage -->
<!--nextpage-->
<!-- /wp:nextpage -->

<!-- wp:paragraph {"className":"magazine-kicker"} -->
<p class="magazine-kicker">Section</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"magazine-title is-size-m"} -->
<h2 class="wp-block-heading magazine-title is-size-m">The Story’s Title</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"magazine-deck"} -->
<p class="magazine-deck">One or two sentences that set up the story.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"dropCap":true} -->
<p class="has-drop-cap">The first paragraph opens with a drop cap. Keep this page to about 500 characters of body text below the title.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>A second paragraph. Add a Page Break block where the next page should start.</p>
<!-- /wp:paragraph -->
