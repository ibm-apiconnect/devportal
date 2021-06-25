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

namespace Drupal\apic_app\Service;

interface ApplicationRestInterface {

  /**
   * Get the details of an application from apim using
   * the url provided
   *
   * @param $url - /apps/{org}/{app_id}
   *
   * @return \stdClass|NULL
   */
  public function getApplicationDetails($url): ?\stdClass;

  /**
   * Create a new application in apim using
   * the url provided and the request body given
   *
   * @param $url - /orgs/{org}/apps
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function postApplication($url, $requestBody): ?\stdClass;

  /**
   * Delete an application in apim using
   * the url provided
   *
   * @param $url - /apps/{org}/{app}
   *
   * @return \stdClass|NULL
   */
  public function deleteApplication($url): ?\stdClass;

  /**
   * @param $name
   * @param $summary
   * @param $oauthUrls
   * @param null $certificate
   * @param null $formState
   *
   * @return \stdClass|null
   */
  public function createApplication($name, $summary, $oauthUrls, $certificate = NULL, $formState = NULL): ?\stdClass;

  /**
   * Promote an application in apim using the url provided
   * and the request body given
   *
   * @param $url - /apps/{org}/{app}
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function promoteApplication($url, $requestBody): ?\stdClass;

  /**
   * Update an existing application in apim using the
   * url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function patchApplication($url, $requestBody): ?\stdClass;

  /**
   * Create a new set of application credentials in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function postCredentials($url, $requestBody): ?\stdClass;

  /**
   * Delete a set of application credentials in apim
   * using the url provided
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}
   *
   * @return \stdClass|NULL
   */
  public function deleteCredentials($url): ?\stdClass;

  /**
   * Update an existing set of application credentials in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function patchCredentials($url, $requestBody): ?\stdClass;

  /**
   * Migrate an existing subscription in apim using
   * the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/subscriptions/{subscription}
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function patchSubscription($url, $requestBody): ?\stdClass;

  /**
   * Reset an applications client id & secret in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}/reset
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function postClientId($url, $requestBody): ?\stdClass;

  /**
   * Reset an applications client secret in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}/reset-client-secret
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function postClientSecret($url, $requestBody): ?\stdClass;

  /**
   * Create an application subscription in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/subscriptions
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function postSubscription($url, $requestBody): ?\stdClass;

  /**
   * Deletes a subscription to an application in apim
   * using the url provided
   *
   * @param $url - /apps/{org}/{app}/subscriptions/{subscription}
   *
   * @return \stdClass|NULL
   */
  public function deleteSubscription($url): ?\stdClass;

  /**
   * Verify a client secret in apim using the
   * url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}/verify-client-secret
   * @param $requestBody - json encoded request body
   *
   * @return \stdClass|NULL
   */
  public function postSecret($url, $requestBody): ?\stdClass;

}