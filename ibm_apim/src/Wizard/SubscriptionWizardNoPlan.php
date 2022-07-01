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

namespace Drupal\ibm_apim\Wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\ibm_apim\Wizard\Subscription\ChooseApplicationStep;
use Drupal\ibm_apim\Wizard\Subscription\ConfirmSubscribe;
use Drupal\ibm_apim\Wizard\Subscription\SubscribeSummary;

/**
 * Class SubscriptionWizardNoPlan
 *
 * @package Drupal\ibm_apim\Wizard
 */
class SubscriptionWizardNoPlan extends FormWizardBase {

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  public function getWizardLabel() {
    return t('Subscription Wizard');
  }

  /**
   * @return string
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
      'form' => ChooseApplicationStep::class,
    ];

    $steps['confirm'] = [
      'title' => t('Subscribe'),
      'form' => ConfirmSubscribe::class,
    ];

    $steps['summary'] = [
      'title' => t('Summary'),
      'form' => SubscribeSummary::class,
    ];

    return $steps;
  }

  /**
   * Purely here so we can rename Previous to be "Back"
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    if (isset($form['actions']['previous'])) {
      $form['actions']['previous']['#value'] = t('Back');
      $form['actions']['previous']['#attributes'] = ['class' => ['button', 'apicSecondary']];
    }
    $cached_values = $form_state->getTemporaryValue('wizard');
    $step = $this->getStep($cached_values);
    //if ($step === 'confirm') {
    // commented out since this seems to break the wizard and the summary screen never appears
    //$form['actions']['submit']['#value'] = t('Subscribe');
    //}
    // using php union operator to get the cancel button at the beginning of the array
    $form['actions'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('view.products.page_1'),
        '#attributes' => ['class' => ['button', 'apicTertiary']],
      ] + $form['actions'];

    return $form;
  }

}
