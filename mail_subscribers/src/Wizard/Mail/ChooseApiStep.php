<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Wizard\Mail;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ChooseApiStep extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mail_subscribers_wizard_choose_api';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['intro'] = [
      '#markup' => '<p>' . t('This wizard will email all subscribers of product plans that contain the chosen APIs. Each subscriber will be sent an individual email.') . '</p>'
        . '<p>' . t('Enter the names of the APIs:') . '</p>',
      '#weight' => 0,
    ];

    $form['apis'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      '#tags' => TRUE,
      '#maxlength' => NULL,
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_settings' => [
        'target_bundles' => ['api'],
      ],
      '#title' => t('APIs'),
      '#description' => t('Type the first few characters of the API you would like to add then select from the available search results. Multiple apis can be added by separating them by a comma. Please note the search results are affected by which APIs you can access.'),

    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): ?bool {

    if (empty($form_state->getUserInput()['apis'])) {
      $form_state->setErrorByName('apis', t('You must select at least one API.'));
      return FALSE;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $apis = $form_state->getValue('apis');
    $cached_values['objectType'] = 'api';
    $values = [];
    foreach ($apis as $api) {
      $values[] = $api['target_id'];
    }
    $cached_values['apis'] = $values;

    $form_state->setTemporaryValue('wizard', $cached_values);

  }

}
