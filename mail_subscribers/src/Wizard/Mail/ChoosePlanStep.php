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

namespace Drupal\mail_subscribers\Wizard\Mail;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\node\Entity\Node;

class ChoosePlanStep extends FormBase {

  protected $plans;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_subscribers_wizard_choose_plan';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = array(
      '#markup' => '<p>' . t('Select the desired Plan below.') . '</p>',
      '#weight' => 0
    );

    $cached_values = $form_state->getTemporaryValue('wizard');
    $product = $cached_values['product'];

    if (empty($product)) {
      $wizard_url = \Drupal::l(t('Plan subscription wizard'), \Drupal\Core\Url::fromRoute('mail_subscribers.plan_wizard'));
      drupal_set_message(t("Email wizard was invoked with no product. Start the wizard again from the %wizardurl page.", array('%wizardurl' => $wizard_url)), 'error');
      $this->redirect("<front>")->send();
      return NULL;
    }
    $product = Node::load($product);

    $options = array();

    $productPlans = array();
    foreach($product->product_plans->getValue() as $arrayValue) {
      $productPlans[] = unserialize($arrayValue['value']);
    }
    if (!isset($productPlans) || empty($productPlans)) {
      $wizard_url = \Drupal::l(t('Plan subscription wizard'), \Drupal\Core\Url::fromRoute('mail_subscribers.plan_wizard'));
      drupal_set_message(t("No plans found for this product. Start the wizard again from the %wizardurl page.", array('%wizardurl' => $wizard_url)), 'error');
      $this->redirect("<front>")->send();
      return NULL;
    }
    foreach ($productPlans as $planname => $plan) {
      $options[$plan['name']] = $plan['title'];
    }
    $this->plans = $options;
    $keys = array_keys($options);
    $default = reset($keys);

    $form['plan'] = array(
      '#type' => 'radios',
      '#title' => t('Plan'),
      '#options' => $options,
      '#description' => t('Select which product plan to use'),
      '#default_value' => $default,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getUserInput())) {
      $form_state->setErrorByName('plan', t('You must select a plan.'));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $plan = $form_state->getUserInput()['plan'];

    $cached_values['objectType'] = 'plan';
    $cached_values['plan'] = array('name' =>$plan, 'title' => $this->plans[$plan]['title']);

    $form_state->setTemporaryValue('wizard', $cached_values);

  }

}
