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

namespace Drupal\ibm_apim\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\SiteConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * APIC settings form.
 */
class AdminForm extends ConfigFormBase {

  protected $siteConfig;

  /**
   * AdminForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\ibm_apim\Service\SiteConfig $config
   */
  public function __construct(ConfigFactoryInterface $config_factory, SiteConfig $config) {
    parent::__construct($config_factory);
    $this->siteConfig = $config;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('ibm_apim.site_config'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID(): string {
    return 'ibm_apim_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ibm_apim.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('ibm_apim.settings');
    $apim_host = $this->siteConfig->parseApimHost();

    $form['intro'] = [
      '#markup' => t('This form allows the configuration of different settings of this IBM API Connect Developer Portal.'),
      '#weight' => -30,
    ];
    $form['apim_host'] = [
      '#markup' => t('IBM API Connect Management Service URL: @hostname', [
        '@hostname' => Html::escape($apim_host['url']),
      ]),
      '#weight' => -20,
    ];
    $form['config'] = [
      '#type' => 'fieldset',
      '#title' => t('Configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['config']['autocreate_apiforum'] = [
      '#type' => 'checkbox',
      '#title' => t('Automatically create a forum per API'),
      '#default_value' => $config->get('autocreate_apiforum'),
      '#weight' => -15,
      '#description' => t('If checked then a new forum will be created for each API that is available to a developer. Note that the forums will only be created when a developer first sees the APIs listed in the Developer Portal.'),
    ];

    $form['config']['show_placeholder_images'] = [
      '#type' => 'checkbox',
      '#title' => t('Show placeholder images for Apps and APIs'),
      '#default_value' => $config->get('show_placeholder_images'),
      '#weight' => -14,
      '#description' => t('If checked then a placeholder image will be shown for the Applications and APIs that do not have one explicitly set. Uncheck to not show an image at all.'),
    ];

    $form['config']['show_register_app'] = [
      '#type' => 'checkbox',
      '#title' => t('Show links to register applications'),
      '#default_value' => $config->get('show_register_app'),
      '#weight' => -13,
      '#description' => t('If unchecked then all links to register new applications will be hidden. Applications will have to be registered externally to this portal.'),
    ];

    $form['config']['show_versions'] = [
      '#type' => 'checkbox',
      '#title' => t('Show version numbers for APIs and Products'),
      '#default_value' => $config->get('show_versions'),
      '#weight' => -12,
      '#description' => t('If unchecked then version numbers will not be displayed for APIs or Products.'),
    ];

    $form['config']['enable_api_test'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow live testing of APIs'),
      '#default_value' => $config->get('enable_api_test'),
      '#weight' => -11,
      '#description' => t('If unchecked then interactive testing of APIs will be disabled.'),
    ];

    $form['config']['validate_apis'] = [
      '#type' => 'checkbox',
      '#title' => t('Validate API documents'),
      '#default_value' => $config->get('validate_apis'),
      '#weight' => -11,
      '#description' => t('If unchecked then the built in validation of API documents will be disabled.'),
    ];

    $form['config']['autotag_with_phase'] = [
      '#type' => 'checkbox',
      '#title' => t('Automatically tag APIs with their phase'),
      '#default_value' => $config->get('autotag_with_phase'),
      '#weight' => -10,
      '#description' => t('If checked then APIs will be automatically tagged with their lifecycle "Phase".'),
    ];

    $form['config']['show_cors_warnings'] = [
      '#type' => 'checkbox',
      '#title' => t('Display CORS warnings for unenforced APIs'),
      '#default_value' => $config->get('show_cors_warnings'),
      '#weight' => -10,
      '#description' => t('If checked then unenforced APIs will have a warning about needing to check CORS (Cross-Origin Response Sharing) has been implemented. Uncheck to suppress the warnings.'),
    ];

    $form['config']['render_api_schema_view'] = [
      '#type' => 'checkbox',
      '#title' => t('Render API schema objects'),
      '#default_value' => $config->get('render_api_schema_view'),
      '#weight' => -10,
      '#description' => t('If checked then API schema objects will be displayed as navigable objects. If unchecked then they will be displayed as raw JSON.'),
    ];

    $form['config']['show_analytics'] = [
      '#type' => 'checkbox',
      '#title' => t('Display Analytics'),
      '#default_value' => $config->get('show_analytics'),
      '#weight' => -10,
      '#description' => t('Display API Consumer analytics in the portal. If unchecked then all analytics links will be removed.'),
    ];

    $form['config']['soap_swagger_download'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Download of Open API documents for SOAP APIs'),
      '#default_value' => $config->get('soap_swagger_download'),
      '#weight' => -10,
      '#description' => t('If checked API consumers will be able to download the Open API documents for SOAP APIs as well as REST APIs.'),
    ];

    $form['config']['optimise_oauth_ux'] = [
      '#type' => 'checkbox',
      '#title' => t('Optimise OAuth experience in test tool'),
      '#default_value' => $config->get('optimise_oauth_ux'),
      '#weight' => -10,
      '#description' => t('If checked then certain OAuth flows (such as implicit or access code) which cannot be completed from the test tool for technical reasons are optimised to improve usability.'),
    ];

    $form['config']['show_mtls_header'] = [
      '#type' => 'checkbox',
      '#title' => t('Show certificate in header for APIs secured with mutual TLS'),
      '#default_value' => $config->get('show_mtls_header'),
      '#weight' => -10,
      '#description' => t('If checked then the x-client-certificate header will be shown in an API when mutual TLS is configured.'),
    ];

    $form['config']['application_image_upload'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow upload of custom images for applications'),
      '#default_value' => $config->get('application_image_upload'),
      '#weight' => -10,
      '#description' => t('If checked API consumers will be able to upload custom images for their applications.'),
    ];

    $form['config']['hide_admin_registry'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide the admin registry on the login form.'),
      '#default_value' => $config->get('hide_admin_registry'),
      '#weight' => -10,
      '#description' => t('Hide the admin user registry on the login form. 
                           The login form for admin can be found at
                           :url', [':url' => Url::fromRoute('user.login', [], ['query' => ['registry_url' => '/admin']])->toString()]),
    ];
    $form['config']['enable_oidc_register_form'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable the OIDC register form.'),
      '#default_value' => $config->get('enable_oidc_register_form'),
      '#weight' => -1,
      '#description' => t('If checked then users will be redirected to a form before being sent to the OIDC provider when registering with OIDC.'),
    ];
    $form['config']['enable_oidc_login_form'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable the OIDC login form.'),
      '#default_value' => $config->get('enable_oidc_login_form'),
      '#weight' => -1,
      '#description' => t('If checked then users will be redirected to a form before being sent to the OIDC provider when logging in with OIDC.'),
    ];
    // code snippets options
    $form['categories'] = [
      '#type' => 'fieldset',
      '#title' => t('Categories'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['categories']['categories_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Tag APIs and Products using provided categories'),
      '#default_value' => $config->get('categories')['enabled'],
      '#weight' => -15,
      '#description' => t('If checked then APIs and Products will be tagged with the categories defined within them.'),
    ];
    $form['categories']['create_taxonomies_from_categories'] = [
      '#type' => 'checkbox',
      '#title' => t('Create taxonomies for categories if they do not already exist'),
      '#default_value' => $config->get('categories')['create_taxonomies_from_categories'],
      '#weight' => -14,
      '#description' => t('If checked then new taxonomies will be created to match the provided categories.'),
    ];

    // consumerorg options
    $form['consumerorgs'] = [
      '#type' => 'fieldset',
      '#title' => t('Consumer organizations'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['consumerorgs']['allow_consumerorg_creation'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow users to create additional consumer organizations'),
      '#default_value' => $config->get('allow_consumerorg_creation'),
      '#weight' => -1,
      '#description' => t('If checked then users will be allowed to create additional consumer organizations. Note that self service onboarding must also be enabled in API Manager catalog settings.'),
    ];
    $form['consumerorgs']['allow_consumerorg_rename'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow users to rename their organization'),
      '#default_value' => $config->get('allow_consumerorg_rename'),
      '#weight' => -1,
      '#description' => t('If checked then consumer organization @owner and @administrators will be able to rename their consumer organizations.', [
        '@owner' => 'Owner',
        '@administrators' => 'Administrators',
      ]),
    ];
    $form['consumerorgs']['allow_consumerorg_change_owner'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow users to change the owner of their organization'),
      '#default_value' => $config->get('allow_consumerorg_change_owner'),
      '#weight' => -1,
      '#description' => t('If checked then consumer organization @owner and @administrators will be able to change the owner of their consumer organizations.', [
        '@owner' => 'Owner',
        '@administrators' => 'Administrators',
      ]),
    ];
    $form['consumerorgs']['allow_consumerorg_delete'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow users to delete their organizations'),
      '#default_value' => $config->get('allow_consumerorg_delete'),
      '#weight' => -1,
      '#description' => t('If checked then @owner or @administrators users will be allowed to delete their consumer organizations.', [
        '@owner' => 'Owner',
        '@administrators' => 'Administrators',
      ]),
    ];
    $form['consumerorgs']['allow_user_delete'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow users to delete their accounts'),
      '#default_value' => $config->get('allow_user_delete'),
      '#weight' => -12,
      '#description' => t('If checked then users will be allowed to delete their accounts.'),
    ];

    // application options
    $form['applications'] = [
      '#type' => 'fieldset',
      '#title' => t('Applications'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['applications']['allow_clientid_reset'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow consumer organization members to reset the application Client ID'),
      '#default_value' => $config->get('allow_clientid_reset'),
      '#weight' => -15,
      '#description' => t('If checked then authorised consumer organization members will be allowed to reset the client IDs'),
    ];
    $form['applications']['allow_clientsecret_reset'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow consumer organization members to reset the application Client Secret'),
      '#default_value' => $config->get('allow_clientsecret_reset'),
      '#weight' => -14,
      '#description' => t('If checked then authorised consumer organization members will be allowed to reset the client secrets'),
    ];
    $form['applications']['allow_new_credentials'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow consumer organization members to create additional credentials'),
      '#default_value' => $config->get('allow_new_credentials'),
      '#weight' => -14,
      '#description' => t('If checked then authorised consumer organization members will be allowed to create additional application credentials.'),
    ];

    // code snippets options
    $form['codesnippets'] = [
      '#type' => 'fieldset',
      '#title' => t('API Code Snippets'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['codesnippets']['soap_codesnippets'] = [
      '#type' => 'checkbox',
      '#title' => t('Display code snippets for SOAP APIs as well as REST APIs'),
      '#default_value' => $config->get('soap_codesnippets'),
      '#weight' => -1,
      '#description' => t('Code snippets are displayed for REST APIs, if this field is checked then they will also be displayed for SOAP APIs. The code snippets will use raw HTTP and not a client SOAP library.'),
    ];
    $form['codesnippets']['intro'] = [
      '#markup' => '<p>' . t('Select which languages are included in the code snippets for APIs.') . '</p>',
      '#weight' => 0,
    ];
    $codesnippets = $config->get('codesnippets');
    $form['codesnippets']['codesnippets_curl'] = [
      '#type' => 'checkbox',
      '#title' => t('cURL'),
      '#default_value' => $codesnippets['curl'],
      '#weight' => 1,
    ];
    $form['codesnippets']['codesnippets_ruby'] = [
      '#type' => 'checkbox',
      '#title' => t('Ruby'),
      '#default_value' => $codesnippets['ruby'],
      '#weight' => 2,
    ];
    $form['codesnippets']['codesnippets_python'] = [
      '#type' => 'checkbox',
      '#title' => t('Python'),
      '#default_value' => $codesnippets['python'],
      '#weight' => 3,
    ];
    $form['codesnippets']['codesnippets_php'] = [
      '#type' => 'checkbox',
      '#title' => t('PHP'),
      '#default_value' => $codesnippets['php'],
      '#weight' => 4,
    ];
    $form['codesnippets']['codesnippets_java'] = [
      '#type' => 'checkbox',
      '#title' => t('Java'),
      '#default_value' => $codesnippets['java'],
      '#weight' => 5,
    ];
    $form['codesnippets']['codesnippets_node'] = [
      '#type' => 'checkbox',
      '#title' => t('Node'),
      '#default_value' => $codesnippets['node'],
      '#weight' => 6,
    ];
    $form['codesnippets']['codesnippets_go'] = [
      '#type' => 'checkbox',
      '#title' => t('Go'),
      '#default_value' => $codesnippets['go'],
      '#weight' => 7,
    ];
    $form['codesnippets']['codesnippets_swift'] = [
      '#type' => 'checkbox',
      '#title' => t('Swift'),
      '#default_value' => $codesnippets['swift'],
      '#weight' => 8,
    ];
    $form['codesnippets']['codesnippets_c'] = [
      '#type' => 'checkbox',
      '#title' => t('C'),
      '#default_value' => $codesnippets['c'],
      '#weight' => 9,
    ];
    $form['codesnippets']['codesnippets_csharp'] = [
      '#type' => 'checkbox',
      '#title' => t('C#'),
      '#default_value' => $codesnippets['csharp'],
      '#weight' => 10,
    ];

    // code snippets options
    $form['certificates'] = [
      '#type' => 'fieldset',
      '#title' => t('Application Certificates'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['certificates']['certificate_strip_newlines'] = [
      '#type' => 'checkbox',
      '#title' => t('Automatically strip newline characters from application certificates'),
      '#default_value' => $config->get('certificate_strip_newlines'),
      '#weight' => -15,
      '#description' => t('If checked then any new line characters within the application certificates will be automatically removed when the certificate is uploaded.'),
    ];
    $form['certificates']['certificate_strip_prefix'] = [
      '#type' => 'checkbox',
      '#title' => t('Automatically strip prefix and suffixes from application certificates'),
      '#default_value' => $config->get('certificate_strip_prefix'),
      '#weight' => -14,
      '#description' => t('If checked then the \'-----BEGIN CERTIFICATE-----\' prefix and suffixes will be automatically removed from application certificates when uploaded.'),
    ];

    $form['proxy'] = [
      '#type' => 'fieldset',
      '#title' => t('Proxy Configuration (Experimental)'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['proxy']['intro'] = [
      '#markup' => '<p>' . t('If a proxy is required to allow communication from the portal server to the APIC Consumer API, then it is recommended to use a transparent proxy if possible. If a transparent proxy is not available then the experimental settings below can be used. These settings will only affect communication to the consumer API.') . '</p>',
      '#weight' => 0,
    ];
    $form['proxy']['use_proxy'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Proxy Support'),
      '#default_value' => $config->get('use_proxy'),
      '#weight' => 20,
    ];
    $defaults_for_api = $config->get('proxy_for_api.');
    if ($defaults_for_api === null) {
      $defaults_for_api = ['CONSUMER', 'PLATFORM', 'ANALYTICS'];
    } else {
      $defaults_for_api = explode(',', $config->get('proxy_for_api.'));
    }
    $form['proxy']['proxy_for_api'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'CONSUMER' => t('Consumer API'),
        'PLATFORM' => t('Platform API'),
        'ANALYTICS' => t('Analytics API')
      ],
      '#title' => t('If enabled, use the Proxy for Consumer, Platform or Analytics APIs'),
      '#description' => t('Select whether to use the proxy for the Consumer, Platform or Analytics APIs. All are selected by default.'),
      '#default_value' => $defaults_for_api,
      '#required' => FALSE,
      '#weight' => 25,
    ];

    $proxyTypeDefault = $config->get('proxy_type');
    if ($proxyTypeDefault === NULL || empty($proxyTypeDefault)) {
      $proxyTypeDefault = 'CURLPROXY_HTTP';
    }
    $form['proxy']['proxy_type'] = [
      '#type' => 'select',
      '#options' => [
        'CURLPROXY_HTTP' => 'CURLPROXY_HTTP',
        'CURLPROXY_HTTPS' => 'CURLPROXY_HTTPS',
        'CURLPROXY_HTTP_1_0' => 'CURLPROXY_HTTP_1_0',
        'CURLPROXY_SOCKS4' => 'CURLPROXY_SOCKS4',
        'CURLPROXY_SOCKS4A' => 'CURLPROXY_SOCKS4A',
        'CURLPROXY_SOCKS5' => 'CURLPROXY_SOCKS5',
        'CURLPROXY_SOCKS5_HOSTNAME' => 'CURLPROXY_SOCKS5_HOSTNAME',
      ],
      '#title' => t('Proxy type'),
      '#description' => t('Select what type of proxy to use, CURLPROXY_HTTP is the default.'),
      '#default_value' => $proxyTypeDefault,
      '#required' => FALSE,
      '#weight' => 30,
    ];
    $form['proxy']['proxy_url'] = [
      '#type' => 'textfield',
      '#title' => t('Proxy URL'),
      '#description' => t('Provide the URL of the proxy e.g. http://proxyserver.domain.com:8080.'),
      '#size' => 25,
      '#maxlength' => 256,
      '#default_value' => $config->get('proxy_url'),
      '#required' => FALSE,
      '#weight' => 40,
    ];
    $form['proxy']['proxy_auth'] = [
      '#type' => 'textfield',
      '#title' => t('Proxy Authentication'),
      '#description' => t('If the proxy requires authentication then provide the \'username:password\'.'),
      '#size' => 25,
      '#maxlength' => 128,
      '#default_value' => $config->get('proxy_auth'),
      '#required' => FALSE,
      '#weight' => 50,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $currentCategories = $this->config('ibm_apim.settings')->get('categories');

    $codesnippets = [
      'curl' => (bool) $form_state->getValue('codesnippets_curl'),
      'ruby' => (bool) $form_state->getValue('codesnippets_ruby'),
      'python' => (bool) $form_state->getValue('codesnippets_python'),
      'php' => (bool) $form_state->getValue('codesnippets_php'),
      'java' => (bool) $form_state->getValue('codesnippets_java'),
      'node' => (bool) $form_state->getValue('codesnippets_node'),
      'go' => (bool) $form_state->getValue('codesnippets_go'),
      'swift' => (bool) $form_state->getValue('codesnippets_swift'),
      'c' => (bool) $form_state->getValue('codesnippets_c'),
      'csharp' => (bool) $form_state->getValue('codesnippets_csharp'),
    ];
    $categories = [
      'enabled' => (bool) $form_state->getValue('categories_enabled'),
      'create_taxonomies_from_categories' => (bool) $form_state->getValue('create_taxonomies_from_categories'),
    ];

    // Set the submitted configuration setting
    $this->config('ibm_apim.settings')
      ->set('autocreate_apiforum', (bool) $form_state->getValue('autocreate_apiforum'))
      ->set('show_placeholder_images', (bool) $form_state->getValue('show_placeholder_images'))
      ->set('show_register_app', (bool) $form_state->getValue('show_register_app'))
      ->set('show_versions', (bool) $form_state->getValue('show_versions'))
      ->set('enable_api_test', (bool) $form_state->getValue('enable_api_test'))
      ->set('validate_apis', (bool) $form_state->getValue('validate_apis'))
      ->set('autotag_with_phase', (bool) $form_state->getValue('autotag_with_phase'))
      ->set('show_cors_warnings', (bool) $form_state->getValue('show_cors_warnings'))
      ->set('show_analytics', (bool) $form_state->getValue('show_analytics'))
      ->set('soap_swagger_download', (bool) $form_state->getValue('soap_swagger_download'))
      ->set('optimise_oauth_ux', (bool) $form_state->getValue('optimise_oauth_ux'))
      ->set('show_mtls_header', (bool) $form_state->getValue('show_mtls_header'))
      ->set('application_image_upload', (bool) $form_state->getValue('application_image_upload'))
      ->set('hide_admin_registry', (bool) $form_state->getValue('hide_admin_registry'))
      ->set('render_api_schema_view', (bool) $form_state->getValue('render_api_schema_view'))
      ->set('allow_consumerorg_creation', (bool) $form_state->getValue('allow_consumerorg_creation'))
      ->set('allow_consumerorg_rename', (bool) $form_state->getValue('allow_consumerorg_rename'))
      ->set('allow_consumerorg_change_owner', (bool) $form_state->getValue('allow_consumerorg_change_owner'))
      ->set('allow_consumerorg_delete', (bool) $form_state->getValue('allow_consumerorg_delete'))
      ->set('enable_oidc_register_form', (bool) $form_state->getValue('enable_oidc_register_form'))
      ->set('enable_oidc_login_form', (bool) $form_state->getValue('enable_oidc_login_form'))
      ->set('allow_user_delete', (bool) $form_state->getValue('allow_user_delete'))
      ->set('allow_new_credentials', (bool) $form_state->getValue('allow_new_credentials'))
      ->set('allow_clientid_reset', (bool) $form_state->getValue('allow_clientid_reset'))
      ->set('allow_clientsecret_reset', (bool) $form_state->getValue('allow_clientsecret_reset'))
      ->set('soap_codesnippets', (bool) $form_state->getValue('soap_codesnippets'))
      ->set('certificate_strip_newlines', (bool) $form_state->getValue('certificate_strip_newlines'))
      ->set('certificate_strip_prefix', (bool) $form_state->getValue('certificate_strip_prefix'))
      ->set('use_proxy', (bool) $form_state->getValue('use_proxy'))
      ->set('proxy_for_api', implode(',', $form_state->getValue('proxy_for_api')))
      ->set('proxy_type', $form_state->getValue('proxy_type'))
      ->set('proxy_url', $form_state->getValue('proxy_url'))
      ->set('proxy_auth', $form_state->getValue('proxy_auth'))
      ->set('categories', $categories)
      ->set('codesnippets', $codesnippets)
      ->save();

    // If we're just enabling categories then we should go process all the apis & products in our db to check them for categories
    if ((bool) $form_state->getValue('enabled') === TRUE && ($currentCategories['enabled'] !== (bool) $form_state->getValue('enabled') ||
        $currentCategories['create_taxonomies_from_categories'] !== (bool) $form_state->getValue('create_taxonomies_from_categories'))) {
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      $batch = [
        'title' => t('Processing categories...'),
        'operations' => [],
        'init_message' => t('Commencing'),
        'progress_message' => t('Processed @current out of @total.'),
        'error_message' => t('An error occurred during processing'),
      ];
      // APIs
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'api');
      $query->condition('status', 1);
      $nids = $query->execute();
      if ($nids !== NULL && !empty($nids)) {
        foreach ($nids as $key => $nid) {
          $batch['operations'][] = ['\Drupal\apic_api\Api::processCategoriesForNode', [$nid]];
        }
      }

      // Products
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('status', 1);
      $nids = $query->execute();
      if ($nids !== NULL && !empty($nids)) {
        foreach ($nids as $key => $nid) {
          $batch['operations'][] = ['\Drupal\product\Product::processCategoriesForNode', [$nid]];
        }
      }

      if (!empty($batch['operations'])) {
        batch_set($batch);
      }

      if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }

    parent::submitForm($form, $form_state);
  }
}
