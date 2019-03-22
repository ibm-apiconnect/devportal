<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Banner block bean plugin.
 */
class BannerBlockBean extends BeanPlugin {
  private $_bean;

  /**
   * Builds extra settings for the block edit form.
   *
   * @param $bean
   * @param $form
   * @param $form_state
   * @return array
   */
  public function form($bean, $form, &$form_state) {
    $form = array();
    // add JS to update editor background with current image
    $form['#attached']['js'][] = array(
      'data' => 'jQuery(document).ready(function(){
          var action = jQuery("form#bean-form").attr("action");
          var langcode = action.substr(action.lastIndexOf("/") + 1);
          if (langcode == "edit") {
            langcode = "en";
          }

          var href = jQuery(".image-widget-data").find(".file a:first").attr("href");
          if (href) {
            jQuery("#edit-bannercontent-"+langcode+"-0-value_ifr").css("background-image","url(\'"+href+"\')");
            jQuery("#edit-bannercontent-"+langcode+"-0-value_ifr").removeClass("banner_block").removeClass("nocustomimg");
          } else {
            jQuery("#edit-bannercontent-"+langcode+"-0-value_ifr").addClass("banner_block").addClass("nocustomimg");
          }
          setTimeout(function () {
            var action = jQuery("form#bean-form").attr("action");
            var langcode = action.substr(action.lastIndexOf("/") + 1);
            if (langcode == "edit") {
              langcode = "en";
            }
            var href = jQuery(".image-widget-data").find(".file a:first").attr("href");
            if (href) {
              jQuery("#edit-bannercontent-"+langcode+"-0-value_ifr").css("background-image","url(\'"+href+"\')");
              jQuery("#edit-bannercontent-"+langcode+"-0-value_ifr").removeClass("banner_block").removeClass("nocustomimg");
            } else {
              jQuery("#edit-bannercontent-"+langcode+"-0-value_ifr").addClass("banner_block").addClass("nocustomimg");
            }
          }, 1000);

        });',
      'type' => 'inline'
    );

    return $form;
  }

  /**
   * Displays the bean.
   *
   * @param $bean
   * @param $content
   * @param string $view_mode
   * @param string $langcode
   * @return array
   */
  public function view($bean, $content, $view_mode = 'default', $langcode = 'en') {
    if (!isset($langcode) || empty($langcode)) {
      global $language;
      $langcode = $language->language;
    }
    global $base_url;
    // Set the bean instance
    $this->_bean = $bean;

    $alt = '';
    $title = '';
    $class = '';
    $bannercontent = '';
    if (isset($bean->bannerbgimage[$langcode][0]['uri'])) {
      $url = file_create_url($bean->bannerbgimage[$langcode][0]['uri']);
    }
    elseif (isset($bean->bannerbgimage['und'][0]['uri'])) {
      $url = file_create_url($bean->bannerbgimage['und'][0]['uri']);
    }
    if (isset($bean->bannerbgimage[$langcode][0]['title'])) {
      $title = 'title="' . $bean->bannerbgimage[$langcode][0]['title'] . '"';
    }
    elseif (isset($bean->bannerbgimage['und'][0]['title'])) {
      $title = 'title="' . $bean->bannerbgimage['und'][0]['title'] . '"';
    }
    if (isset($bean->bannerbgimage[$langcode][0]['alt'])) {
      $alt = 'alt="' . $bean->bannerbgimage[$langcode][0]['alt'] . '"';
    }
    elseif (isset($bean->bannerbgimage['und'][0]['alt'])) {
      $alt = 'alt="' . $bean->bannerbgimage['und'][0]['alt'] . '"';
    }
    if (!isset($url) || rtrim($url, "/") == $base_url) {
      $url = url(drupal_get_path('module', 'bannerblock') . '/images/poly_background.svg');
      $class = "nocustomimg";
    }
    if (isset($bean->bannercontent[$langcode][0]['value'])) {
      $bannercontent = $bean->bannercontent[$langcode][0]['value'];
    }
    elseif (isset($bean->bannercontent['und'][0]['value'])) {
      $bannercontent = $bean->bannercontent['und'][0]['value'];
    }
    $markup = '';
    if (isset($bean->bannerblocktitle[$langcode][0]['value'])) {
      $markup .= '<h2 class="pane-title block-title">' . $bean->bannerblocktitle[$langcode][0]['value'] . '</h2>';
    }
    elseif (isset($bean->bannerblocktitle['und'][0]['value'])) {
      $markup .= '<h2 class="pane-title block-title">' . $bean->bannerblocktitle['und'][0]['value'] . '</h2>';
    }
    $markup .= '<div class="banner_block ' . $class . '" style="background-image: url(' . $url . ');" ' . $alt . ' ' . $title . '>';
    $markup .= $bannercontent . "</div>";

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