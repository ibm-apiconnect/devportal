<?php

/**
 * @file
 * Default teaser theme implementation for apis.
 *
 * @see template_preprocess()
 * @see template_preprocess_api()
 * @see template_process()
 * @see theme_api()
 *
 * @ingroup themeable
 */
?>
<article id="node-<?php print $node->nid; ?>"
	class="<?php print $classes; ?> apimTeaser clearfix embedded"
	<?php print $attributes; ?>>

	<div class="apimSummaryContainer">
		<div class="apimOuterContainer">
			<div class="apimSummary">
				<div class="apimInnerContainer">
      <div class="apimTeaserRating">
      <?php if (isset($content['field_apirating'])) {
    $content['field_apirating']['#label_display'] = 'hidden';
    print render($content['field_apirating']);
  } ?></div>
					<h4 class="apimSummaryTitle"><?php print $titlelink; ?></h4>
					</div>
				</div>
			</div>
		</div>
</article>
