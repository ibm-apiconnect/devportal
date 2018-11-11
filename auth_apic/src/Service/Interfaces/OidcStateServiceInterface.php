<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\auth_apic\Service\Interfaces;


interface OidcStateServiceInterface {

  /**
   * Store data in state.
   *
   * @param $data data to be stored.
   *
   * @return string encrypted key for the data.
   */
  public function store($data);

  /**
   * Get an item from state.
   *
   * @param string $key encrypted key
   *
   * @return mixed
   */
  public function get(string $key);

  /**
   * Delete an item from state.
   *
   * @param string $key encrypted key
   *
   * @return bool Success of deletion.
   */
  public function delete(string $key);


  /**
   * Prune expired entries from state.
   * Based on the exp time stored in the state.
   *
   * @return mixed
   */
  public function prune();

}
