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

namespace Drupal\ibm_apim\ApicType;


class TlsClientProfile {

  /**
   * @var string|null
   */
  private ?string $id = NULL;

  /**
   * @var string|null
   */
  private ?string $name = NULL;

  /**
   * @var string|null
   */
  private ?string $url = NULL;

  /**
   * @var array
   */
  private array $keystore = [];

  /**
   * @return string|null
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * @param string|null $id
   */
  public function setId(?string $id): void {
    $this->id = $id;
  }

  /**
   * @return string|null
   */
  public function getName(): ?string {
    return $this->name;
  }

  /**
   * @param string|null $name
   */
  public function setName(?string $name): void {
    $this->name = $name;
  }

  /**
   * @return string|null
   */
  public function getUrl(): ?string {
    return $this->url;
  }

  /**
   * @param string|null $url
   */
  public function setUrl(?string $url): void {
    $this->url = $url;
  }

  /**
   * @return array
   */
  public function getKeystore(): array {
    return $this->keystore;
  }

  /**
   * @param array $keystore
   */
  public function setKeystore(array $keystore): void {
    $this->keystore = $keystore;
  }


  /**
   * Configured this analytics service definition using the values provided in
   * the $data array.
   *
   * @param array $data
   */
  public function setValues(array $data): void {

    if (isset($data['id'])) {
      $this->setId($data['id']);
    }
    if (isset($data['name'])) {
      $this->setName($data['name']);
    }
    if (isset($data['url'])) {
      $this->setUrl($data['url']);
    }
    if (isset($data['keystore'])) {
      $this->setKeystore($data['keystore']);
    }

  }

  /**
   * Gets the private key entry (keyfile) from the keystore of this tls profile
   *
   * @return mixed
   */
  public function getKeyFile() {
    $returnValue = NULL;
    if ($this->getKeystore() !== NULL && isset($this->getKeystore()['private_key_entry'])) {
      $returnValue = $this->getKeystore()['private_key_entry'];
    }
    return $returnValue;
  }

  /**
   * Gets the pem of the public certificate entry from the keystore of this tls profile
   *
   * @return mixed
   */
  public function getCertFile() {
    $returnValue = NULL;
    $keystore = $this->getKeystore();
    if (isset($keystore['public_certificate_entry']['pem'])) {
      $returnValue = $keystore['public_certificate_entry']['pem'];
    }
    return $returnValue;
  }

  /**
   * Used in the getconfig drush command
   *
   * @return array
   */
  public function toArray(): array {
    $output = [];
    $output['id'] = $this->getId();
    $output['name'] = $this->getName();
    $output['keyFile'] = $this->getKeyFile();
    $output['keyStore'] = $this->getKeyStore();
    return $output;
  }

}