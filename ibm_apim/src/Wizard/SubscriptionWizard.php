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

namespace Drupal\ibm_apim\Wizard;

use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\Core\Form\FormStateInterface;

class SubscriptionWizard extends FormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return t('Subscription Wizard');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('subscription_wizard');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'ibm_apim.subscription_wizard.step';
  }

  /**
   * {@inheritdoc}
   */
  function getOperations($cached_values) {
    $steps = array();

    $steps['chooseplan'] = array(
      'title' => t('Select Plan'),
      'form' => 'Drupal\ibm_apim\Wizard\Subscription\ChoosePlanStep'
    );

    $steps['chooseapp'] = array(
      'title' => t('Select Application'),
      'form' => 'Drupal\ibm_apim\Wizard\Subscription\ChooseApplicationStep'
    );

    $steps['confirm'] = array(
      'title' => t('Subscribe'),
      'form' => 'Drupal\ibm_apim\Wizard\Subscription\ConfirmSubscribe'
    );

    $steps['summary'] = array(
      'title' => t('Summary'),
      'form' => 'Drupal\ibm_apim\Wizard\Subscription\SubscribeSummary'
    );

    return $steps;
  }

}
