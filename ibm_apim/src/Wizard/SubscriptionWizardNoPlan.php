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

namespace Drupal\ibm_apim\Wizard;

use Drupal\ctools\Wizard\FormWizardBase;

class SubscriptionWizardNoPlan extends FormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return t('Subscription Wizard');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel(): string {
    return 'subscription_wizard.noplan';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(): string {
    return 'ibm_apim.subscription_wizard.noplan.step';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values): array {
    $steps = [];

    $steps['chooseapp'] = [
      'title' => t('Select Application'),
      'form' => 'Drupal\ibm_apim\Wizard\Subscription\ChooseApplicationStep',
    ];

    $steps['confirm'] = [
      'title' => t('Subscribe'),
      'form' => 'Drupal\ibm_apim\Wizard\Subscription\ConfirmSubscribe',
    ];

    $steps['summary'] = [
      'title' => t('Summary'),
      'form' => 'Drupal\ibm_apim\Wizard\Subscription\SubscribeSummary',
    ];

    return $steps;
  }

}
