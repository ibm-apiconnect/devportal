<?php

namespace Drupal\ibm_apim\Session;

use \Drupal\Core\Session\SessionManager;


class APICSessionManager extends SessionManager {

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options): void {
    parent::setOptions($options);

    if ($this->requestStack->getCurrentRequest()->isSecure()) {
      if (\Drupal::service('ibm_apim.user_registry')->isOidcRegistryPresent()) {
        ini_set('session.cookie_samesite', 'Lax');
      }
      else {
        ini_set('session.cookie_samesite', 'Strict');
      }
    }
  }

}