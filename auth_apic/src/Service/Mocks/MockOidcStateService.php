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

namespace Drupal\auth_apic\Service\Mocks;

use Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface;

class MockOidcStateService implements OidcStateServiceInterface {

  private $storage = [];

  public function __construct(){
    $stateObj = [];
    $stateObj['registry_url'] = 'valid';
    $this->storage["key"] = $stateObj;

  }

  /**
   * {@inheritdoc}
   */
  public function store($data) {
  }
  
  /**
   * {@inheritdoc}
   */
  public function get(string $key) {
    if (array_key_exists($key, $this->storage)) {
      return $this->storage[$key]; 
    } else {
      return null;
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function delete(string $key) {
  }

  /**
   * {@inheritdoc}
   */
  public function prune() {
  }

}
