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

namespace Drupal\apic_app\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\ibm_apim\Service\Utils;

class CertificateService {

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $ibm_apim_config;

  /**
   * CertificateService constructor.
   *
   * @param \Drupal\ibm_apim\Service\Utils $utils
   * @param \Drupal\Core\Config\ConfigFactoryInterface $ibm_apim_config
   */
  public function __construct(Utils $utils, ConfigFactoryInterface $ibm_apim_config) {
    $this->utils = $utils;
    $this->ibm_apim_config = $ibm_apim_config->get('ibm_apim.settings');
  }

  /**
   * Cleanup the certificate based on the settings
   *
   * @param string|NULL $certificate
   *
   * @return string
   */
  public function cleanup(?string $certificate): string {
    if ($certificate === NULL) {
      $certificate = '';
    }
    if ((boolean) $this->ibm_apim_config->get('certificate_strip_prefix') === TRUE) {
      $certificate = $this->stripPrefix($certificate);
    }
    if ((boolean) $this->ibm_apim_config->get('certificate_strip_newlines') === TRUE) {
      $certificate = $this->stripNewlines($certificate);
    }

    return trim($certificate);
  }

  /**
   * Remove whitespace from the certificate
   *
   * @param $certificate
   *
   * @return string
   */
  public function stripNewlines($certificate): string {
    if ($certificate === NULL) {
      $certificate = '';
    }
    $whitespaceInPrefix = FALSE;
    if ($this->utils->startsWith($certificate, '-----BEGIN CERTIFICATE-----')) {
      $whitespaceInPrefix = TRUE;
    }

    $certificate = str_replace([' ', '\t', '\n', '\r', '\0', '\x0B'], '', $certificate);
    if ($whitespaceInPrefix) {
      // reinstate the space char in the prefix and suffix
      $certificate = str_replace(['BEGINCERTIFICATE', 'ENDCERTIFICATE'], ['BEGIN CERTIFICATE', 'END CERTIFICATE'], $certificate);
    }

    return $certificate;
  }

  /**
   * Remove the prefix and suffix from the certificate
   *
   * @param $certificate
   *
   * @return string
   */
  public function stripPrefix($certificate): string {
    if ($certificate === NULL) {
      $certificate = '';
    }
    return str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'], '', $certificate);
  }

}