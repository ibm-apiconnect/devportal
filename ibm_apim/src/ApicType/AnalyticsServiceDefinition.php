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

namespace Drupal\ibm_apim\ApicType;


class AnalyticsServiceDefinition {

  private $id;

  private $name;

  private $title;

  private $summary;

  private $client_endpoint;

  private $client_endpoint_tls_client_profile_url;

  /**
   * @return string|null
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * @param string $id
   */
  public function setId($id): void {
    $this->id = $id;
  }

  /**
   * @return string|null
   */
  public function getName(): ?string {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName($name): void {
    $this->name = $name;
  }

  /**
   * @return string|null
   */
  public function getTitle(): ?string {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle($title): void {
    $this->title = $title;
  }

  /**
   * @return string|null
   */
  public function getSummary(): ?string {
    return $this->summary;
  }

  /**
   * @param string $summary
   */
  public function setSummary($summary): void {
    $this->summary = $summary;
  }

  /**
   * @return string|null
   */
  public function getClientEndpoint(): ?string {
    return $this->client_endpoint;
  }

  /**
   * @param string $client_endpoint
   */
  public function setClientEndpoint(string $client_endpoint): void {
    $this->client_endpoint = $client_endpoint;
  }

  /**
   * @return string|null
   */
  public function getClientEndpointTlsClientProfileUrl(): ?string {
    return $this->client_endpoint_tls_client_profile_url;
  }

  /**
   * @param string $client_endpoint_tls_client_profile_url
   */
  public function setClientEndpointTlsClientProfileUrl(string $client_endpoint_tls_client_profile_url): void {
    $this->client_endpoint_tls_client_profile_url = $client_endpoint_tls_client_profile_url;
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
    if (isset($data['title'])) {
      $this->setTitle($data['title']);
    }
    if (isset($data['summary'])) {
      $this->setSummary($data['summary']);
    }
    if (isset($data['client_endpoint'])) {
      $this->setClientEndpoint($data['client_endpoint']);
    }
    if (isset($data['client_endpoint_tls_client_profile_url'])) {
      $this->setClientEndpointTlsClientProfileUrl($data['client_endpoint_tls_client_profile_url']);
    }

  }

}