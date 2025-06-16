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

namespace Drupal\mail_subscribers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\mail_subscribers\Entity\EmailList;

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
    \Drupal::service('tempstore.private')->get('mail_subscribers')->set('data', []);

    $form['intro'] = [
      '#markup' => '<p>' . t('From here you can send messages to your Developer Portal community. Use the wizards below to send a message to specific segments or all of your API consumers. Each recipient will receive an individual email.') . '</p>'
        . '<p>' . t('You can email all subscribers of a specific Product, Plan or API, or all registered consumer organizations. For each consumer organization you can elect whether to email just the owner or all members.') . '</p>',
      '#weight' => 0,
    ];

    $options = [
      'product' => t('Product subscribers'),
      'plan' => t('Plan subscribers'),
      'api' => t('API subscribers'),
      'consumerorg' => t('Consumer organizations'),
      'all' => t('All users'),
    ];

    $lists = [];
    $query = \Drupal::entityQuery('mail_subscribers_email_list');
    $entityIds = $query->accessCheck()->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      $entities = EmailList::loadMultiple($entityIds);
      foreach($entities as $entity ) {
        $lists[$entity->id()] = $entity->get('title')->value;
      }
    }

    if (count($lists) > 0) {
      $options =array_merge(['list' => t('Selected list')],$options);
      $form['list'] = [
        '#type' => 'select',
        '#title' => 'Email lists',
        '#description' => t('Select a predefined list to send emails to.'),
        '#required' => False,
        '#weight' => 0,
        '#options' => $lists
      ];
    }

      $form['objectType'] = [
      '#type' => 'radios',
      '#title' => t('Who would you like to email?'),
      '#options' => $options,
      '#description' => t('You can email the members of specific consumer organizations or subscribers of given products, plans or APIs. Or alternatively email all registered consumer organizations. Select which to use.'),
      '#default_value' => empty($lists) ? 'product' : 'list',
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

    if ($objectType === 'list') {
      $listId = $form_state->getValue('list');
      $entity = EmailList::load($listId);
      if (empty($entity)) {
        $form_state->setErrorByName('list', $this->t('Selected email list does not exist.'));
      }
    }

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
    if ($objectType === 'list') {
      $listId = $form_state->getValue('list');
      $entity = EmailList::load($listId);
      $cached_values = $entity->get('data')->getValue()[0];

      $objectType = $cached_values['objectType'];

      \Drupal::service('tempstore.private')->get('mail_subscribers')->set('data', $cached_values);
      $step = 'entercontent';
    }

    if ($objectType === 'product') {
      if (!empty($step)) {
        $url = Url::fromRoute('mail_subscribers.product_wizard.step', ['step' => $step], ['query' => ['start' => 1]]);
      } else {
        $url = Url::fromRoute('mail_subscribers.product_wizard', [], ['query' => ['start' => 1]]);
      }
      $form_state->setRedirectUrl($url);
    }
    elseif ($objectType === 'consumerorg') {
      if (!empty($step)) {
        $url = Url::fromRoute('mail_subscribers.consumerorg_wizard.step', ['step' => $step], ['query' => ['start' => 1]]);
      } else {
        $url = Url::fromRoute('mail_subscribers.consumerorg_wizard', [], ['query' => ['start' => 1]]);
      }
      $form_state->setRedirectUrl($url);
    }
    elseif ($objectType === 'api') {
      if (!empty($step)) {
        $url = Url::fromRoute('mail_subscribers.api_wizard.step', ['step' => $step], ['query' => ['start' => 1]]);
      } else {
        $url = Url::fromRoute('mail_subscribers.api_wizard', [], ['query' => ['start' => 1]]);
      }
      $form_state->setRedirectUrl($url);
    }
    elseif ($objectType === 'plan') {
      if (!empty($step)) {
        $url = Url::fromRoute('mail_subscribers.plan_wizard.step', ['step' => $step], ['query' => ['start' => 1]]);
      } else {
        $url = Url::fromRoute('mail_subscribers.plan_wizard', [], ['query' => ['start' => 1]]);
      }
      $form_state->setRedirectUrl($url);
    }
    elseif ($objectType === 'all') {
      if (!empty($step)) {
        $url = Url::fromRoute('mail_subscribers.all_wizard.step', ['step' => $step], ['query' => ['start' => 1]]);
      } else {
        $url = Url::fromRoute('mail_subscribers.all_wizard', [], ['query' => ['start' => 1]]);
      }
      $form_state->setRedirectUrl($url);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
