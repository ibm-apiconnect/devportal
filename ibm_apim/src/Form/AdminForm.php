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

namespace Drupal\ibm_apim\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\ibm_apim\Service\SiteConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * APIC settings form.
 */
class AdminForm extends ConfigFormBase {

  protected $siteConfig;

  /**
   * AdminForm constructor.
   */
  public function __construct(SiteConfig $config) {
    $this->siteConfig = $config;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('ibm_apim.site_config'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ibm_apim_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('ibm_apim.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('ibm_apim.settings');
    $apim_host = $this->siteConfig->parseApimHost();

    $form['intro'] = array(
      '#markup' => t('This form allows the configuration of different settings of this IBM API Connect Developer Portal.'),
      '#weight' => -30
    );
    $form['apim_host'] = array(
      '#markup' => t('IBM API Connect Management Service URL: @hostname', array(
        '@hostname' => Html::escape($apim_host['url'])
      )),
      '#weight' => -20
    );
    $form['config'] = array(
      '#type' => 'fieldset',
      '#title' => t('Configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE
    );
    $form['config']['autocreate_apiforum'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically create a forum per API'),
      '#default_value' => $config->get('autocreate_apiforum'),
      '#weight' => -15,
      '#description' => t('If checked then a new forum will be created for each API that is available to a developer. Note that the forums will only be created when a developer first sees the APIs listed in the Developer Portal.')
    );

    $form['config']['show_placeholder_images'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show placeholder images for Apps and APIs'),
      '#default_value' => $config->get('show_placeholder_images'),
      '#weight' => -14,
      '#description' => t('If checked then a placeholder image will be shown for the Applications and APIs that do not have one explicitly set. Uncheck to not show an image at all.')
    );

    $form['config']['show_register_app'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show links to register applications'),
      '#default_value' => $config->get('show_register_app'),
      '#weight' => -13,
      '#description' => t('If unchecked then all links to register new applications will be hidden. Applications will have to be registered externally to this portal.')
    );

    $form['config']['show_versions'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show version numbers for APIs and Products'),
      '#default_value' => $config->get('show_versions'),
      '#weight' => -12,
      '#description' => t('If unchecked then version numbers will not be displayed for APIs or Products.')
    );

    $form['config']['enable_api_test'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow live testing of APIs'),
      '#default_value' => $config->get('enable_api_test'),
      '#weight' => -11,
      '#description' => t('If unchecked then interactive testing of APIs will be disabled.')
    );

    $form['config']['autotag_with_phase'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically tag APIs with their phase'),
      '#default_value' => $config->get('autotag_with_phase'),
      '#weight' => -10,
      '#description' => t('If checked then APIs will be automatically tagged with their lifecycle "Phase".')
    );

    $form['config']['show_cors_warnings'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display CORS warnings for unenforced APIs'),
      '#default_value' => $config->get('show_cors_warnings'),
      '#weight' => -10,
      '#description' => t('If checked then unenforced APIs will have a warning about needing to check CORS (Cross-Origin Response Sharing) has been implemented. Uncheck to suppress the warnings.')
    );

    $form['config']['render_api_schema_view'] = array(
      '#type' => 'checkbox',
      '#title' => t('Render API schema objects'),
      '#default_value' => $config->get('render_api_schema_view'),
      '#weight' => -10,
      '#description' => t('If checked then API schema objects will be displayed as navigable objects. If unchecked then they will be displayed as raw JSON.')
    );

    $form['config']['show_analytics'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display Analytics'),
      '#default_value' => $config->get('show_analytics'),
      '#weight' => -10,
      '#description' => t('Display API Consumer analytics in the portal. If unchecked then all analytics links will be removed.')
    );

    $form['config']['soap_swagger_download'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow Download of Open API documents for SOAP APIs'),
      '#default_value' => $config->get('soap_swagger_download'),
      '#weight' => -10,
      '#description' => t('If checked API consumers will be able to download the Open API documents for SOAP APIs as well as REST APIs.')
    );

    $form['config']['application_image_upload'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow upload of custom images for applications'),
      '#default_value' => $config->get('application_image_upload'),
      '#weight' => -10,
      '#description' => t('If checked API consumers will be able to upload custom images for their applications.')
    );

    // code snippets options
    $form['categories'] = array(
      '#type' => 'fieldset',
      '#title' => t('Categories'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE
    );
    $form['categories']['categories_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Tag APIs and Products using provided categories'),
      '#default_value' => $config->get('categories')['enabled'],
      '#weight' => -15,
      '#description' => t('If checked then APIs and Products will be tagged with the categories defined within them.')
    );
    $form['categories']['create_taxonomies_from_categories'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create taxonomies for categories if they do not already exist'),
      '#default_value' => $config->get('categories')['create_taxonomies_from_categories'],
      '#weight' => -14,
      '#description' => t('If checked then new taxonomies will be created to match the provided categories.')
    );

    // consumerorg options
    $form['consumerorgs'] = array(
      '#type' => 'fieldset',
      '#title' => t('Consumer organizations'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE
    );
    $form['consumerorgs']['allow_consumerorg_creation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow users to create additional consumer organizations'),
      '#default_value' => $config->get('allow_consumerorg_creation'),
      '#weight' => -1,
      '#description' => t('If checked then users will be allowed to create additional consumer organizations. Note that self service onboarding must also be enabled in API Manager catalog settings.')
    );
    $form['consumerorgs']['allow_consumerorg_rename'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow users to rename their organization'),
      '#default_value' => $config->get('allow_consumerorg_rename'),
      '#weight' => -1,
      '#description' => t('If checked then consumer organization @owner and @administrators will be able to rename their consumer organizations.', array('@owner' => 'Owner', '@administrators' => 'Administrators'))
    );
    $form['consumerorgs']['allow_consumerorg_change_owner'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow users to change the owner of their organization'),
      '#default_value' => $config->get('allow_consumerorg_change_owner'),
      '#weight' => -1,
      '#description' => t('If checked then consumer organization @owner and @administrators will be able to change the owner of their consumer organizations.', array('@owner' => 'Owner', '@administrators' => 'Administrators'))
    );
    $form['consumerorgs']['allow_consumerorg_delete'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow users to delete their organizations'),
      '#default_value' => $config->get('allow_consumerorg_delete'),
      '#weight' => -1,
      '#description' => t('If checked then @owner or @administrators users will be allowed to delete their consumer organizations.', array('@owner' => 'Owner', '@administrators' => 'Administrators'))
    );
    $form['consumerorgs']['allow_user_delete'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow users to delete their accounts'),
      '#default_value' => $config->get('allow_user_delete'),
      '#weight' => -12,
      '#description' => t('If checked then users will be allowed to delete their accounts.')
    );

    // application options
    $form['applications'] = array(
      '#type' => 'fieldset',
      '#title' => t('Applications'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE
    );
    $form['applications']['allow_clientid_reset'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow consumer organization members to reset the application Client ID'),
      '#default_value' => $config->get('allow_clientid_reset'),
      '#weight' => -15,
      '#description' => t('If checked then authorised consumer organization members will be allowed to reset the client IDs')
    );
    $form['applications']['allow_clientsecret_reset'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow consumer organization members to reset the application Client Secret'),
      '#default_value' => $config->get('allow_clientsecret_reset'),
      '#weight' => -14,
      '#description' => t('If checked then authorised consumer organization members will be allowed to reset the client secrets')
    );
    $form['applications']['allow_new_credentials'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow consumer organization members to create additional credentials'),
      '#default_value' => $config->get('allow_new_credentials'),
      '#weight' => -14,
      '#description' => t('If checked then authorised consumer organization members will be allowed to create additional application credentials.')
    );

    // code snippets options
    $form['codesnippets'] = array(
      '#type' => 'fieldset',
      '#title' => t('API Code Snippets'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE
    );
    $form['codesnippets']['soap_codesnippets'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display code snippets for SOAP APIs as well as REST APIs'),
      '#default_value' => $config->get('soap_codesnippets'),
      '#weight' => -1,
      '#description' => t('Code snippets are displayed for REST APIs, if this field is checked then they will also be displayed for SOAP APIs. The code snippets will use raw HTTP and not a client SOAP library.')
    );
    $form['codesnippets']['intro'] = array(
      '#markup' => '<p>' . t('Select which languages are included in the code snippets for APIs.') . '</p>',
      '#weight' => 0
    );
    $codesnippets = $config->get('codesnippets');
    $form['codesnippets']['codesnippets_curl'] = array(
      '#type' => 'checkbox',
      '#title' => t('cURL'),
      '#default_value' => $codesnippets['curl'],
      '#weight' => 1
    );
    $form['codesnippets']['codesnippets_ruby'] = array(
      '#type' => 'checkbox',
      '#title' => t('Ruby'),
      '#default_value' => $codesnippets['ruby'],
      '#weight' => 2
    );
    $form['codesnippets']['codesnippets_python'] = array(
      '#type' => 'checkbox',
      '#title' => t('Python'),
      '#default_value' => $codesnippets['python'],
      '#weight' => 3
    );
    $form['codesnippets']['codesnippets_php'] = array(
      '#type' => 'checkbox',
      '#title' => t('PHP'),
      '#default_value' => $codesnippets['php'],
      '#weight' => 4
    );
    $form['codesnippets']['codesnippets_java'] = array(
      '#type' => 'checkbox',
      '#title' => t('Java'),
      '#default_value' => $codesnippets['java'],
      '#weight' => 5
    );
    $form['codesnippets']['codesnippets_node'] = array(
      '#type' => 'checkbox',
      '#title' => t('Node'),
      '#default_value' => $codesnippets['node'],
      '#weight' => 6
    );
    $form['codesnippets']['codesnippets_go'] = array(
      '#type' => 'checkbox',
      '#title' => t('Go'),
      '#default_value' => $codesnippets['go'],
      '#weight' => 7
    );
    $form['codesnippets']['codesnippets_swift'] = array(
      '#type' => 'checkbox',
      '#title' => t('Swift'),
      '#default_value' => $codesnippets['swift'],
      '#weight' => 8
    );
    $form['codesnippets']['codesnippets_c'] = array(
      '#type' => 'checkbox',
      '#title' => t('C'),
      '#default_value' => $codesnippets['c'],
      '#weight' => 9
    );
    $form['codesnippets']['codesnippets_csharp'] = array(
      '#type' => 'checkbox',
      '#title' => t('C#'),
      '#default_value' => $codesnippets['csharp'],
      '#weight' => 10
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $codesnippets = array(
      'curl' => (bool) $form_state->getValue('codesnippets_curl'),
      'ruby' => (bool) $form_state->getValue('codesnippets_ruby'),
      'python' => (bool) $form_state->getValue('codesnippets_python'),
      'php' => (bool) $form_state->getValue('codesnippets_php'),
      'java' => (bool) $form_state->getValue('codesnippets_java'),
      'node' => (bool) $form_state->getValue('codesnippets_node'),
      'go' => (bool) $form_state->getValue('codesnippets_go'),
      'swift' => (bool) $form_state->getValue('codesnippets_swift'),
      'c' => (bool) $form_state->getValue('codesnippets_c'),
      'csharp' => (bool) $form_state->getValue('codesnippets_csharp')
    );
    $categories = array(
      'enabled' => (bool) $form_state->getValue('categories_enabled'),
      'create_taxonomies_from_categories' => (bool) $form_state->getValue('create_taxonomies_from_categories')
    );

    // Set the submitted configuration setting
    $this->config('ibm_apim.settings')
      ->set('autocreate_apiforum', (bool) $form_state->getValue('autocreate_apiforum'))
      ->set('show_placeholder_images', (bool) $form_state->getValue('show_placeholder_images'))
      ->set('show_register_app', (bool) $form_state->getValue('show_register_app'))
      ->set('show_versions', (bool) $form_state->getValue('show_versions'))
      ->set('enable_api_test', (bool) $form_state->getValue('enable_api_test'))
      ->set('autotag_with_phase', (bool) $form_state->getValue('autotag_with_phase'))
      ->set('show_cors_warnings', (bool) $form_state->getValue('show_cors_warnings'))
      ->set('show_analytics', (bool) $form_state->getValue('show_analytics'))
      ->set('soap_swagger_download', (bool) $form_state->getValue('soap_swagger_download'))
      ->set('application_image_upload', (bool) $form_state->getValue('application_image_upload'))
      ->set('render_api_schema_view', (bool) $form_state->getValue('render_api_schema_view'))
      ->set('allow_consumerorg_creation', (bool) $form_state->getValue('allow_consumerorg_creation'))
      ->set('allow_consumerorg_rename', (bool) $form_state->getValue('allow_consumerorg_rename'))
      ->set('allow_consumerorg_change_owner', (bool) $form_state->getValue('allow_consumerorg_change_owner'))
      ->set('allow_consumerorg_delete', (bool) $form_state->getValue('allow_consumerorg_delete'))
      ->set('allow_user_delete', (bool) $form_state->getValue('allow_user_delete'))
      ->set('allow_new_credentials', (bool) $form_state->getValue('allow_new_credentials'))
      ->set('allow_clientid_reset', (bool) $form_state->getValue('allow_clientid_reset'))
      ->set('allow_clientsecret_reset', (bool) $form_state->getValue('allow_clientsecret_reset'))
      ->set('soap_codesnippets', (bool) $form_state->getValue('soap_codesnippets'))
      ->set('categories', $categories)
      ->set('codesnippets', $codesnippets)
      ->save();

    parent::submitForm($form, $form_state);
  }
}
