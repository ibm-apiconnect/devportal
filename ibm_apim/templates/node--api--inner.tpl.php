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
$ibm_apim_enable_api_test = variable_get('ibm_apim_enable_api_test', 1);
global $user;
$apim_session = &_ibm_apim_get_apim_session();
$protocol_lower = strtolower($api_protocol[0]['value']);
if (isset($protocol_lower) && $protocol_lower == 'wsdl') {
  $protocol = 'wsdl';
}
else {
  $protocol = 'rest';
} ?>
<article id="node-<?php print $node->nid; ?>"
         class="mesh-portal-api <?php print $classes . ' ' . $content['api_apiid'][0]['#markup'] . ' ' . $protocol; ?> apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?> inner clearfix" <?php print $attributes; ?>>
  <div class="navigate-apis">
    <div class="readAndInteract">

      <section
        class="documentation navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>">
        <h1 class="stickyHeader" style="display: inline-block">
          <span><?php print $api['info']['title'] . ' ' . $api['info']['version']; ?></span>
          <span class="floatRight">
            <?php if ($protocol != 'wsdl') : ?>
              <a
                href="<?php print url('ibm_apim/swaggerjson/' . ibm_apim_base64_url_encode($api['info']['x-ibm-name'] . ':' . $api['info']['version'])) ?>"
                target='_blank'>
                <button style="margin-top: -12px;" class="material-icons" title="<?php print t('Download'); ?>">
                  file_download
                </button>
              </a>
            <?php elseif (isset($protocol) && strtolower($protocol) == 'wsdl') : ?>
              <div class="protocol">SOAP</div>
            <?php endif; ?>
            <?php if (user_is_logged_in() && node_access("update", $node, $user) === TRUE) : ?>
              <span class="edit"><a href="<?php print url('node/' . $node->nid . '/edit'); ?>"><i
                    class="material-icons" title="<?php print t('Edit'); ?>">mode_edit</i></a></span>
            <?php endif; ?>
          </span>
        </h1>
        <div class="documentationContent apicSelfClear">
          <div class="apimImage">
            <?php if (isset($content['api_image'])) : ?>
              <?php print render($content['api_image']); ?>
            <?php elseif ($showplaceholders != 0) : ?>
              <div class="apimIcon">
                <div class="field field-name-api-image field-type-image field-label-hidden view-mode-teaser">
                  <div class="field-items">
                    <figure class="clearfix field-item even">
                      <img typeof="foaf:Image" class="image-style-none"
                           src="<?php print file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/api/' . api_random_image($node->title)); ?>"
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
            <div class="apiRating">
              <?php if (isset($content['field_apirating'])) {
                $content['field_apirating']['#label_display'] = 'hidden';
                print render($content['field_apirating']);
              } ?>
            </div>
            <div class="apiTags">
              <?php if (isset($api['tags']) && is_array($api['tags'])) : ?>
                <?php foreach ($api['tags'] as $tag): ?>
                  <?php if (isset($tag['description'])) {
                    $tagdescr = 'title="' . $tag['description'] . '"';
                  }
                  else {
                    $tagdescr = '';
                  } ?>

                  <span
                    class='apiTag <?php print api_tag_to_class($tag['name']); ?> <?php print drupal_html_class($tag['name']); ?>' <?php print $tagdescr; ?>>
                    <a
                      onclick='product.selecttag("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print drupal_html_class($tag['name']); ?>")'
                      href='javascript:;'><span class="apiName"><?php print $tag['name']; ?></span></a> <a
                      onclick='product.unselecttag("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>")'
                      href='javascript:;' class='unselect hidden'><span>x</span></a></span>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <?php if (isset($api['info']['description']) && !empty($api['info']['description'])) : ?>
              <div>
                <label><?php print t('Description'); ?></label>
                <div><?php print ibm_apim_markdown_field($api['info']['description']); ?></div>
              </div>
            <?php endif; ?>
            <?php $docs = render($content['api_attachments']); ?>
            <?php if ((isset($docs) && !empty($docs)) || (isset($api['externalDocs']) && !empty($api['externalDocs']) && isset($api['externalDocs']['url'])) || isset($api['x-ibm-configuration']['externalDocs'])) : ?>
              <div>
                <label><?php print t('Documentation'); ?></label>
                <?php if (isset($api['externalDocs'])) : ?>
                  <div><a href="<?php print $api['externalDocs']['url']; ?>">
                      <?php if (isset($api['externalDocs']['description'])) {
                        print $api['externalDocs']['description'];
                      }
                      else {
                        print $api['externalDocs']['url'];
                      } ?>
                    </a></div>
                <?php endif; ?>
                <?php if (isset($api['x-ibm-configuration']['externalDocs'])) : ?>
                  <?php foreach ($api['x-ibm-configuration']['externalDocs'] as $doc) : ?>
                    <div><a href="<?php print $doc['url']; ?>">
                        <?php if (isset($doc['description'])) {
                          print $doc['description'];
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
          <?php if (isset($api['securityDefinitions']) && !empty($api['securityDefinitions'])) : ?>
            <h2
              class="navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_security"><?php print t('Security'); ?></h2>
            <div class='securitySection listHeader'>
              <label class='name'><?php print t('Name'); ?>
              </label><label class='type'><?php print t('Type'); ?>
              </label>
            </div>
            <?php foreach ($api['securityDefinitions'] as $securityDefName => $securityDef) : ?>
              <div class='securitySection listContent'>
                <div class='name'><?php if (isset($securityDef['name'])) {
                    print $securityDef['name'];
                  }
                  else {
                    print $securityDefName;
                  } ?>
                </div><div class='type'><?php print $securityDef['type']; ?><?php if (isset($securityDef['in'])) {
                    print " " . $securityDef['in'];
                  } ?><?php if (isset($securityDef['flow'])) {
                    print " " . $securityDef['flow'];
                  } ?>
                </div><div
                  class='description'><?php if (isset($securityDef['description'])) {
                    print ibm_apim_markdown_field($securityDef['description']);
                  } ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
      <section class="interact" style="padding: 15px;">
        <?php if (isset($api['info']['contact'])) : ?>
          <div class='clearBoth'>
            <label><?php print t('Contact information'); ?></label>
            <?php if (isset($api['info']['contact']['name']) && !empty($api['info']['contact']['name'])) : ?>
              <div><?php print $api['info']['contact']['name']; ?></div>
            <?php endif; ?>
            <?php if (isset($api['info']['contact']['email']) && !empty($api['info']['contact']['email'])): ?>
              <div><a
                  href='mailto:<?php print $api['info']['contact']['email']; ?>'><?php print $api['info']['contact']['email']; ?>
                  <a/></div>
            <?php endif; ?>
            <?php if (isset($api['info']['contact']['url']) && !empty($api['info']['contact']['url'])) : ?>
              <div><a
                  href='<?php print $api['info']['contact']['url']; ?>'><?php print $api['info']['contact']['url']; ?></a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if (isset($api['info']['termsOfService']) && !empty($api['info']['termsOfService'])) : ?>
          <div>
            <label><?php print t('Terms of service'); ?></label>
            <div><?php print $api['info']['termsOfService']; ?></div>
          </div>
        <?php endif; ?>
        <?php if (isset($api['info']['license']) && isset($api['info']['license']['url']) && isset($api['info']['license']['name'])) : ?>
          <div><a href='<?php print $api['info']['license']['url']; ?>'
                  target='_blank'><?php print $api['info']['license']['name']; ?></a></div>
        <?php endif; ?>
        <div "<?php print $content_attributes; ?>">

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
    </div>

    <?php if ($links = render($content['links'])): ?>
      <nav <?php print $links_attributes; ?>><?php print $links; ?></nav>
    <?php endif; ?>
    </section>
  </div>
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
          <?php if (in_array(strtoupper($verb), array('PUT', 'POST', 'GET', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH'))) : ?>
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
                  class='operationDetails topBorder <?php print $verb; ?> navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>'>
                  <a
                    onclick='product.toggleop("apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>")'
                    href='javascript:;'><div class='header bottomBorder stickyHeader'>
                      <?php if ($protocol != 'wsdl') : ?>
                        <span
                          class='verb'><?php print $verb; ?></span><?php print " " . $pathSegment; ?>
                      <?php else : ?>
                        <?php $parts = explode('/', $pathSegment);
                        print end($parts);
                        ?>
                      <?php endif; ?>
                      <span
                        class="expand_more"><i class="material-icons">expand_more</i></span><span class="expand_less"><i
                          class="material-icons">expand_less</i></span></div></a>
                  <div class='operationContent'>
                    <div class="apiTags">
                      <?php if (isset($operation['tags']) && is_array($operation['tags'])) : ?>
                        <?php foreach ($operation['tags'] as $tagname): ?>
                          <?php $tagfound = FALSE; ?>
                          <?php if (isset($api['tags']) && is_array($api['tags'])) {
                            foreach ($api['tags'] as $tag) {
                              if (isset($tag['description']) && isset($tag['name']) && $tag['name'] == $tagname) {
                                $description = $tag['description'];
                                $tagfound = TRUE;
                              }
                            }
                          } ?>
                          <?php if ($tagfound == TRUE && isset($description)) {
                            $tagdescr = 'title="' . $description . '"';
                          }
                          else {
                            $tagdescr = '';
                          } ?>
                          <span
                            class='apiTag <?php print api_tag_to_class($tagname); ?>' <?php print $tagdescr; ?>><?php print $tagname; ?> </span>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                    <?php if (isset($operation['summary']) && !empty($operation['summary'])) : ?>
                      <div>
                        <label><?php print t('Summary'); ?></label>
                        <div><?php print ibm_apim_markdown_field($operation['summary']); ?></div>
                      </div>
                    <?php endif; ?>
                    <?php if (isset($operation['description']) && !empty($operation['description'])) : ?>
                      <div>
                        <label><?php print t('Description'); ?></label>
                        <div><?php print ibm_apim_markdown_field($operation['description']); ?></div>
                      </div>
                    <?php endif; ?>
                    <?php if (isset($operation['externalDocs']) && !empty($operation['externalDocs']) && isset($operation['externalDocs']['url'])) : ?>
                      <div>
                        <label><?php print t('Documentation'); ?></label>
                        <div><a href="<?php print $operation['externalDocs']['url']; ?>" target='_new'>
                            <?php if (isset($operation['externalDocs']['description'])) {
                              print $operation['externalDocs']['description'];
                            }
                            else {
                              print $operation['externalDocs']['url'];
                            } ?>
                          </a></div>
                      </div>
                    <?php endif; ?>
                    <div>
                      <?php if (isset($operation['security']) && !empty($api['security'])) : ?>
                        <label><?php print t('Security'); ?></label>
                        <div class='securitySection listHeader'>
                          <label class='name'><?php print t('Name'); ?>
                          </label><label class='type'><?php print t('Type'); ?>
                          </label>
                        </div>
                        <?php foreach ($operation['security'] as $securityArray) : ?>
                          <?php foreach ($securityArray as $securityDefName => $securityDef) : ?>
                            <div class='securitySection listContent'>
                              <div
                                class='name'><?php if (isset($api['securityDefinitions'][$securityDefName]['name'])) {
                                  print $api['securityDefinitions'][$securityDefName]['name'];
                                }
                                else {
                                  print $securityDefName;
                                } ?>
                              </div><div
                                class='type'><?php print $api['securityDefinitions'][$securityDefName]['type']; ?><?php if (isset($api['securityDefinitions'][$securityDefName]['located-in'])) {
                                  print " " . $api['securityDefinitions'][$securityDefName]['located-in'];
                                } ?><?php if (isset($api['securityDefinitions'][$securityDefName]['flow'])) {
                                  print " " . $api['securityDefinitions'][$securityDefName]['flow'];
                                } ?>
                              </div><div
                                class='description'><?php print ibm_apim_markdown_field($api['securityDefinitions'][$securityDefName]['description']); ?></div>
                            </div>
                          <?php endforeach; ?>
                        <?php endforeach; ?>
                      <?php endif; ?>
                      <?php if ((!isset($operation['security']) || empty($api['security'])) && (isset($api['securityDefinitions']) && !empty($api['securityDefinitions'])))  : ?>
                        <label><?php print t('Security'); ?></label>
                        <div class='securitySection listHeader'>
                          <label class='name'><?php print t('Name'); ?>
                          </label><label class='type'><?php print t('Type'); ?>
                          </label>
                        </div>
                        <?php foreach ($api['securityDefinitions'] as $securityDefName => $securityDef) : ?>
                          <div class='securitySection listContent'>
                            <div class='name'><?php if (isset($securityDef['name'])) {
                                print $securityDef['name'];
                              }
                              else {
                                print $securityDefName;
                              } ?>
                            </div><div
                              class='type'><?php print $securityDef['type']; ?><?php if (isset($securityDef['in'])) {
                                print " " . $securityDef['in'];
                              } ?><?php if (isset($securityDef['flow'])) {
                                print " " . $securityDef['flow'];
                              } ?>
                            </div><div
                              class='description'><?php if (isset($securityDef['description'])) {
                                print ibm_apim_markdown_field($securityDef['description']);
                              } ?></div>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>

                    <?php if (isset($operation['parameters']) && !empty($operation['parameters']) || (isset($path['parameters']) && !empty($path['parameters']))) : ?>
                      <?php if ($protocol != 'wsdl') : ?>
                        <div>
                          <label><?php print t('Parameters'); ?></label>
                          <div class='parametersSection listHeader'>
                            <label class='name'><?php print t('Name'); ?>
                            </label><label class='located-in'><?php print t('Located in'); ?>
                            </label><label class='required'><?php print t('Required'); ?>
                            </label><label class='schema'><?php print t('Schema'); ?></label>
                          </div>
                          <?php if (isset($path['parameters']) && is_array($path['parameters'])): ?>
                            <?php foreach ($path['parameters'] as $parameter) : ?>
                              <?php if (isset($parameter['$ref'])) {
                                // handle parameter references
                                $parameter = api_get_ref_param($api, $parameter['$ref']);
                              } ?>

                              <div class='parametersSection listContent'>
                                <div class='name'><?php print $parameter['name']; ?>
                                </div><div class='located-in'><?php print $parameter['in']; ?>
                                </div><div
                                  class='required'><?php if (isset($parameter['required'])) {
                                    print ibm_apim_translatable_boolean($parameter['required']);
                                  }
                                  else {
                                    print t('No');
                                  } ?>
                                </div><div
                                  class='schema'>
                                  <?php if (isset($parameter) && isset($parameter['schema']) && isset($parameter['schema']['$ref'])) : ?>
                                    <a
                                      onclick='product.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($parameter['schema']['$ref'])); ?>")'
                                      href='javascript:;'><?php print ibm_apim_return_schema($parameter); ?></a>
                                  <?php elseif (isset($parameter) && isset($parameter['schema']['type']) && $parameter['schema']['type'] == "array" && isset($parameter['schema']['items']) && isset($parameter['schema']['items']['$ref'])) : ?>
                                    <a
                                      onclick='product.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($parameter['schema']['items']['$ref'])); ?>")'
                                      href='javascript:;'><?php print ibm_apim_return_schema($parameter); ?></a>
                                  <?php elseif (isset($parameter) && (isset($parameter['schema']['type']) && !empty($parameter['schema']['type'])) || (isset($parameter['type']) && !empty($parameter['type']))) : ?>
                                    <pre
                                      class="inlineSchema"><code><?php print json_encode(ibm_apim_return_inline_schema($parameter), JSON_PRETTY_PRINT); ?></code></pre>
                                  <?php else: ?>
                                    <?php print ibm_apim_return_schema($parameter); ?>
                                  <?php endif; ?>
                                </div>
                                <?php if (isset($parameter['description'])): ?>
                                  <div
                                    class='description'><?php print ibm_apim_markdown_field($parameter['description']); ?></div>
                                <?php endif; ?>
                              </div>
                            <?php endforeach; ?>
                          <?php endif; ?>
                          <?php if (isset($operation['parameters']) && is_array($operation['parameters'])): ?>
                            <?php foreach ($operation['parameters'] as $parameter) : ?>
                              <?php if (isset($parameter['$ref'])) {
                                // handle parameter references
                                $parameter = api_get_ref_param($api, $parameter['$ref']);
                              } ?>
                              <div class='parametersSection listContent'>
                                <div class='name'><?php print $parameter['name']; ?>
                                </div><div class='located-in'><?php print $parameter['in']; ?>
                                </div><div
                                  class='required'><?php if (isset($parameter['required'])) {
                                    print ibm_apim_translatable_boolean($parameter['required']);
                                  }
                                  else {
                                    print t('No');
                                  } ?>
                                </div><div
                                  class='schema'>
                                  <?php if (isset($parameter) && isset($parameter['schema']) && isset($parameter['schema']['$ref'])) : ?>
                                    <a
                                      onclick='product.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($parameter['schema']['$ref'])); ?>")'
                                      href='javascript:;'><?php print ibm_apim_return_schema($parameter); ?></a>
                                  <?php elseif (isset($parameter) && isset($parameter['schema']['type']) && $parameter['schema']['type'] == "array" && isset($parameter['schema']['items']) && isset($parameter['schema']['items']['$ref'])) : ?>
                                    <a
                                      onclick='product.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($parameter['schema']['items']['$ref'])); ?>")'
                                      href='javascript:;'><?php print ibm_apim_return_schema($parameter); ?></a>
                                  <?php elseif (isset($parameter) && (isset($parameter['schema']['type']) && !empty($parameter['schema']['type'])) || (isset($parameter['type']) && !empty($parameter['type']))) : ?>
                                    <pre
                                      class="inlineSchema"><code><?php print json_encode(ibm_apim_return_inline_schema($parameter), JSON_PRETTY_PRINT); ?></code></pre>
                                  <?php else: ?>
                                    <?php print ibm_apim_return_schema($parameter); ?>
                                  <?php endif; ?>
                                </div>
                                <?php if (isset($parameter['description']) && !empty($parameter['description'])) : ?>
                                  <div
                                    class='description'><?php print ibm_apim_markdown_field($parameter['description']); ?></div>
                                <?php endif; ?>
                              </div>
                            <?php endforeach; ?>
                          <?php endif; ?>
                          <?php if (isset($operation['consumes']) || isset($api['consumes'])) : ?>
                            <div class='parametersSection listContent'>
                              <div class='name'>Content-Type
                              </div><div class='located-in'>header
                              </div><div
                                class='required'><?php print ibm_apim_translatable_boolean(FALSE); ?>
                              </div><div class='schema'>
                                <?php if (isset($operation['consumes'])) : ?>
                                  <?php foreach ($operation['consumes'] as $contenttype) : ?>
                                    <?php print $contenttype; ?><br/>
                                  <?php endforeach; ?>
                                <?php elseif (isset($api['consumes'])) : ?>
                                  <?php foreach ($api['consumes'] as $contenttype) : ?>
                                    <?php print $contenttype; ?><br/>
                                  <?php endforeach; ?>
                                <?php endif; ?>
                              </div><div
                                class='description'></div>
                            </div>
                          <?php endif; ?>
                          <?php if (isset($operation['produces']) || isset($api['produces'])) : ?>
                            <div class='parametersSection listContent'>
                              <div class='name'>Accept
                              </div><div class='located-in'>header
                              </div><div
                                class='required'><?php print ibm_apim_translatable_boolean(FALSE); ?>
                              </div><div class='schema'>
                                <?php if (isset($operation['produces'])) : ?>
                                  <?php foreach ($operation['produces'] as $contenttype) : ?>
                                    <?php print $contenttype; ?><br/>
                                  <?php endforeach; ?>
                                <?php elseif (isset($api['produces'])) : ?>
                                  <?php foreach ($api['produces'] as $contenttype) : ?>
                                    <?php print $contenttype; ?><br/>
                                  <?php endforeach; ?>
                                <?php endif; ?>
                              </div><div
                                class='description'></div>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php else: ?>
                        <div><label><?php print t('Request'); ?></label></div>
                        <?php if (isset($operation['parameters']) && is_array($operation['parameters'])): ?>
                          <?php foreach ($operation['parameters'] as $parameter) : ?>
                            <?php if (isset($parameter['in']) && $parameter['in'] == 'body') : ?>
                              <div>
                                <pre
                                  class="hljs inlineSchema"><code><?php print htmlspecialchars($parameter['schema']['example']); ?></code></pre>
                              </div>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      <?php endif; ?>
                    <?php endif; ?>
                    <?php if (isset($operation['responses']) && $protocol != 'wsdl') : ?>
                      <div>
                        <label><?php print t('Responses'); ?></label>
                        <div class='responsesSection listHeader'>
                          <label class='code'><?php print t('Code'); ?>
                          </label><label class='schema'><?php print t('Schema'); ?></label>
                        </div>
                        <?php foreach ($operation['responses'] as $code => $response) : ?>
                          <div class='responsesSection listContent'>
                            <div class='code'><?php print $code; ?>
                            </div><div
                              class='schema'>
                              <?php if (isset($response) && isset($response['$ref'])) {
                                $ref = _ibm_apim_ref_to_objectname($response['$ref']);
                                $response = $api['responses'][$ref];
                              }
                              ?>
                              <?php if (isset($response) && isset($response['schema']) && isset($response['schema']['$ref'])) : ?>
                                <a
                                  onclick='product.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($response['schema']['$ref'])); ?>")'
                                  href='javascript:;'><?php print ibm_apim_return_schema($response); ?></a>
                              <?php elseif (isset($response) && isset($response['schema']['type']) && $response['schema']['type'] == "array" && isset($response['schema']['items']) && isset($response['schema']['items']['$ref'])) : ?>
                                <a
                                  onclick='product.navigatedefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>", "<?php print preg_replace("/\W/", "", _ibm_apim_ref_to_objectname($response['schema']['items']['$ref'])); ?>")'
                                  href='javascript:;'><?php print ibm_apim_return_schema($response); ?></a>
                              <?php elseif (isset($response) && isset($response['schema']['type']) && !empty($response['schema']['type'])) : ?>
                                <pre
                                  class="inlineSchema"><code><?php print json_encode(ibm_apim_return_inline_schema($response), JSON_PRETTY_PRINT); ?></code></pre>
                              <?php endif; ?>
                            </div><div
                              class='description'><?php print ibm_apim_markdown_field($response['description']); ?></div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </section>
              <section class='interact'>
                <h3 class='pathSegmentSpacer'></h3>
                <div
                  class='tabs tabs-major topBorder bottomBorder stickyHeader interact-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>'>
                  <div class="subscribeButton"><a style="font-weight: 500;" onclick="product.navigate('plans')"
                                                  href="javascript:;"><?php print t('Subscribe'); ?></a></div>
                </div>
                <div
                  class='tabs-content content-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment); ?>_<?php print $verb; ?>'>
                  <?php if ($protocol != 'wsdl') : ?>
                    <div
                      id='tab-content_code_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . '_' . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>'
                      class='tab-content code'>
                      <div class="rightSectionHeading"><?php print t('Example Request'); ?></div>
                      <ul class="langs-menu">
                        <li class="language current"><a
                            href="#langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-curl">cURL</a>
                        </li>
                        <li class="language"><a
                            href="#langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-ruby">Ruby</a>
                        </li>
                        <li class="language"><a
                            href="#langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-python">Python</a>
                        </li>
                        <li class="language"><a
                            href="#langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-php">PHP</a>
                        </li>
                        <li class="language"><a
                            href="#langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-java">Java</a>
                        </li>
                        <li class="language"><a
                            href="#langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-node">Node</a>
                        </li>
                        <li class="language"><a
                            href="#langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-go">Go</a>
                        </li>
                        <li class="language"><a
                            href="#langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-swift">Swift</a>
                        </li>
                      </ul>
                      <div class="langtab">
                        <div
                          id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-curl"
                          class="langtab-content show">
                          <pre class="bash"><code></code></pre>
                        </div>
                        <div
                          id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-ruby"
                          class="langtab-content">
                          <pre class="ruby"><code></code></pre>
                        </div>
                        <div
                          id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-python"
                          class="langtab-content">
                          <pre class="python"><code></code></pre>
                        </div>
                        <div
                          id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-php"
                          class="langtab-content">
                          <pre class="php"><code></code></pre>
                        </div>
                        <div
                          id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-java"
                          class="langtab-content">
                          <pre class="java"><code></code></pre>
                        </div>
                        <div
                          id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-node"
                          class="langtab-content">
                          <pre class="javascript"><code></code></pre>
                        </div>
                        <div
                          id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-go"
                          class="langtab-content">
                          <pre class="go"><code></code></pre>
                        </div>
                        <div
                          id="langtab-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>-swift"
                          class="langtab-content">
                          <pre class="swift"><code></code></pre>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                  <div
                    id='tab-content_example_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . '_paths_' . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>'
                    class='tab-content example'>
                    <div class="rightSectionHeading"><?php print t('Example Response'); ?></div>
                    <?php if ($protocol != 'wsdl') : ?>
                      <label><?php print t('Definition'); ?></label>
                      <div
                        class="exampleDefinition"><?php print strtoupper($verb) . ' '; ?><?php print $api['schemes'][0] . '://' . $api['host'] . $api['basePath'] . $pathSegment; ?></div>
                    <?php endif; ?>
                    <label><?php print t('Response'); ?></label>
                    <div class="exampleResponse">
                      <div class="exampleRespData"
                           id="exampleresponse_apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>">
                        <pre><code class="json"></code></pre>
                      </div>
                    </div>
                  </div>

                  <?php if ($ibm_apim_enable_api_test == 1 && (!isset($api['x-ibm-configuration']['testable']) || $api['x-ibm-configuration']['testable'] == TRUE)) : ?>
                    <div
                      id='tab-content_try_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . '_' . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>'
                      class='tab-content'>
                      <div class="rightSectionHeading"><?php print t('Try this operation'); ?></div>
                      <form class="testForm"
                            name="request_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . "_" . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>"
                            onsubmit="product.test('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print $pathSegment ?>');">
                        <input type="password" style="display:none;"/>
                        <input type="submit" style="display: none;" tabindex="-1"/>
                        <div class='contrast'>
                          <?php if (count($api['schemes']) > 1) : ?>
                            <select name="scheme">
                              <?php foreach ($api['schemes'] as $scheme)  : ?>
                                <option value="<?php print $scheme; ?>"><?php print $scheme; ?></option>
                              <?php endforeach; ?>
                            </select>
                            <span>://<?php print $api['host'] . $api['basePath'] . $pathSegment; ?></span>
                          <?php else: ?>
                            <input type="hidden" name="scheme" value="<?php print $api['schemes'][0]; ?>"/>
                            <span><?php print $api['schemes'][0] . '://' . $api['host'] . $api['basePath'] . $pathSegment; ?></span>
                          <?php endif; ?>
                        </div>
                        <div class="securitySection hidden">
                          <div class="identificationSection hidden">
                            <label><?php print t('Identification'); ?></label>
                            <div class="contrast">
                              <?php if (user_is_logged_in()) : ?>
                                <?php if (count($creds) > 0) : ?>
                                  <div class='parameter clientId hidden'>
                                    <div class='parameterName'><?php print t('Client ID'); ?></div>
                                    <select name="apimClientId" class='parameterValue'>
                                      <?php foreach ($creds as $cred)  : ?>
                                        <option
                                          value="<?php print $cred['id']; ?>"><?php print $cred['name']; ?></option>
                                      <?php endforeach; ?>
                                    </select>
                                  </div>
                                  <div class='parameter clientSecret hidden'>
                                    <div class='parameterName'><?php print t('Client secret'); ?></div>
                                    <input type="password" name="apimClientSecret" class='parameterValue'/>
                                  </div>
                                <?php else: ?>
                                  <div><?php print l(t('Register an application to test this API.'), 'application/new'); ?></div>
                                <?php endif; ?>
                              <?php else: ?>
                                <div><?php print l(t('Login to test this API.'), 'user/login'); ?></div>
                              <?php endif; ?>
                            </div>
                          </div>
                          <div class="authorizationSection hidden">
                            <label><?php print t('Authorization'); ?></label>
                            <?php if (user_is_logged_in()) : ?>
                              <div class="contrast userCredentials hidden">
                                <div class='parameter apimUsername'>
                                  <div class='parameterName'><?php print t('Username'); ?></div>
                                  <input name="apimUsername" class='parameterValue'/>
                                </div>
                                <div class='parameter apimPassword'>
                                  <div class='parameterName'><?php print t('Password'); ?></div>
                                  <input type="password" name="apimPassword" class='parameterValue'/>
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
                                <div class="parameter threelegged hidden">
                                  <div
                                    class='parameterName'><?php print t('Authorization URL'); ?></div>
                                  <div class='parameterValue authurl'></div>
                                </div>
                                <div class="tokenBasedFlow parameter hidden">
                                  <div class='parameterName'><?php print t('Token URL'); ?></div>
                                  <div class='parameterValue tokenurl'></div>
                                </div>
                                <div class="scopes">
                                  <label><?php print t('Scope'); ?></label>
                                  <div class="oauthscopes"></div>
                                </div>
                                <?php if (TRUE == FALSE) : ?>
                                  <!-- removed for v5 -->
                                  <div class="parameter twolegged buttons hidden">
                                    <button type="button"
                                            onclick="product.forgetToken('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print $pathSegment ?>')"
                                            title="<?php print t('Forget token'); ?>"><?php print t('Forget token'); ?></button>
                                    <button class="accessCodeFlow hidden" type="button"
                                            onclick="product.refreshToken('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print $pathSegment ?>')"
                                            title="<?php print t('Refresh token'); ?>"><?php print t('Refresh token'); ?></button>
                                    <button type="button"
                                            onclick="product.authorize('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print $pathSegment ?>')"
                                            title="<?php print t('Authorize'); ?>"><?php print t('Authorize'); ?></button>
                                  </div>
                                <?php endif; ?>
                                <div class="parameter accesstoken">
                                  <div class='parameterName'><?php print t('Access Token'); ?></div>
                                  <input type="password" name="authToken" class='result parameterValue'/>
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
                        unset($bodyparam); ?>
                        <?php if (isset($path['parameters']) && is_array($path['parameters'])) : ?>
                          <?php foreach ($path['parameters'] as $parameter)  : ?>
                            <?php if (isset($parameter['$ref'])) {
                              // handle parameter references
                              $parameter = api_get_ref_param($api, $parameter['$ref']);
                            } ?>
                            <?php if (isset($parameter['in']) && $parameter['in'] == "body")  : ?>
                              <?php $bodypresent = TRUE;
                              $bodyparam = $parameter; ?>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (isset($operation['parameters']) && is_array($operation['parameters'])) : ?>
                          <?php foreach ($operation['parameters'] as $parameter)  : ?>
                            <?php if (isset($parameter['$ref'])) {
                              // handle parameter references
                              $parameter = api_get_ref_param($api, $parameter['$ref']);
                            } ?>
                            <?php if (isset($parameter['in']) && $parameter['in'] == "body")  : ?>
                              <?php $bodypresent = TRUE;
                              $bodyparam = $parameter; ?>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ($bodypresent == TRUE)  : ?>
                          <label><?php print t('Body'); ?></label>
                          <div class="contrast">
                            <div class='parameter'
                                 id='body_apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_paths_<?php print preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>'>
                              <textarea name="<?php print $bodyparam['name']; ?>" class='parameterValue'></textarea>
                            </div>
                          </div>
                        <?php endif; ?>
                        <label><?php print t('Headers'); ?></label>
                        <div class="contrast">
                          <?php if (isset($bodypresent)) : ?>
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
                                  foreach ($consumes as $contenttype) {
                                    if ($contenttype == 'application/json') {
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
                                <input class='parameterValue' name="content-type" value='application/json'/>
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
                                foreach ($produces as $contenttype) {
                                  if ($contenttype == 'application/json') {
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
                              <input class='parameterValue' name="accept" value='application/json'/>
                            <?php endif; ?>
                          </div>
                        </div>
                        <?php if (isset($path['parameters']) && is_array($path['parameters']))  : ?>
                          <?php foreach ($path['parameters'] as $parameter)  : ?>
                            <?php if (isset($parameter['$ref'])) {
                              // handle parameter references
                              $parameter = api_get_ref_param($api, $parameter['$ref']);
                            } ?>
                            <?php if (isset($parameter['in']) && $parameter['in'] == "header")  : ?>
                              <div class='parameter'>
                                <div class='parameterName'><?php print $parameter['name']; ?></div>
                                <input <?php if ($parameter['required']) {
                                  print 'required';
                                } ?> name="<?php print $parameter['name']; ?>" class='parameterValue'/>
                              </div>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (isset($operation['parameters']) && is_array($operation['parameters']))  : ?>
                          <?php foreach ($operation['parameters'] as $parameter)  : ?>
                            <?php if (isset($parameter['$ref'])) {
                              // handle parameter references
                              $parameter = api_get_ref_param($api, $parameter['$ref']);
                            } ?>
                            <?php if (isset($parameter['in']) && $parameter['in'] == "header")  : ?>
                              <div class='parameter'>
                                <div class='parameterName'><?php print $parameter['name']; ?></div>
                                <input <?php if ($parameter['required']) {
                                  print 'required';
                                } ?> name="<?php print $parameter['name']; ?>" class='parameterValue'/>
                              </div>
                            <?php endif; ?>
                          <?php endforeach; ?>
                        <?php endif; ?>
                        <?php $paramspresent = FALSE;
                        if (isset($path['parameters']) && is_array($path['parameters'])) {
                          foreach ($path['parameters'] as $parameter) {
                            if (isset($parameter['$ref'])) {
                              // handle parameter references
                              $parameter = api_get_ref_param($api, $parameter['$ref']);
                            }
                            if (isset($parameter['in']) && ($parameter['in'] == "path" || $parameter['in'] == "query")) {
                              $paramspresent = TRUE;
                            }
                          }
                        }
                        if (isset($operation['parameters']) && is_array($operation['parameters'])) {
                          foreach ($operation['parameters'] as $parameter) {
                            if (isset($parameter['$ref'])) {
                              // handle parameter references
                              $parameter = api_get_ref_param($api, $parameter['$ref']);
                            }
                            if (isset($parameter['in']) && ($parameter['in'] == "path" || $parameter['in'] == "query")) {
                              $paramspresent = TRUE;
                            }
                          }
                        }
                        ?>
                        <?php if (isset($paramspresent) && $paramspresent == TRUE): ?>
                          <label><?php print t('Parameters'); ?></label>
                          <div class="contrast">
                            <?php if (isset($path['parameters']) && is_array($path['parameters'])) : ?>
                              <?php foreach ($path['parameters'] as $parameter)  : ?>
                                <?php if (isset($parameter['$ref'])) {
                                  // handle parameter references
                                  $parameter = api_get_ref_param($api, $parameter['$ref']);
                                } ?>
                                <?php if (isset($parameter['in']) && ($parameter['in'] == "path" || $parameter['in'] == "query"))  : ?>
                                  <div class='parameter'>
                                    <div class='parameterName'><?php print $parameter['name']; ?></div>
                                    <input <?php if (isset($parameter['required'])) {
                                      print 'required';
                                    } ?> name="<?php print $parameter['name']; ?>" class='parameterValue'/>
                                  </div>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (isset($operation['parameters']) && is_array($operation['parameters'])) : ?>
                              <?php foreach ($operation['parameters'] as $parameter)  : ?>
                                <?php if (isset($parameter['$ref'])) {
                                  // handle parameter references
                                  $parameter = api_get_ref_param($api, $parameter['$ref']);
                                } ?>
                                <?php if (isset($parameter['in']) && ($parameter['in'] == "path" || $parameter['in'] == "query"))  : ?>
                                  <div class='parameter'>
                                    <div class='parameterName'><?php print $parameter['name']; ?></div>
                                    <input <?php if (isset($parameter['required'])) {
                                      print 'required';
                                    } ?> name="<?php print $parameter['name']; ?>" class='parameterValue'/>
                                  </div>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>

                      </form>
                      <button style="margin: 0px 15px 15px 0px;" class='mesh floatRight'
                              onclick="product.test('<?php print $api['info']['x-ibm-name'] . $api['info']['version']; ?>', '<?php print $verb ?>', '<?php print $pathSegment ?>');"><?php print t('Call operation'); ?></button>
                      <div class='clearBoth'></div>
                      <div class="responseSection hidden"
                           id="response_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']) . '_' . preg_replace("/\W/", "", $pathSegment) . '_' . $verb; ?>">
                        <label><?php print t('Request'); ?></label>
                        <div>
                        <pre class="contrast"><code class="http requestDetails requestUrl"></code><code
                            class="http requestDetails requestHeaders"></code>
                        </pre>
                        </div>
                        <label><?php print t('Response'); ?></label>
                        <div>
                            <span
                              class="responseDetails mixedContentWarning hidden"><?php print t('No response. This is a mixed content call. It is not possible to test HTTP APIs from an HTTPS secured Portal site and vice versa.'); ?></span>
                            <span
                              class="responseDetails corsWarning hidden"><?php print t('No response. This is a cross-origin call. Make sure the server accepts requests from this portal. Or if using self-signed SSL certificates then paste the URL above into your browser to accept the certificate before trying again.'); ?></span>
                            <pre class="contrast"><code class="http responseDetails responseStatus"></code><code
                                class="http responseDetails responseHeaders"></code><code
                                class="responseDetails responseBody"></code>
                            </pre>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
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
          <a
            onclick='product.toggledefs("<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>")'
            href='javascript:;'>
            <h2
              class="navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_definitions"><?php print t('Definitions'); ?>
              <span class="expand_more"><i class="material-icons">expand_more</i></span><span class="expand_less"><i
                  class="material-icons">expand_less</i></span></h2></a>
          <div class="definitionsWrapper">
            <?php foreach ($api['definitions'] as $definitionName => $definition) : ?>
              <label
                class="navigate-apis_<?php print drupal_html_class($api['info']['x-ibm-name'] . $api['info']['version']); ?>_definitions_<?php print preg_replace("/\W/", "", $definitionName); ?>"><?php print $definitionName; ?></label>
              <?php if (isset($definition['externalDocs']) && !empty($definition['externalDocs']) && isset($definition['externalDocs']['url'])) : ?>
                <div>
                  <label><?php print t('Documentation'); ?></label>
                  <div><a href="<?php print $definition['externalDocs']['url']; ?>" target='_new'>
                      <?php if (isset($definition['externalDocs']['description'])) {
                        print $definition['externalDocs']['description'];
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
                  class="definitionDetails"><?php print json_encode($definition, JSON_PRETTY_PRINT); ?></code>
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
