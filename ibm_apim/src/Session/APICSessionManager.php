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

namespace Drupal\ibm_apim\Session;

use \Drupal\Core\Session\SessionManager;


class APICSessionManager extends SessionManager {

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    if ($this->requestStack->getCurrentRequest()->isSecure()) {
      if (\Drupal::service('ibm_apim.user_registry')->isOidcRegistryPresent()) {
        ini_set('session.cookie_samesite', 'Lax');
      } else {
        ini_set('session.cookie_samesite', 'Strict');
      }
    }
  }
}