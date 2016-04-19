<?php

/**
 * @file
 * Default theme implementation for products.
 *
 *
 * @see template_preprocess()
 * @see template_preprocess_product()
 * @see template_process()
 * @see theme_product()
 *
 * @ingroup themeable
 */

drupal_add_library('system', 'ui.dialog');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/mesh.css');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/product.css');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/sunburst.min.css');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/googlecode.min.css');
drupal_add_js(libraries_get_path('waypoints') . '/lib/jquery.waypoints.min.js', array(
  'weight' => 2
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/httpsnippet-browser.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/chance.min.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/json2xml.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/vkBeautify.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/Product.js', array(
  'weight' => 4
));
drupal_add_js(libraries_get_path('highlightjs') . '/highlight.min.js', array(
  'weight' => 5
));
drupal_add_js(libraries_get_path('easing') . '/jquery.easing.min.js', array(
  'weight' => 6
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/subscribe.js', array(
  'weight' => 8
));
$showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1);

?>
<script><?php
  print "window.productJson = " . json_encode($product) . ";";
  print "window.apiJson = " . json_encode($apis) . ";";
  print "window.expandedapiJson = " . json_encode($expandedapis) . ";";
  ?></script>
<article id="node-<?php print $node->nid; ?>"
         class="<?php print $classes; ?> clearfix" <?php print $attributes; ?>>
  <div class="hamburger hidden" id="hamburger"><?php print t('Product navigation'); ?></div>
  <div class="mesh-portal-product productWrapper">
    <div class="product">

      <nav class="toc navigate-toc sticky stickyHeader">
        <ul>
          <li class="tocItem toc-product"><a onclick="product.navigate('product')"
                                             href="javascript:;"
                                             title="<?php print $product['info']['title'] . ' ' . $product['info']['version']; ?>"><?php print $product['info']['title'] . ' ' . $product['info']['version']; ?></a>
          </li>
          <li class="tocItem tocSeparator"><span class="innerSeparator"></span></li>
          <li class="tocItem toc-apis"><a style="font-weight: 500;" onclick="product.navigate('apis')"
                                          href="javascript:;"
                                          title="<?php print t('APIs'); ?>"><?php print t('APIs'); ?></a></li>
          <li>
            <?php if (isset($apis)) : ?>
            <?php foreach ($apis as $api) : ?>
          <li
            class='tocItem tocApi toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>'>
            <a
              onclick="product.navigate('apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>', 'apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>')"
              href="javascript:;"
              title="<?php print $api['info']['title'] . ' ' . $api['info']['version']; ?>"><?php print $api['info']['title'] . ' ' . $api['info']['version']; ?></a>
          </li>
          <ul
            class="toc-container toc-container-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?> hidden">
            <?php if (isset($api['securityDefinitions']) && !empty($api['securityDefinitions'])) : ?>
              <li
                class='tocItem toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_security'>
                <a
                  onclick="product.navigate('apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_security', 'apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>')"
                  href="javascript:;" title="<?php print t('Security'); ?>"><?php print t('Security'); ?></a>
              </li>
            <?php endif; ?>
            <ul>
              <li
                class='tocItem toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_ops'>
                <a
                  onclick="product.navigate('apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_ops', 'apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>')"
                  href="javascript:;" title="<?php print t('Operations'); ?>"><?php print t('Operations'); ?></a>
              </li>
              <?php foreach ($api['paths'] as $pathSegment => $path) : ?>
                <?php foreach ($path as $verb => $operation) : ?>
                  <?php if (in_array(strtoupper($verb), array(
                    'PUT',
                    'POST',
                    'GET',
                    'DELETE',
                    'OPTIONS',
                    'HEAD',
                    'PATCH'
                  ))) : ?>
                    <li
                      class='tocItem toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>'>
                      <a class="<?php print strtolower($verb); ?>"
                         onclick="product.navigateop('apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>', 'apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>')"
                         href="javascript:;"
                         title="<?php print strtoupper($verb) . ' ' . $pathSegment; ?>"><?php print strtoupper($verb) . ' ' . $pathSegment; ?></a>
                    </li>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </ul>
            <?php if (isset($api['definitions']) && !empty($api['definitions'])) : ?>
              <li
                class='tocItem toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_definitions'>
                <a
                  onclick="product.navigate('apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_definitions', 'apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>')"
                  href="javascript:;" title="<?php print t('Definitions'); ?>"><?php print t('Definitions'); ?></a>
              </li>
            <?php endif; ?>
          </ul>
          <?php endforeach; ?>
          <?php endif; ?>
          </li>
          <li class="tocItem tocSeparator"><span class="innerSeparator"></span></li>
          <li class="tocItem toc-plans"><a style="font-weight: 500;" onclick="product.navigate('plans')"
                                           href="javascript:;"><?php print t('Plans'); ?></a></li>
        </ul>
      </nav>
      <section class="productDetails">
        <div class="readAndInteract product">
          <h1 class="stickyHeader">
            <?php print $product['info']['title'] . ' ' . $product['info']['version'] ?>
            <?php if (isset($product_state[0]['value']) && strtolower($product_state[0]['value']) == 'deprecated') : ?>
              <span class="deprecated">
                <div class="protocol"><?php print t('Deprecated'); ?></div>
              </span>
            <?php endif; ?>
          </h1>
          <section class="documentation bottomBorder navigate-product productLeft">
            <div class="documentationContent apicSelfClear">
              <div class="apimImage">
                <?php if (isset($content['product_image'])) : ?>
                  <?php print render($content['product_image']); ?>
                <?php elseif ($showplaceholders != 0) : ?>
                  <div class="apimIcon">
                    <div class="field field-name-api-image field-type-image field-label-hidden view-mode-teaser">
                      <div class="field-items">
                        <figure class="clearfix field-item even">
                          <img typeof="foaf:Image" class="image-style-none"
                               src="<?php print file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/product/' . product_random_image($node->title)); ?>"
                               width="123" height="123" alt="">
                        </figure>
                      </div>
                    </div>
                  </div>
                <?php else : ?>
                  <div class='apimIcon' style='display:none;'></div>
                <?php endif; ?>
              </div>
              <div class="info">
                <div class="productRating">
                  <?php if (isset($content['field_productrating'])) {
                    $content['field_productrating']['#label_display'] = 'hidden';
                    print render($content['field_productrating']);
                  } ?>
                </div>
                <?php if (isset($product['info']['description']) && !empty($product['info']['description'])) : ?>
                  <div>
                    <label for='product_description'><?php print t('Description'); ?></label>
                    <div
                      id='product_description'><?php print ibm_apim_markdown_field($product['info']['description']); ?></div>
                  </div>
                <?php endif; ?>
                <?php $docs = render($content['product_attachments']); ?>
                <?php if (isset($docs) && !empty($docs)) : ?>
                  <div>
                    <label><?php print t('Documentation'); ?></label>
                    <div><?php print $docs; ?></div>
                  </div>
                <?php endif; ?>
                <?php if (isset($customfields) && is_array($customfields) && count($customfields) > 0) : ?>
                  <div class="customFields product">
                    <?php foreach ($customfields as $customfield) : ?>
                      <?php print render($content[$customfield]); ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </section>
          <section class="productRight interact bottomBorder" style="padding: 15px;">
            <?php if (isset($product['info']['contact']) && ((isset($product['info']['contact']['name']) && !empty($product['info']['contact']['name'])) || (isset($product['info']['contact']['email']) && !empty($product['info']['contact']['email'])) || (isset($product['info']['contact']['url']) && !empty($product['info']['contact']['url'])))) : ?>
              <div>
                <label style='margin-top: 0;'><?php print t('Contact information'); ?></label>
                <?php if (isset($product['info']['contact']['name']) && !empty($product['info']['contact']['name'])) : ?>
                  <div><?php print $product['info']['contact']['name']; ?></div>
                <?php endif; ?>
                <?php if (isset($product['info']['contact']['email']) && !empty($product['info']['contact']['email'])) : ?>
                  <div><a
                      href='mailto:<?php print $product['info']['contact']['email']; ?>'><?php print $product['info']['contact']['email']; ?></a>
                  </div>
                <?php endif; ?>
                <?php if (isset($product['info']['contact']['url']) && !empty($product['info']['contact']['url'])) : ?>
                  <div><a href='<?php print $product['info']['contact']['url']; ?>'
                          target='_blank'><?php print $product['info']['contact']['url']; ?></a></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if (isset($product['info']['termsOfService']) && !empty($product['info']['termsOfService'])) : ?>
              <div>
                <label><?php print t('Terms of service'); ?></label>
                <div><?php print $product['info']['termsOfService']; ?></div>
              </div>
            <?php endif; ?>
            <?php if (isset($product['info']['license']) && isset($product['info']['license']['url']) && isset($product['info']['license']['name']) && !empty($product['info']['license']['url']) && !empty($product['info']['license']['name'])) : ?>
              <div>
                <label><?php print t('License'); ?></label>
                <div><a href='<?php print $product['info']['license']['url']; ?>'
                        target='_blank'><?php print $product['info']['license']['name']; ?></a></div>
              </div>
            <?php endif; ?>
            <div "<?php print $content_attributes; ?>">

            <?php hide($content['comments']);
            hide($content['links']); ?>

            <?php if (isset($content['field_producttags'])) {
              print "<label>" . t('Taxonomies') . "</label>";
              print render($content['field_producttags']);
            }
            ?>
        </div>
        <?php if ($links = render($content['links'])): ?>
          <nav <?php print $links_attributes; ?>><?php print $links; ?></nav>
        <?php endif; ?>
      </section>
    </div>

    <?php if (isset($apinodes) && is_array($apinodes) && count($apinodes) > 0) {
      foreach ($apinodes as $apinode) {
        $view = node_view($apinode, 'inner');
        print drupal_render($view);
      }
    }
    else {
      print "<p>" . t('No APIs found.') . "</p>";
    } ?>

    <div class="bottomBorder plansSection navigate-plans">
      <h2 class="plansectiontitle"><?php print t('Plans'); ?></h2>
      <div class="apiList">
        <div class="api empty top">&nbsp;</div>
        <?php foreach ($apis as $api) : ?>
          <div
            class="planapiwrapper planapiwrapper-<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>">
            <a
              onclick='product.toggleplanapi("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>")'
              href='javascript:;'>
              <div class="api highlightText">
                <span class="apiname"><?php print $api['info']['title'] . ' ' . $api['info']['version']; ?></span><span
                  class="expand_more"><i class="material-icons">expand_more</i></span><span class="expand_less"><i
                    class="material-icons">expand_less</i></span>
              </div></a>
            <div
              class="apicontents apicontents-<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>">
              <?php foreach ($api['paths'] as $pathSegment => $path) : ?>
                <?php foreach ($path as $verb => $operation) : ?>
                  <?php if (in_array(strtoupper($verb), array(
                    'PUT',
                    'POST',
                    'GET',
                    'DELETE',
                    'OPTIONS',
                    'HEAD',
                    'PATCH'
                  ))) : ?>
                    <div class="apioperation"><?php print strtoupper($verb); ?>&nbsp;<?php print $pathSegment; ?></div>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <div class="api empty bottom">&nbsp;</div>
      </div>
      <div class="plans">
        <div class="plansinner">
          <?php foreach ($product['plans'] as $planName => $plan) : ?>
            <?php
            if (!isset($plan['approval']) || $plan['approval'] != TRUE) {
              $approvalstring = t("Approval not required.");
              $approvalclass = 'disabled';
              $approvalimage = '<i class="material-icons" title="' . $approvalstring . '">lock_open</i>';
            }
            else {
              $approvalstring = t("Approval is required");
              $approvalclass = 'enabled';
              $approvalimage = '<i class="material-icons" title="' . $approvalstring . '">lock</i>';
            }
            if (isset($product_state[0]['value']) && $product_state[0]['value'] == 'deprecated') {
              $deprecatedstr = '<span class="deprecated">' . t('Deprecated') . '</span>';
            }
            else {
              $deprecatedstr = '';
            }
            ?>
            <div class="plan">
              <div
                class="title <?php print $approvalclass; ?>"><?php print $plan['title']; ?><?php print $approvalimage; ?><?php print $deprecatedstr; ?>
              </div>
              <?php foreach ($planarray[$product['info']['name'] . ':' . $product['info']['version'] . ':' . $planName]['nodes'] as $key => $api) : ?>
                <div
                  class="api planapiwrapper planapiwrapper-<?php print $key; ?>">
                  <div class="apiratelimit">
                    <?php
                    if (isset($api['enabled']) && $api['enabled'] == TRUE) {
                      if (isset($plan['rate-limit'])) {
                        print product_parse_rate_limit($plan['rate-limit']['value']);
                      }
                      else {
                        print product_parse_rate_limit('unlimited');
                      }
                    }
                    else {
                      print '<i class="material-icons notincluded">close</i>';
                    }
                    ?>

                  </div>
                  <div
                    class="apicontents apicontents-<?php print $key; ?>">
                    <?php foreach ($api['resources'] as $pathSegment => $path) : ?>
                      <?php foreach ($path as $verb => $operation) : ?>
                        <?php if (in_array(strtoupper($verb), array(
                          'PUT',
                          'POST',
                          'GET',
                          'DELETE',
                          'OPTIONS',
                          'HEAD',
                          'PATCH'
                        ))) : ?>
                          <div class="individualoperation">
                            <?php if (isset($operation['enabled']) && $operation['enabled'] == TRUE) : ?>
                              <?php if (isset($operation['rateLimit'])) : ?>
                                <span><?php print $operation['rateLimit']; ?></span>
                              <?php else: ?>
                                <i class="material-icons">check</i>
                              <?php endif; ?>
                            <?php else: ?>
                              <i class="material-icons notincluded">close</i>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php
              if (isset($plan['approval']) && $plan['approval'] == true) {
                $approvestr = 'true';
              } else {
                $approvestr = 'false';
              }
              $planid = $product['info']['name'] . ':' . $product['info']['version'] . ':' . $planName; ?>
              <div style="padding: 10px;">
                <?php if (!user_is_logged_in()) : ?>
                  <span class="planbutton notuser"><button type="button" class="mesh" disabled>
                      <?php print t('Subscribe'); ?></button><?php print t("Login to use this plan"); ?></span>
                <?php elseif (!ibm_apim_check_is_developer()) : ?>
                  <span class="planbutton notdev"><button type="button" class="mesh" disabled>
                      <?php print t('Subscribe'); ?></button><?php print t("Only developers can subscribe to plans"); ?></span>
                <?php else: ?>
                  <?php if (product_check_product_subscribe($node)): ?>
                    <button type="button" id="planSignupButton"
                            data-href="<?php print url("application/subscribe/" . ibm_apim_base64_url_encode($planid) . '/' . $approvestr); ?>"
                            data-title="<?php print t('Subscribe'); ?>" data-name="content"
                            data-rel="width:500;resizable:false"
                            class="mesh simple-dialog my-link-class"><?php print t('Subscribe'); ?></button>
                  <?php else: ?>
                    <?php
                    if (isset($product_state[0]['value']) && $product_state[0]['value'] == 'deprecated') {
                      $notsubscribabletext = t("This plan is deprecated");
                    }
                    else {
                      $notsubscribabletext = t("This plan is not subscribable");
                    }
                    ?>
                    <span class="planbutton notsubscribable"><button type="button" class="mesh" disabled>
                        <?php print t('Subscribe'); ?></button><?php print $notsubscribabletext; ?></span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="comments">
      <?php if ($links = render($content['links'])): ?>
        <nav <?php print $links_attributes; ?>><?php print $links; ?></nav>
      <?php endif; ?>

      <?php print render($content['comments']); ?>
    </div>
    </section>

  </div>
  </div>

</article>
