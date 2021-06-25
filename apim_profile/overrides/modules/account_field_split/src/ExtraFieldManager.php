<?php

namespace Drupal\account_field_split;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ExtraFieldManager.
 *
 * Split User useless "User name and password" field into extra fields which
 * could be managed separately.
 */
class ExtraFieldManager {

  use StringTranslationTrait;

  /**
   * Do form alter.
   */
  public function formAlter(array &$form, FormStateInterface &$form_state, $form_id) {

    if (isset($form['account']) && is_array($form['account'])) {
      $account_elements = Element::children($form['account']);
      foreach ($account_elements as $account_element) {
        if (isset($form['account'][$account_element])) {
          $form[$account_element] = $form['account'][$account_element];
          unset($form['account'][$account_element]);
        }
      }
    }
  }

  /**
   * Return list of extra fields.
   *
   * @return array
   *   Array of extra fields description.
   */
  public function extraFieldInfo() {

    $extra = [];
    $fields = [
      'mail' => 'E-mail address',
      'name' => 'Username',
      'pass' => 'Password',
      'status' => 'Status',
      'roles' => 'Roles',
      'notify' => 'Notify user about new account',
      'current_pass' => 'Current password',
    ];
    $description = $this->t('User profile element');

    foreach ($fields as $field => $label) {
      $extra['user']['user']['form'][$field] = [
        'label' => $label,
        'description' => $description,
        'weight' => 0,
        'visible' => TRUE,
      ];
    }

    return $extra;
  }

}
