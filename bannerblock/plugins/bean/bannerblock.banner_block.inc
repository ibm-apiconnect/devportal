<?php
/**
 * @file
 * Banner block bean plugin.
 */
class BannerBlockBean extends BeanPlugin {
  private $_bean;

  /**
   * Builds extra settings for the block edit form.
   */
  public function form($bean, $form, &$form_state) {
    $form = array();
    // add JS to update editor background with current image
    $form['#attached']['js'][] = array(
        'data' => 'jQuery(document).ready(function(){
          var href = jQuery(".image-widget-data").find(".file a:first").attr("href");
          jQuery("#edit-bannercontent-und-0-value_ifr").css("background-image","url("+href+")");

          setTimeout(function () {
            var href = jQuery(".image-widget-data").find(".file a:first").attr("href");
            jQuery("#edit-bannercontent-und-0-value_ifr").css("background-image","url(\'"+href+"\')");
          }, 1000);

        });',
        'type' => 'inline');

    return $form;
  }

  /**
   * Displays the bean.
   */
  public function view($bean, $content, $view_mode = 'default', $langcode = NULL) {
    global $base_url;
    // Set the bean instance
    $this->_bean = $bean;

    $alt = '';
    $title = '';
    $class = '';
    if (isset($bean->bannerbgimage['und'][0]['uri'])) {
      $url = file_create_url($bean->bannerbgimage['und'][0]['uri']);
    }
    if (isset($bean->bannerbgimage['und'][0]['title'])) {
      $title = 'title="' . $bean->bannerbgimage['und'][0]['title'] . '"';
    }
    if (isset($bean->bannerbgimage['und'][0]['alt'])) {
      $alt = 'alt="' . $bean->bannerbgimage['und'][0]['alt'] . '"';
    }
    if (!isset($url) || rtrim($url, "/") == $base_url) {
      $url = url(drupal_get_path('module', 'bannerblock') . '/images/poly_background.svg');
      $class = "nocustomimg";
    }

    $markup = '<div class="banner_block ' . $class . '" style="background-image: url(' . $url . ');" ' . $alt . ' ' . $title . '>';
    $markup .= $bean->bannercontent['und'][0]['value'] . "</div>";

    // hide the raw fields since we're handling the rendering
    $id = $bean->delta;
    unset($content['bean'][$id]['bannerbgimage']);
    unset($content['bean'][$id]['bannercontent']);

    // Add the markup to the render array
    $content[] = array(
      '#markup' => $markup,
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'bannerblock') . '/bannerblock.css',
        )
      )
    );

    return $content;
  }
}