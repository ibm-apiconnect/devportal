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

namespace Drupal\apic_app\Service;

interface ApplicationRestInterface {

  /**
   * Get the details of an application from apim using
   * the url provided
   *
   * @param $url - /apps/{org}/{app_id}
   *
   * @return mixed
   */
  public function getApplicationDetails($url);

  /**
   * Create a new application in apim using
   * the url provided and the request body given
   *
   * @param $url - /orgs/{org}/apps
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function postApplication($url, $requestBody);

  /**
   * Delete an application in apim using
   * the url provided
   *
   * @param $url - /apps/{org}/{app}
   *
   * @return mixed
   */
  public function deleteApplication($url);

  /**
   * Promote an application in apim using the url provided
   * and the request body given
   *
   * @param $url - /apps/{org}/{app}
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function promoteApplication($url, $requestBody);

  /**
   * Update an existing application in apim using the
   * url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function patchApplication($url, $requestBody);

  /**
   * Create a new set of application credentials in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function postCredentials($url, $requestBody);

  /**
   * Delete a set of application credentials in apim
   * using the url provided
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}
   *
   * @return mixed
   */
  public function deleteCredentials($url);

  /**
   * Update an existing set of application credentials in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function patchCredentials($url, $requestBody);

  /**
   * Migrate an existing subscription in apim using
   * the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/subscriptions/{subscription}
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function patchSubscription($url, $requestBody);

  /**
   * Reset an applications client id & secret in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}/reset
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function postClientId($url, $requestBody);

  /**
   * Reset an applications client secret in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}/reset-client-secret
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function postClientSecret($url, $requestBody);

  /**
   * Create an application subscription in apim
   * using the url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/subscriptions
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function postSubscription($url, $requestBody);

  /**
   * Deletes a subscription to an application in apim
   * using the url provided
   *
   * @param $url - /apps/{org}/{app}/subscriptions/{subscription}
   *
   * @return mixed
   */
  public function deleteSubscription($url);

  /**
   * Verify a client secret in apim using the
   * url provided and the request body given
   *
   * @param $url - /apps/{org}/{app}/credentials/{credential}/verify-client-secret
   * @param $requestBody - json encoded request body
   *
   * @return mixed
   */
  public function postSecret($url, $requestBody);

}