<?php

namespace Drupal\Tests\auth_apic\Unit\UserManagement {

  use Drupal\Tests\auth_apic\Unit\Base\AuthApicTestBaseClass;

  class AuthApicUserManagementBaseTestClass extends AuthApicTestBaseClass {

  }

}

namespace Drupal\auth_apic\UserManagement {

  /**
   * Shadow t() system call.
   *
   * @param string $string
   *   A string containing the English text to translate.
   * @param array $inserts
   *
   * @return string
   */
  function t(string $string, array $inserts = []): string {
    return $string;
  }

  /**
   * @param $edit
   * @param $uid
   * @param $method
   */
  function user_cancel($edit, $uid, $method) {

  }

}
