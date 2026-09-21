<?php

namespace Drupal\auth_apic\Service\Interfaces;

use Drupal\auth_apic\JWTToken;
use Drupal\ibm_apim\ApicType\UserRegistry;

interface OidcRegistryServiceInterface {

  /**
   * Get information about an oidc registry, specifically:
   *   - oidc authorization url
   *   - image to display on user management forms.
   *
   * @param \Drupal\ibm_apim\ApicType\UserRegistry $registry
   * @param \Drupal\auth_apic\JWTToken $invitation_object
   *
   * @return ?array Associative array, keys az_url and image or NULL;
   */
  public function getOidcMetadata(UserRegistry $registry, JWTToken $invitation_object): ?array;

}
