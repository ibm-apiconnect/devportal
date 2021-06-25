<?php

/**
 * @file
 * Contains \Drupal\apic_type_count\Controller\ApicNodeListController.
 */

namespace Drupal\apic_type_count\Controller;

use Drupal\Core\Controller\ControllerBase;


class ApicConfigController extends ControllerBase {

  /**
   *
   * @return array|null
   */
  public static function getConfig(): ?array {
    $json = [];
    $siteConfigSvc = \Drupal::service('ibm_apim.site_config');
    $urSvc = \Drupal::service('ibm_apim.user_registry');
    $billingSvc = \Drupal::service('ibm_apim.billing');
    $groupSvc = \Drupal::service('ibm_apim.group');
    $permissionSvc = \Drupal::service('ibm_apim.permissions');
    $paymentMethodSvc = \Drupal::service('ibm_apim.payment_method_schema');
    $analyticsSvc = \Drupal::service('ibm_apim.analytics');
    $tlsSvc = \Drupal::service('ibm_apim.tls_client_profiles');
    $apiUtils = \Drupal::service('apic_api.utils');
    $json['clientId'] = $siteConfigSvc->getClientId();
    $json['orgId'] = $siteConfigSvc->getOrgId();
    $json['catalogId'] = $siteConfigSvc->getEnvId();
    $json['catalog'] = $siteConfigSvc->getCatalog();
    $json['platformAPIEndpoint'] = $siteConfigSvc->getPlatformApimEndpoint();
    $json['consumerAPIEndpoint'] = $siteConfigSvc->getApimHost();
    $json['inCloud'] = $siteConfigSvc->isInCloud();
    $json['selfSignUpEnabled'] = $siteConfigSvc->isSelfOnboardingEnabled();
    $json['consumerOrgInvitationEnabled'] = $siteConfigSvc->isConsumerOrgInvitationEnabled();
    $json['consumerOrgInvitationRoles'] = $siteConfigSvc->getConsumerOrgInvitationRoles();
    $json['accountApprovalEnabled'] = $siteConfigSvc->isAccountApprovalsEnabled();
    $defUR = $urSvc->getDefaultRegistry();
    if ($defUR !== NULL) {
      $defUR = $defUR->toArray();
    }
    $json['defaultUserRegistry'] = $defUR;
    $urs = $urSvc->getAll();
    $ursResult = [];
    foreach ($urs as $urUrl => $ur) {
      $ursResult[$urUrl] = $ur->toArray();
    }
    $json['userRegistries'] = $ursResult;
    $json['billingProviders'] = $billingSvc->getAll();
    $json['paymentMethodSchemas'] = $paymentMethodSvc->getAll();
    $json['groups'] = $groupSvc->getAll();
    $json['permissions'] = $permissionSvc->getAll();
    $analyticsList = $analyticsSvc->getAll();
    $analytics = [];
    foreach ($analyticsList as $url => $anal) {
      $analytics[$url] = $anal->toArray();
    }
    $json['analytics'] = $analytics;
    $tlsList = $tlsSvc->getAll();
    $tlsResult = [];
    foreach ($tlsList as $url => $tls) {
      $tlsResult[$url] = $tls->toArray();
    }
    $json['tlsProfiles'] = $tlsResult;
    $json['asyncApisPresent'] = $apiUtils->areEventAPIsPresent();
    return $json;
  }

}
