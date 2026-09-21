<?php


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
 *   label = @Translation("API Management Registry URL"),
 *   field_types = {
 *     "registry_url_field",
 *     "string"
 *   }
 * )
 */
class RegistryUrlFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['value'] = [
      '#type' => 'textfield',
      '#default_value' => '',
    ];

    return $element;
  }

}
