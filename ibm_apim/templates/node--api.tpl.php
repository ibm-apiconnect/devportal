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
libraries_load('underscore');
drupal_add_library('underscore', 'underscore');
drupal_add_js(libraries_get_path('waypoints') . '/lib/jquery.waypoints.min.js', array(
  'weight' => 2
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/httpsnippet-browser.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/chance.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/json2xml.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/vkbeautify.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/vkbeautify.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/x2js.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/apiconnect-example-generator.js', array(
  'weight' => 3
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/CommonAPI.js', array(
  'weight' => 4
));
drupal_add_js(libraries_get_path('highlightjs') . '/highlight.min.js', array(
  'weight' => 4
));
drupal_add_js(drupal_get_path('module', 'ibm_apim') . '/js/API.js', array(
  'weight' => 5
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
$apim_session = &_ibm_apim_get_apim_session();
$productnid = $apim_session['productid'];
$protocol_lower = strtolower($api_protocol[0]['value']);
if (isset($protocol_lower) && $protocol_lower == 'wsdl') {
  $protocol = 'wsdl';
}
else {
  $protocol = 'rest';
} ?>
<script><?php
  print "window.apiJson = " . json_encode(array($api), JSON_UNESCAPED_UNICODE) . ";";
  print "window.appJson = " . json_encode(array($redirect_uris), JSON_UNESCAPED_UNICODE) . ";";
  print "window.expandedapiJson = " . json_encode(array($expandedapi), JSON_UNESCAPED_UNICODE) . ";";
  print "window.codeSnippets = " . json_encode($codesnippets, JSON_UNESCAPED_UNICODE) . ";";
  ?></script>
<article id="node-<?php print $node->nid; ?>"
         class="mesh-portal-product singleapi <?php print $classes . ' ' . $content['api_apiid'][0]['#markup'] . ' ' . $protocol; ?> clearfix" <?php print $attributes; ?>>
  <nav class="toc navigate-toc sticky stickyHeader">
    <ul>
      <?php if ($showversions == 1) {
        $product_title = ibm_apim_get_translated_string($product, ['info'], 'title') . ' ' . $product['info']['version'];
      }
      else {
        $product_title = ibm_apim_get_translated_string($product, ['info'], 'title');
      } ?>
      <li class="tocItem toc-product"><a
          href="<?php print url('node/' . $productnid); ?>"
          title="<?php print $product_title; ?>"><?php print $product_title; ?></a>
      </li>
      <?php if (isset($product_docpages) && !empty($product_docpages)) : ?>
        <li
          class='tocItem productDocumentationHeader toc-product_pages'>
          <span><?php print t('Documentation'); ?></span>
        </li>
        <?php foreach ($product_docpages as $product_docpage) : ?>
          <?php $product_page = node_load($product_docpage); ?>
          <li
            class='tocItem tocPage toc-pages_<?php print $product_docpage; ?>'>
            <a href="<?php print url('node/' . $product_docpage); ?>"><span
                class=""><?php print $product_page->title; ?></span></a>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
      <li class="tocItem toc-apis"><span><?php print t('APIs'); ?></span></li>
      <li>
        <?php if (isset($apis)) : ?>
        <?php foreach ($apis as $api) : ?>
        <?php if ($showversions == 1) {
          $api_title = ibm_apim_get_translated_string($api, ['info'], 'title') . ' ' . $api['info']['version'];
        }
        else {
          $api_title = ibm_apim_get_translated_string($api, ['info'], 'title');
        } ?>
      <li
        class='tocItem tocApi toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>'>
        <a
          href="<?php print url('productapi/' . $productnid . '/' . ibm_apim_base64_url_encode($api['info']['x-ibm-name'] . ':' . $api['info']['version'])) ?>"
          title="<?php print check_plain($api_title); ?>"><?php print check_plain($api_title); ?></a>
      </li>
    <?php if ($api_ref[0]['value'] == $api['info']['x-ibm-name'] . ':' . $api['info']['version']) : ?>
      <ul
        class="toc-container toc-container-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>">
        <ul>
          <?php if (isset($docpages) && !empty($docpages)) : ?>
            <li
              class='tocItem apiDocumentationHeader toc-api_pages'>
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
          <li
            class='tocItem operationsHeader toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_ops'>
            <span><?php print t('Operations'); ?></span>
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
                <?php if (isset($protocol) && strtolower($protocol) == 'wsdl' && (isset($operation['operationId']) || isset($operation['x-ibm-soap']['soap-action']))) {
                  if (isset($operation['operationId'])) {
                    $linktitle = $operation['operationId'];
                  }
                  else {
                    $linktitle = $operation['x-ibm-soap']['soap-action'];
                    $parts = explode(':', $linktitle);
                    if (isset($parts[0]) && $parts[0] == 'urn' && isset($parts[1])) {
                      $linktitle = $parts[1];
                    }
                  }
                }
                else {
                  $linktitle = strtoupper($verb) . ' ' . $pathSegment;
                } ?>
                <li
                  class='tocItem operation toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>'>
                  <a class="<?php print strtolower($verb); ?>"
                     onclick="API.navigateop('apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>', 'apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>')"
                     href="javascript:;"
                     title="<?php print $linktitle; ?>"><?php print $linktitle; ?></a>
                </li>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </ul>
        <?php if (isset($api['definitions']) && !empty($api['definitions'])) : ?>
          <ul>
            <li
              class='tocItem definitionsHeader toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_definitions'>
              <span><?php print t('Definitions'); ?></span>
            </li>
            <?php foreach ($api['definitions'] as $definitionName => $definition) : ?>
              <li
                class='tocItem definition toc-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_definitions_<?php print drupal_html_class(preg_replace("/\W/", "", $definitionName)); ?>'>
                <a class="def"
                   onclick="API.navigatedefs('<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>', '<?php print drupal_html_class(preg_replace("/\W/", "", $definitionName)); ?>')"
                   href="javascript:;"
                   title="<?php print $definitionName; ?>"><?php print $definitionName; ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </ul>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>
      </li>
    </ul>
  </nav>
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
