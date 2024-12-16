<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Wizard\Mail;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ChooseConsumerOrgStep extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mail_subscribers_wizard_choose_consumerorg';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['intro'] = [
      '#markup' => '<p>' . t('This wizard will email all owners or members of the chosen consumer organization. Each user will be sent an individual email.') . '</p>'
        . '<p>' . t('Enter the names of the consumer organizations:') . '</p>',
      '#weight' => 0,
    ];

    $form['consumerorg'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_settings' => [
        'target_bundles' => ['consumerorg'],
      ],
      '#tags' => TRUE,
      '#maxlength' => NULL,
      '#title' => t('Type the first few characters of the consumer organizations you would like to select. You can then select from the available search results.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): ?bool {

    if (empty($form_state->getUserInput()['consumerorg'])) {
      $form_state->setErrorByName('consumerorg', t('You must select atleast one consumerorg.'));
      return FALSE;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $consumerorgs = $form_state->getValue('consumerorg');
    $cached_values['objectType'] = 'consumerorg';
    $values = [];
    foreach ($consumerorgs as $consumerorg) {
      $values[] = $consumerorg['target_id'];
    }
    $cached_values['consumerorgs'] = $values;

    $form_state->setTemporaryValue('wizard', $cached_values);
  }
}
