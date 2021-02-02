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

namespace Drupal\apic_app\Form\Multistep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class ApplicationCreateStep2Form extends MultistepFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId(): string {
    return 'application_create_form_two';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $form = parent::buildForm($form, $form_state);

    $creds = $this->store->get('creds');
    if (!isset($creds) || empty($creds)) {
      $form['intro'] = [
        '#markup' => '<p>' . t('An error has occurred, please click \'Previous\' to restart the Create Application wizard.') . '</p>',
        '#weight' => 0,
      ];
      $form['actions']['submit']['#value'] = t('Previous');
      $form['actions']['submit']['#url'] = Url::fromRoute('apic_app.create');
    }
    else {
      $form['intro'] = [
        '#markup' => '<p>' . t('The API Key and Secret have been generated for your application.') . '</p>',
        '#weight' => 0,
      ];

      $creds = unserialize($this->store->get('creds'), ['allowed_classes' => FALSE]);

      $form['client_id'] = [
        '#markup' => \Drupal\Core\Render\Markup::create('<div class="clientIDContainer toggleParent"><p class="field__label">' . t('Key') . '</p><div class="bx--form-item appID js-form-item form-item js-form-type-textfield form-type-password js-form-item-password form-item-password form-group"><input class="form-control toggle" id="client_id" type="password" readonly value="' . $creds['client_id'] . '"></div><div class="apicAppCheckButton">
        <div class="password-toggle bx--form-item js-form-item form-item js-form-type-checkbox form-type-checkbox checkbox"><label title="" data-toggle="tooltip" class="bx--label option" data-original-title=""><input class="form-checkbox bx--checkbox" type="checkbox"><span class="bx--checkbox-appearance"><svg class="bx--checkbox-checkmark" width="12" height="9" viewBox="0 0 12 9" fill-rule="evenodd"><path d="M4.1 6.1L1.4 3.4 0 4.9 4.1 9l7.6-7.6L10.3 0z"></path></svg></span><span class="children"> ' . t('Show') . '</span></label></div></div></div>'),
        '#weight' => 10,
      ];

      $form['client_secret'] = [
        '#markup' => \Drupal\Core\Render\Markup::create('<div class="clientSecretContainer toggleParent"><p class="field__label">' . t('Secret') . '</p><div class="bx--form-item appSecret js-form-item form-item js-form-type-textfield form-type-password js-form-item-password form-item-password form-group"><input class="form-control toggle" id="client_secret" type="password" readonly value="' . $creds['client_secret'] . '"></div><div class="apicAppCheckButton">
        <div class="password-toggle bx--form-item js-form-item form-item js-form-type-checkbox form-type-checkbox checkbox"><label title="" data-toggle="tooltip" class="bx--label option" data-original-title=""><input class="form-checkbox bx--checkbox" type="checkbox"><span class="bx--checkbox-appearance"><svg class="bx--checkbox-checkmark" width="12" height="9" viewBox="0 0 12 9" fill-rule="evenodd"><path d="M4.1 6.1L1.4 3.4 0 4.9 4.1 9l7.6-7.6L10.3 0z"></path></svg></span><span class="children"> ' . t('Show') . '</span></label></div></div></div>'),
        '#weight' => 20,
      ];

      $form['outro'] = [
        '#markup' => '<p>' . t('The Secret will only be displayed here one time. Please copy your API Secret and keep it for your records.') . '</p>',
        '#weight' => 30,
      ];

      $form['actions']['submit']['#value'] = t('Continue');
      $form['#attached']['library'][] = 'apic_app/basic';
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $nid = $this->store->get('nid');
    $node = Node::load($nid);
    $form_state->setRedirectUrl($node->toUrl());

    // If we came to this wizard from somewhere else, go back there
    $redirectTo = $this->store->get('redirect_to');
    if (!empty($redirectTo)) {
      $redirectEndpoint = \Drupal::service('path.validator')->getUrlIfValid($redirectTo);
      if ($redirectEndpoint !== FALSE) {
        $form_state->setRedirect($redirectEndpoint->getRouteName(), $redirectEndpoint->getRouteParameters());
      }
    }

    // Delete the saved data
    $this->deleteStore();
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
