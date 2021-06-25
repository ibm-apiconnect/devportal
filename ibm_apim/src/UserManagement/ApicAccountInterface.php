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

namespace Drupal\ibm_apim\UserManagement;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\user\UserInterface;

interface ApicAccountInterface {

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return EntityInterface
   */
  public function registerApicUser(ApicUser $user): ?EntityInterface;

  /**
   * Checks to see if the user details provided match with an account in the drupal database.
   * If not, an account is created.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return null|UserInterface
   */
  public function createOrUpdateLocalAccount(ApicUser $user): ?UserInterface;

  /**
   * Updates the local drupal user account based on the possibly updated
   * user provided.
   *
   * @param ApicUser $user
   *  the user to be updated
   *
   * @return null|UserInterface|EntityInterface
   */
  public function updateLocalAccount(ApicUser $user);

  /**
   * Updates the roles of a given user. The roles specified replace all existing
   * roles. The list must be a complete list of all roles that you want the user
   * to have.
   *
   * @param ApicUser $user
   *  the user to update
   * @param array $roles
   *  the complete list of roles to set for this user
   *
   * @return bool
   */
  public function updateLocalAccountRoles(ApicUser $user, array $roles): bool;

  /**
   * Updates the user profile of an apim user.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *   the user profile to update including the updated fields
   *
   * @return ApicUser|null
   */
  public function updateApicAccount(ApicUser $user): ?ApicUser;

  /**
   * @param ApicUser $apic_user
   * @param $user
   * @param FormStateInterface $form_state
   * @param $view_mode
   */
  public function saveCustomFields(ApicUser $apic_user, $user, FormStateInterface $form_state, $view_mode): void;

  /**
   * @param $user
   */
  public function setDefaultLanguage($user): void;

}
