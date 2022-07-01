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

namespace Drupal\mail_subscribers\Wizard\Mail;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\Messenger;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChoosePlanStep extends FormBase {

  protected $plans;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * ChoosePlanStep constructor.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(Messenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ChoosePlanStep {
    // Load the service required to construct this class
    return new static($container->get('messenger'));
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mail_subscribers_wizard_choose_plan';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['intro'] = [
      '#markup' => '<p>' . t('Select the desired Plan below.') . '</p>',
      '#weight' => 0,
    ];

    $cached_values = $form_state->getTemporaryValue('wizard');
    $product = $cached_values['product'];

    if (empty($product)) {
      $wizardUrl = Link::fromTextAndUrl(t('Plan subscription wizard'), \Drupal\Core\Url::fromRoute('mail_subscribers.plan_wizard'));
      $this->messenger->addError(t('Email wizard was invoked with no product. Start the wizard again from the %wizardurl page.', ['%wizardurl' => $wizardUrl]));
      $this->redirect('<front>')->send();
      return [];
    }
    $product = Node::load($product);

    $options = [];

    $productPlans = [];
    foreach ($product->product_plans->getValue() as $arrayValue) {
      $productPlans[] = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
    }
    if ($productPlans === NULL || empty($productPlans)) {
      $wizard_url = Link::fromTextAndUrl(t('Plan subscription wizard'), \Drupal\Core\Url::fromRoute('mail_subscribers.plan_wizard'));
      $this->messenger->addError(t('No plans found for this product. Start the wizard again from the %wizardurl page.', ['%wizardurl' => $wizard_url]));
      $this->redirect('<front>')->send();
      return [];
    }
    foreach ($productPlans as $plan) {
      $options[$plan['name']] = $plan['title'];
    }
    $this->plans = $options;
    $keys = array_keys($options);
    $default = reset($keys);

    $form['plan'] = [
      '#type' => 'radios',
      '#title' => t('Plan'),
      '#options' => $options,
      '#description' => t('Select which product plan to use'),
      '#default_value' => $default,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): ?bool {
    if (empty($form_state->getUserInput())) {
      $form_state->setErrorByName('plan', t('You must select a plan.'));
      return FALSE;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $plan = $form_state->getUserInput()['plan'];

    $cached_values['objectType'] = 'plan';
    $cached_values['plan'] = ['name' => $plan, 'title' => $this->plans[$plan]];

    $form_state->setTemporaryValue('wizard', $cached_values);

  }

}
