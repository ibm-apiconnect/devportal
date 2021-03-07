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

namespace Drupal\auth_apic\Service\Mocks;


use Drupal\Core\State\State;

class MockLDAPStateService extends State {

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    if ($key === 'ibm_apim.readonly_idp') {
      return 1;
    }
    else {
      return parent::get($key, $default);
    }
  }

}
