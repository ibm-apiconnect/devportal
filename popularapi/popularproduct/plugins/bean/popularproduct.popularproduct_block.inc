<?php

/**
 * @file
 * PopularAPI block bean plugin.
 */

/**
 * Class PopularProductBlockBean
 */
class PopularProductBlockBean extends BeanPlugin {
  private $_bean;

  /**
   * Declares default block settings.
   *
   * @return array
   */
  public function values() {
    return array(
      'productnid' => ''
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
      ->entityCondition('bundle', 'product');
    $results = $query->execute();
    if (isset($results['node'])) {
      $nids = array_keys($results['node']);
      $nodes = node_load_multiple($nids);
      foreach ($nodes as $product) {
        $nid_options[$product->nid] = $product->product_ref[$product->language][0]['value'];
      }
    }

    $form['productnid'] = array(
      '#title' => t('Product'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $nid_options,
      '#default_value' => isset($bean->productnid) ? $bean->productnid : ''
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
  public function view($bean, $content, $view_mode = 'default', $langcode = NULL) {
    // Set the bean instance
    $markup = '';
    $this->_bean = $bean;
    $nid = $bean->productnid;
    if (isset($nid)) {
      $node = node_load($nid);
      if (isset($node)) {
        $showplaceholders = variable_get('ibm_apim_show_placeholder_images', 1);
        $notop = '';
        $title = $node->title;
        $description = $node->product_description[$node->language][0]['value'];
        $version = $node->product_version[$node->language][0]['value'];
        if (!isset($title)) {
          $title = '';
        }
        if (!isset($description) || empty($description)) {
          $description = '';
          $notop = 'notop';
        }
        if (!isset($version)) {
          $version = '';
        }
        $markup = '<div class="popularapiblock product"><a href="' . url('node/' . $nid) . '"><div class="wrapper"><div class="popularapi_image">';
        if (isset($node->product_image) && !empty($node->product_image)) {
          $view = field_view_field('node', $node, 'product_image');
          $markup .= render($view);
        }
        elseif ($showplaceholders != 0) {
          $markup .= '<div class="popularIcon">
			 <div class="field field-name-api-image field-type-image field-label-hidden view-mode-teaser">
			   <div class="field-items">
				 <figure class="clearfix field-item even">
				   <img typeof="foaf:Image" style="margin-left: auto;margin-right: auto;" class="image-style-none" src="' . file_create_url(drupal_get_path('module', 'ibm_apim') . '/images/icons/product/' . product_random_image($title)) . '" width="123" height="123" alt="">
				 </figure>
		       </div>
			 </div>
		   </div>';
        }
        $markup .= '</div>'; // product image
        $markup .= '<div class="popularapi_title">' . popularproduct_truncate_string($title, 50) . '<span class="version">' . $version . '</span></div>';
        $markup .= '<div class="popularapi_description ' . $notop . '">' . ibm_apim_markdown_field($description) . '</div>';
        $markup .= '<div class="popularapi_detaillink"><span>' . t('Detail') . '</span></div>';
        $markup .= '</div></a></div>';
      }
    }
    // hide the raw field since we're handling the rendering
    $id = $bean->delta;
    unset($content['bean'][$id]['productnid']);

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