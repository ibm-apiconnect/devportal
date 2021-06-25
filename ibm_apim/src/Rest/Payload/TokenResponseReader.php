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

namespace Drupal\ibm_apim\Rest\Payload;

use Drupal\ibm_apim\Rest\TokenResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ibm_apim\Rest\Exception\RestResponseParseException;
use Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface;

/**
 * Response to POST to /token.
 */
class TokenResponseReader extends RestResponseReader {

  /**
   * Read the HTTP response body and pull out the fields we care about.
   *
   * @param $response
   * @param null $response_object
   *
   * @return \Drupal\ibm_apim\Rest\TokenResponse|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function read($response, $response_object = NULL): ?TokenResponse {
    // Create a new, specific object for the API response.
    $token_response = new TokenResponse();

    parent::read($response, $token_response);

    // If we don't have a 200 then bail out early.
    if ($token_response->getCode() === 200) {
      $data = $token_response->getData();
      if (!isset($data['access_token'])) {
        throw new RestResponseParseException('No access_token available from GET /token with success response');
      }
      $token_response->setBearerToken($data['access_token']);
      if (!isset($data['expires_in'])) {
        throw new RestResponseParseException('No expires_in available from GET /token with success response');
      }
      $token_response->setExpiresIn(time() + (int) $data['expires_in']);
      if (isset($data['refresh_token'])) {
        $token_response->setRefreshToken($data['refresh_token']);
      }
      if (isset($data['refresh_expires_in'])) {
        $token_response->setRefreshExpiresIn(time() + (int) $data['refresh_expires_in']);
      }
    } else if ( $token_response->getCode() === 201) {
      return $token_response;
    } else {
      throw new RestResponseParseException('Unexpected response from GET /token: ' . $token_response->getCode() . '. Message: ' . serialize($token_response));
    }
    return $token_response;
  }

}
