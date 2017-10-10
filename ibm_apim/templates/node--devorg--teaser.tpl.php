<?php

/**
 * @file
 * Default teaser theme implementation for devorgs.
 *
 * @see template_preprocess()
 * @see template_preprocess_devorg()
 * @see template_process()
 * @see theme_devorg()
 *
 * @ingroup themeable
 */
?>
<article id="node-<?php print $node->nid; ?>"
         class="<?php print $classes; ?> apimTeaser clearfix"
  <?php print $attributes; ?>>

  <div class="apimSummaryContainer devorgTeaser">
    <div class="apimOuterContainer">
      <div class="apimSummary">
        <div class="apimInnerContainer">
          <div class="apimTeaserContainer">
            <h2 class="apimSummaryTitle"><?php print $node->title; ?></h2>
            <div class="apimSummaryDescription">
              <div class="devorgOwner"><?php print t('Owner: ') . $devorg_owner[0]['safe_value']; ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</article>
