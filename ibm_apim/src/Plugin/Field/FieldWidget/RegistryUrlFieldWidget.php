<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
namespace Drupal\ibm_apim\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

// TODO: should field_types include registry_url?
/**
 * A widget for registry_url.
 *
 * @FieldWidget(
 *   id = "registry_url_widget",
 *   label = @Translation("API Connect Registry URL"),
 *   field_types = {
 *     "registry_url_field",
 *     "string"
 *   }
 * )
 */
class RegistryUrlFieldWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = [
        '#type' => 'textfield',
        '#default_value' => '',
      ];

    return $element;
  }

}
