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
   *
   * @return string
   */
  function t($string) {
    return $string;
  }


  function user_cancel() {

  }

}
