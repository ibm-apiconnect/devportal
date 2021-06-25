<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
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
          . '<p>' . t('Select the Product below and then select one of its Plans on the next page.') . '</p>',
        '#weight' => 0,
      ];
    }
    else {
      $form['intro'] = [
        '#markup' => '<p>' . t('This wizard will email all subscribers of any plan for a specific Product. Each subscriber will be sent an individual email.') . '</p>'
          . '<p>' . t('Enter the name of the Product:') . '</p>',
        '#weight' => 0,
      ];
    }

    $form['product'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_settings' => [
        'target_bundles' => ['product'],
      ],
      '#title' => t('Type the first few characters of the Product you would like to select. You can then select from the available search results. Please note the search results are affected by which Products you can access.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): ?bool {

    if (empty($form_state->getUserInput()['product'])) {
      $form_state->setErrorByName('product', t('You must select a Product.'));
      return FALSE;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $product = $form_state->getValue('product');

    $cached_values['objectType'] = 'product';
    $cached_values['product'] = $product;

    $form_state->setTemporaryValue('wizard', $cached_values);

  }

}
