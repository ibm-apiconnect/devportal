<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\auth_apic\Service;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface;
use Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Utils;
use Psr\Log\LoggerInterface;

/**
 * Provide consistent information about the provider type of oidc registries
 *
 * Class OidcRegistryService
 *
 * @package Drupal\auth_apic\Service
 */
class OidcRegistryService implements OidcRegistryServiceInterface {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected $utils;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected $apimUtils;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface
   */
  protected $oidcStateService;

  public function __construct(StateInterface $state,
                              LoggerInterface $logger,
                              Utils $utils,
                              ApimUtils $apim_utils,
                              OidcStateServiceInterface $oidc_state_service) {
    $this->state = $state;
    $this->logger = $logger;
    $this->utils = $utils;
    $this->apimUtils = $apim_utils;
    $this->oidcStateService = $oidc_state_service;
  }

  /**
   * @inheritdoc
   */
  public function getOidcMetadata(UserRegistry $registry, JWTToken $invitation_object = NULL): ?array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    if ($registry->getRegistryType() !== 'oidc') {
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'non oidc registry');
      }
      $this->logger->warning('attempt to get metadata from non-oidc registry');
      return NULL;
    }
    $info = [];
    if ($registry) {
      $info['az_url'] = $this->getOidcUrl($registry, $invitation_object);
      $info['image'] = $this->getImageAsSvg($registry);
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $info;
  }

  /**
   * Get authorization url for oidc registry.
   * That is the url on apim to initiate the oidc dance.
   *
   * @param \Drupal\ibm_apim\ApicType\UserRegistry $registry
   * @param \Drupal\auth_apic\JWTToken|NULL $invitation_object
   *
   * @return string|null
   */
  private function getOidcUrl(UserRegistry $registry, JWTToken $invitation_object = NULL): ?string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $client_id = $this->state->get('ibm_apim.site_client_id');

    if ($client_id === NULL) {
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'no client id');
      }
      $this->logger->warning('unable to retrieve site client id to build oidc authentication url');
      return NULL;
    }

    $state_obj = [];
    $state_obj['registry_url'] = $registry->getUrl();

    if (isset($invitation_object)) {
      // add invitation information to state object (potentially sensitive)
      $state_obj['invitation_object'] = serialize($invitation_object);
      $state_obj['created'] = time();
    }
    // store the state object and send a key as the parameter
    $state_param = $this->utils->base64_url_encode(serialize($this->oidcStateService->store($state_obj)));

    $host = $this->apimUtils->getHostUrl();

    if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
      $route = URL::fromRoute('auth_apic.azcode')->toString();
    }
    else {
      $route = '/test/env';
    }

    $redirect_uri = $host . $route;

    $url = $this->apimUtils->createFullyQualifiedUrl('/consumer-api/oauth2/authorize');

    $url .= '?client_id=' . $client_id;
    $url .= '&state=' . $state_param;
    $url .= '&redirect_uri=' . $redirect_uri;
    $url .= '&realm=' . $registry->getRealm(); // TODO: need to support multiple IDPS.
    $url .= '&response_type=code';
    if (isset($invitation_object)) {
      $url .= '&token=' . $invitation_object->getDecodedJwt();
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    return $url;
  }

  /**
   * Get image to show as icon in forms as svg.
   * Attempts to match on a provider type, or returns a default image.
   *
   * @param \Drupal\ibm_apim\ApicType\UserRegistry $registry
   *
   * @return string
   */
  private function getImageAsSvg(UserRegistry $registry) : ?string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    switch ($registry->getProviderType()) {
      case 'google':
        if (function_exists('ibm_apim_exit_trace')) {
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'google');
        }
        return '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18px" height="18px" viewBox="0 0 48 48" class="abcRioButtonSvg">
                  <g>
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                    <path fill="none" d="M0 0h48v48H0z"></path>
                  </g>
                </svg>';
      case 'github':
        if (function_exists('ibm_apim_exit_trace')) {
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'github');
        }
        return '<svg width="18px" height="18px" viewBox="0 0 256 250" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid">
                  <g>
                    <path d="M128.00106,0 C57.3172926,0 0,57.3066942 0,128.00106 C0,184.555281 36.6761997,232.535542 87.534937,249.460899 C93.9320223,250.645779 96.280588,246.684165 96.280588,243.303333 C96.280588,240.251045 96.1618878,230.167899 96.106777,219.472176 C60.4967585,227.215235 52.9826207,204.369712 52.9826207,204.369712 C47.1599584,189.574598 38.770408,185.640538 38.770408,185.640538 C27.1568785,177.696113 39.6458206,177.859325 39.6458206,177.859325 C52.4993419,178.762293 59.267365,191.04987 59.267365,191.04987 C70.6837675,210.618423 89.2115753,204.961093 96.5158685,201.690482 C97.6647155,193.417512 100.981959,187.77078 104.642583,184.574357 C76.211799,181.33766 46.324819,170.362144 46.324819,121.315702 C46.324819,107.340889 51.3250588,95.9223682 59.5132437,86.9583937 C58.1842268,83.7344152 53.8029229,70.715562 60.7532354,53.0843636 C60.7532354,53.0843636 71.5019501,49.6441813 95.9626412,66.2049595 C106.172967,63.368876 117.123047,61.9465949 128.00106,61.8978432 C138.879073,61.9465949 149.837632,63.368876 160.067033,66.2049595 C184.49805,49.6441813 195.231926,53.0843636 195.231926,53.0843636 C202.199197,70.715562 197.815773,83.7344152 196.486756,86.9583937 C204.694018,95.9223682 209.660343,107.340889 209.660343,121.315702 C209.660343,170.478725 179.716133,181.303747 151.213281,184.472614 C155.80443,188.444828 159.895342,196.234518 159.895342,208.176593 C159.895342,225.303317 159.746968,239.087361 159.746968,243.303333 C159.746968,246.709601 162.05102,250.70089 168.53925,249.443941 C219.370432,232.499507 256,184.536204 256,128.00106 C256,57.3066942 198.691187,0 128.00106,0 Z M47.9405593,182.340212 C47.6586465,182.976105 46.6581745,183.166873 45.7467277,182.730227 C44.8183235,182.312656 44.2968914,181.445722 44.5978808,180.80771 C44.8734344,180.152739 45.876026,179.97045 46.8023103,180.409216 C47.7328342,180.826786 48.2627451,181.702199 47.9405593,182.340212 Z M54.2367892,187.958254 C53.6263318,188.524199 52.4329723,188.261363 51.6232682,187.366874 C50.7860088,186.474504 50.6291553,185.281144 51.2480912,184.70672 C51.8776254,184.140775 53.0349512,184.405731 53.8743302,185.298101 C54.7115892,186.201069 54.8748019,187.38595 54.2367892,187.958254 Z M58.5562413,195.146347 C57.7719732,195.691096 56.4895886,195.180261 55.6968417,194.042013 C54.9125733,192.903764 54.9125733,191.538713 55.713799,190.991845 C56.5086651,190.444977 57.7719732,190.936735 58.5753181,192.066505 C59.3574669,193.22383 59.3574669,194.58888 58.5562413,195.146347 Z M65.8613592,203.471174 C65.1597571,204.244846 63.6654083,204.03712 62.5716717,202.981538 C61.4524999,201.94927 61.1409122,200.484596 61.8446341,199.710926 C62.5547146,198.935137 64.0575422,199.15346 65.1597571,200.200564 C66.2704506,201.230712 66.6095936,202.705984 65.8613592,203.471174 Z M75.3025151,206.281542 C74.9930474,207.284134 73.553809,207.739857 72.1039724,207.313809 C70.6562556,206.875043 69.7087748,205.700761 70.0012857,204.687571 C70.302275,203.678621 71.7478721,203.20382 73.2083069,203.659543 C74.6539041,204.09619 75.6035048,205.261994 75.3025151,206.281542 Z M86.046947,207.473627 C86.0829806,208.529209 84.8535871,209.404622 83.3316829,209.4237 C81.8013,209.457614 80.563428,208.603398 80.5464708,207.564772 C80.5464708,206.498591 81.7483088,205.631657 83.2786917,205.606221 C84.8005962,205.576546 86.046947,206.424403 86.046947,207.473627 Z M96.6021471,207.069023 C96.7844366,208.099171 95.7267341,209.156872 94.215428,209.438785 C92.7295577,209.710099 91.3539086,209.074206 91.1652603,208.052538 C90.9808515,206.996955 92.0576306,205.939253 93.5413813,205.66582 C95.054807,205.402984 96.4092596,206.021919 96.6021471,207.069023 Z" fill="#161614"></path>
                  </g>
                </svg>';
      default:
        if (function_exists('ibm_apim_exit_trace')) {
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'default');
        }
        return '<svg width="18" height="18" viewBox="0 0 32 32" fill-rule="evenodd">
                  <path d="M16 6.4c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm0-2c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9z"></path>
                  <path d="M16 0C7.2 0 0 7.2 0 16s7.2 16 16 16 16-7.2 16-16S24.8 0 16 0zm7.3 24.3H8.7c-1.2 0-2.2.5-2.8 1.3C3.5 23.1 2 19.7 2 16 2 8.3 8.3 2 16 2s14 6.3 14 14c0 3.7-1.5 7.1-3.9 9.6-.6-.8-1.7-1.3-2.8-1.3z"></path>
                </svg>';
    }

  }


}
