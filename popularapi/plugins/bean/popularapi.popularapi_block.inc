<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * PopularAPI block bean plugin.
 */

/**
 * Class PopularAPIBlockBean
 */
class PopularAPIBlockBean extends BeanPlugin {
  private $_bean;

  /**
   * Declares default block settings.
   *
   * @return array
   */
  public function values() {
    return array(
      'apinid' => ''
    );
  }

  /**
   * Builds extra settings for the block edit form.
   *
   * @param $bean
   * @param $form
   * @param $form_state
   * @return array
   */
  public function form($bean, $form, &$form_state) {

    $nid_options = array();

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'api');
    $results = $query->execute();
    if (isset($results['node'])) {
      $nids = array_keys($results['node']);
      $nodes = node_load_multiple($nids);
      foreach ($nodes as $api) {
        $nid_options[$api->nid] = $api->api_ref[$api->language][0]['value'];
      }
    }

    $form['apinid'] = array(
      '#title' => t('API'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $nid_options,
      '#default_value' => isset($bean->apinid) ? $bean->apinid : ''
    );

    return $form;
  }

  /**
   * Displays the bean.
   *
   * @param $bean
   * @param $content
   * @param string $view_mode
   * @param null $langcode
   * @return array
   */
  public function view($bean, $content, $view_mode = 'default', $langcode = 'en') {
    if (!isset($langcode) || empty($langcode)) {
      global $language;
      $langcode = $language->language;
    }
    // Set the bean instance
    $markup = '';
    $notop = '';
    $this->_bean = $bean;
    $nid = $bean->apinid;
    if (isset($nid)) {
      $node = node_load($nid);
      if (isset($node)) {
        $showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1);

        $title = isset($node->title) ? $node->title : '';
        $version = isset($node->api_version[$node->language][0]['value']) ? $node->api_version[$node->language][0]['value'] : '';
        if (isset($bean->popapiblocktitle[$langcode][0]['value'])) {
          $blocktitle = $bean->popapiblocktitle[$langcode][0]['value'];
        } else {
          $blocktitle = $bean->popapiblocktitle['und'][0]['value'];
        }
        if (isset($node->api_description[$langcode][0]['value']) && !empty($node->api_description[$langcode][0]['value'])) {
          $description = $node->api_description[$langcode][0]['value'];
        } elseif (isset($node->api_description[$node->language][0]['value']) && !empty($node->api_description[$node->language][0]['value'])) {
          $description = $node->api_description[$node->language][0]['value'];
        } else {
          $description = '';
          $notop = 'notop';
        }

        $markup = '';

        $markup .= '<h2 class="pane-title block-title">' . check_plain($blocktitle) . '</h2>';
        $markup .= '<div class="popularapiblock api"><a href="' . url('node/' . $nid) . '" title="' . check_plain($blocktitle) . '"><div class="wrapper"><div class="popularapi_image">';
        if (isset($node->api_image) && !empty($node->api_image)) {
          $view = field_view_field('node', $node, 'api_image');
          $markup .= render($view);
        }
        elseif ($showplaceholders != 0) {
          $markup .= '<div class="popularIcon">
			 <div class="field field-name-api-image field-type-image field-label-hidden view-mode-teaser">
			   <div class="field-items">
				 <figure class="clearfix field-item even">
				   <img typeof="foaf:Image" style="margin-left: auto;margin-right: auto;" class="image-style-none" src="' . file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/api/' . api_random_image($title)) . '" width="123" height="123" alt="">
				 </figure>
		       </div>
			 </div>
		   </div>';
        }
        $markup .= '</div>'; // api image
        $markup .= '<div class="popularapi_title">' . popularapi_truncate_string($title, 50) . '<span class="version">' . $version . '</span></div>';
        $markup .= '<div class="popularapi_description ' . $notop . '">' . ibm_apim_markdown_field($description) . '</div>';
        $markup .= '<div class="popularapi_detaillink"><a href="' . url('node/' . $nid) . '" title="' . check_plain($blocktitle) . '"><span>' . t('Detail') . '</span></a></div>';
        $markup .= '</div></a></div>';
      }
    }
    // hide the raw field since we're handling the rendering
    $id = $bean->delta;
    unset($content['bean'][$id]['apinid']);

    // Add the markup to the render array along with our CSS file
    $content[] = array(
      '#markup' => $markup,
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'popularapi') . '/popularapi.css',
        )
      )
    );

    return $content;
  }
}