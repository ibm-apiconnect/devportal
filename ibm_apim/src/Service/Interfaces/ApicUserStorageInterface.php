<?php

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
  public function loadUserByUrl($url): ?EntityInterface;

}
