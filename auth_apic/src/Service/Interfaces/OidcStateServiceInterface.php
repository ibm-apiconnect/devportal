<?php

namespace Drupal\auth_apic\Service\Interfaces;


interface OidcStateServiceInterface {

  /**
   * Store data in state.
   *
   * @param $data
   *
   * @return mixed
   * @throws \Drupal\encrypt\Exception\EncryptException
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
  public function delete(string $key): bool;


  /**
   * Prune expired entries from state.
   * Based on the exp time stored in the state.
   *
   * @return mixed
   */
  public function prune();

  /**
   * get all state from the service
   * needed to by the updateEncryptionKey function
   */
  public function getAllOidcState();

  /**
   * save all state from the service
   * needed to by the updateEncryptionKey function
   */
  public function saveAllOidcState($state): void;

}
