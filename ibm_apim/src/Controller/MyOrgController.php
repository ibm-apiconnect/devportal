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

namespace Drupal\ibm_apim\Controller;

use Drupal\consumerorg\ApicType\Member;
use Drupal\consumerorg\ApicType\Role;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Billing;
use Drupal\ibm_apim\Service\MyOrgService;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MyOrgController extends ControllerBase {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\Billing
   */
  protected $billingService;

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected $siteConfig;

  /**
   * @var \Drupal\ibm_apim\Service\MyOrgService
   */
  protected $orgService;

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected $consumerOrgService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(UserUtils $userUtils,
                              Billing $billingService,
                              ConfigFactory $config_factory,
                              SiteConfig $site_config,
                              MyOrgService $org_service,
                              ConsumerOrgService $consumerOrgService,
                              EntityTypeManagerInterface $entityTypeManager) {
    $this->userUtils = $userUtils;
    $this->billingService = $billingService;
    $this->config = $config_factory->get('ibm_apim.settings');
    $this->siteConfig = $site_config;
    $this->orgService = $org_service;
    $this->consumerOrgService = $consumerOrgService;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.billing'),
      $container->get('config.factory'),
      $container->get('ibm_apim.site_config'),
      $container->get('ibm_apim.myorgsvc'),
      $container->get('ibm_apim.consumerorg'),
      $container->get('entity_type.manager')
    );
  }

  public function content(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $myorg = NULL;
    $owner = NULL;
    $members = [];
    $orgRoles = [];
    $hasMemberManagePerm = FALSE;
    $hasSettingsManagePerm = FALSE;
    $canTransferOwner = FALSE;
    $canRenameOrg = FALSE;
    $canDeleteOrg = FALSE;

    $org = $this->userUtils->getCurrentConsumerorg();
    // load the current consumerorg node to pass through to the twig template
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $org['url']);
    $nids = $query->execute();
    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $myorg = Node::load($nid);
      // ensure use translated version of the node (just in case - but unlikely to matter here)
      $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $hasTranslation = $myorg->hasTranslation($lang_code);
      if ($hasTranslation === true) {
        $myorg = $myorg->getTranslation($lang_code);
      }

      $myorgOwnerUrl = $myorg->consumerorg_owner->value;
      $cOrgRoles = $myorg->consumerorg_roles->getValue();
      if ($cOrgRoles !== NULL) {
        $whitelist = [Role::class];
        foreach ($cOrgRoles as $arrayValue) {
          // Owner and member are special cases that we handle separately in the org page.
          $role = new Role();
          $role->createFromArray(unserialize($arrayValue['value'], ['allowed_classes' => $whitelist]));
          if ($role->getName() !== 'owner' && $role->getName() !== 'member') {
            $orgRoles[] = $role;
          }
        }
      }

      $cOrgMembers = $myorg->consumerorg_members->getValue();
      if ($cOrgMembers !== NULL) {
        $whitelist = [Member::class, ApicUser::class];
        foreach ($cOrgMembers as $arrayValue) {
          $orgMember = new Member();
          $orgMember->createFromArray(unserialize($arrayValue['value'], ['allowed_classes' => $whitelist]));

          $memberUserUrl = $orgMember->getUserUrl();
          if ($myorgOwnerUrl === $memberUserUrl) {
            $owner = $this->orgService->prepareOrgMemberForDisplay($orgMember);
          }
          else {
            $members[] = $this->orgService->prepareOrgMemberForDisplay($orgMember);
          }
        }
      }

      // add pending invitations into the list of members.
      $cOrgInvites = $myorg->consumerorg_invites->getValue();
      if ($cOrgInvites !== NULL) {
        foreach ($cOrgInvites as $invites_array) {
          $invite = unserialize($invites_array['value'], ['allowed_classes' => FALSE]);
          $invitedMember = [];
          $invitedMember['details'] = $invite['email'];
          $invitedMember['state'] = 'Pending';
          $invitedMember['id'] = basename($invite['url']);
          $invitedMember['role_urls'] = $invite['role_urls'];
          $members[] = $invitedMember;
        }
      }

      foreach ($members as &$member) {
        $roles = [];
        foreach ($member['role_urls'] as $role_url) {
          $role = $orgRoles[array_search($role_url, array_column($orgRoles, 'url'))];
          $roles[] = $role;
        }
        $member['roles'] = $roles;
        // needed otherwise we will keep the reference to $member
        unset($member);
      }

      // TODO: sort members so we are consistent

      $hasMemberManagePerm = $this->userUtils->checkHasPermission('member:manage');
      $hasSettingsManagePerm = $this->userUtils->checkHasPermission('settings:manage');

      $allowConsumerorgChangeOwner = (boolean) $this->config->get('allow_consumerorg_change_owner');
      $allowConsumerorgRename = (boolean) $this->config->get('allow_consumerorg_rename');
      $allowConsumerorgDelete = (boolean) $this->config->get('allow_consumerorg_delete');

      $canTransferOwner = $hasSettingsManagePerm && $allowConsumerorgChangeOwner;
      $canRenameOrg = $hasSettingsManagePerm && $allowConsumerorgRename;
      $canDeleteOrg = $hasSettingsManagePerm && $allowConsumerorgDelete;

    }

    if ($myorg === NULL || empty($myorg)) {
      // the user is not in any orgs. send them somewhere else.
      // if onboarding is enabled, we can redirect to the create org page
      if ($this->siteConfig->isSelfOnboardingEnabled()) {
        $response = new RedirectResponse(Url::fromRoute('consumerorg.create')->toString());
      }
      else {
        // we can't help the user, they need to talk to an administrator
        $response = new RedirectResponse(Url::fromRoute('ibm_apim.noperms')->toString());
      }
      if (isset($response)) {
        $response->send();
      }
    }

    $custom_fields = [];
    $content = [];
    $entityView = $this->entityTypeManager->getStorage('entity_view_display')
      ->load('node.consumerorg.default');
    if ($entityView !== NULL) {
      $fieldList = array_keys($entityView->getComponents());
      $coreFields = ['title', 'vid', 'status', 'nid', 'revision_log', 'created', 'links', 'uid'];
      $ibmFields = $this->consumerOrgService->getIBMFields();
      $merged = array_merge($coreFields, $ibmFields);
      $custom_fields = array_diff($fieldList, $merged);
      foreach($custom_fields as $custom_field) {
        $content[$custom_field] = $myorg->$custom_field->view();
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return [
      '#cache' => [
        'tags' => ['myorg:url:' . $org['url']],
      ],
      '#theme' => 'ibm_apim_myorg',
      '#images_path' => drupal_get_path('module', 'ibm_apim'),
      '#myorg_title' => $myorg->getTitle(),
      '#myorg_name' => $myorg->consumerorg_name->value,
      '#myorg_url' => $myorg->consumerorg_url->value,
      '#myorg_owner' => $owner,
      '#myorg_members' => $members,
      '#myorg_roles' => $orgRoles,
      '#myorg' => $myorg,
      '#myorg_has_member_manage_perm' => $hasMemberManagePerm,
      '#myorg_has_settings_manage_perm' => $hasSettingsManagePerm,
      '#myorg_can_transfer_owner' => $canTransferOwner,
      '#myorg_can_rename_org' => $canRenameOrg,
      '#myorg_can_delete_org' => $canDeleteOrg,
      '#custom_fields' => $custom_fields,
      '#content' => $content,
    ];
  }

  /**
   * Allow consumerorg owner to set billing info for monetization
   */
  public function billing(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $markup = '';
    $billingData = [];
    $consumerOrg = $this->userUtils->getCurrentConsumerorg();

    // get current billing info
    $url = '/' . $consumerOrg['url'] . '/payment-methods';
    $result = ApicRest::get($url);
    // TODO this needs fixing up to reflect the consumer api, and the fact can have multiple payment methods per consumer org now
    // TODO it also needs converting over to have the HTML in a template and pass variables through rather than inlined HTML here.
    if (isset($result, $result->data) && !isset($result->data['errors'])) {
      $billingData = $result->data;
    }
    if (!isset($billingData[0]['properties']['email'])) {
      $billingData[0]['properties']['email'] = '';
    }
    if (!isset($billingData[0]['properties']['name'])) {
      $billingData[0]['properties']['name'] = '';
    }
    if (!isset($billingData[0]['properties']['last4'])) {
      $billingData[0]['properties']['last4'] = '';
    }
    if (!isset($billingData[0]['properties']['exp_month'])) {
      $billingData[0]['properties']['exp_month'] = '';
    }
    if (!isset($billingData[0]['properties']['exp_year'])) {
      $billingData[0]['properties']['exp_year'] = '';
    }
    $markup .= '<p>' . t('The current billing information for your consumer organization is shown below. To update the information use the \'Update\' button to launch a wizard which will guide you through the process.') . '</p>';
    $markup .= "<div id='billing' class='fieldset-wrapper'>";
    $markup .= "<div class='form-item form-item-role'>";
    $markup .= "<label for='edit-role'>" . t('Contact information') . '</label>';
    $markup .= "<div class='form-item form-type-textfield'>";
    $markup .= "<label for='billing_name'>" . t('Name:') . '</label>';
    $markup .= "<input type='text' id='billing_name' readonly size='20' value='" . $billingData[0]['properties']['name'] . "' placeholder='" . t('None on file') . "' class='form-text'/>";
    $markup .= '</div>';
    $markup .= "<div class='form-item form-type-textfield'>";
    $markup .= "<label for='billing_email'>" . t('Email:') . '</label>';
    $markup .= "<input type='text' id='billing_email' readonly size='20' value='" . $billingData[0]['properties']['email'] . "' placeholder='" . t('None on file') . "' class='form-text'/>";
    $markup .= '</div>';
    $markup .= '</div>';
    $markup .= "<div class='form-item form-item-role'>";
    $markup .= "<label for='edit-role'>" . t('Credit card') . '</label>';
    $markup .= "<div class='form-item form-type-textfield'>";
    $markup .= "<label for='card_ending'>" . t('Card ending in:') . '</label>';
    $markup .= "<input type='text' id='card_ending' readonly size='11' value='" . $billingData[0]['properties']['last4'] . "' placeholder='" . t('None on file') . "' class='form-text'/>";
    $markup .= '</div>';
    $markup .= "<div class='form-item form-type-textfield form-item-new-email'>";
    $markup .= "<label for='card_expiring'>" . t('Card expiration:') . '</label>';
    $markup .= "<input type='text' id='card_expiring' readonly size='10' value='" . $billingData[0]['properties']['exp_month'] . '/' . $billingData[0]['properties']['exp_year'] . "' placeholder='" . t('None on file') . "' class='form-text'/>";
    $markup .= '</div>';
    $markup .= '</div>';
    $markup .= "<button id='billing_button' class='form-submit'>" . t('Update') . '</button>';
    $markup .= '</div>';

    // get the current site logo
    if (!$logo = theme_get_setting('logo_path')) {
      $logo = theme_get_setting('logo');
    }

    if (empty($logo)) {
      $logo = Url::fromUri('internal:/modules/ibm_apim/images/product_05.png')->toString();
    }

    // TODO : multiple billing services?
    $billing_service = $this->billingService->getAll();
    if (!empty($billing_service)) {
      $billing_service = $billing_service[0];
    }
    if (!isset($billing_service['configuration']['publishableKey'])) {
      $billing_service['configuration']['publishableKey'] = '';
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return [
      '#markup' => $markup,
      '#attached' => [
        'drupalSettings' => [
          'billing_key' => $billing_service['configuration']['publishableKey'],
          'billing_siteName' => \Drupal::config('system.site')->get('name'),
          'billing_logoPath' => $logo,
          'billing_label' => t('Update Billing Details'),
          'billing_description' => t('Update your Billing Information'),
          'billing_endpoint' => Url::fromUri('internal:/ibm_apim/billing')->toString(),
        ],
        'library' => [
          'ibm_apim/billing',
        ],
      ],
    ];
  }

  /**
   * Take token from stripe and pass to APIM
   *
   * @param $input
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function billingSubmit($input): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $token = json_decode(ibm_apim_base64_url_decode($input), TRUE);
    $consumerOrg = $this->userUtils->getCurrentConsumerorg();

    if (!isset($token['card']['name'])) {
      $token['card']['name'] = ' ';
    }
    if (!isset($token['email'])) {
      $token['email'] = ' ';
    }
    $data = [
      'provider' => 'stripe',
      'model' => 'stripe_customer',
      'properties' => [
        'source' => $token['id'],
        'email' => $token['email'],
        'name' => $token['card']['name'],
      ],
    ];

    $url = '/' . $consumerOrg['url'] . '/payment-gateways';
    $result = ApicRest::put($url, json_encode($data));
    if (isset($result, $result->data) && !isset($result->data['errors'])) {
      drupal_set_message(t('Billing information updated successfully'));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
