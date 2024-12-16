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

class ChooseProductStep extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mail_subscribers_wizard_choose_product';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $cached_values = $form_state->getTemporaryValue('wizard');
    if ($cached_values['objectType'] === 'plan') {
      $form['intro'] = [
        '#markup' => '<p>' . t('This wizard will email all subscribers of a specific plan for a specific Product. Each subscriber will be sent an individual email.') . '</p>'
          . '<p>' . t('Select the Products below and then select one of its Plans on the next page.') . '</p>',
        '#weight' => 0,
      ];
    } else {
      $form['intro'] = [
        '#markup' => '<p>' . t('This wizard will email all subscribers of any plan for a specific Product. Each subscriber will be sent an individual email.') . '</p>'
          . '<p>' . t('Enter the name of the Product:') . '</p>',
        '#weight' => 0,
      ];
    }

    $form['products'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      '#tags' => TRUE,
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_settings' => [
        'target_bundles' => ['product'],
      ],
      '#maxlength' => NULL,
      '#title' => t('Products'),
      '#description' => t('Type the first few characters of the Product you would like to add then select from the available search results. Multiple products can be added by separating them by a comma. Please note the search results are affected by which Products you can access.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): ?bool {

    if (empty($form_state->getUserInput()['products'])) {
      $form_state->setErrorByName('products', t('You must select at least one Product.'));
      return FALSE;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $products = $form_state->getValue('products');
    $cached_values['objectType'] = 'product';
    $values = [];
    foreach ($products as $product) {
      $values[] = $product['target_id'];
    }
    $cached_values['products'] = $values;

    $form_state->setTemporaryValue('wizard', $cached_values);
  }
}
