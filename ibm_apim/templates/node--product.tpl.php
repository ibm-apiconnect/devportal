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
drupal_add_library('system', 'ui.tooltip');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/mesh.css');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/product.css');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/sunburst.min.css');
drupal_add_css(drupal_get_path('module', 'ibm_apim') . '/css/googlecode.min.css');
drupal_add_js(libraries_get_path('waypoints') . '/lib/jquery.waypoints.min.js', array(
  'weight' => 2
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
$showversions = variable_get('ibm_apim_show_versions', 1);
$codesnippets = variable_get('ibm_apim_codesnippets', array(
  'curl' => 1,
  'ruby' => 1,
  'python' => 1,
  'php' => 1,
  'java' => 1,
  'node' => 1,
  'go' => 1,
  'swift' => 1,
  'c' => 0,
  'csharp' => 0
));
?>
<script><?php
  print "window.productJson = " . json_encode($product, JSON_UNESCAPED_UNICODE) . ";";
  print "window.apiJson = " . json_encode($apis, JSON_UNESCAPED_UNICODE) . ";";
  print "window.expandedapiJson = " . json_encode($expandedapis, JSON_UNESCAPED_UNICODE) . ";";
  print "window.codeSnippets = " . json_encode($codesnippets, JSON_UNESCAPED_UNICODE) . ";";
  ?></script>
<article id="node-<?php print $node->nid; ?>"
         class="<?php print $classes; ?> clearfix" <?php print $attributes; ?>>
  <div class="hamburger hidden"
       id="hamburger"><?php print t('Product navigation'); ?></div>
  <div class="mesh-portal-product productWrapper">
    <div class="product">

      <nav class="toc navigate-toc sticky stickyHeader">
        <ul>
          <?php if ($showversions == 1) {
            $product_title = ibm_apim_get_translated_string($product, ['info'], 'title') . ' ' . $product['info']['version'];
          }
          else {
            $product_title = ibm_apim_get_translated_string($product, ['info'], 'title');
          } ?>
          <li class="tocItem toc-product"><a
              onclick="product.navigate('product')"
              href="javascript:;"
              title="<?php print $product_title; ?>"><?php print $product_title; ?></a>
          </li>
          <?php if (isset($docpages) && !empty($docpages)) : ?>
            <li
              class='tocItem productDocumentationHeader toc-product_pages'>
              <span><?php print t('Documentation'); ?></span>
            </li>
            <?php foreach ($docpages as $docpage) : ?>
              <?php $page = node_load($docpage); ?>
              <li class='tocItem tocPage toc-pages_<?php print $docpage; ?>'>
                <a href="<?php print url('node/' . $docpage); ?>"><span
                    class=""><?php print check_plain($page->title); ?></span></a>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
          <li class="tocItem toc-apis"><span><?php print t('APIs'); ?></span>
          </li>
          <li>
            <?php if (isset($apinodes)) : ?>
            <?php foreach ($apinodes as $apinode) : ?>
          <li
            class='tocItem tocApi toc-apis_<?php print drupal_html_class($apinode->api_ref[$apinode->language][0]['value']); ?>'>
            <?php if (isset($apinode->status) && $apinode->status == 1) : ?>
              <a
                href="<?php print url('productapi/' . $node->nid . '/' . ibm_apim_base64_url_encode($apinode->api_ref[$apinode->language][0]['value'])) ?>"
                title="<?php print $apinode->title; ?>"><?php print check_plain($apinode->title); ?></a>
            <?php else: ?>
              <span
                class="moderatedAPI"><?php print check_plain($apinode->title); ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
        <?php if (count($apinodes) == 0): ?>
          <li><span
              class="moderatedAPI"><?php print t('No published APIs available');; ?></span>
          </li>
        <?php endif; ?>
        <?php endif; ?>
          </li>
        </ul>
      </nav>
      <section class="productDetails">
        <div class="product">
          <section class="documentation navigate-product">
            <div class="documentationContent apicSelfClear">
              <div class="titleHeader">
                <div class="apimImage">
                  <?php if (isset($content['product_image'])) : ?>
                    <div class="apimIcon">
                      <?php print render($content['product_image']); ?>
                    </div>
                  <?php elseif ($showplaceholders != 0) : ?>
                    <div class="apimIcon">
                      <div
                        class="field field-name-api-image field-type-image field-label-hidden view-mode-teaser">
                        <div class="field-items">
                          <figure class="clearfix field-item even">
                            <img typeof="foaf:Image" class="image-style-none"
                                 src="<?php print file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/product/' . product_random_image($node->title)); ?>"
                                 width="64" height="64" alt="">
                          </figure>
                        </div>
                      </div>
                    </div>
                  <?php else : ?>
                    <div class='apimIcon' style='display:none;'></div>
                  <?php endif; ?>
                </div>
                <h1 class="name">
                  <?php print ibm_apim_get_translated_string($product, ['info'], 'title'); ?>
                  <?php if ($showversions == 1): ?>
                    <span
                      class="version"><?php print $product['info']['version']; ?></span>
                  <?php endif; ?>
                </h1>
                <?php if (isset($product_state[0]['value']) && strtolower($product_state[0]['value']) == 'deprecated') : ?>
                  <div class="deprecated">
                    <div class="protocol"><?php print t('Deprecated'); ?></div>
                  </div>
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
                    <label
                      for='product_description'><?php print t('Description'); ?></label>
                    <div class="markdown"
                         id='product_description'><?php print ibm_apim_markdown_field(ibm_apim_get_translated_string($product, ['info'], 'description')); ?></div>
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
          <?php if (isset($product['info']['contact']) && ((isset($product['info']['contact']['name']) && !empty($product['info']['contact']['name'])) || (isset($product['info']['contact']['email']) && !empty($product['info']['contact']['email'])) || (isset($product['info']['contact']['url']) && !empty($product['info']['contact']['url'])))) : ?>
            <div class="contactContainer">
              <div class="contactLink"><a
                  onclick='product.togglesection("contact")'
                  href='javascript:;'><span
                    class="expand_more"><i
                      class="material-icons">expand_more</i></span><span
                    class="expand_less"><i
                      class="material-icons">expand_less</i></span><span
                    class="contactTitle"><?php print t('Contact information'); ?></span></a>
              </div>
              <div class="contactContent">
                <?php if (isset($product['info']['contact']['name']) && !empty($product['info']['contact']['name'])) : ?>
                  <div><?php print $product['info']['contact']['name']; ?></div>
                <?php endif; ?>
                <?php if (isset($product['info']['contact']['email']) && !empty($product['info']['contact']['email'])) : ?>
                  <div><a
                      href='mailto:<?php print $product['info']['contact']['email']; ?>'><?php print $product['info']['contact']['email']; ?></a>
                  </div>
                <?php endif; ?>
                <?php if (isset($product['info']['contact']['url']) && !empty($product['info']['contact']['url'])) : ?>
                  <div><a
                      href='<?php print $product['info']['contact']['url']; ?>'
                      target='_blank'><?php print $product['info']['contact']['url']; ?></a></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if ((isset($product['info']['license']) && isset($product['info']['license']['url']) && isset($product['info']['license']['name']) && !empty($product['info']['license']['url']) && !empty($product['info']['license']['name'])) || (isset($product['info']['termsOfService']) && !empty($product['info']['termsOfService']))) : ?>
            <div class="licenseContainer">
              <div class="licenseLink"><a
                  onclick='product.togglesection("license")'
                  href='javascript:;'><span
                    class="expand_more"><i
                      class="material-icons">expand_more</i></span><span
                    class="expand_less"><i
                      class="material-icons">expand_less</i></span><span
                    class="contactTitle"><?php print t('License'); ?></span></a>
              </div>
              <div class="licenseContent">
                <?php if (isset($product['info']['license']) && isset($product['info']['license']['url']) && isset($product['info']['license']['name']) && !empty($product['info']['license']['url']) && !empty($product['info']['license']['name'])) : ?>
                  <div>
                    <label><?php print t('License'); ?></label>
                    <div><a
                        href='<?php print $product['info']['license']['url']; ?>'
                        target='_blank'><?php print ibm_apim_get_translated_string($product['info']['license'], array(), 'name'); ?></a></div>
                  </div>
                <?php endif; ?>
                <?php if (isset($product['info']['termsOfService']) && !empty($product['info']['termsOfService'])) : ?>
                  <div>
                    <label><?php print t('Terms of service'); ?></label>
                    <div><?php print ibm_apim_markdown_field(ibm_apim_get_translated_string($product, ['info'], 'termsOfService')); ?></div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php foreach ($product as $childname => $child) : ?>
            <?php if (_ibm_apim_startsWith(strtolower($childname), 'x-') && !_ibm_apim_startsWith(strtolower($childname), 'x-ibm-')) : ?>
              <div class="vendorExtension">
                <label><?php print substr($childname, 2); ?></label>
                <div>
                  <div
                    class="vendorExtension"><?php print ibm_apim_render_extension($child); ?></div>
                </div>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>

          <div "<?php print $content_attributes; ?>">

          <?php hide($content['comments']);
          hide($content['links']); ?>

          <?php if (isset($content['field_producttags'])) {
            print "<label>" . t('Taxonomies') . "</label>";
            print render($content['field_producttags']);
          }
          ?>
        </div>

    </div>

    <div class="bottomBorder plansSection navigate-plans">
      <h2 class="plansectiontitle"><?php print t('Plans'); ?></h2>
      <a name="plans"></a>
      <div class="apiList">
        <div class="api empty top">&nbsp;</div>
        <?php foreach ($apis as $api) : ?>
          <div
            class="planapiwrapper planapiwrapper-<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>">
            <a
              onclick='product.toggleplanapi("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>")'
              href='javascript:;'>
              <div class="api highlightText">
                <span
                  class="apiname"><?php print check_plain(ibm_apim_get_translated_string($api, ['info'], 'title')); ?><?php if ($showversions == 1) {
                    print ' ' . check_plain($api['info']['version']);
                  } ?></span><span
                  class="expand_more"><i class="material-icons">expand_more</i></span><span
                  class="expand_less"><i
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
                    <div class="apioperation"><?php print strtoupper($verb); ?>
                      &nbsp;<?php print $pathSegment; ?></div>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php
        // add billing section if any plans contain billing info
        $billing = false;
        $ibm_apim_billing_enabled = variable_get('ibm_apim_billing_enabled', 0);
        if ($ibm_apim_billing_enabled == 1) {
          foreach ($product['plans'] as $planName => $plan) {
            if (isset($plan['billing-model'])) {
              $billing = true;
            }
          }
        } ?>
        <?php if (isset($billing) && $billing == true) :?>
          <div class="api empty top"><?php print t('Pricing');?></div>
        <?php endif; ?>
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
                class="title <?php print $approvalclass; ?>"><?php print check_plain(ibm_apim_get_translated_string($plan, array(), 'title')); ?><?php print $approvalimage; ?><?php print $deprecatedstr; ?>
              </div>
              <?php foreach ($planarray[$product['info']['name'] . ':' . $product['info']['version'] . ':' . $planName]['nodes'] as $key => $api) : ?>
                <div
                  class="api planapiwrapper planapiwrapper-<?php print $key; ?>">
                  <div class="apiratelimit">
                    <?php
                    if (isset($api['enabled']) && $api['enabled'] == TRUE) {
                      if (isset($plan['rate-limits']) || isset($plan['burst-limits'])) {
                        $rateLimitCount = 0;
                        $burstLimitCount = 0;
                        if (isset($plan['rate-limits'])) {
                          $rateLimitCount = count($plan['rate-limits']);
                        }
                        if (isset($plan['burst-limits'])) {
                          $burstLimitCount = count($plan['burst-limits']);
                        }
                        if ($rateLimitCount == 0) {
                          // if no rate limits but there is a burst limit then rate limit assumed to be unlimited
                          $plan['rate-limits'][] = array('value' => 'unlimited');
                          $rateLimitCount = count($plan['rate-limits']);
                        }

                        if (($rateLimitCount + $burstLimitCount) > 1) {
                          $tooltip = array(
                            'rates' => array(),
                            'bursts' => array()
                          );
                          $tooltip['rateLabel'] = t('Rate limits');
                          $tooltip['burstLabel'] = t('Burst limits');
                          foreach ($plan['rate-limits'] as $ratename => $ratelimit) {
                            $tooltip['rates'][] = product_parse_rate_limit($ratelimit['value']);
                          }
                          foreach ($plan['burst-limits'] as $ratename => $ratelimit) {
                            $tooltip['bursts'][] = product_parse_rate_limit($ratelimit['value']);
                          }
                          print '<div style="multiRateLimits" data-ratelimits=\'' . json_encode($tooltip, JSON_UNESCAPED_UNICODE) . '\'>' . t('@count rate limits *', array('@count' => $rateLimitCount + $burstLimitCount)) . '</div>';
                        }
                        else {
                          if ($rateLimitCount > 0) {
                            $lastEl = array_pop($lastEl = (array_slice($plan['rate-limits'], -1)));
                            print product_parse_rate_limit($lastEl['value']);
                          }
                          else {
                            $lastEl = array_pop($lastEl = (array_slice($plan['burst-limits'], -1)));
                            print product_parse_rate_limit($lastEl['value']);
                          }
                        }
                      }
                      elseif (isset($plan['rate-limit'])) {
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
                              <?php if (isset($operation['rateLimit']) && isset($operation['rateData'])) : ?>
                                <div style="multiRateLimits"
                                     data-ratelimits='<?php print json_encode($operation['rateData'], JSON_UNESCAPED_UNICODE); ?>'><?php print $operation['rateLimit']; ?></div>
                              <?php elseif (isset($operation['rateLimit'])): ?>
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
              <?php if (isset($billing)) :?>
                <?php if (!isset($plan['billing-model'])) {
                  $plan['billing-model'] = array();
                }?>
                <div class="api empty top"><?php print product_parse_billing($plan['billing-model']);?></div>
              <?php endif; ?>
              <?php
              if (isset($plan['approval']) && $plan['approval'] == TRUE) {
                $approvestr = 'true';
              }
              else {
                $approvestr = 'false';
              }
              $planid = $product['info']['name'] . ':' . $product['info']['version'] . ':' . $planName; ?>
              <div style="padding: 10px;">
                <?php if (!user_is_logged_in()) : ?>
                  <span class="planbutton notuser"><button type="button"
                                                           class="mesh"
                                                           disabled>
                      <?php print t('Subscribe'); ?></button><?php print t("Login to use this plan"); ?></span>
                <?php elseif (!ibm_apim_check_is_developer()) : ?>
                  <span class="planbutton notdev"><button type="button"
                                                          class="mesh" disabled>
                      <?php print t('Subscribe'); ?></button><?php print t("Only developers can subscribe to plans"); ?></span>
                <?php else: ?>
                  <?php if (product_check_product_subscribe($node)): ?>
                    <button type="button" id="planSignupButton"
                            data-href="<?php print url("application/subscribe/" . ibm_apim_base64_url_encode($planid) . '/' . $approvestr); ?>"
                            data-title="<?php print t('Subscribe'); ?>"
                            data-name="content"
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
                    <span class="planbutton notsubscribable"><button
                        type="button" class="mesh" disabled>
                        <?php print t('Subscribe'); ?></button><?php print $notsubscribabletext; ?></span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div
          class="plansFooter"><?php print t('* = Mouseover for more information'); ?></div>
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
