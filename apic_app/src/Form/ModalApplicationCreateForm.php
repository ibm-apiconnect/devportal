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

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Application;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to create an application.
 */
class ModalApplicationCreateForm extends FormBase {

  protected $restService;
  protected $userUtils;

  /**
   * ApplicationCreateForm constructor.
   * @param ApplicationRestInterface $restService
   * @param UserUtils $userUtils
   */
  public function __construct(
    ApplicationRestInterface $restService,
    UserUtils $userUtils) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static(
      $container->get('apic_app.rest_service'),
      $container->get('ibm_apim.user_utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modal_application_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $form['#parents'] = array();
    $max_weight = 500;

    $entity = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'application',
    ]);
    $entity_form = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.application.default');

    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'application');

    foreach ($entity_form->getComponents() as $name => $options) {

      if (($configuration = $entity_form->getComponent($name)) && isset($configuration['type']) && ($definition = $definitions[$name])) {
        $widget = \Drupal::service('plugin.manager.field.widget')->getInstance([
          'field_definition' => $definition,
          'form_mode' => 'default',
          // No need to prepare, defaults have been merged in setComponent().
          'prepare' => FALSE,
          'configuration' => $configuration
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

    if (isset($form['application_image'])) {
      unset($form['application_image']);
    }

    $ibm_apim_application_certificates = \Drupal::state()->get('ibm_apim.application_certificates');
    if ($ibm_apim_application_certificates) {

      $form['certificate'] = array(
        '#type' => 'textarea',
        '#title' => t('Certificate'),
        '#description' => t('Paste the content of your application\'s x509 certificate.'),
        '#required' => FALSE,
        '#wysiwyg' => FALSE,
      );
    }

    $form['#prefix'] = '<div id="modal_application_create_form">';
    $form['#suffix'] = '</div>';

    $form['title']['#required'] = TRUE;

    $form['actions']['#type'] = 'actions';
    $form['actions']['#weight'] = $max_weight + 1;
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#attributes' => array(
        'class' => array(
          'use-ajax',
        ),
      ),
      '#ajax' => array(
        'callback' => array($this, 'submitModalFormAjax'),
        'event' => 'click',
        'method' => 'append',
      ),
    );

    $form['#attached']['library'][] = 'apic_app/basic';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('title');
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    if (!isset($name) || empty($name)) {
      $form_state->setErrorByName('Name', $this->t('Application name is a required field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('view.applications.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitModalFormAjax(array &$form, FormStateInterface $form_state) {

    $certificate = NULL;

    // Get form inputs
    $name = $form_state->getValue('title');
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    $summary = $form_state->getValue('apic_summary');
    if (is_array($summary) && isset($summary[0]['value'])) {
      $summary = $summary[0]['value'];
    }
    $oauth_endpoints = array();
    $oauth = $form_state->getValue('application_redirect_endpoints');
    foreach ($oauth as $oauth_value) {
      if (is_array($oauth_value) && !empty($oauth_value['value'])) {
        $oauth_endpoints[] = trim($oauth_value['value']);
      }
    }
    $ibm_apim_application_certificates = \Drupal::state()->get('ibm_apim.application_certificates');
    if ($ibm_apim_application_certificates) {
      $certificate = $form_state->getValue('certificate');
    }

    // Create the application
    $restService = \Drupal::service('apic_app.rest_service');
    $result = $restService->createApplication($name, $summary, $oauth_endpoints, $certificate, $form_state);

    // Response is a set of Ajax commands to update the DOM or reload the page etc
    $response = new AjaxResponse();
    if (isset($result->data['errors'])) {
      $response->addCommand(new RedirectCommand(Url::fromRoute('ibm_apim.subscription_wizard.step', array('step' => 'chooseapp'))
        ->toString()));
    }
    else {

      // Swallow the create app success drupal status message
      drupal_get_messages();

      $data = $result->data;

      $clientId = $data['client_id'];
      $clientSecret = $data['client_secret'];
      $nid = $data['nid'];

      // Add the new app to the app list
      $node = Node::load($nid);
      $renderArray = node_view($node, 'subscribewizard');
      $renderer = \Drupal::service('renderer');
      $html = $renderer->render($renderArray);
      $response->addCommand(new InsertCommand('div.apicNewAppWrapper', $html, array()));

      // Need to update message area on underlying form - re-check if we have suspended or subscribed apps
      $product_id = \Drupal::request()->query->get('productId');
      if (isset($product_id)) {
        $product_node = Node::load($product_id);
        $productName = $product_node->getTitle();
        $product_url = $product_node->apic_url->value;
      }
      else {
        $productName = 'undefined';
        $product_url = 'undefined';
      }
      $allApps = Application::listApplications();
      $allApps = Node::loadMultiple($allApps);
      $suspendedApps = array();
      $subscribedApps = array();

      foreach ($allApps as $nid => $nextApp) {
        if (isset($nextApp->apic_state->value) && mb_strtoupper($nextApp->apic_state->value) == 'SUSPENDED') {
          array_push($suspendedApps, $nextApp);
        }
        else {
          if (isset($nextApp->application_subscriptions->value)) {
            $subs = unserialize($nextApp->application_subscriptions->value);
            if (is_array($subs)) {
              foreach ($subs as $sub) {
                if (isset($sub['product_url']) && $sub['product_url'] == $product_url) {
                  array_push($subscribedApps, $nextApp);
                  $appSubscribedToProduct = TRUE;
                  break;
                }
              }
            }
          }
        }
      }

      // Generate the appropriate messages div and replace the div that is already there
      $suspendedAppMsg = t('There are %number suspended applications not displayed in this list.', array('%number' => sizeof($suspendedApps)));
      $subscribedAppMsg = t('There are %number applications that are already subscribed to the %product product. They are not displayed in this list.',
        array('%number' => sizeof($subscribedApps), '%product' => $productName));
      $messagesHtml = '<div class="apicSubscribeInfotext">' .
        (!empty($suspendedApps) ? $suspendedAppMsg : "") .
        (!empty($subscribedApps) ? $subscribedAppMsg : "") .
        '</div>';
      $response->addCommand(new ReplaceCommand('div.apicSubscribeInfotext', $messagesHtml));

      // Pop up a new modal dialog to display the client id and secret
      $credsform = array();
      $credsform['intro'] = [
        '#markup' => '<p>' . t('The API Key and Secret have been generated for your application.') . '</p>',
        '#weight' => 0,
      ];

      $credsform['client_id'] = [
        '#markup' => \Drupal\Core\Render\Markup::create('<div class="clientIDContainer toggleParent"><p class="field__label">' . $this->t('Key') . '</p><div class="bx--form-item appID js-form-item form-item js-form-type-textfield form-type-password js-form-item-password form-item-password form-group"><input class="form-control toggle" id="client_id" type="password" readonly value="' . $clientId . '"></div><div class="apicAppCheckButton">
        <div class="password-toggle bx--form-item js-form-item form-item js-form-type-checkbox form-type-checkbox checkbox"><label title="" data-toggle="tooltip" class="bx--label option" data-original-title=""><input class="form-checkbox bx--checkbox" type="checkbox"><span class="bx--checkbox-appearance"><svg class="bx--checkbox-checkmark" width="12" height="9" viewBox="0 0 12 9" fill-rule="evenodd"><path d="M4.1 6.1L1.4 3.4 0 4.9 4.1 9l7.6-7.6L10.3 0z"></path></svg></span><span class="children"> ' . t('Show') . '</span></label></div></div></div>'),
        '#weight' => 10,
      ];

      $credsform['client_secret'] = [
        '#markup' => \Drupal\Core\Render\Markup::create('<div class="clientSecretContainer toggleParent"><p class="field__label">' . $this->t('Secret') . '</p><div class="bx--form-item appSecret js-form-item form-item js-form-type-textfield form-type-password js-form-item-password form-item-password form-group"><input class="form-control toggle" id="client_secret" type="password" readonly value="' . $clientSecret . '"></div><div class="apicAppCheckButton">
        <div class="password-toggle bx--form-item js-form-item form-item js-form-type-checkbox form-type-checkbox checkbox"><label title="" data-toggle="tooltip" class="bx--label option" data-original-title=""><input class="form-checkbox bx--checkbox" type="checkbox"><span class="bx--checkbox-appearance"><svg class="bx--checkbox-checkmark" width="12" height="9" viewBox="0 0 12 9" fill-rule="evenodd"><path d="M4.1 6.1L1.4 3.4 0 4.9 4.1 9l7.6-7.6L10.3 0z"></path></svg></span><span class="children"> ' . t('Show') . '</span></label></div></div></div>'),
        '#weight' => 20,
      ];

      $credsform['outro'] = [
        '#markup' => '<p>' . t('The Secret will only be displayed here one time. Please copy your API Secret and keep it for your records.') . '</p>',
        '#weight' => 30,
      ];

      $response->addCommand(new OpenModalDialogCommand(t("Credentials for your new application"), $credsform));
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    return $response;
  }
}
