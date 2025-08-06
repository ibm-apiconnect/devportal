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

namespace Drupal\mail_subscribers\Wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\ctools\Wizard\FormWizardInterface;
use Drupal\mail_subscribers\Wizard\Mail\ChooseConsumerOrgStep;
use Drupal\mail_subscribers\Wizard\Mail\ChooseRoleStep;
use Drupal\mail_subscribers\Wizard\Mail\EnterContentStep;
use Drupal\mail_subscribers\Wizard\Mail\ConfirmSend;
use Drupal\mail_subscribers\Wizard\Mail\MailSummary;

class ConsumerOrganizationWizard extends FormWizardBase {

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  public function getWizardLabel() {
    return t('Mail Consumer Organizations Wizard');
  }

  /**
   * @return string
   *
   */
  public function getMachineLabel(): string {
    return 'mail_consumerorg_wizard';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(): string {
    return 'mail_subscribers.consumerorg_wizard.step';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values): array {
    $steps = [];

    $steps['chooseconsumerorgs'] = [
      'title' => t('Select consumer organizations'),
      'form' => ChooseConsumerOrgStep::class,
    ];
    $steps['choosesubs'] = [
      'title' => t('Select Users'),
      'form' => ChooseRoleStep::class,
    ];
    $steps['entercontent'] = [
      'title' => t('Enter content'),
      'form' => EnterContentStep::class,
    ];

    $steps['confirm'] = [
      'title' => t('Confirm'),
      'form' => ConfirmSend::class,
    ];

    $steps['summary'] = [
      'title' => t('Summary'),
      'form' => MailSummary::class,
    ];

    return $steps;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $data = \Drupal::service('tempstore.private')->get('mail_subscribers')->get('data');
    if ($this->getStep($cached_values) === 'chooseconsumerorgs' && \Drupal::request()->get('start') === '1') {
      $cached_values = [];
    }
    $cached_values['objectType'] = 'consumerorg';
    if (isset($cached_values['products'])) {
      unset($cached_values['products']);
    }
    if (isset($cached_values['apis'])) {
      unset($cached_values['apis']);
    }
    if (isset($cached_values['plans'])) {
      unset($cached_values['plans']);
    }
    if (!empty($data)) {
      $cached_values = array_merge($cached_values, $data);
      $cached_values['predefined'] = true;
    }

    $form_state->setTemporaryValue('wizard', $cached_values);

    $form = parent::buildForm($form, $form_state);

    if ($this->getStep($cached_values) === 'summary') {
      unset($form['actions']['previous']);
    }

    return $form;
  }

  public function initValues() {
    $values = [];
    $event = new WizardEvent($this, $values);
    $this->dispatcher->dispatch($event, FormWizardInterface::LOAD_VALUES);
    $tempValues = $event->getValues();
    $tempValues['objectType'] = 'consumerorg';
    $event->setValues($tempValues);
    return $event->getValues();
  }
}
