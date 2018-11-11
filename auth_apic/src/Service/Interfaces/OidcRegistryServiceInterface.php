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

namespace Drupal\auth_apic\Service\Interfaces;

use Drupal\auth_apic\JWTToken;
use Drupal\ibm_apim\ApicType\UserRegistry;

interface OidcRegistryServiceInterface {

  /**
   * Get information about an oidc registry, specifically:
   *   - oidc authorization url
   *   - image to display on user management forms.
   * @param \Drupal\ibm_apim\ApicType\UserRegistry $registry
   * @param \Drupal\auth_apic\JWTToken $invitation_object
   *
   * @return array Associative array, keys az_url and image;
   */
  public function getOidcMetadata(UserRegistry $registry, JWTToken $invitation_object);

}
