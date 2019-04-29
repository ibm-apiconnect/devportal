<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
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
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Form to edit the consumerorg name.
 */
class OrgEditForm extends FormBase {

  protected $consumerOrgService;

  protected $currentOrg;

  protected $userUtils;

  protected $themeHandler;

  protected $entityTypeManager;

  protected $entityFieldManager;

  /**
   * Constructs an Org User Invitation Form.
   *
   * {@inheritdoc}
   *
   * @param ConsumerOrgService $consumerOrgService
   */
  public function __construct(ConsumerOrgService $consumer_org_service,
                              UserUtils $user_utils,
                              ThemeHandler $themeHandler,
                              EntityTypeManager $entityTypeManager,
                              EntityFieldManager $entityFieldManager) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
    $this->themeHandler = $themeHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
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
      $container->get('entity_field.manager')
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
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('You do not have sufficient access to perform this action.') . '</p>'];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#href' => 'myorg',
        '#attributes' => ['class' => ['button']],
      ];
      if ($this->themeHandler->themeExists('bootstrap')) {
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }
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
        '#value' => t('Submit'),
      ];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $this->getCancelUrl(),
        '#attributes' => ['class' => ['button', 'apicSecondary']],
      ];
      $form['actions']['#weight'] = $max_weight + 1;

      if ($this->themeHandler->themeExists('bootstrap')) {
        $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }
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
      drupal_set_message(t('An organization title is required.'), 'error');
    }
    else {

      $response = $this->consumerOrgService->edit($this->currentOrg, $form_state->getValues());
      if ($response->success()) {
        drupal_set_message(t('Organization updated.'));
      }
      else {
        drupal_set_message(t('Error during organization update. Contact the system administrator.'), 'error');
      }

    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
