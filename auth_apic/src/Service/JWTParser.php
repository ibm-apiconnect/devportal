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

namespace Drupal\auth_apic\Service;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Drupal\ibm_apim\Service\Utils;
use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Throwable;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\Core\State\State;

/**
 * Parse and validate activation tokens.
 */
class JWTParser implements TokenParserInterface {

  protected LoggerInterface $logger;
  protected Utils $utils;
  protected ManagementServerInterface $mgmtServer;
  protected State $state;


  public function __construct(LoggerInterface $logger,
                              Utils $utils,
                              ManagementServerInterface $mgmtServer,
                              State $state
                              ) {
    $this->logger = $logger;
    $this->utils = $utils;
    $this->mgmtServer = $mgmtServer;
    $this->state = $state;
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
    $decoded_token = $token;
    if ($this->isBase64($decoded_token)) {
      $decoded_token = base64_decode($decoded_token);
    }

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
    $MAX_UPDATES = 1;
    $tries = 0;

    if (substr_count($token, '.') !== 2) {
      $this->logger->error('Invalid JWT token. Expected 3 period separated elements.');
      $returnValue = FALSE;
    }
    $data = $this->state->get('ibm_apim.apim_keys');
    if (isset($data['keys']) && !empty($data['keys'])) {
      $keys = $data['keys'];
    } else {
      $keys = $this->mgmtServer->updateKeys();
      $tries++;
    }
    if (empty($keys)) {
      $returnValue = FALSE;
    }

    if ($returnValue) {
      while ($tries <= $MAX_UPDATES && !empty($keys)) {
        $tries++;
        $keys = ['keys' => $keys];
        $keys = JWK::parseKeySet($keys);
        try {
          JWT::decode($token, $keys);
          break;
        } catch (Throwable $e) {
          if ($tries <= $MAX_UPDATES) {
            $keys = $this->mgmtServer->updateKeys();
          } else {
            $returnValue = FALSE;
            $this->logger->error('JWT validation error: ' . $e->getMessage());
          }
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    }
    return $returnValue;
  }

  public function isBase64($token) {
    return (bool) preg_match('/^([A-Za-z0-9+\/]{4})*([A-Za-z0-9+\/]{3}=|[A-Za-z0-9+\/]{2}==)?$/', $token);
  }
}