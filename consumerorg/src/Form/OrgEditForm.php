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

namespace Drupal\consumerorg\Form;

use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Form to edit the consumerorg name.
 */
class OrgEditForm extends FormBase {

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected $consumerOrgService;

  /**
   * @var
   */
  protected $currentOrg;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected $userUtils;

  /**
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * OrgEditForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(ConsumerOrgService $consumer_org_service,
                              UserUtils $user_utils,
                              ThemeHandler $themeHandler,
                              EntityTypeManager $entityTypeManager,
                              EntityFieldManager $entityFieldManager,
                              Messenger $messenger) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
    $this->themeHandler = $themeHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('ibm_apim.user_utils'),
      $container->get('theme_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('messenger')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'consumerorg_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (!$this->userUtils->checkHasPermission('settings:manage')) {
      $this->messenger->addError(t('Permission denied.'));

      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('You do not have sufficient access to perform this action.') . '</p>'];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $this->getCancelUrl(),
        '#attributes' => ['class' => ['button']],
      ];
    }
    else {
      $org = $this->userUtils->getCurrentConsumerorg();
      $nid = $this->consumerOrgService->getNid($org['url']);
      $this->currentOrg = $this->consumerOrgService->getByNid($nid);

      $form['#parents'] = [];
      $max_weight = 500;

      $entity = $this->entityTypeManager->getStorage('node')->load($nid);
      $entity_form = $this->entityTypeManager->getStorage('entity_form_display')->load('node.consumerorg.default');

      $definitions = $this->entityFieldManager->getFieldDefinitions('node', 'consumerorg');
      if ($entity !== NULL && $entity_form !== NULL) {
        foreach ($entity_form->getComponents() as $name => $options) {

          if (($configuration = $entity_form->getComponent($name)) && isset($configuration['type']) && ($definition = $definitions[$name])) {
            $widget = \Drupal::service('plugin.manager.field.widget')->getInstance([
              'field_definition' => $definition,
              'form_mode' => 'default',
              // No need to prepare, defaults have been merged in setComponent().
              'prepare' => FALSE,
              'configuration' => $configuration,
            ]);
          }

          if (isset($widget)) {
            $items = $entity->get($name);
            $items->filterEmptyItems();
            $form[$name] = $widget->form($items, $form, $form_state);
            $form[$name]['#access'] = $items->access('edit');

            // Assign the correct weight.
            $form[$name]['#weight'] = $options['weight'];
            if ($options['weight'] > $max_weight) {
              $max_weight = $options['weight'];
            }
          }
        }
      }

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
      ];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $this->getCancelUrl(),
        '#attributes' => ['class' => ['button', 'apicSecondary']],
      ];
      $form['actions']['#weight'] = $max_weight + 1;

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $name = $form_state->getValue('title');
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    $name = trim($name);
    if (!isset($name) || empty($name)) {
      $form_state->setErrorByName('title', $this->t('Organization title is a required field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('ibm_apim.myorg');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $orgname = $form_state->getValue('title');

    if (empty($orgname)) {
      $this->messenger->addError(t('An organization title is required.'));
    }
    else {
      $customFields = $this->consumerOrgService->getCustomFields();
      $values = $form_state->getValues();
      if (!empty($customFields)) {
        $customFieldValues = \Drupal::service('ibm_apim.user_utils')->handleFormCustomFields($customFields, $form_state);
        $values = array_replace($values, $customFieldValues);
      }
      $response = $this->consumerOrgService->edit($this->currentOrg, $values);
      if ($response->success()) {
        $this->messenger->addMessage(t('Organization updated.'));
      }
      else {
        $this->messenger->addError(t('Error during organization update. Contact the system administrator.'));
      }

    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
