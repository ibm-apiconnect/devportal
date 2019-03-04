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

namespace Drupal\mail_subscribers\Wizard\Mail;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

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
      '#markup' => '<p>' . t('This wizard will email all subscribers of product plans that contain a specific API. Each subscriber will be sent an individual email.') . '</p>'
        . '<p>' . t('Enter the name of the API:') . '</p>',
      '#weight' => 0,
    ];

    $form['api'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_settings' => [
        'target_bundles' => ['api'],
      ],
      '#title' => t('Type the first few characters of the API you would like to select. You can then select from the available search results. Please note the search results are affected by which APIs you can access.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (empty($form_state->getUserInput()['api'])) {
      $form_state->setErrorByName('api', t('You must select an API.'));
      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $api = $form_state->getValue('api');

    $cached_values['objectType'] = 'api';
    $cached_values['api'] = $api;

    $form_state->setTemporaryValue('wizard', $cached_values);

  }

}
