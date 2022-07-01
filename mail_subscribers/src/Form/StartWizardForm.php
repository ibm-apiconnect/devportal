<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form to start the subscription wizards.
 */
class StartWizardForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mail_subscribers_start_wizard_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $form['intro'] = [
      '#markup' => '<p>' . t('From here you can send messages to your Developer Portal community. Use the wizards below to send a message to specific segments or all of your API consumers. Each recipient will receive an individual email.') . '</p>'
        . '<p>' . t('You can email all subscribers of a specific Product, Plan or API, or all registered consumer organizations. For each consumer organization you can elect whether to email just the owner or all members.') . '</p>',
      '#weight' => 0,
    ];

    $options = [
      'product' => t('Product subscribers'),
      'plan' => t('Plan subscribers'),
      'api' => t('API subscribers'),
      'all' => t('All users'),
    ];

    $form['objectType'] = [
      '#type' => 'radios',
      '#title' => t('Who would you like to email?'),
      '#options' => $options,
      '#description' => t('You can email the subscribers of a given product, plan or API. Or alternatively email all registered consumer organizations. Select which to use.'),
      '#default_value' => 'product',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $objectType = $form_state->getValue('objectType');

    if ($objectType === NULL || empty($objectType)) {
      $form_state->setErrorByName('objectType', $this->t('Object type is a required field.'));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $objectType = $form_state->getValue('objectType');
    if ($objectType === 'product') {
      $url = Url::fromRoute('mail_subscribers.product_wizard');
      $form_state->setRedirectUrl($url);
    }
    elseif ($objectType === 'api') {
      $url = Url::fromRoute('mail_subscribers.api_wizard');
      $form_state->setRedirectUrl($url);
    }
    elseif ($objectType === 'plan') {
      $url = Url::fromRoute('mail_subscribers.plan_wizard');
      $form_state->setRedirectUrl($url);
    }
    elseif ($objectType === 'all') {
      $url = Url::fromRoute('mail_subscribers.all_wizard');
      $form_state->setRedirectUrl($url);
    }
    else {
      $form_state->setErrorByName('objectType', $this->t('Invalid object type value.'));
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
