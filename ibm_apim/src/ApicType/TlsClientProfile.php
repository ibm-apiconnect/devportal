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

namespace Drupal\ibm_apim\ApicType;


class TlsClientProfile {

  private $id;
  private $name;
  private $url;
  private $keystore;

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @param mixed $url
   */
  public function setUrl($url) {
    $this->url = $url;
  }

  /**
   * @return mixed
   */
  public function getKeystore() {
    return $this->keystore;
  }

  /**
   * @param mixed $keystore
   */
  public function setKeystore($keystore) {
    $this->keystore = $keystore;
  }


  /**
   * Configured this analytics service definition using the values provided in
   * the $data array.
   *
   * @param $data
   */
  public function setValues($data) {

    if(isset($data['id'])) {
      $this->setId($data['id']);
    }
    if(isset($data['name'])) {
      $this->setName($data['name']);
    }
    if(isset($data['url'])) {
      $this->setUrl($data['url']);
    }
    if(isset($data['keystore'])) {
      $this->setKeystore($data['keystore']);
    }

  }

  /**
   * Gets the private key entry (keyfile) from the keystore of this tls profile
   *
   * @return mixed
   */
  public function getKeyFile(){

    if($this->getKeystore() !== NULL && isset($this->getKeystore()['private_key_entry'])) {
      return $this->getKeystore()['private_key_entry'];
    }

  }

  /**
   * Gets the pem of the public certificate entry from the keystore of this tls profile
   *
   * @return mixed
   */
  public function getCertFile() {

    $keystore = $this->getKeystore();
    if(isset($keystore) && isset($keystore['public_certificate_entry']) && isset($keystore['public_certificate_entry']['pem'])) {
      return $keystore['public_certificate_entry']['pem'];
    }

  }

}