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
$showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1);
$showversions = variable_get('ibm_apim_show_versions', 1);
$ibm_apim_enable_api_test = variable_get('ibm_apim_enable_api_test', 1);
$ibm_apim_soap_codesnippets = variable_get('ibm_apim_soap_codesnippets', FALSE);
$ibm_apim_soap_swagger_download = variable_get('ibm_apim_soap_swagger_download', FALSE);
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
global $user;
if (isset($user->uid)) {
  // need fully populated user object
  $user = user_load($user->uid);
}
// set default to the first enabled language
$preferred_lang = array_search(1, $codesnippets);
// if user has set a preference and its an enabled language then honour it
if (isset($user->uid) && isset($user->field_codesnippet['und'][0]['value'])) {
  if (array_key_exists($user->field_codesnippet['und'][0]['value'], $codesnippets) && $codesnippets[$user->field_codesnippet['und'][0]['value']] == 1) {
    $preferred_lang = $user->field_codesnippet['und'][0]['value'];
  }
}
$index = 0;
$apim_session = &_ibm_apim_get_apim_session();
$protocol_lower = strtolower($api_protocol[0]['value']);
if (isset($protocol_lower) && $protocol_lower == 'wsdl') {
  $protocol = 'wsdl';
}
else {
  $protocol = 'rest';
}
if (isset($api['x-ibm-endpoints'][0]['endpointUrl'])) {
  $default_endpoint = $api['x-ibm-endpoints'][0]['endpointUrl'];
}
else {
  if (isset($api['schemes'][0])) {
    $scheme = $api['schemes'][0];
  }
  else {
    $scheme = 'https';
  }
  $default_endpoint = $scheme . '://' . $api['host'];
}
?>
<article id="node-inner-<?php print $node->nid; ?>"
         class="mesh-portal-api <?php print $classes . ' ' . $content['api_apiid'][0]['#markup'] . ' ' . $protocol; ?> apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?> inner clearfix" <?php print $attributes; ?>>
  <div class="navigate-apis">
    <div class="readAndInteract">

      <section
        class="documentation navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>">
        <div class="documentationContent apicSelfClear">
          <div class="titleHeader">
            <div class="apimImage">
              <?php if (isset($content['api_image'])) : ?>
                <div class="apimIcon">
                  <?php print render($content['api_image']); ?>
                </div>
              <?php elseif ($showplaceholders != 0) : ?>
                <div class="apimIcon">
                  <div
                    class="field field-name-api-image field-type-image field-label-hidden view-mode-teaser">
                    <div class="field-items">
                      <figure class="clearfix field-item even">
                        <img typeof="foaf:Image" class="image-style-none"
                             src="<?php print file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/api/' . api_random_image($node->title)); ?>"
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
              <span><?php print check_plain(ibm_apim_get_translated_string($api, ['info'], 'title')); ?> <?php if ($showversions == 1): ?>
                  <span
                    class="version"><?php print check_plain($api['info']['version']); ?></span><?php endif; ?></span>
            </h1>
            <div class="downloadButton">
              <?php if (strtolower($protocol) != 'wsdl' || $ibm_apim_soap_swagger_download == TRUE) : ?>
                <a
                  href="<?php print url('ibm_apim/swaggerjson/' . ibm_apim_base64_url_encode($api['info']['x-ibm-name'] . ':' . $api['info']['version'])) ?>"
                  target='_blank'>
                  <button style="margin-top: -12px;"
                          title="<?php print t('Download'); ?>">
                    <span
                      class="material-icons">file_download</span> <?php print t('Open API'); ?>
                  </button>
                </a>
              <?php endif; ?>
              <?php if (isset($protocol) && strtolower($protocol) == 'wsdl') : ?>
                <div class="protocol">SOAP</div>
              <?php endif; ?>
              <?php if (user_is_logged_in() && node_access("update", $node, $user) === TRUE) : ?>
                <span class="edit"><a
                    href="<?php print url('node/' . $node->nid . '/edit'); ?>"><i
                      class="material-icons" title="<?php print t('Edit'); ?>">mode_edit</i></a></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="info">
            <div class="apiRating">
              <?php if (isset($content['field_apirating'])) {
                $content['field_apirating']['#label_display'] = 'hidden';
                print render($content['field_apirating']);
              } ?>
            </div>
            <div class="apiTags">
              <?php if (isset($api['tags']) && is_array($api['tags'])) : ?>
                <?php foreach ($api['tags'] as $key=>$tag): ?>
                  <?php if (isset($tag['description'])) {
                    $tagdescr = 'title="' . ibm_apim_get_translated_string($api, ['tags', $key], 'description') . '"';
                  }
                  else {
                    $tagdescr = '';
                  } ?>

                  <span
                    class='apiTag <?php print api_tag_to_class($tag['name']); ?> <?php print drupal_html_class($tag['name']); ?>' <?php print $tagdescr; ?>>
                    <a
                      onclick='API.selecttag("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print drupal_html_class($tag['name']); ?>")'
                      title="<?php print t('Filter operations by @tag', array('@tag' => ibm_apim_get_translated_string($tag, array(), 'name'))); ?>"
                      href='javascript:;'><span
                        class="apiName"><?php print ibm_apim_get_translated_string($tag, array(), 'name'); ?></span></a> <a
                      onclick='API.unselecttag("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>")'
                      title="<?php print t('Clear filter'); ?>"
                      href='javascript:;' class='unselect hidden'><span>x</span></a></span>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <?php if (isset($api['info']['description']) && !empty($api['info']['description'])) : ?>
              <div>
                <label><?php print t('Description'); ?></label>
                <div
                  class="markdown"><?php print ibm_apim_markdown_field(ibm_apim_get_translated_string($api, ['info'], 'description')); ?></div>
              </div>
            <?php endif; ?>
            <?php $docs = render($content['api_attachments']); ?>
            <?php if ((isset($docs) && !empty($docs)) || (isset($api['externalDocs']) && !empty($api['externalDocs']) && isset($api['externalDocs']['url'])) || isset($api['x-ibm-configuration']['externalDocs'])) : ?>
              <div>
                <label><?php print t('Documentation'); ?></label>
                <?php if (isset($api['externalDocs'])) : ?>
                  <div><a href="<?php print $api['externalDocs']['url']; ?>" target='_blank'>
                      <?php if (isset($api['externalDocs']['description'])) {
                        print ibm_apim_get_translated_string($api, ['externalDocs'], 'description');
                      }
                      else {
                        print $api['externalDocs']['url'];
                      } ?>
                    </a></div>
                <?php endif; ?>
                <?php if (isset($api['x-ibm-configuration']['externalDocs'])) : ?>
                  <?php foreach ($api['x-ibm-configuration']['externalDocs'] as $key=>$doc) : ?>
                    <div><a href="<?php print $doc['url']; ?>" target='_blank'>
                        <?php if (isset($doc['description'])) {
                          print ibm_apim_get_translated_string($api, ['x-ibm-configuration', 'externalDocs' ,$key], 'description');
                        }
                        else {
                          print $doc['url'];
                        } ?>
                      </a></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <?php if (isset($docs)) : ?>
                  <div><?php print $docs; ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if (isset($customfields) && is_array($customfields) && count($customfields) > 0) : ?>
              <div class="customFields api">
                <?php foreach ($customfields as $customfield) : ?>
                  <?php print render($content[$customfield]); ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="clearBoth"></div>
          <?php if (isset($api['info']['contact']) && ((isset($api['info']['contact']['name']) && !empty($api['info']['contact']['name'])) || (isset($api['info']['contact']['email']) && !empty($api['info']['contact']['email'])) || (isset($api['info']['contact']['url']) && !empty($api['info']['contact']['url'])))) : ?>
            <div class="contactContainer">
              <div class="contactLink"><a onclick='API.togglesection("contact")'
                                          href='javascript:;'><span
                    class="expand_more"><i
                      class="material-icons">expand_more</i></span><span
                    class="expand_less"><i
                      class="material-icons">expand_less</i></span><span
                    class="contactTitle"><?php print t('Contact information'); ?></span></a>
              </div>
              <div class="contactContent">
                <?php if (isset($api['info']['contact']['name']) && !empty($api['info']['contact']['name'])) : ?>
                  <div><?php print $api['info']['contact']['name']; ?></div>
                <?php endif; ?>
                <?php if (isset($api['info']['contact']['email']) && !empty($api['info']['contact']['email'])) : ?>
                  <div><a
                      href='mailto:<?php print $api['info']['contact']['email']; ?>'><?php print $api['info']['contact']['email']; ?></a>
                  </div>
                <?php endif; ?>
                <?php if (isset($api['info']['contact']['url']) && !empty($api['info']['contact']['url'])) : ?>
                  <div><a href='<?php print $api['info']['contact']['url']; ?>'
                          target='_blank'><?php print $api['info']['contact']['url']; ?></a></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if ((isset($api['info']['license']) && isset($api['info']['license']['url']) && isset($api['info']['license']['name']) && !empty($api['info']['license']['url']) && !empty($api['info']['license']['name'])) || (isset($api['info']['termsOfService']) && !empty($api['info']['termsOfService']))) : ?>
            <div class="licenseContainer">
              <div class="licenseLink"><a onclick='API.togglesection("license")'
                                          href='javascript:;'><span
                    class="expand_more"><i
                      class="material-icons">expand_more</i></span><span
                    class="expand_less"><i
                      class="material-icons">expand_less</i></span><span
                    class="contactTitle"><?php print t('License'); ?></span></a>
              </div>
              <div class="licenseContent">
                <?php if (isset($api['info']['license']) && isset($api['info']['license']['url']) && isset($api['info']['license']['name']) && !empty($api['info']['license']['url']) && !empty($api['info']['license']['name'])) : ?>
                  <div>
                    <label><?php print t('License'); ?></label>
                    <div><a
                        href='<?php print $api['info']['license']['url']; ?>'
                        target='_blank'><?php print $api['info']['license']['name']; ?></a></div>
                  </div>
                <?php endif; ?>
                <?php if (isset($api['info']['termsOfService']) && !empty($api['info']['termsOfService'])) : ?>
                  <div>
                    <label><?php print t('Terms of service'); ?></label>
                    <div><?php print ibm_apim_get_translated_string($api, ['info'], 'termsOfService'); ?></div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          <?php foreach ($api as $childname => $child) : ?>
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
        </div>
      </section>
      <section class="interact" style="padding: 0;">
        <div
          class="rightHeader stick <?php print $protocol . ' ' . strtolower($ibm_apim_soap_codesnippets); ?>">
          <div class="tableWrapper">
            <?php if (($protocol != 'wsdl' || $ibm_apim_soap_codesnippets == TRUE) && isset($codesnippets)) : ?>
              <ul class="langs-menu">
                <?php $currentClass = ''; ?>
                <?php if (isset($codesnippets['curl']) && $codesnippets['curl'] == 1): ?>
                  <?php $preferred_lang == 'curl' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#curl">cURL</a>
                  </li>
                <?php endif; ?>
                <?php if (isset($codesnippets['ruby']) && $codesnippets['ruby'] == 1): ?>
                  <?php $preferred_lang == 'ruby' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#ruby">Ruby</a>
                  </li>
                <?php endif; ?>
                <?php if (isset($codesnippets['python']) && $codesnippets['python'] == 1): ?>
                  <?php $preferred_lang == 'python' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#python">Python</a>
                  </li>
                <?php endif; ?>
                <?php if (isset($codesnippets['php']) && $codesnippets['php'] == 1): ?>
                  <?php $preferred_lang == 'php' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#php">PHP</a>
                  </li>
                <?php endif; ?>
                <?php if (isset($codesnippets['java']) && $codesnippets['java'] == 1): ?>
                  <?php $preferred_lang == 'java' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#java">Java</a>
                  </li>
                <?php endif; ?>
                <?php if (isset($codesnippets['node']) && $codesnippets['node'] == 1): ?>
                  <?php $preferred_lang == 'node' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#node">Node</a>
                  </li>
                <?php endif; ?>
                <?php if (isset($codesnippets['go']) && $codesnippets['go'] == 1): ?>
                  <?php $preferred_lang == 'go' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#go">Go</a>
                  </li>
                <?php endif; ?>
                <?php if (isset($codesnippets['swift']) && $codesnippets['swift'] == 1): ?>
                  <?php $preferred_lang == 'swift' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#swift">Swift</a>
                  </li>
                <?php endif; ?>
                <?php if (isset($codesnippets['c']) && $codesnippets['c'] == 1): ?>
                  <?php $preferred_lang == 'c' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#c">C</a>
                  </li>
                <?php endif; ?>
                <?php if (isset($codesnippets['csharp']) && $codesnippets['csharp'] == 1): ?>
                  <?php $preferred_lang == 'csharp' ? $currentClass = ' current' : $currentClass = ''; ?>
                  <li class="language<?php print $currentClass; ?>"><a
                      href="#csharp">C#</a>
                  </li>
                <?php endif; ?>
              </ul>
            <?php endif; ?>
            <?php if (isset($productnid)) {
              $subscribeurl = url('node/' . $productnid, array(), 'plans');
            }
            else {
              $subscribeurl = url('product/select/' . $node->nid);
            } ?>
            <div class="subscribeButton"><div class="subscribeButtonInner"><a
                  style="font-weight: 500;"
                  href="<?php print $subscribeurl; ?>"><?php print t('Subscribe'); ?></a></div></div>
          </div>
        </div>
        <div style="padding: 50px 15px 15px 15px;"
        "<?php print $content_attributes; ?>">

        <?php hide($content['comments']);
        hide($content['links']); ?>

        <?php if (isset($content['field_apitags'])) {
          print "<label>" . t('Taxonomies') . "</label>";
          print render($content['field_apitags']);
        }
        if (module_exists('forum')) {
          $autocreate_forum = variable_get('ibm_apim_autocreate_apiforum', 1);
          if ($autocreate_forum == 1 && isset($apiforumlink)) {
            print "<label>" . t('Support') . "</label>";
            print "<div class=\"apiForumLink\">" . $apiforumlink . "</div>";
          }
        }
        ?>
      </section>
    </div>
    <?php if (isset($api['x-ibm-endpoints'])) : ?>
      <div class="readAndInteract endpoints">
        <section class="documentation">
          <div class="apiEndpointContainer">
            <label><?php print t('Endpoints'); ?></label>
            <div class='apiEndpoints'>
              <?php foreach ($api['x-ibm-endpoints'] as $key => $endpoint) : ?>
                <div class='apiEndpoint'>
                  <div class='apiEndpointTypes'>
                    <?php foreach ($endpoint['type'] as $type) : ?>
                      <div
                        class='apiEndpointType <?php print drupal_html_class($type); ?>'><?php print $type; ?></div>
                    <?php endforeach; ?>
                  </div>
                  <div
                    class='apiEndpointUrl'><?php print $endpoint['endpointUrl']; ?></div>
                  <?php if (isset($endpoint['description'])) : ?>
                    <div
                      class='apiEndpointDescr'><?php print ibm_apim_markdown_field(ibm_apim_get_translated_string($endpoint, array(), 'description')); ?></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
        <section class="interact">
          <div class="contrast endpointSelect">
            <select name="endpoints"
                    data-api="<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>"
                    class="endpointSelect navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_endpoints">
              <?php foreach ($api['x-ibm-endpoints'] as $endpoint)  : ?>
                <option
                  value="<?php print $endpoint['endpointUrl']; ?>"><?php print $endpoint['endpointUrl']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </section>
      </div>
    <?php endif; ?>
    <div class="pathHeading">
      <div class='left'>
        <?php if ($protocol == 'wsdl') : ?>
          <h2><?php print t('Operations'); ?></h2>
        <?php else: ?>
          <h2><?php print t('Paths'); ?></h2>
        <?php endif; ?>
      </div><div
        class='right'></div>
    </div>

    <?php foreach ($api['paths'] as $pathSegment => $path) : ?>
      <div class='pathWrapper'>
        <?php if ($protocol != 'wsdl') : ?>
          <div class='pathTitleWrapper'><div class='pathTitle left'>
              <h3><?php print $pathSegment ?></h3>
            </div><div
              class='pathTitle right'></div></div>
        <?php endif; ?>
        <div
          class='pathDetails navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths'>
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
              <?php $optags = ''; ?>
              <?php if (isset($operation['tags']) && is_array($operation['tags'])) : ?>
                <?php foreach ($operation['tags'] as $tagname): ?>
                  <?php $optags .= ' operation-tag-' . drupal_html_class($tagname); ?>
                <?php endforeach; ?>
              <?php endif; ?>
              <div
                class='readAndInteract operation <?php print $optags; ?> opwrapper-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>'>
                <section
                  class='documentation navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>'>
                  <div
                    class='operationDetails <?php print $verb; ?> navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>'>
                    <div class='header bottomBorder stickyHeader'>
                      <?php if ($protocol != 'wsdl') : ?>
                        <?php if (isset($operation['deprecated']) && $operation['deprecated'] == TRUE) : ?>
                          <span
                            class='deprecated'><?php print t('Deprecated'); ?></span>
                        <?php endif; ?>
                        <span
                          class='verb'><?php print $verb; ?></span><?php print " " . $pathSegment; ?>
                      <?php else : ?>
                        <?php $parts = explode('/', $pathSegment);
                        print end($parts);
                        ?>
                      <?php endif; ?>
                    </div>
                    <div class='operationContent'>
                      <div class="apiTags">
                        <?php if (isset($operation['tags']) && is_array($operation['tags'])) : ?>
                          <?php foreach ($operation['tags'] as $tagname): ?>
                            <?php $tagfound = FALSE;
                            $description = null;
                            $tag_title = null; ?>
                            <?php if (isset($api['tags']) && is_array($api['tags'])) {
                              foreach ($api['tags'] as $tagkey => $tag) {
                                if (isset($tag['name']) && $tag['name'] == $tagname) {
                                  $tagfound = TRUE;
                                  $tag_title = ibm_apim_get_translated_string($tag, array(), 'name');
                                  if (isset($tag['description'])) {
                                    $description = ibm_apim_get_translated_string($tag, array(), 'description');
                                  }
                                }
                              }
                              if ($tagfound != TRUE) {
                                $tag_title = $tagname;
                              }
                            } else {
                              $tag_title = $tagname;
                            } ?>
                            <?php if ($tagfound == TRUE && isset($description)) {
                              $tagdescr = 'title="' . $description . '"';
                            }
                            else {
                              $tagdescr = '';
                            } ?>
                            <span
                              class='apiTag <?php print api_tag_to_class($tagname); ?>' <?php print $tagdescr; ?>><?php print $tag_title; ?> </span>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>
                      <?php if (isset($operation['summary']) && !empty($operation['summary'])) : ?>
                        <div>
                          <label><?php print t('Summary'); ?></label>
                          <div
                            class="markdown"><?php print ibm_apim_markdown_field(ibm_apim_get_translated_string($operation, array(), 'summary')); ?></div>
                        </div>
                      <?php endif; ?>
                      <?php if (isset($operation['description']) && !empty($operation['description'])) : ?>
                        <div>
                          <label><?php print t('Description'); ?></label>
                          <div
                            class="markdown"><?php print ibm_apim_markdown_field(ibm_apim_get_translated_string($operation, array(), 'description')); ?></div>
                        </div>
                      <?php endif; ?>
                      <?php if (isset($operation['externalDocs']) && !empty($operation['externalDocs']) && isset($operation['externalDocs']['url'])) : ?>
                        <div>
                          <label><?php print t('Documentation'); ?></label>
                          <div><a
                              href="<?php print $operation['externalDocs']['url']; ?>"
                              target='_blank'>
                              <?php if (isset($operation['externalDocs']['description'])) {
                                print ibm_apim_get_translated_string($api, ['paths', $pathSegment, $verb, 'externalDocs'], 'description');
                              }
                              else {
                                print $operation['externalDocs']['url'];
                              } ?>
                            </a></div>
                        </div>
                      <?php endif; ?>
                      <div class="security">
                        <?php if (isset($operation['security'])) {
                          $securityRequirements = $operation['security'];
                        }
                        elseif (isset($api['security'])) {
                          $securityRequirements = $api['security'];
                        }
                        else {
                          $securityRequirements = NULL;
                        } ?>
                        <?php if (isset($securityRequirements)) : ?>
                          <label><?php print t('Security'); ?></label>
                          <?php if (count($securityRequirements) > 1) : ?>
                            <div
                              class="securityMessage"><?php print t('To use this operation you must use one of the following sets of security requirements.'); ?></div>
                          <?php endif; ?>
                          <?php foreach ($securityRequirements as $securityArray) : ?>
                            <div
                              class='securityRequirement count<?php print count($securityRequirements); ?>'>
                              <?php foreach ($securityArray as $securityDefName => $securityDef) : ?>
                                <div class='securitySection listContent'>
                                  <div
                                    class='key'><?php print $securityDefName; ?></div>
                                  <div
                                    class='name'><?php if (isset($api['securityDefinitions'][$securityDefName]['name'])) {
                                      print $api['securityDefinitions'][$securityDefName]['name'];
                                    }
                                    else {
                                      print $securityDefName;
                                    } ?>
                                  </div><div
                                    class='type'>(<?php if (isset($api['securityDefinitions'][$securityDefName]['in'])) {
                                      print t('@type located in @in', array(
                                        '@type' => $api['securityDefinitions'][$securityDefName]['type'],
                                        '@in' => $api['securityDefinitions'][$securityDefName]['in']
                                      ));
                                    }
                                    else {
                                      print $api['securityDefinitions'][$securityDefName]['type'];
                                      if (isset($api['securityDefinitions'][$securityDefName]['flow'])) {
                                        print " " . $api['securityDefinitions'][$securityDefName]['flow'];
                                      }
                                    } ?>)
                                  </div><div class='description markdown'>
                                    <?php
                                      if(isset($api['securityDefinitions'][$securityDefName]['description'])) {
                                        print ibm_apim_markdown_field($api['securityDefinitions'][$securityDefName]['description']);
                                      }
                                    ?>
                                    <?php if (isset($operation['security'])) {
                                      $operation_array_length = count($operation['security']);
                                      if (isset($api['securityDefinitions'][$securityDefName]['scopes'])) {
                                        $api_scopes_array = $api['securityDefinitions'][$securityDefName]['scopes'];
                                        if (isset($api_scopes_array) && ($operation_array_length > 0) ){
                                          $scope_array = array();
                                          for($x = 0; $x < $operation_array_length; $x++) {
                                            if (isset($operation['security'][$x][$securityDefName])){
                                              $scope = $operation['security'][$x][$securityDefName];
                                              for($y = 0; $y < count($scope); $y++) {
                                                $scope_array[$scope[$y]] = $api_scopes_array[$scope[$y]];
                                              }
                                            }
                                          }
                                        }
                                      }
                                    }
                                    else if (isset($api['securityDefinitions'][$securityDefName]['scopes'])) {
                                      $scope_array = $api['securityDefinitions'][$securityDefName]['scopes'];
                                    } ?>
                                    <?php if (isset($api['securityDefinitions'][$securityDefName]['type']) && strtolower($api['securityDefinitions'][$securityDefName]['type']) == 'oauth2'): ?>
                                      <?php if (isset($api['securityDefinitions'][$securityDefName]['authorizationUrl']) && !empty($api['securityDefinitions'][$securityDefName]['authorizationUrl'])) : ?>
                                        <div class="authURL">
                                          <div
                                            class="label"><?php print t('Authorization URL'); ?></div>
                                          <div
                                            class="value"><?php print $api['securityDefinitions'][$securityDefName]['authorizationUrl']; ?></div>
                                        </div>
                                      <?php endif; ?>
                                      <?php if (isset($api['securityDefinitions'][$securityDefName]['tokenUrl']) && !empty($api['securityDefinitions'][$securityDefName]['tokenUrl'])) : ?>
                                        <div class="tokenURL">
                                          <div
                                            class="label"><?php print t('Token URL'); ?></div>
                                          <div
                                            class="value"><?php print $api['securityDefinitions'][$securityDefName]['tokenUrl']; ?></div>
                                        </div>
                                      <?php endif; ?>
                                      <?php if (isset($scope_array) && !empty($scope_array)) : ?>
                                        <div class="scope">
                                          <div
                                            class="label"><?php print t('Scopes'); ?></div>
                                          <?php foreach ($scope_array as $name => $description) : ?>
                                            <div class="scopeWrapper">
                                              <div
                                                class="scopeName"><?php print $name; ?></div>
                                              <div
                                                class="scopeDescription"><?php print $description; ?></div>
                                            </div>
                                          <?php endforeach; ?>
                                        </div>
                                      <?php endif; ?>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>

                      <?php if ($protocol != 'wsdl') : ?>
                        <?php
                        // build up parameters list
                        $operation_parameters = array();
                        $bodypresent = FALSE;
                        $formDataPresent = FALSE;
                        if (isset($path['parameters']) && is_array($path['parameters'])) {
                          foreach ($path['parameters'] as $key => $parameter) {
                            if (isset($parameter['$ref'])) {
                              // handle parameter references
                              $parameter = api_get_ref_param($api, $parameter['$ref']);
                            }
                            // check for a body parameter
                            if (isset($parameter['in']) && $parameter['in'] == "body") {
                              $bodypresent = TRUE;
                            }
                            if (isset($parameter['in']) && $parameter['in'] == "formData") {
                              $formDataPresent = TRUE;
                            }
                            $operation_parameters[$parameter['name']] = $parameter;
                          }
                        }
                        if (isset($operation['parameters']) && is_array($operation['parameters'])) {
                          foreach ($operation['parameters'] as $key => $parameter) {
                            if (isset($parameter['$ref'])) {
                              // handle parameter references
                              $parameter = api_get_ref_param($api, $parameter['$ref']);
                            }
                            $operation_parameters[$parameter['name']] = $parameter;
                            // check for a body parameter
                            if (isset($parameter['in']) && $parameter['in'] == "body") {
                              $bodypresent = TRUE;
                            }
                            if (isset($parameter['in']) && $parameter['in'] == "formData") {
                              $formDataPresent = TRUE;
                            }
                          }
                        }
                        $operation_parameters_lower = array_change_key_case($operation_parameters, CASE_LOWER);
                        ?>
                        <?php if ((isset($operation_parameters) && is_array($operation_parameters) && !empty($operation_parameters)) || (isset($operation['consumes']) || isset($api['consumes'])) && $bodypresent == TRUE && !isset($operation_parameters_lower['content-type']) || (isset($operation['produces']) || isset($api['produces'])) && !isset($operation_parameters_lower['accept'])): ?>
                          <div class="parameters">
                            <label><?php print t('Parameters'); ?></label>

                            <?php if (isset($operation_parameters) && is_array($operation_parameters)): ?>
                              <?php foreach ($operation_parameters as $key => $parameter) : ?>
                                <?php if (isset($parameter['in']) && ($parameter['in'] == "path")) {
                                  // path parameters are always required
                                  $parameter['required'] = "true";
                                } ?>

                                <div class='parametersSection listContent'>
                                  <div class='name'><div
                                      class="title"><?php print $parameter['name']; ?></div>
                                    <div class='located-in'><?php
                                      if (isset($parameter['required']) && $parameter['required'] == TRUE) {
                                        print t('<span class="required">Required</span> in %1', array('%1' => $parameter['in']));
                                      }
                                      else {
                                        print t('Optional in %1', array('%1' => $parameter['in']));
                                      } ?>
                                      <?php
                                      $type = 'object';
                                      unset($format);
                                      if (isset($parameter['schema']['type']) && !empty($parameter['schema']['type'])) {
                                        $type = $parameter['schema']['type'];
                                      }
                                      elseif (isset($parameter['type']) && !empty($parameter['type'])) {
                                        $type = $parameter['type'];
                                      }
                                      elseif (isset($parameter) && (isset($parameter['schema']['type']) && !empty($parameter['schema']['type']))) {
                                        $type = $parameter['schema']['type'];
                                      }
                                      elseif (isset($parameter['type']) && !empty($parameter['type'])) {
                                        $type = $parameter['type'];
                                      }
                                      if (isset($parameter['schema']['format']) && !empty($parameter['schema']['format'])) {
                                        $format = $parameter['schema']['format'];
                                      }
                                      elseif (isset($parameter['format']) && !empty($parameter['format'])) {
                                        $format = $parameter['format'];
                                      }
                                      elseif (isset($parameter) && (isset($parameter['schema']['format']) && !empty($parameter['schema']['format']))) {
                                        $format = $parameter['schema']['format'];
                                      }
                                      elseif (isset($parameter['format']) && !empty($parameter['format'])) {
                                        $format = $parameter['format'];
                                      }
                                      if (isset($type)) {
                                        print "<div class='parameterType'>" . $type;
                                        if (isset($format)) {
                                          print " / " . $format;
                                        }
                                        print "</div>";
                                      }
                                      ?>
                                    </div></div><div class="schemawrapper">
                                    <?php if (isset($parameter['description'])): ?>
                                      <div
                                        class='description markdown'><?php print ibm_apim_markdown_field(ibm_apim_get_translated_string($operation_parameters, [$key], 'description')); ?></div>
                                    <?php endif; ?><div
                                      class='schema'>
                                      <?php if (isset($parameter) && isset($parameter['schema']) && isset($parameter['schema']['$ref'])) : ?>
                                        <a
                                          onclick='API.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print drupal_html_class(preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($parameter['schema']['$ref']))); ?>")'
                                          href='javascript:;'><?php print ibm_apim_return_schema($parameter); ?></a>
                                      <?php elseif (isset($parameter) && isset($parameter['schema']['type']) && $parameter['schema']['type'] == "array" && isset($parameter['schema']['items']) && isset($parameter['schema']['items']['$ref'])) : ?>
                                        <a
                                          onclick='API.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print drupal_html_class(preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($parameter['schema']['items']['$ref']))); ?>")'
                                          href='javascript:;'><?php print ibm_apim_return_schema($parameter); ?></a>
                                      <?php elseif (isset($parameter) && (isset($parameter['schema']['type']) && !empty($parameter['schema']['type'])) || (isset($parameter['type']) && !empty($parameter['type']))) : ?>
                                        <?php
                                        $inline_schema = ibm_apim_return_inline_schema($parameter);
                                        if (isset($inline_schema) && !empty($inline_schema)) {
                                          print "<pre
                                          class='inlineSchema'><code>" . json_encode($inline_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</code></pre>";
                                        }
                                        ?>
                                      <?php elseif (isset($parameter) && (isset($parameter['schema']['type']) && !empty($parameter['schema']['type'])) || (isset($parameter['type']) && !empty($parameter['type']))) : ?>
                                        <?php $inline_schema = ibm_apim_return_inline_schema($parameter);
                                        if (isset($inline_schema) && !empty($inline_schema)) {
                                          print "<pre
                                          class='inlineSchema'><code>" . json_encode($inline_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</code></pre>";
                                        }
                                        ?>
                                      <?php else: ?>
                                        <?php print ibm_apim_return_schema($parameter); ?>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ((isset($operation['consumes']) || isset($api['consumes'])) && ($bodypresent == TRUE || $formDataPresent == TRUE) && !isset($operation_parameters_lower['content-type'])) : ?>
                              <div class='parametersSection listContent'>
                                <div class='name'><div
                                    class="title">Content-Type</div>
                                  <div
                                    class='located-in'><?php print t('Optional in %1', array('%1' => 'header')); ?>
                                  </div>
                                  <div
                                    class="parameterType">string</div></div><div
                                  class="schemawrapper"><div
                                    class='description'></div><div
                                    class='schema'>
                                    <?php if (isset($operation['consumes'])) : ?>
                                      <?php foreach ($operation['consumes'] as $contenttype) : ?>
                                        <?php print $contenttype; ?><br/>
                                      <?php endforeach; ?>
                                    <?php elseif (isset($api['consumes'])) : ?>
                                      <?php foreach ($api['consumes'] as $contenttype) : ?>
                                        <?php print $contenttype; ?><br/>
                                      <?php endforeach; ?>
                                    <?php endif; ?>
                                  </div></div>
                              </div>
                            <?php endif; ?>
                            <?php if ((isset($operation['produces']) || isset($api['produces'])) && !isset($operation_parameters_lower['accept'])) : ?>
                              <div class='parametersSection listContent'>
                                <div class='name'><div
                                    class="title">Accept</div>
                                  <div
                                    class='located-in'><?php print t('Optional in %1', array('%1' => 'header')); ?>
                                  </div>
                                  <div
                                    class="parameterType">string</div></div><div
                                  class="schemawrapper"><div
                                    class='description'></div><div
                                    class='schema'>
                                    <?php if (isset($operation['produces'])) : ?>
                                      <?php foreach ($operation['produces'] as $contenttype) : ?>
                                        <?php print $contenttype; ?><br/>
                                      <?php endforeach; ?>
                                    <?php elseif (isset($api['produces'])) : ?>
                                      <?php foreach ($api['produces'] as $contenttype) : ?>
                                        <?php print $contenttype; ?><br/>
                                      <?php endforeach; ?>
                                    <?php endif; ?>
                                  </div></div>
                              </div>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                      <?php else: ?>
                        <div><label><?php print t('Request'); ?></label></div>
                        <?php if (isset($operation['parameters']) && is_array($operation['parameters'])): ?>
                          <?php foreach ($operation['parameters'] as $parameter) : ?>
                            <?php if (isset($parameter['in']) && $parameter['in'] == 'body') : ?>
                              <div>
                                <?php if (isset($parameter['schema']['example'])) {
                                  $schema_example = $parameter['schema']['example'];
                                }
                                else {
                                  $schema_example = $parameter['schema'];
                                }
                                // could be a $ref that needs resolving
                                if(!empty($schema_example['$ref'])) {
                                  $schema_example = api_get_ref_definition($api, $schema_example['$ref']);
                                  $schema_example = $schema_example['example'];
                                }
                                ?>
                                <?php if (strlen($schema_example) > 1000) : ?>
                                  <?php $truncated = mb_substr($schema_example, 0, 1000); ?>
                                  <pre
                                    class="truncate hljs inlineSchema"><code><?php print htmlspecialchars($truncated); ?></code></pre>
                                  <div class="showMore"><a class="btn"
                                                           data-popup-open="popup-<?php print $index; ?>"
                                                           href="#"><?php print t('Show more'); ?>
                                      <i
                                        class="material-icons">open_in_new</i></a></div>
                                  <div class="popup"
                                       data-popup="popup-<?php print $index; ?>"
                                       style="display: none;">
                                    <div class="popup-inner">
                                        <pre
                                          class="hljs inlineSchema">
                                          <code><?php print htmlspecialchars($schema_example); ?></code>
                                        </pre>
                                      <p><a
                                          data-popup-close="popup-<?php print $index; ?>"
                                          href="#"><?php t('Close'); ?></a>
                                      </p>
                                      <a class="popup-close"
                                         data-popup-close="popup-<?php print $index; ?>"
                                         href="#">x</a>
                                    </div>
                                  </div>
                                  <?php $index++; ?>
                                <?php else: ?>
                                  <pre
                                    class="hljs inlineSchema"><code><?php print htmlspecialchars($schema_example); ?></code></pre>
                                <?php endif; ?>
                              </div>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      <?php endif; ?>
                      <?php if (isset($operation['responses']) && $protocol != 'wsdl') : ?>
                        <div class="responses">
                          <label><?php print t('Responses'); ?></label>
                          <?php foreach ($operation['responses'] as $code => $response) : ?>
                            <?php $expanded_response = $operation['responses'][$code]; ?>
                            <div class='responsesSection listContent'>
                              <div class='code'><?php print $code; ?>
                              </div><div class="responseContent"><div
                                  class='description markdown'><?php print ibm_apim_markdown_field(ibm_apim_get_translated_string($response, array(), 'description')); ?></div><div
                                  class='schema'>
                                  <?php if (isset($response) && isset($response['$ref'])) {
                                    $ref = _ibm_apim_ref_to_objectname($response['$ref']);
                                    $response = $api['responses'][$ref];
                                  }
                                  ?>
                                  <?php if (isset($response) && isset($response['schema']) && isset($response['schema']['$ref'])) : ?>
                                    <a
                                      onclick='API.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print drupal_html_class(preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($response['schema']['$ref']))); ?>")'
                                      href='javascript:;'><?php print ibm_apim_return_schema($response); ?></a>
                                  <?php elseif (isset($response) && isset($response['schema']['type']) && $response['schema']['type'] == "array" && isset($response['schema']['items']) && isset($response['schema']['items']['$ref'])) : ?>
                                    <a
                                      onclick='API.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print drupal_html_class(preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($response['schema']['items']['$ref']))); ?>")'
                                      href='javascript:;'><?php print ibm_apim_return_schema($response); ?></a>
                                  <?php elseif (isset($expanded_response) && isset($expanded_response['schema']['type']) && !empty($expanded_response['schema']['type'])) : ?>
                                    <?php $response_json = json_encode(ibm_apim_return_inline_schema($expanded_response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>
                                    <?php if (strlen($response_json) > 1000) : ?>
                                      <?php $truncated = mb_substr($response_json, 0, 1000); ?>
                                      <pre
                                        class="truncate hljs inlineSchema"><code><?php print $truncated; ?></code></pre>
                                      <div class="showMore"><a class="btn"
                                                               data-popup-open="popup-<?php print $index; ?>"
                                                               href="#"><?php print t('Show more'); ?>
                                          <i
                                            class="material-icons">open_in_new</i></a></div>
                                      <div class="popup"
                                           data-popup="popup-<?php print $index; ?>"
                                           style="display: none;">
                                        <div class="popup-inner">
                                        <pre
                                          class="hljs inlineSchema">
                                          <code><?php print $response_json; ?></code>
                                        </pre>
                                          <p><a
                                              data-popup-close="popup-<?php print $index; ?>"
                                              href="#"><?php t('Close'); ?></a>
                                          </p>
                                          <a class="popup-close"
                                             data-popup-close="popup-<?php print $index; ?>"
                                             href="#">x</a>
                                        </div>
                                      </div>
                                      <?php $index++; ?>
                                    <?php else: ?>
                                      <pre
                                        class="hljs inlineSchema"><code><?php print $response_json; ?></code></pre>
                                    <?php endif; ?>
                                  <?php endif; ?>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </section>
                <section class='interact'>
                  <div
                    class='tabs-content content-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>'>
                    <?php if (($protocol != 'wsdl' || $ibm_apim_soap_codesnippets == TRUE) && isset($codesnippets)) : ?>
                      <div
                        id='tab-content_code_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . '_' . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>'
                        class='tab-content code'>
                        <div
                          class="rightSectionHeading"><?php print t('Example Request'); ?></div>
                        <div class="langtab">
                          <?php if (isset($codesnippets['curl']) && $codesnippets['curl'] == 1): ?>
                            <?php $preferred_lang == 'curl' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-curl"
                              class="curl langtab-content <?php print $showclass; ?>">
                              <pre class="truncatedPre bash"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="bash"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>

                            </div>
                          <?php endif; ?>
                          <?php if (isset($codesnippets['ruby']) && $codesnippets['ruby'] == 1): ?>
                            <?php $preferred_lang == 'ruby' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-ruby"
                              class="ruby langtab-content <?php print $showclass; ?>">
                              <pre class="truncatedPre ruby"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="ruby"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (isset($codesnippets['python']) && $codesnippets['python'] == 1): ?>
                            <?php $preferred_lang == 'python' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-python"
                              class="python langtab-content <?php print $showclass; ?>">
                              <pre
                                class="truncatedPre python"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="python"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (isset($codesnippets['php']) && $codesnippets['php'] == 1): ?>
                            <?php $preferred_lang == 'php' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-php"
                              class="php langtab-content <?php print $showclass; ?>">
                              <pre class="truncatedPre php"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="php"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (isset($codesnippets['java']) && $codesnippets['java'] == 1): ?>
                            <?php $preferred_lang == 'java' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-java"
                              class="java langtab-content <?php print $showclass; ?>">
                              <pre class="truncatedPre java"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="java"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (isset($codesnippets['node']) && $codesnippets['node'] == 1): ?>
                            <?php $preferred_lang == 'node' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-node"
                              class="node langtab-content <?php print $showclass; ?>">
                              <pre class="truncatedPre javascript"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="javascript"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (isset($codesnippets['go']) && $codesnippets['go'] == 1): ?>
                            <?php $preferred_lang == 'go' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-go"
                              class="go langtab-content <?php print $showclass; ?>">
                              <pre class="truncatedPre go"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="go"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (isset($codesnippets['swift']) && $codesnippets['swift'] == 1): ?>
                            <?php $preferred_lang == 'swift' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-swift"
                              class="swift langtab-content <?php print $showclass; ?>">
                              <pre
                                class="truncatedPre swift"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="swift"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (isset($codesnippets['c']) && $codesnippets['c'] == 1): ?>
                            <?php $preferred_lang == 'c' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-c"
                              class="c langtab-content <?php print $showclass; ?>">
                              <pre class="truncatedPre cpp"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="cpp"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>
                            </div>
                          <?php endif; ?>
                          <?php if (isset($codesnippets['csharp']) && $codesnippets['csharp'] == 1): ?>
                            <?php $preferred_lang == 'csharp' ? $showclass = ' show' : $showclass = ''; ?>
                            <div
                              id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-csharp"
                              class="csharp langtab-content <?php print $showclass; ?>">
                              <pre class="truncatedPre cs"><code></code></pre>
                              <div class="showMore hidden"><a class="btn"
                                                              data-popup-open="popup-<?php print $index; ?>"
                                                              href="#"><?php print t('Show more'); ?>
                                  <i
                                    class="material-icons">open_in_new</i></a></div>
                              <div class="popup"
                                   data-popup="popup-<?php print $index; ?>"
                                   style="display: none;">
                                <div class="popup-inner">
                                  <pre class="cs"><code></code></pre>
                                  <p><a
                                      data-popup-close="popup-<?php print $index; ?>"
                                      href="#"><?php t('Close'); ?></a>
                                  </p>
                                  <a class="popup-close"
                                     data-popup-close="popup-<?php print $index; ?>"
                                     href="#">x</a>
                                </div>
                              </div>
                              <?php $index++; ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <div
                      id='tab-content_example_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . '_paths_' . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>'
                      class='tab-content example'>
                      <div
                        class="rightSectionHeading"><?php print t('Example Response'); ?></div>
                      <?php if ($protocol != 'wsdl') : ?>
                        <label><?php print t('Definition'); ?></label>
                        <?php $url = $default_endpoint . $api['basePath'] . $pathSegment;
                        $url_escaped = preg_replace('/(https?:\/\/)|(\/)+/', '$1$2', $url); // remove double slashes ?>
                        <div
                          class="exampleDefinition"><?php print strtoupper($verb) . ' '; ?><?php print $url_escaped; ?></div>
                      <?php endif; ?>
                      <label><?php print t('Response'); ?></label>
                      <div class="exampleResponse">
                        <div class="exampleRespData"
                             id="exampleresponse_apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>">
                          <pre class="exampleResponsePre"><code
                              class="json"></code></pre>
                          <div class="showMore hidden"><a class="btn"
                                                          data-popup-open="popup-<?php print $index; ?>"
                                                          href="#"><?php print t('Show more'); ?>
                              <i
                                class="material-icons">open_in_new</i></a></div>
                          <div class="popup"
                               data-popup="popup-<?php print $index; ?>"
                               style="display: none;">
                            <div class="popup-inner">
                                        <pre class="hljs">
                                          <code></code>
                                        </pre>
                              <p><a
                                  data-popup-close="popup-<?php print $index; ?>"
                                  href="#"><?php t('Close'); ?></a>
                              </p>
                              <a class="popup-close"
                                 data-popup-close="popup-<?php print $index; ?>"
                                 href="#">x</a>
                            </div>
                          </div>
                        </div>
                        <?php $index++; ?>
                      </div>
                    </div>

                    <?php if ($ibm_apim_enable_api_test == 1 && (!isset($api['x-ibm-configuration']['testable']) || $api['x-ibm-configuration']['testable'] == TRUE)) : ?>
                    <div
                      id='tab-content_try_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . '_' . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>'
                      class='tab-content'>
                      <div
                        class="rightSectionHeading"><?php print t('Try this operation'); ?></div>
                      <form class="testForm"
                            name="request_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . "_" . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>"
                            onsubmit="API.test('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print str_replace("'","%27",$pathSegment); ?>');">
                        <input type="password" style="display:none;"/>
                        <input type="submit" style="display: none;"
                               tabindex="-1"/>
                        <div class='contrast'>
                          <?php if (count($api['schemes']) > 1 && !isset($api['x-ibm-endpoints'])) : ?>
                            <select name="scheme">
                              <?php foreach ($api['schemes'] as $scheme)  : ?>
                                <option
                                  value="<?php print $scheme; ?>"><?php print $scheme; ?></option>
                              <?php endforeach; ?>
                            </select>
                            <?php if ($protocol != 'wsdl') : ?>
                              <?php $url = $api['host'] . $api['basePath'] . $pathSegment;
                              $url_escaped = preg_replace('/(\/)+/', '$1', $url); // remove double slashes?>
                              <span
                                class="apiURL">://<?php print $url_escaped; ?></span>
                            <?php else : ?>
                              <?php $parts = explode('/', $pathSegment);
                              $end = array_pop($parts);
                              $url = $api['host'] . $api['basePath'] . join('/', $parts);
                              $url_escaped = preg_replace('/(\/)+/', '$1', $url); // remove double slashes?>
                              <span
                                class="apiURL">://<?php print $url_escaped; ?></span>
                            <?php endif; ?>
                          <?php else: ?>
                            <input type="hidden" name="scheme"
                                   value="<?php print $api['schemes'][0]; ?>"/>
                            <?php if ($protocol != 'wsdl') : ?>
                              <?php $url = $default_endpoint . $api['basePath'] . $pathSegment;
                              $url_escaped = preg_replace('/(https?:\/\/)|(\/)+/', '$1$2', $url); // remove double slashes?>
                              <span
                                class="apiURL"><?php print $url_escaped; ?></span>
                            <?php else : ?>
                              <?php $parts = explode('/', $pathSegment);
                              $end = array_pop($parts);
                              $url = $default_endpoint . $api['basePath'] . join('/', $parts);
                              $url_escaped = preg_replace('/(https?:\/\/)|(\/)+/', '$1$2', $url); // remove double slashes
                              ?>
                              <span
                                class="apiURL"><?php print $url_escaped; ?></span>
                            <?php endif; ?>
                          <?php endif; ?>
                        </div>
                        <div class="securitySection hidden">
                          <div class="securitySelectionSection hidden">
                            <label><?php print t('Security'); ?></label>
                            <div class="contrast">
                              <div class='parameter apimSecurityType'>
                                <select name="securityType"
                                        class='securityType parameterValue'>
                                </select>
                              </div>
                            </div>
                          </div>
                          <div class="identificationSection hidden">
                            <label><?php print t('Identification'); ?></label>
                            <div class="contrast">
                              <?php if (user_is_logged_in()) : ?>
                                <?php if (count($creds) > 0) : ?>
                                  <div class='parameter clientId hidden'>
                                    <div
                                      class='parameterName'><?php print t('Client ID'); ?></div>
                                    <select name="apimClientId"
                                            class='parameterValue'>
                                      <?php foreach ($creds as $cred)  : ?>
                                        <option
                                          value="<?php print $cred['id']; ?>"><?php print $cred['name']; ?></option>
                                      <?php endforeach; ?>
                                    </select>
                                  </div>
                                  <div class='parameter clientSecret hidden'>
                                    <div
                                      class='parameterName'><?php print t('Client secret'); ?></div>
                                    <input type="password"
                                           name="apimClientSecret"
                                           class='parameterValue'/>
                                  </div>
                                  <div
                                    class="requiresOauth noRedirectURI hidden"><?php print t('Warning: This application does not have a redirect_uri set. It may have problems authorizing via OAuth. The redirect URI can be set by clicking Apps in the main menu.'); ?></div>
                                <?php else: ?>
                                  <div><?php print l(t('Register an application to test this API.'), 'application/new'); ?></div>
                                <?php endif; ?>
                              <?php else: ?>
                                <div
                                  class="loginMessage"><?php print l(t('Login to test this API.'), 'user/login'); ?></div>
                              <?php endif; ?>
                            </div>
                          </div>
                          <div class="apimScopes hidden">
                            <label><?php print t('Scope'); ?></label>
                            <div class="contrast scopes">
                              <div class="parameter oauthscopes"></div>
                            </div>
                          </div>
                          <div class="authorizationSection hidden">
                            <label><?php print t('Authorization'); ?></label>
                            <?php if (user_is_logged_in()) : ?>
                            <div class="contrast userCredentials hidden">
                              <div class='parameter apimUsername'>
                                <div
                                  class='parameterName'><?php print t('Username'); ?></div>
                                <input name="apimUsername"
                                       class='parameterValue'/>
                              </div>
                              <div class='parameter apimPassword'>
                                <div
                                  class='parameterName'><?php print t('Password'); ?></div>
                                <input type="password" name="apimPassword"
                                       class='parameterValue'/>
                              </div>
                            </div>
                            <div class="contrast apimAuthUrl hidden">
                              <div
                                class="requiresOauth passwordFlow hidden"><?php print t('This operation is secured with password flow OAuth'); ?></div>
                              <div
                                class="requiresOauth applicationFlow hidden"><?php print t('This operation is secured with application flow OAuth'); ?></div>
                              <div
                                class="requiresOauth implicitFlow hidden"><?php print t('This operation is secured with implicit flow OAuth'); ?></div>
                              <div
                                class="requiresOauth accessCodeFlow hidden"><?php print t('This operation is secured with access code flow OAuth'); ?></div>
                              <div
                                class="requiresOauth accessCodeFlow implicitFlow applicationFlow hidden"><?php print t('Click below to initiate the authorization process. Depending on your Application\'s redirect_uri you may need to retrieve the access token and then enter it in the field below in order to call this operation.'); ?></div>
                              <div class="parameter threelegged hidden">
                                <div
                                  class='parameterName'><?php print t('Authorization URL'); ?></div>
                                <div class='parameterValue authurl'></div>
                              </div>
                              <div class="tokenBasedFlow parameter hidden">
                                <div
                                  class='parameterName'><?php print t('Token URL'); ?></div>
                                <div class='parameterValue tokenurl'></div>
                              </div>
                              <div class="parameter buttons">
                                <div
                                  class="authoriseButton accessCodeFlow hidden accesscode">
                                  <button type="button"
                                          onclick="API.authorize('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print str_replace("'","%27",$pathSegment); ?>')"
                                          title="<?php print t('Authorize'); ?>"><?php print t('Authorize'); ?></button>
                                  <div
                                    class='parameterName'><?php print t('Access Code'); ?></div>
                                  <input type="password" name="accessCode"
                                         class='result parameterValue'/>
                                </div>
                                <div
                                  class="getTokenButton accesstoken">
                                  <button type="button"
                                          class="accessCodeFlow hidden"
                                          onclick="API.getToken('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print str_replace("'","%27",$pathSegment); ?>')"
                                          title="<?php print t('Get token'); ?>"><?php print t('Get token'); ?></button>
                                  <button type="button"
                                          class="notAccessCodeFlow"
                                          onclick="API.authorize('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print str_replace("'","%27",$pathSegment); ?>')"
                                          title="<?php print t('Authorize'); ?>"><?php print t('Authorize'); ?></button>
                                  <div
                                    class='parameterName'><?php print t('Access Token'); ?></div>
                                  <input type="password" name="authToken"
                                         class='result parameterValue'/>
                                </div>
                                <div
                                  class="refreshButton hidden">
                                  <button type="button"
                                          onclick="API.refreshToken('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print str_replace("'","%27",$pathSegment); ?>')"
                                          title="<?php print t('Refresh token'); ?>"><?php print t('Refresh token'); ?></button>
                                  <div class="refreshDone hidden"><i
                                      class="material-icons">done</i></div>
                                </div>
                                <span
                                  class="oauthMessage mixedContentWarning hidden"><?php print t('No response. This is a mixed content call. It is not possible to test HTTP APIs from an HTTPS secured Portal site and vice versa.'); ?></span>
                                <span
                                  class="oauthMessage corsWarning hidden"><?php print t('No response. This is a cross-origin call. Make sure the server accepts requests from this portal. Or if using self-signed SSL certificates then paste the URL above into your browser to accept the certificate before trying again (On Internet Explorer it must be the same browser tab.).'); ?></span>
                                <div
                                  class="oauthMessage unauthorized hidden"><?php print t('Unauthorized. Ensure you have specified the client ID and secret of a subscribed application above.'); ?></div>
                                <div
                                  class="oauthMessage oauthError hidden"><?php print t('Error. An error has occurred. See browser developer tools for more details.'); ?></div>
                                <div
                                  class="oauthMessage norefresh hidden"><?php print t('Error. No refresh token provided.'); ?></div>
                              </div>

                            </div>
                          </div>
                          <?php else: ?>
                            <div class="contrast">
                              <div><?php print l(t('Login to test this API.'), 'user/login'); ?></div>
                            </div>
                          <?php endif; ?>
                        </div>
                    </div>
                    <?php $bodypresent = FALSE;
                    $formDataPresent = FALSE;
                    $paramspresent = FALSE;
                    unset($bodyparam); ?>
                    <?php
                    // build up parameters list
                    $operation_parameters = array();
                    if (isset($path['parameters']) && is_array($path['parameters'])) {
                      foreach ($path['parameters'] as $key => $parameter) {
                        if (isset($parameter['$ref'])) {
                          // handle parameter references
                          $parameter = api_get_ref_param($api, $parameter['$ref']);
                        }
                        $operation_parameters[$parameter['name']] = $parameter;
                        if (isset($parameter['in']) && $parameter['in'] == "body") {
                          $bodypresent = TRUE;
                          $bodyparam = $parameter;
                        }
                        if (isset($parameter['in']) && ($parameter['in'] == "path" || $parameter['in'] == "query" || $parameter['in'] == "formData")) {
                          $paramspresent = TRUE;
                          if ($parameter['in'] == "formData") {
                            $formDataPresent = TRUE;
                          }
                        }
                      }
                    }
                    if (isset($operation['parameters']) && is_array($operation['parameters'])) {
                      foreach ($operation['parameters'] as $key => $parameter) {
                        if (isset($parameter['$ref'])) {
                          // handle parameter references
                          $parameter = api_get_ref_param($api, $parameter['$ref']);
                        }
                        $operation_parameters[$parameter['name']] = $parameter;
                        if (isset($parameter['in']) && $parameter['in'] == "body") {
                          $bodypresent = TRUE;
                          $bodyparam = $parameter;
                        }
                        if (isset($parameter['in']) && ($parameter['in'] == "path" || $parameter['in'] == "query" || $parameter['in'] == "formData")) {
                          $paramspresent = TRUE;
                          if ($parameter['in'] == "formData") {
                            $formDataPresent = TRUE;
                          }
                        }
                      }
                    }
                    $operation_parameters_lower = array_change_key_case($operation_parameters, CASE_LOWER);
                    ?>
                    <?php if ($bodypresent == TRUE)  : ?>
                      <label><?php print t('Body'); ?></label>
                      <div class="contrast">
                        <div class='parameter'
                             id='body_apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>'>
                    <textarea name="param<?php print $bodyparam['name']; ?>"
                              class='parameterValue'></textarea>
                        </div>
                      </div>
                    <?php endif; ?>
                    <label><?php print t('Headers'); ?></label>
                    <div class="contrast">
                      <?php if ($bodypresent == TRUE || $formDataPresent == TRUE) : ?>
                        <div class='parameter'>
                          <div class='parameterName'>content-type</div>
                          <?php if (isset($operation['consumes']) || isset($api['consumes'])): ?>
                            <select name="content-type" class='parameterValue'>
                              <?php
                              $consumes = array();
                              if (isset($operation['consumes'])) {
                                $consumes = array_merge($consumes, $operation['consumes']);
                              }
                              elseif (isset($api['consumes'])) {
                                $consumes = array_merge($consumes, $api['consumes']);
                              }
                              if (isset($operation_parameters_lower['content-type']) && isset($operation_parameters_lower['content-type']['default'])) {
                                $default_content_type = $operation_parameters_lower['content-type']['default'];
                              }
                              else {
                                $default_content_type = 'application/json';
                              }
                              foreach ($consumes as $contenttype) {
                                if ($contenttype == $default_content_type) {
                                  print '<option
                                          value="' . $contenttype . '"
                                          selected>' . $contenttype . '</option>';
                                }
                                else {
                                  print '<option
                                          value="' . $contenttype . '">' . $contenttype . '</option>';
                                }
                              }
                              ?>
                            </select>
                          <?php else: ?>
                            <input class='parameterValue' name="content-type"
                                   value='application/json'/>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                      <div class='parameter'>
                        <div class='parameterName'>accept</div>
                        <?php if (isset($operation['produces']) || isset($api['produces'])): ?>
                          <select name="accept" class='parameterValue'>
                            <?php
                            $produces = array();
                            if (isset($operation['produces'])) {
                              $produces = array_merge($produces, $operation['produces']);
                            }
                            elseif (isset($api['produces'])) {
                              $produces = array_merge($produces, $api['produces']);
                            }
                            if (isset($operation_parameters_lower['accept']) && isset($operation_parameters_lower['accept']['default'])) {
                              $default_content_type = $operation_parameters_lower['accept']['default'];
                            }
                            else {
                              $default_content_type = 'application/json';
                            }
                            foreach ($produces as $contenttype) {
                              if ($contenttype == $default_content_type) {
                                print '<option
                                          value="' . $contenttype . '"
                                          selected>' . $contenttype . '</option>';
                              }
                              else {
                                print '<option
                                          value="' . $contenttype . '">' . $contenttype . '</option>';
                              }
                            }
                            ?>
                          </select>
                        <?php else: ?>
                          <input class='parameterValue' name="accept"
                                 value='application/json'/>
                        <?php endif; ?>
                      </div>

                      <?php // Header params ?>
                      <?php if (isset($operation_parameters) && is_array($operation_parameters))  : ?>
                        <?php foreach ($operation_parameters as $parameter)  : ?>
                          <?php if (isset($parameter['in']) && $parameter['in'] == "header" && strtolower($parameter['name']) != "content-type" && strtolower($parameter['name']) != "accept")  : ?>
                            <div
                              class='parameter <?php if (isset($parameter['required']) && $parameter['required'] == "true") {
                                print 'required';
                              } ?>'>
                              <div
                                class='parameterName'><?php print $parameter['name']; ?></div>
                              <?php if (isset($parameter['enum']) && count($parameter['enum']) > 0) : ?>
                                <select
                                  name="param<?php print $parameter['name']; ?>"
                                  class='parameterValue'>
                                  <?php
                                  if (!$parameter['required']) {
                                    print '<option
                                          value=" "> --- </option>';
                                  }
                                  foreach ($parameter['enum'] as $option) {
                                    if (isset($parameter['default']) && $option == $parameter['default']) {
                                      print '<option
                                          value="' . $option . '"
                                          selected>' . $option . '</option>';
                                    }
                                    else {
                                      print '<option
                                          value="' . $option . '">' . $option . '</option>';
                                    }
                                  }
                                  ?>
                                </select>
                              <?php elseif (isset($parameter['type']) && $parameter['type'] == "boolean" && $parameter['required'] != "true") : ?>
                                <select
                                  name="param<?php print $parameter['name']; ?>"
                                  class='parameterValue'>
                                  <option value=" "> ---</option>
                                  <option
                                    value="true" <?php if ($parameter['default'] == TRUE) {
                                    print "selected";
                                  } ?>>true
                                  </option>
                                  <option
                                    value="false" <?php if ($parameter['default'] == FALSE) {
                                    print "selected";
                                  } ?>>false
                                  </option>
                                </select>
                              <?php else: ?>
                                <input <?php if (isset($parameter['required']) && $parameter['required'] == "true") {
                                  print 'required';
                                } ?>
                                  name="param<?php print $parameter['name']; ?>"
                                  <?php if (isset($parameter['type']) && $parameter['type'] == "integer") : ?>
                                    type="number"
                                  <?php elseif (isset($parameter['type']) && $parameter['type'] == "number") : ?>
                                    type="number" step="any"
                                  <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "password") : ?>
                                    type="password"
                                  <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "date") : ?>
                                    type="date"
                                  <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "date-time") : ?>
                                    type="datetime-local"
                                  <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "email") : ?>
                                    type="email"
                                  <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "url") : ?>
                                    type="url"
                                  <?php elseif (isset($parameter['type']) && $parameter['type'] == "boolean") : ?>
                                    type="checkbox" value="true"
                                    <?php if (isset($parameter['default']) && $parameter['default'] == "true") : ?>
                                      checked="checked"
                                    <?php endif; ?>
                                  <?php endif; ?>
                                  <?php if (isset($parameter['maxLength']))  : ?>
                                    <?php if (isset($parameter['exclusiveMaximum']))  : ?>
                                      maxlength="<?php print ($parameter['maxLength'] - 1); ?>"
                                    <?php else: ?>
                                      maxlength="<?php print $parameter['maxLength']; ?>"
                                    <?php endif; ?>
                                  <?php endif; ?>
                                  class='parameterValue <?php if (isset($parameter['required']) && $parameter['required'] == "true") {
                                    print 'required';
                                  } ?>'
                                  <?php if ((!isset($parameter['type']) || $parameter['type'] != "boolean") && !empty($parameter['default'])) : ?>
                                    value="<?php print $parameter['default']; ?>"
                                  <?php endif; ?>
                                />
                              <?php endif; ?>
                            </div>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                    <?php // Other params ?>
                    <?php if (isset($paramspresent) && $paramspresent == TRUE): ?>
                      <label><?php print t('Parameters'); ?></label>
                      <div class="contrast">
                        <?php if (isset($operation_parameters) && is_array($operation_parameters)) : ?>
                          <?php foreach ($operation_parameters as $parameter)  : ?>
                            <?php if (isset($parameter['in']) && ($parameter['in'] == "path" || $parameter['in'] == "query" || $parameter['in'] == "formData"))  : ?>
                              <?php if (isset($parameter['in']) && ($parameter['in'] == "path")) {
                                // path parameters are always required
                                $parameter['required'] = "true";
                              } ?>
                              <div
                                class='parameter <?php if (isset($parameter['required']) && $parameter['required'] == "true") {
                                  print 'required';
                                } ?>'>
                                <div
                                  class='parameterName'><?php print $parameter['name']; ?></div>
                                <?php if (isset($parameter['enum']) && count($parameter['enum']) > 0) : ?>
                                  <select
                                    name="param<?php print $parameter['name']; ?>"
                                    class='parameterValue'>
                                    <?php
                                    if (!$parameter['required']) {
                                      print '<option
                                          value=" "> --- </option>';
                                    }
                                    foreach ($parameter['enum'] as $option) {
                                      if (isset($parameter['default']) && $option == $parameter['default']) {
                                        print '<option
                                          value="' . $option . '"
                                          selected>' . $option . '</option>';
                                      }
                                      else {
                                        print '<option
                                          value="' . $option . '">' . $option . '</option>';
                                      }
                                    }
                                    ?>
                                  </select>
                                <?php elseif (isset($parameter['type']) && $parameter['type'] == "boolean" && $parameter['required'] != "true") : ?>
                                  <select
                                    name="param<?php print $parameter['name']; ?>"
                                    class='parameterValue'>
                                    <option value=" "> ---</option>
                                    <option
                                      value="true" <?php if ($parameter['default'] == TRUE) {
                                      print "selected";
                                    } ?>>true
                                    </option>
                                    <option
                                      value="false" <?php if ($parameter['default'] == FALSE) {
                                      print "selected";
                                    } ?>>false
                                    </option>
                                  </select>
                                <?php else: ?>
                                  <input <?php if (isset($parameter['required']) && $parameter['required'] == "true") {
                                    print 'required';
                                  } ?>
                                    name="param<?php print $parameter['name']; ?>"
                                    class='parameterValue <?php if (isset($parameter['required']) && $parameter['required'] == "true") {
                                      print 'required';
                                    } ?>'
                                    <?php if (isset($parameter['type']) && $parameter['type'] == "integer")  : ?>
                                      type="number"
                                    <?php elseif (isset($parameter['type']) && $parameter['type'] == "number") : ?>
                                      type="number" step="any"
                                    <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "password") : ?>
                                      type="password"
                                    <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "date") : ?>
                                      type="date"
                                    <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "date-time") : ?>
                                      type="datetime-local"
                                    <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "email") : ?>
                                      type="email"
                                    <?php elseif (isset($parameter['type']) && $parameter['type'] == "string" && isset($parameter['format']) && $parameter['format'] == "url") : ?>
                                      type="url"
                                    <?php elseif (isset($parameter['type']) && $parameter['type'] == "boolean") : ?>
                                      type="checkbox" value="true"
                                      <?php if (isset($parameter['default']) && $parameter['default'] == "true") : ?>
                                        checked="checked"
                                      <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (isset($parameter['maxLength']))  : ?>
                                      <?php if (isset($parameter['exclusiveMaximum']))  : ?>
                                        maxlength="<?php print ($parameter['maxLength'] - 1); ?>"
                                      <?php else: ?>
                                        maxlength="<?php print $parameter['maxLength']; ?>"
                                      <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ((!isset($parameter['type']) || $parameter['type'] != "boolean") && !empty($parameter['default'])) : ?>
                                      value="<?php print $parameter['default']; ?>"
                                    <?php endif; ?>
                                  />
                                <?php endif; ?>
                              </div>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                    </form>
                    <button style="margin: 0px 15px 15px 0px;"
                            class='mesh floatRight'
                            onclick="API.test('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print str_replace("'","%27",$pathSegment); ?>');"><?php print t('Call operation'); ?></button>
                    <div class='clearBoth'></div>
                    <div class="responseSection hidden"
                         id="response_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . '_' . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>">
                      <label><?php print t('Request'); ?></label>
                      <div>
                        <pre class="contrast"><code
                            class="http requestDetails requestUrl"></code><code
                            class="http requestDetails requestHeaders"></code>
                        </pre>
                      </div>
                      <label><?php print t('Response'); ?></label>
                      <div>
                            <span
                              class="responseDetails mixedContentWarning hidden"><?php print t('No response. This is a mixed content call. It is not possible to test HTTP APIs from an HTTPS secured Portal site and vice versa.'); ?></span>
                            <span
                              class="responseDetails corsWarning hidden"><?php print t('No response. This is a cross-origin call. Make sure the server accepts requests from this portal. Or if using self-signed SSL certificates then paste the URL above into your browser to accept the certificate before trying again (On Internet Explorer it must be the same browser tab.).'); ?></span>
                            <pre class="contrast"><code
                                class="http responseDetails responseStatus"></code><code
                                class="http responseDetails responseHeaders"></code><code
                                class="responseDetails responseBody"></code>
                            </pre>
                      </div>
                    </div>
                  </div>
                  <?php else: ?>
                    <div
                      id='tab-content_try_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . '_' . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>'
                      class='tab-content'>
                      <form class="testForm"
                            name="request_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . "_" . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>"
                            onsubmit="API.test('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print str_replace("'","%27",$pathSegment); ?>');">
                        <div class="securitySection hidden">
                          <div class="securitySelectionSection hidden">
                            <label><?php print t('Security'); ?></label>
                            <div class="contrast">
                              <div class='parameter apimSecurityType'>
                                <select name="securityType"
                                        class='securityType parameterValue'>
                                </select>
                              </div>
                            </div>
                          </div>
                        </div>
                      </form>
                    </div>
                  <?php endif; ?>
                </section>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if ((isset($api['definitions'])) && !empty($api['definitions'])) : ?>
      <div class="definitionsSection bottomBorder">
        <div class="readAndInteract">
          <section
            class="documentation definitionsContent navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?> definitions_toggle_apis-<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>">
            <h2
              class="navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_definitions"><?php print t('Definitions'); ?>
            </h2>
            <div class="definitionsWrapper">
              <?php foreach ($api['definitions'] as $definitionName => $definition) : ?>
                <label
                  class="navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_definitions_<?php print drupal_html_class(preg_replace("/\W/", "", $definitionName)); ?>"><?php print $definitionName; ?></label>
                <?php if (isset($definition['description'])) : ?>
                  <div
                    class="markdown"><?php print ibm_apim_markdown_field(ibm_apim_get_translated_string($definition, array(), 'description')); ?></div>
                  <?php unset($definition['description']); ?>
                <?php endif; ?>
                <?php if (isset($definition['externalDocs']) && !empty($definition['externalDocs']) && isset($definition['externalDocs']['url'])) : ?>
                  <div>
                    <label><?php print t('Documentation'); ?></label>
                    <div><a
                        href="<?php print $definition['externalDocs']['url']; ?>"
                        target='_blank'>
                        <?php if (isset($definition['externalDocs']['description'])) {
                          print ibm_apim_get_translated_string($definition, ['externalDocs'], 'description');
                          unset($definition['externalDocs']);
                        }
                        else {
                          print $definition['externalDocs']['url'];
                          unset($definition['externalDocs']);
                        } ?>
                      </a></div>
                  </div>
                <?php endif; ?>
                <?php if ($protocol == 'wsdl' && isset($definition['example'])) {
                  unset($definition['example']);
                } ?>
                <pre class="inlineSchema"><code
                    class="definitionDetails"><?php print json_encode($definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></code>
              </pre>
              <?php endforeach; ?>
            </div>
          </section>
          <section class="interact">
          </section>
        </div>
      </div>
    <?php endif; ?>
  </div>
</article>
