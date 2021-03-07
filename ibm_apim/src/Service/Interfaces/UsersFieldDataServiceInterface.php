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

namespace Drupal\ibm_apim\Service\Interfaces;


/**
 * Manipulate the users_field_data table.
 *
 * Interface UsersFieldDataServiceInterface
 *
 * @package Drupal\ibm_apim\Service\Interfaces
 */
interface UsersFieldDataServiceInterface {

  /**
   * drop the user__name unique key and add a user__registry__name unique key.
   *
   * @return bool
   */
  public function addNameAndRegistryUniqueKey(): bool;

  /**
   * set the admin user (uid=1) registry_url field.
   * this user is special cased, all other users will be handled via data from apim.
   */
  public function setAdminRegistryUrl(): void;

  /**
   * used in upgrade code to support moving from apic_user_registry_url to registry_url
   * update the (new) registry_url field with the value from (old) apic_user_registry_url field.
   *
   * load all users which have an empty registry_url field.
   * if there is an apic_user_registry_url field with a value then use this to set registry_url.
   */
  public function updateRegistryUrlFieldIfEmpty(): void;

}
