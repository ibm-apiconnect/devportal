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

namespace Drupal\ibm_apim\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\Service\MyOrgService;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Billing;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MyOrgController extends ControllerBase {

  protected $userUtils;
  protected $billingService;
  protected $config;
  protected $siteConfig;
  protected $orgService;

  public function __construct(UserUtils $userUtils,
                              Billing $billingService,
                              ConfigFactory $config_factory,
                              SiteConfig $site_config,
                              MyOrgService $org_service) {
    $this->userUtils = $userUtils;
    $this->billingService = $billingService;
    $this->config = $config_factory->get('ibm_apim.settings');
    $this->siteConfig = $site_config;
    $this->orgService = $org_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.billing'),
      $container->get('config.factory'),
      $container->get('ibm_apim.site_config'),
      $container->get('ibm_apim.myorgsvc')
    );
  }

  public function content() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $myorg = NULL;
    $owner = NULL;
    $members = array();
    $org_roles = array();
    $org = $this->userUtils->getCurrentConsumerorg();
    // load the current consumerorg node to pass through to the twig template
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $org['url']);
    $nids = $query->execute();
    if ($nids !== null && !empty($nids)) {
      $nid = array_shift($nids);
      $myorg = Node::load($nid);

      $myorgOwnerUrl = $myorg->consumerorg_owner->value;
      $cOrgRoles = $myorg->consumerorg_roles->getValue();
      if ($cOrgRoles !== null) {
        foreach ($cOrgRoles as $arrayValue) {
          // Owner and member are special cases that we handle separately in the org page.
          $role = unserialize($arrayValue['value']);
          if ($role->getName() !== 'owner' && $role->getName() !== 'member') {
            $org_roles[] = $role;
          }
        }
      }

      $cOrgMembers = $myorg->consumerorg_members->getValue();
      if ($cOrgMembers !== null) {
        foreach ($cOrgMembers as $arrayValue) {
          $orgmember = unserialize($arrayValue['value']);

          $member_user_url = $orgmember->getUserUrl();
          if ($myorgOwnerUrl === $member_user_url) {
            $owner = $this->orgService->prepareOrgMemberForDisplay($orgmember);
          }
          else {
            $members[] = $this->orgService->prepareOrgMemberForDisplay($orgmember);
          }
        }
      }

      // add pending invitations into the list of members.
      $cOrgInvites = $myorg->consumerorg_invites->getValue();
      if ($cOrgInvites !== null) {
        foreach ($cOrgInvites as $invites_array) {
          $invite = unserialize($invites_array['value']);
          $invited_member = [];
          $invited_member['details'] = $invite['email'];
          $invited_member['state'] = 'Pending';
          $invited_member['id'] = basename($invite['url']);
          $invited_member['role_urls'] = $invite['role_urls'];
          $members[] = $invited_member;
        }
      }

      foreach ($members as &$member) {
        $roles = array();
        foreach ($member['role_urls'] as $role_url) {
          $role = $org_roles[array_search($role_url, array_column($org_roles, 'url'))];
          $roles[] = $role;
        }
        $member['roles'] = $roles;
        // needed otherwise we will keep the reference to $member
        unset($member);
      }

      // TODO: sort members so we are consistent

      $has_member_manage_perm = $this->userUtils->checkHasPermission('member:manage');
      $has_settings_manage_perm = $this->userUtils->checkHasPermission('settings:manage');

      $allow_consumerorg_change_owner = $this->config->get('allow_consumerorg_change_owner');
      $allow_consumerorg_rename = $this->config->get('allow_consumerorg_rename');
      $allow_consumerorg_delete = $this->config->get('allow_consumerorg_delete');

      $can_transfer_owner = $has_settings_manage_perm && $allow_consumerorg_change_owner;
      $can_rename_org = $has_settings_manage_perm && $allow_consumerorg_rename;
      $can_delete_org = $has_settings_manage_perm && $allow_consumerorg_delete;

    }

    if ($myorg === null || empty($myorg)) {
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

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return array(
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
      '#myorg_roles' => $org_roles,
      '#myorg' => $myorg,
      '#myorg_has_member_manage_perm' => $has_member_manage_perm,
      '#myorg_has_settings_manage_perm' => $has_settings_manage_perm,
      '#myorg_can_transfer_owner' => $can_transfer_owner,
      '#myorg_can_rename_org' => $can_rename_org,
      '#myorg_can_delete_org' => $can_delete_org
    );
  }

  /**
   * Allow consumerorg owner to set billing info for monetization
   */
  public function billing() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $markup = '';
    $billing_data = array();
    $consumer_org = $this->userUtils->getCurrentConsumerorg();

    // get current billing info
    $url = '/' . $consumer_org['url'] . '/payment-methods';
    $result = ApicRest::get($url);
    // TODO this needs fixing up to reflect the consumer api, and the fact can have multiple payment methods per consumer org now
    // TODO it also needs converting over to have the HTML in a template and pass variables through rather than inlined HTML here.
    if (isset($result) && isset($result->data) && !isset($result->data['errors'])) {
      $billing_data = $result->data;
    }
    if (!isset($billing_data[0]['properties']['email'])) {
      $billing_data[0]['properties']['email'] = '';
    }
    if (!isset($billing_data[0]['properties']['name'])) {
      $billing_data[0]['properties']['name'] = '';
    }
    if (!isset($billing_data[0]['properties']['last4'])) {
      $billing_data[0]['properties']['last4'] = '';
    }
    if (!isset($billing_data[0]['properties']['exp_month'])) {
      $billing_data[0]['properties']['exp_month'] = '';
    }
    if (!isset($billing_data[0]['properties']['exp_year'])) {
      $billing_data[0]['properties']['exp_year'] = '';
    }
    $markup .= "<p>" . t('The current billing information for your consumer organization is shown below. To update the information use the \'Update\' button to launch a wizard which will guide you through the process.') . "</p>";
    $markup .= "<div id='billing' class='fieldset-wrapper'>";
    $markup .= "<div class='form-item form-item-role'>";
    $markup .= "<label for='edit-role'>" . t('Contact information') . "</label>";
    $markup .= "<div class='form-item form-type-textfield'>";
    $markup .= "<label for='billing_name'>" . t('Name:') . "</label>";
    $markup .= "<input type='text' id='billing_name' readonly size='20' value='" . $billing_data[0]['properties']['name'] . "' placeholder='" . t('None on file') . "' class='form-text'/>";
    $markup .= "</div>";
    $markup .= "<div class='form-item form-type-textfield'>";
    $markup .= "<label for='billing_email'>" . t('Email:') . "</label>";
    $markup .= "<input type='text' id='billing_email' readonly size='20' value='" . $billing_data[0]['properties']['email'] . "' placeholder='" . t('None on file') . "' class='form-text'/>";
    $markup .= "</div>";
    $markup .= "</div>";
    $markup .= "<div class='form-item form-item-role'>";
    $markup .= "<label for='edit-role'>" . t('Credit card') . "</label>";
    $markup .= "<div class='form-item form-type-textfield'>";
    $markup .= "<label for='card_ending'>" . t('Card ending in:') . "</label>";
    $markup .= "<input type='text' id='card_ending' readonly size='11' value='" . $billing_data[0]['properties']['last4'] . "' placeholder='" . t('None on file') . "' class='form-text'/>";
    $markup .= "</div>";
    $markup .= "<div class='form-item form-type-textfield form-item-new-email'>";
    $markup .= "<label for='card_expiring'>" . t('Card expiration:') . "</label>";
    $markup .= "<input type='text' id='card_expiring' readonly size='10' value='" . $billing_data[0]['properties']['exp_month'] . '/' . $billing_data[0]['properties']['exp_year'] . "' placeholder='" . t('None on file') . "' class='form-text'/>";
    $markup .= "</div>";
    $markup .= "</div>";
    $markup .= "<button id='billing_button' class='form-submit'>" . t('Update') . "</button>";
    $markup .= "</div>";

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
    return array(
      '#markup' => $markup,
      '#attached' => array(
        'drupalSettings' => array(
          'billing_key' => $billing_service['configuration']['publishableKey'],
          'billing_siteName' => \Drupal::config('system.site')->get('name'),
          'billing_logoPath' => $logo,
          'billing_label' => t('Update Billing Details'),
          'billing_description' => t('Update your Billing Information'),
          'billing_endpoint' => Url::fromUri('internal:/ibm_apim/billing')->toString()
        ),
        'library' => array(
          'ibm_apim/billing',
        ),
      ),
    );
  }

  /**
   * Take token from stripe and pass to APIM
   * @param $input
   */
  public function billingSubmit($input) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $token = json_decode(ibm_apim_base64_url_decode($input), TRUE);
    $consumer_org = $this->userUtils->getCurrentConsumerorg();

    if (!isset($token['card']['name'])) {
      $token['card']['name'] = ' ';
    }
    if (!isset($token['email'])) {
      $token['email'] = ' ';
    }
    $data = array(
      'provider' => 'stripe',
      'model' => 'stripe_customer',
      'properties' => array(
        'source' => $token['id'],
        "email" => $token['email'],
        "name" => $token['card']['name']
      )
    );

    $url = '/' . $consumer_org['url'] . '/payment-gateways';
    $result = ApicRest::put($url, json_encode($data));
    if (isset($result) && isset($result->data) && !isset($result->data['errors'])) {
      drupal_set_message(t('Billing information updated successfully'));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
