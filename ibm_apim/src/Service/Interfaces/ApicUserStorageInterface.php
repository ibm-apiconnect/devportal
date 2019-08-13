<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service\Interfaces;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\user\UserInterface;

interface ApicUserStorageInterface {

  // TODO: should these consistently use UserInterface?
  public function register(ApicUser $user): ?EntityInterface;
  public function load(ApicUser $user): ?EntityInterface;
  public function loadUserByEmailAddress(string $email): ?EntityInterface;
  public function userLoginFinalize(UserInterface $account): UserInterface;
  public function loadUserByUrl($url): ?AccountInterface;

}
