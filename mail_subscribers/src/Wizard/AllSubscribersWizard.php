<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Wizard;

use Drupal\ctools\Event\WizardEvent;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\ctools\Wizard\FormWizardInterface;

class AllSubscribersWizard extends FormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return t('Mail All Subscribers Wizard');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('mail_all_subscribers_wizard');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'mail_subscribers.all_wizard.step';
  }

  /**
   * {@inheritdoc}
   */
  function getOperations($cached_values) {
    $steps = array();

    $steps['choosesubs'] = array(
      'title' => t('Select Subscribers'),
      'form' => 'Drupal\mail_subscribers\Wizard\Mail\ChooseRoleStep'
    );

    $steps['entercontent'] = array(
      'title' => t('Enter content'),
      'form' => 'Drupal\mail_subscribers\Wizard\Mail\EnterContentStep'
    );

    $steps['confirm'] = array(
      'title' => t('Confirm'),
      'form' => 'Drupal\mail_subscribers\Wizard\Mail\ConfirmSend'
    );

    $steps['summary'] = array(
      'title' => t('Summary'),
      'form' => 'Drupal\mail_subscribers\Wizard\Mail\MailSummary'
    );

    return $steps;
  }

  public function initValues() {
    $values = [];
    $event = new WizardEvent($this, $values);
    $this->dispatcher->dispatch(FormWizardInterface::LOAD_VALUES, $event);
    $tempvalues = $event->getValues();
    $tempvalues['objectType'] = 'all';
    $event->setValues($tempvalues);
    return $event->getValues();
  }

}
