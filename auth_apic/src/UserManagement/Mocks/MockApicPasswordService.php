<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\auth_apic\UserManagement\Mocks;


use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\UserManagement\ApicPasswordInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\User;

class MockApicPasswordService implements ApicPasswordInterface {

  /**
   * @inheritDoc
   */
  public function resetPassword(JWTToken $obj, $password): int {

    \Drupal::messenger()->addStatus('MOCKED SERVICE:: In resetPassword, returned:' . $obj->getOrg() . ':' . $obj->getEnv());
    if ($obj->getOrg() === 'testorg' && $obj->getEnv() === 'testcatalog') {
      \Drupal::messenger()->addStatus('MOCKED SERVICE:: In resetPassword return 200');
      return 200;
    }
    return 200;
  }

  /**
   * @inheritDoc
   */
  public function changePassword(User $username, $old_password, $new_password): bool {
    if ($old_password === 'thisiswrong') {
      // In the real form we rely on the message we get back from the management server to inform the user that the
      // pw is incorrect, we'll put the message out ourselves.
      \Drupal::messenger()->addStatus('MOCKED SERVICE:: The old password is incorrect');
      return FALSE;
    }
    elseif ($new_password === 'thisisinvalid') {
      \Drupal::messenger()
        ->addStatus('MOCKED SERVICE:: Password must contain characters from 3 of the 4 following categories: 1. upper-case, 2. lower-case, 3. numeric, and 4. punctuation (for example, !, $, #, %)');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function lookupUpAccount(string $reset_password_for, string $registry_url = NULL): ?EntityInterface {
    \Drupal::logger('mock_auth_apic')->debug('@class::@function: TODO IMPL', ['@class' => __CLASS__, '@function' => __FUNCTION__]);
    return NULL;
  }

}
