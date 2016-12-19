<?php

/**
 * @file
 * Default teaser theme implementation for products.
 *
 * @see template_preprocess()
 * @see template_preprocess_product()
 * @see template_process()
 * @see theme_product()
 *
 * @ingroup themeable
 */
?>
<article id="node-<?php print $node->nid; ?>"
         class="<?php print $classes; ?> apimTeaser clearfix"
  <?php print $attributes; ?>>
  <?php $showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1); ?>

  <div class="apimSummaryContainer productTeaser">
    <div class="apimOuterContainer">
      <div class="apimSummary">
        <div class="apimInnerContainer">
          <?php if (isset($content['product_image'])) : ?>
            <div class="apimIcon">
              <?php print render($content['product_image']); ?>
            </div>
          <?php elseif ($showplaceholders != 0): ?>
            <div class="apimIcon">
              <div
                class="field field-name-product-image field-type-image field-label-hidden view-mode-teaser">
                <div class="field-items">
                  <figure class="clearfix field-item even">
                    <img typeof="foaf:Image" class="image-style-none"
                         src="<?php print file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/product/' . product_random_image($node->title)); ?>"
                         width="48" height="48" alt="">
                  </figure>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="apimIcon" style="display:none"></div>
          <?php endif; ?>
          <div class="apimTeaserContainer">
            <div class="apimTeaserRating">
              <?php if (isset($content['field_productrating'])) {
                $content['field_productrating']['#label_display'] = 'hidden';
                print render($content['field_productrating']);
              } ?></div>
            <h2 class="apimSummaryTitle"><?php print $titlelink; ?>
              <?php if (isset($product_state[0]['value']) && strtolower($product_state[0]['value']) == 'deprecated') : ?>
                <div class="deprecated">
                  <div class="protocol"><?php print t('Deprecated'); ?></div>
                </div>
              <?php endif; ?>
            </h2>
            <div class="apimDescriptionContainer">
              <div class="apimSummaryDescription markdown">
                <?php
                if (isset($product_description[0]['safe_value']) && !empty($product_description[0]['safe_value'])) {
                  print '<div class="apimFade" title="' . $product_description[0]['safe_value'] . '">';
                  print ibm_apim_markdown_field($product_description[0]['value']);
                  print '</div>';
                }
                ?>
              </div>
            </div>
            <div class="extraFields">
              <?php if (is_array($customfields) && count($customfields) > 0) {
                foreach ($customfields as $customfield) {
                  print render($content[$customfield]);
                }
              } ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</article>
