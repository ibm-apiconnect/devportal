<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg\ApicType;


use Drupal\ibm_apim\ApicType\ApicUser;

class PaymentMethodObj {

  /**
   * @var string|NULL
   */
  private ?string $url = NULL;

  /**
   * @var string|NULL
   */
  private ?string $org_url = NULL;

  /**
   * @var string|NULL
   */
  private ?string $billing_url = NULL;

  /**
   * @var string|null
   */
  private ?string $payment_method_type_url = NULL;
  /**
   * @var string|null
   */
  private ?string $created_at = NULL;

  /**
   * @var string|null
   */
  private ?string $updated_at = NULL;

  /**
   * @var string|null
   */
  private ?string $title = NULL;

  /**
   * @var string|array|null
   */
  private $configuration = [];

  /**
   * @var string|null
   */
  private ?string $created_by = NULL;

  /**
   * @var string|null
   */
  private ?string $updated_by = NULL;

  /**
   * @var string|null
   */
  private ?string $id = NULL;

  /**
   * @return string|null
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * @param string $id
   */
  public function setId(string $id): void {
    $this->id = $id;
  }

  /**
   * @return string|null
   */
  public function getUrl(): ?string {
    return $this->url;
  }

  /**
   * @param string $url
   */
  public function setUrl(string $url): void {
    $this->url = $url;
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
  public function getOrgUrl(): ?string {
    return $this->org_url;
  }

  /**
   * @param string $org_url
   */
  public function setOrgUrl(string $org_url): void {
    $this->org_url = $org_url;
  }

  /**
   * @return string|null
   */
  public function getBillingUrl(): ?string {
    return $this->billing_url;
  }

  /**
   * @param string $billing_url
   */
  public function setBillingUrl(string $billing_url): void {
    $this->billing_url = $billing_url;
  }

  /**
   * @return string|null
   */
  public function getPaymentMethodTypeUrl(): ?string {
    return $this->payment_method_type_url;
  }

  /**
   * @param string $payment_method_type_url
   */
  public function setPaymentMethodTypeUrl(string $payment_method_type_url): void {
    $this->payment_method_type_url = $payment_method_type_url;
  }

  /**
   * @return string|null
   */
  public function getCreatedAt(): ?string {
    return $this->created_at;
  }

  /**
   * @param string|null $created_at
   */
  public function setCreatedAt(?string $created_at): void {
    $this->created_at = $created_at;
  }

  /**
   * @return string|null
   */
  public function getUpdatedAt(): ?string {
    return $this->updated_at;
  }

  /**
   * @param string|null $updated_at
   */
  public function setUpdatedAt(?string $updated_at): void {
    $this->updated_at = $updated_at;
  }

  /**
   * @return string|null
   */
  public function getCreatedBy(): ?string {
    return $this->created_by;
  }

  /**
   * @param string|null $created_by
   */
  public function setCreatedBy(?string $created_by): void {
    $this->created_by = $created_by;
  }

  /**
   * @return string|null
   */
  public function getUpdatedBy(): ?string {
    return $this->updated_by;
  }

  /**
   * @param string|null $updated_by
   */
  public function setUpdatedBy(?string $updated_by): void {
    $this->updated_by = $updated_by;
  }

  /**
   * @return string|array
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * @param string|array $configuration
   */
  public function setConfiguration($configuration): void {
    $this->configuration = $configuration;
  }

  /**
   * convert array to object
   *
   * @param array $content
   *
   * @throws \JsonException
   */
  public function createFromArray(array $content): void {
    $apimUtils = \Drupal::service('ibm_apim.apim_utils');
    if (array_key_exists('id', $content)) {
      $this->setId($content['id']);
    }
    if (array_key_exists('url', $content)) {
      $this->setUrl($apimUtils->removeFullyQualifiedUrl($content['url']));
    }
    if (array_key_exists('org_url', $content)) {
      $this->setOrgUrl($apimUtils->removeFullyQualifiedUrl($content['org_url']));
    }
    if (array_key_exists('billing_url', $content)) {
      $this->setBillingUrl($apimUtils->removeFullyQualifiedUrl($content['billing_url']));
    }
    if (array_key_exists('payment_method_type_url', $content)) {
      $this->setPaymentMethodTypeUrl($apimUtils->removeFullyQualifiedUrl($content['payment_method_type_url']));
    }
    if (array_key_exists('title', $content)) {
      $this->setTitle($content['title']);
    } else {
      $this->setTitle($content['id']);
    }
    if (array_key_exists('configuration', $content)) {
      $this->setConfiguration($content['configuration']);
    }
    if (array_key_exists('created_at', $content)) {
      $this->setCreatedAt(strtotime($content['created_at']));
    }
    if (array_key_exists('updated_at', $content)) {
      $this->setUpdatedAt(strtotime($content['updated_at']));
    }
    if (array_key_exists('created_by', $content)) {
      $this->setCreatedBy($content['created_by']);
    }
    if (array_key_exists('updated_by', $content)) {
      $this->setUpdatedBy($content['updated_by']);
    }
  }

  /**
   * Convert object to array
   *
   * @return array
   * @throws \JsonException
   */
  public function toArray(): array {
    $content = [];
    if ($this->id !== NULL) {
      $content['id'] = $this->id;
    }
    if ($this->url !== NULL) {
      $content['url'] = $this->url;
    }
    if ($this->org_url !== NULL) {
      $content['org_url'] = $this->org_url;
    }
    if ($this->billing_url !== NULL) {
      $content['billing_url'] = $this->billing_url;
    }
    if ($this->payment_method_type_url !== NULL) {
      $content['payment_method_type_url'] = $this->payment_method_type_url;
    }
    if ($this->title !== NULL) {
      $content['title'] = $this->title;
    }
    if ($this->configuration !== NULL) {
      $content['configuration'] = $this->configuration;
    }
    if ($this->created_at !== NULL) {
      $content['created_at'] = $this->created_at;
    }
    if ($this->updated_at !== NULL) {
      $content['updated_at'] = $this->updated_at;
    }
    if ($this->created_by !== NULL) {
      $content['created_by'] = $this->created_by;
    }
    if ($this->updated_by !== NULL) {
      $content['updated_by'] = $this->updated_by;
    }
    return $content;
  }

}
