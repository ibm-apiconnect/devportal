<?php

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

    return parent::get($key, $default);
  }

}
