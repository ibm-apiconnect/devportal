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

namespace Drupal\auth_apic\Rest\Payload;

use Drupal\auth_apic\Rest\TokenResponse;
use Drupal\ibm_apim\Rest\Exception\RestResponseParseException;
use Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;

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
   * //@return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|null
   * @return \Drupal\auth_apic\Rest\TokenResponse|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function read($response, $response_object = NULL): ?RestResponseInterface {

    // Create a new, specific object for the API response.
    $token_response = new TokenResponse();

    parent::read($response, $token_response);

    // If we don't have a 200 then bail out early.
    if ($token_response->getCode() !== 200) {
      throw new RestResponseParseException('Unexpected response from GET /token: ' . $token_response->getCode() . '. Message: ' . serialize($token_response));
    }

    if ($token_response->getData() === NULL) {
      throw new RestResponseParseException('No data on response from GET /token with success response');
    }
    $data = $token_response->getData();

    if (!isset($data['access_token'])) {
      throw new RestResponseParseException('No access_token available from GET /token with success response');
    }
    $token_response->setBearerToken($data['access_token']);

    if (!isset($data['expires_in'])) {
      throw new RestResponseParseException('No expires_in available from GET /token with success response');
    }
    $token_response->setExpiresIn(time() + (int)$data['expires_in']);

    return $token_response;
  }

}
