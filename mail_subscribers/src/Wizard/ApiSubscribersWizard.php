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

namespace Drupal\mail_subscribers\Wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\ctools\Wizard\FormWizardInterface;
use Drupal\mail_subscribers\Wizard\Mail\ChooseApiStep;
use Drupal\mail_subscribers\Wizard\Mail\ChooseRoleStep;
use Drupal\mail_subscribers\Wizard\Mail\EnterContentStep;
use Drupal\mail_subscribers\Wizard\Mail\ConfirmSend;
use Drupal\mail_subscribers\Wizard\Mail\MailSummary;

class ApiSubscribersWizard extends FormWizardBase {

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  public function getWizardLabel() {
    return t('Mail API Subscribers Wizard');
  }

  /**
   * @return string
   */
  public function getMachineLabel(): string {
    return 'mail_api_subscribers_wizard';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(): string {
    return 'mail_subscribers.api_wizard.step';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values): array {
    $steps = [];

    $steps['chooseitem'] = [
      'title' => t('Select an API'),
      'form' => ChooseApiStep::class,
    ];

    $steps['choosesubs'] = [
      'title' => t('Select Subscribers'),
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

    $cached_values['objectType'] = 'api';
    if (isset($cached_values['product'])) {
      unset($cached_values['product']);
    }
    if (isset($cached_values['plan'])) {
      unset($cached_values['plan']);
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
    $tempValues['objectType'] = 'api';
    $event->setValues($tempValues);
    return $event->getValues();
  }

}
