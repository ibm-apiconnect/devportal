<?php

/**
 * @file
 * Default theme implementation for apis.
 *
 * @see template_preprocess()
 * @see template_preprocess_api()
 * @see template_process()
 * @see theme_api()
 *
 * @ingroup themeable
 */


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
$showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1);

$apim_session = &_ibm_apim_get_apim_session();
$protocol_lower = strtolower($api_protocol[0]['value']);
if (isset($protocol_lower) && $protocol_lower == 'soap') {
  $protocol = 'soap';
}
else {
  $protocol = 'rest';
} ?>
<script><?php
  print "window.productJson = " . json_encode(array("null")) . ";";
  print "window.apiJson = " . json_encode(array($api)) . ";";
  print "window.expandedapiJson = " . json_encode(array($expandedapi)) . ";";
  ?></script>
<article id="node-<?php print $node->nid; ?>"
         class="mesh-portal-product singleapi <?php print $classes . ' ' . $content['api_apiid'][0]['#markup'] . ' ' . $protocol; ?> clearfix" <?php print $attributes; ?>>

  <?php if (isset($node)) {
    $view = node_view($node, 'inner');
    print drupal_render($view);
  }
  else {
    print "<p>" . t('No API found.') . "</p>";
  } ?>

  <div class="comments">
    <?php if ($links = render($content['links'])): ?>
      <nav <?php print $links_attributes; ?>><?php print $links; ?></nav>
    <?php endif; ?>

    <?php print render($content['comments']); ?>
  </div>
</article>
