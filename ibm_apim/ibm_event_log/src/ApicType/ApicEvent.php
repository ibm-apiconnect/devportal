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

namespace Drupal\ibm_event_log\ApicType;

/**
 * Class ApicEvent
 *
 * @package Drupal\ibm_event_log\ApicType
 */
class ApicEvent {

  /**
   * @var string|null
   */
  private ?string $event = NULL;

  /**
   * @var string|null
   */
  private ?string $artifact_type = NULL;

  /**
   * @var string|null
   */
  private ?string $artifact_url = NULL;

  /**
   * @var int
   */
  private int $timestamp = 0;

  /**
   * @var string|null
   */
  private ?string $user_url = NULL;

  /**
   * @var string|null
   */
  private ?string $consumerorg_url = NULL;

  /**
   * @var array
   */
  private array $data = [];

  /**
   * @var string|null
   */
  private ?string $app_url = NULL;

  /**
   * @return ?string
   */
  public function getEvent(): ?string {
    return $this->event;
  }

  /**
   * @param string $event
   *
   * @throws \RuntimeException
   */
  public function setEvent(string $event): void {
    $allowedEvents = ['create', 'update', 'delete', 'request_promotion', 'reset', 'resetSecret', 'change_owner', 'resend_invitation'];
    if (in_array($event, $allowedEvents)) {
      $this->event = $event;
    }
    else {
      throw new \RuntimeException('setEvent: Invalid event type provided');
    }
  }

  /**
   * @return ?string
   */
  public function getArtifactType(): ?string {
    return $this->artifact_type;
  }

  /**
   * @param string $artifact_type
   */
  public function setArtifactType(string $artifact_type): void {
    $allowedTypes = [
      'application',
      'user',
      'consumer_org',
      'credential',
      'subscription',
      'api',
      'product',
      'invitation',
      'member',
      'payment_method',
    ];
    if (in_array($artifact_type, $allowedTypes)) {
      $this->artifact_type = $artifact_type;
    }
    else {
      throw new \RuntimeException('setArtifactType: Invalid artifact type provided');
    }
  }

  /**
   * @return ?string
   */
  public function getArtifactUrl(): ?string {
    return $this->artifact_url;
  }

  /**
   * @param ?string $artifact_url
   */
  public function setArtifactUrl(?string $artifact_url): void {
    if (\Drupal::hasContainer()) {
      $apimUtils = \Drupal::service('ibm_apim.apim_utils');
      $this->artifact_url = $apimUtils->removeFullyQualifiedUrl($artifact_url);
    }
    else {
      $this->artifact_url = $artifact_url;
    }
  }

  /**
   * @return ?string
   */
  public function getAppUrl(): ?string {
    return $this->app_url;
  }

  /**
   * @param ?string $app_url
   */
  public function setAppUrl(?string $app_url): void {
    if (\Drupal::hasContainer()) {
      $apimUtils = \Drupal::service('ibm_apim.apim_utils');
      $this->app_url = $apimUtils->removeFullyQualifiedUrl($app_url);
    }
    else {
      $this->app_url = $app_url;
    }
  }

  /**
   * @return int
   */
  public function getTimestamp(): int {
    return $this->timestamp;
  }

  /**
   * @param int $timestamp
   */
  public function setTimestamp(int $timestamp): void {
    $this->timestamp = $timestamp;
  }

  /**
   * @return string
   */
  public function getUserUrl(): string {
    return $this->user_url ?? '';
  }

  /**
   * @param ?string $user_url
   */
  public function setUserUrl(?string $user_url): void {
    if (\Drupal::hasContainer()) {
      $apimUtils = \Drupal::service('ibm_apim.apim_utils');
      $this->user_url = $apimUtils->removeFullyQualifiedUrl($user_url);
    }
    else {
      $this->user_url = $user_url;
    }
  }

  /**
   * @return ?string
   */
  public function getConsumerOrgUrl(): ?string {
    return $this->consumerorg_url;
  }

  /**
   * @param ?string $consumerorg_url
   */
  public function setConsumerOrgUrl(?string $consumerorg_url): void {
    if (\Drupal::hasContainer()) {
      $apimUtils = \Drupal::service('ibm_apim.apim_utils');
      $this->consumerorg_url = $apimUtils->removeFullyQualifiedUrl($consumerorg_url);
    }
    else {
      $this->consumerorg_url = $consumerorg_url;
    }
  }

  /**
   * @return array
   */
  public function getData(): array {
    return $this->data ?? [];
  }

  /**
   * @param array $data
   */
  public function setData(array $data): void {
    $this->data = $data;
  }

}
