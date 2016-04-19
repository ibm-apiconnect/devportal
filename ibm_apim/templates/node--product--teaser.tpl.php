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
drupal_add_library('system', 'ui.accordion');
drupal_add_js('jQuery(document).ready(function(){
      jQuery("div#accordion").accordion({
        header: "> div > h3",
        collapsible: true,
        active: false,
        heightStyle: "content",
      });
    });', 'inline');
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
              <div class="field field-name-product-image field-type-image field-label-hidden view-mode-teaser">
                <div class="field-items">
                  <figure class="clearfix field-item even">
                    <img typeof="foaf:Image" class="image-style-none"
                         src="<?php print file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/product/' . product_random_image($node->title)); ?>"
                         width="123" height="123" alt="">
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
                <span class="deprecated">
                  <div class="protocol"><?php print t('Deprecated'); ?></div>
                </span>
              <?php endif; ?>
            </h2>
            <div class="apimDescriptionContainer">
              <div class="apimSummaryDescription">

              <?php
              if (isset($product_description[0]['safe_value']) && !empty($product_description[0]['safe_value'])) {
                print '<div class="apimFade" title="' . $product_description[0]['safe_value'] . '">';
                if (module_exists('markdown')) {
                  print _filter_markdown($product_description[0]['safe_value'], NULL);
                }
                else {
                  print '<p>' . $product_description[0]['safe_value'] . '</p>';
                }
                print '</div>';
              }
              ?>
              </div>
              <div id='accordion'>
                <div><h3><?php print t('APIs'); ?></h3>
                  <div class='portalApi animateMaxHeight'>
                    <?php
                    if (isset($apinodes) && is_array($apinodes) && count($apinodes) > 0) {
                      $showversion = variable_get('ibm_apim_show_versions', 1);
                      foreach ($apinodes as $apinode) {
                        $versiontext = '';
                        if ($showversion == 1) {
                          $versiontext = '<span class="apiVersionText">' . $apinode->api_version[$apinode->language][0]['value'] . '</span>';
                        }
                        //print '<p class="productAPILink"><a href="' . url('node/' . $apinode->nid) . '">' . $apinode->title . '</a> ' . $versiontext . '</p>';
                        print '<p class="productAPILink">' . $apinode->title . ' ' . $versiontext . '</p>';
                      }
                    }
                    else {
                      print "<p>" . t('No APIs found.') . "</p>";
                    }
                    ?>
                  </div>
                </div>
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
