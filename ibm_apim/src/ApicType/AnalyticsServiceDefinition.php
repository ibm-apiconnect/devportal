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


class AnalyticsServiceDefinition {

  private $id;
  private $name;
  private $title;
  private $summary;
  private $client_endpoint;
  private $client_endpoint_tls_client_profile_url;

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
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param mixed $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * @return mixed
   */
  public function getSummary() {
    return $this->summary;
  }

  /**
   * @param mixed $summary
   */
  public function setSummary($summary) {
    $this->summary = $summary;
  }

  /**
   * @return mixed
   */
  public function getClientEndpoint() {
    return $this->client_endpoint;
  }

  /**
   * @param mixed $client_endpoint
   */
  public function setClientEndpoint($client_endpoint) {
    $this->client_endpoint = $client_endpoint;
  }

  /**
   * @return mixed
   */
  public function getClientEndpointTlsClientProfileUrl() {
    return $this->client_endpoint_tls_client_profile_url;
  }

  /**
   * @param mixed $client_endpoint_tls_client_profile_url
   */
  public function setClientEndpointTlsClientProfileUrl($client_endpoint_tls_client_profile_url) {
    $this->client_endpoint_tls_client_profile_url = $client_endpoint_tls_client_profile_url;
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
    if(isset($data['title'])) {
      $this->setTitle($data['title']);
    }
    if(isset($data['summary'])) {
      $this->setSummary($data['summary']);
    }
    if(isset($data['client_endpoint'])) {
      $this->setClientEndpoint($data['client_endpoint']);
    }
    if(isset($data['client_endpoint_tls_client_profile_url'])) {
      $this->setClientEndpointTlsClientProfileUrl($data['client_endpoint_tls_client_profile_url']);
    }

  }

}