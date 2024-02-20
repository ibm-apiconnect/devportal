<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\ApicType;


class AnalyticsServiceDefinition {

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
  private ?string $title = NULL;

  /**
   * @var string|null
   */
  private ?string $summary = NULL;

  /**
   * @var string|null
   */
  private ?string $clientEndpoint = NULL;

  /**
   * @var string|null
   */
  private ?string $clientEndpointTlsClientProfileUrl = NULL;

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
  public function getTitle(): ?string {
    return $this->title;
  }

  /**
   * @param string|null $title
   */
  public function setTitle(?string $title): void {
    $this->title = $title;
  }

  /**
   * @return string|null
   */
  public function getSummary(): ?string {
    return $this->summary;
  }

  /**
   * @param string|null $summary
   */
  public function setSummary(?string $summary): void {
    $this->summary = $summary;
  }

  /**
   * @return string|null
   */
  public function getClientEndpoint(): ?string {
    return $this->clientEndpoint;
  }

  /**
   * @param string|null $clientEndpoint
   */
  public function setClientEndpoint(?string $clientEndpoint): void {
    $this->clientEndpoint = $clientEndpoint;
  }

  /**
   * @return string|null
   */
  public function getClientEndpointTlsClientProfileUrl(): ?string {
    return $this->clientEndpointTlsClientProfileUrl;
  }

  /**
   * @param string|null $clientEndpointTlsClientProfileUrl
   */
  public function setClientEndpointTlsClientProfileUrl(?string $clientEndpointTlsClientProfileUrl): void {
    $this->clientEndpointTlsClientProfileUrl = $clientEndpointTlsClientProfileUrl;
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
      $this->setClientEndpoint(rtrim($data['client_endpoint'],"/"));
    }
    if (isset($data['client_endpoint_tls_client_profile_url'])) {
      $this->setClientEndpointTlsClientProfileUrl($data['client_endpoint_tls_client_profile_url']);
    }

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
    $output['title'] = $this->getTitle();
    $output['summary'] = $this->getSummary();
    $output['clientEndpoint'] = $this->getClientEndpoint();
    $output['clientEndpointTlsClientProfileURL'] = $this->getClientEndpointTlsClientProfileUrl();
    return $output;
  }

}