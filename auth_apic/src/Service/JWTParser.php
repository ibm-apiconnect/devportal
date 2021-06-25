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

namespace Drupal\auth_apic\Service;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Drupal\ibm_apim\Service\Utils;
use Psr\Log\LoggerInterface;

/**
 * Parse and validate activation tokens.
 */
class JWTParser implements TokenParserInterface {

  protected LoggerInterface $logger;

  protected Utils $utils;

  public function __construct(LoggerInterface $logger,
                              Utils $utils) {
    $this->logger = $logger;
    $this->utils = $utils;
  }

  /**
   * Parse the activation token.
   *
   * Decode, parse and validate the activation token.
   *
   * @param string $token
   *  JWT token.
   *
   * @return JWTToken|null
   * @throws \Exception
   */
  public function parse($token): ?JWTToken {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $token);
    }

    if (!$token) {
      throw new \Exception('No token provided to parser');
    }

    $jwt = new JWTToken();
    $decoded_token = base64_decode($token);

    if (!$this->validate($decoded_token)) {
      $this->logger->error('invalid invitation JWT');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return NULL;
    }

    $jwt->setDecodedJwt($decoded_token);

    // format = header.payload.signature
    $elements = explode('.', $decoded_token);

    //$header = $elements[0];
    try {
      $header = json_decode($this->utils->base64_url_decode($elements[0]), TRUE, 512, JSON_THROW_ON_ERROR);
      $payload = json_decode($this->utils->base64_url_decode($elements[1]), TRUE, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
    }
    if (!isset($payload['scopes']['url'])) {
      $this->logger->error('payload.scopes.url not available in activation JWT');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return NULL;
    }
    if (!isset($header)) {
      $this->logger->error('header not set from activation JWT');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return NULL;
    }

    // handle the url possibly starting with /consumer-api
    $prefix = '/consumer-api';
    if (strpos($payload['scopes']['url'], $prefix) === 0) {
      $url = substr($payload['scopes']['url'], strlen($prefix));
    }
    else {
      $url = $payload['scopes']['url'];
    }
    $jwt->setUrl($url);

    $signature = $elements[2];

    $jwt->setHeaders($header);
    $jwt->setPayload($payload);
    $jwt->setSignature($signature);

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    return $jwt;
  }

  /**
   * Validate the activation object.
   *
   * @param mixed $token
   *    Decoded activation object.
   *
   * @return bool
   *    Valid activation object.
   */
  private function validate($token): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $returnValue = TRUE;
    if (substr_count($token, '.') !== 2) {
      $this->logger->error('Invalid JWT token. Expected 3 period separated elements.');
      $returnValue = FALSE;
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    }
    return $returnValue;
  }

}
