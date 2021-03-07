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

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\SubscriptionService;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to unsubscribe an application from a plan.
 */
class UnsubscribeForm extends ConfirmFormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * This represents the subscription entity
   *
   * @var \Drupal\apic_app\Entity\ApplicationSubscription
   */
  protected $sub;

  /**
   * @var \Drupal\apic_app\Service\ApplicationRestInterface
   */
  protected $restService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected $userUtils;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * UnsubscribeForm constructor.
   *
   * @param \Drupal\apic_app\Service\ApplicationRestInterface $restService
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(ApplicationRestInterface $restService, UserUtils $userUtils, Messenger $messenger) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static($container->get('apic_app.rest_service'), $container->get('ibm_apim.user_utils'), $container->get('messenger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'application_unsubscribe_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL, $subId = NULL): array {
    $this->node = $appId;
    $this->sub = $subId;
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'apic_app/basic';

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Are you sure you want to unsubscribe from this plan?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Unsubscribe');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Unsubscribe %title from this plan?', ['%title' => $this->node->title->value]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    $analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    if (isset($analytics_service) && $analytics_service->getClientEndpoint() !== NULL) {
      $url = Url::fromRoute('apic_app.subscriptions', ['node' => $this->node->id()]);
    }
    else {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]);
    }
    return $url;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $appId = $this->node->application_id->value;
    $url = $this->node->apic_url->value . '/subscriptions/' . $this->sub->uuid();
    $result = $this->restService->deleteSubscription($url);
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      // Find name and version of product
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('apic_url.value', $this->sub->product_url());
      $nids = $query->execute();
      if (isset($nids) && !empty($nids)) {
        $nids = array_values($nids);
      }
      if (count($nids) < 1) {
        \Drupal::logger('apic_app')->warning('Unable to find product name and version for @productUrl. Found @size matches in db.',
          ['@productUrl' => $this->sub->product_url(), '@size' => count($nids)]);
        $productTitle = 'unknown';
        $theProduct = NULL;
      }
      else {
        $theProduct = Node::load($nids[0]);
        $productTitle = $theProduct->getTitle();
      }

      SubscriptionService::delete($this->node->apic_url->value, $this->sub->uuid());

      $this->messenger->addMessage(t('Application unsubscribed successfully.'));
      $currentUser = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Application @appName unsubscribed from @product @plan plan by @username', [
        '@appName' => $this->node->getTitle(),
        '@product' => $productTitle,
        '@plan' => $this->sub->plan(),
        '@username' => $currentUser->getAccountName(),
      ]);
      // Calling all modules implementing 'hook_apic_app_unsubscribe':
      \Drupal::moduleHandler()->invokeAll('apic_app_unsubscribe', [
        'node' => $this->node,
        'data' => $result->data,
        'appId' => $appId,
        'product_url' => $this->sub->product_url(),
        'plan' => $this->sub->plan(),
        'subId' => $this->sub->uuid(),
      ]);

    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
