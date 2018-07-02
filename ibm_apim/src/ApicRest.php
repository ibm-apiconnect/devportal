<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim;

use Drupal\Component\Utility\Xss;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;
use Exception;

class ApicRest implements ApicRestInterface {

  /**
   * @inheritDoc
   */
  public static function get($url, $auth = 'user', $getting_config = FALSE, $message_errors = TRUE, $returnresult = FALSE) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return ApicRest::call_base($url, 'GET', $auth, NULL, $message_errors, $returnresult, $getting_config);
  }

  /**
   * @inheritDoc
   */
  public static function raw($url, $auth = 'user', $getting_config = FALSE, $message_errors = TRUE, $returnresult = TRUE) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return ApicRest::call_base($url, 'GET', $auth, NULL, $message_errors, $returnresult, $getting_config);
  }

  /**
   * @inheritDoc
   */
  public static function post($url, $data, $auth = 'user') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return ApicRest::call_base($url, 'POST', $auth, $data);
  }

  /**
   * @inheritDoc
   */
  public static function put($url, $data, $auth = 'user') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return ApicRest::call_base($url, 'PUT', $auth, $data);
  }

  /**
   * @inheritDoc
   */
  public static function patch($url, $data, $auth = 'user', $message_errors = TRUE, $returnresult = FALSE) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return ApicRest::call_base($url, 'PATCH', $auth, $data, $message_errors, $returnresult, FALSE);
  }

  /**
   * @inheritDoc
   */
  public static function delete($url, $auth = 'user') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return ApicRest::call_base($url, 'DELETE', $auth);
  }

  /**
   * @param $url
   * @param string $verb
   * @param null $headers
   * @param null $data
   * @param bool $return_result
   * @param null $insecure
   * @param null $provided_certificate
   * @param bool $notify_drupal
   * @return \stdClass
   * @throws \Exception
   */
  static function json_http_request($url, $verb = 'GET', $headers = NULL, $data = NULL, $return_result = FALSE, $insecure = NULL, $provided_certificate = NULL, $notify_drupal = TRUE) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array($url, $verb));
    }
    if (mb_strpos($url, 'https://') !== 0) {
      $siteconfig = \Drupal::service('ibm_apim.site_config');

      $host_pieces = $siteconfig->parseApimHost();
      if (isset($host_pieces['url'])) {
        $url = $host_pieces['url'] . $url;
      }
      else {
        if ($notify_drupal) {
          drupal_set_message(t('APIC Hostname not set. Aborting'), 'error');
        }
        return NULL;
      }
    }

    // remove any double /consumer-api calls
    if (mb_strpos($url, '/consumer-api/consumer-api') !== 0) {
      $url = str_replace('/consumer-api/consumer-api', '/consumer-api', $url);
    }

    // Use curl instead of drupal_http_request so that we can
    // check the server certificates are genuine so that we
    // do not fall foul of a man-in-the-middle attack.
    $resource = curl_init();

    curl_setopt($resource, CURLOPT_URL, $url);
    if (!is_null($headers)) {
      curl_setopt($resource, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);

    // Return the response header as part of the response
    curl_setopt($resource, CURLOPT_HEADER, 1);

    if ($verb != 'GET') {
      curl_setopt($resource, CURLOPT_CUSTOMREQUEST, $verb);
    }

    if (($verb == 'PUT' || $verb == 'POST' || $verb == 'PATCH') && isset($data)) {
      curl_setopt($resource, CURLOPT_POSTFIELDS, $data);
    }
    if ($verb == 'HEAD') {
      curl_setopt($resource, CURLOPT_NOBODY, TRUE);
      curl_setopt($resource, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    }
    if (\Drupal::hasContainer()){
    // set a custom UA string for the portal
    	$apic_version = \Drupal::state()->get('ibm_apim.version');
    	$hostname = gethostname();
    	if (!isset($hostname)) {
      		$hostname = '';
    	}
    }
    curl_setopt($resource, CURLOPT_USERAGENT, 'IBM API Connect Developer Portal/' . $apic_version['value'] . ' (' . $apic_version['description'] . ') ' . $hostname);

    // Enable auto-accept of self-signed certificates if this
    // has been set in the module config by an admin.
    ApicRest::curl_set_accept_ssl($resource, $insecure, $provided_certificate);

    if (\Drupal::hasContainer()) {
      $apim_rest_trace = \Drupal::config('ibm_apim.settings')->get('apim_rest_trace');
      if (isset($apim_rest_trace) && $apim_rest_trace == TRUE) {
        curl_setopt($resource, CURLOPT_VERBOSE, TRUE);
        \Drupal::logger('ibm_apim_rest')->debug('Payload: %data', array('%data' => serialize($data)));
      }
    }

    $response = curl_exec($resource);
    $http_status = curl_getinfo($resource, CURLINFO_HTTP_CODE);
    $error = curl_error($resource);

    // Construct the result object we expect
    $result = new \stdClass();

    // Assign the response headers
    $header_size = curl_getinfo($resource, CURLINFO_HEADER_SIZE);
    $header_txt = mb_substr($response, 0, $header_size);
    $result->headers = array();

    foreach (explode("\r\n", $header_txt) as $line) {
      $parts = explode(': ', $line);
      if (count($parts) == 2) {
        $result->headers[$parts[0]] = $parts[1];
      }
    }

    if ($error) {
      // a return code of zero mostly likely means there has been a certificate error
      // so make sure we surface this in the UI
      if ($http_status == 0) {
        if ($notify_drupal) {
          drupal_set_message(t('Could not communicate with server. Reason: ') . serialize($error), 'error');
          \Drupal::logger('ibm_apim')->error("Failed to communicate with remote server. URL was @url. Error was @error", ['@url' => $url, '@error' => $error]);
        }
        else {
          throw new Exception('Could not communicate with server. Reason: ' . $error);
        }
      }
    }

    $result->data = mb_substr($response, $header_size);

    $result->code = $http_status;

    curl_close($resource);

    if (!$return_result) {
      if ($result->data != '') {
        if (empty($headers) || !in_array('Accept: application/vnd.ibm-apim.swagger2+yaml', $headers)) {
          $result->data = ApicRest::get_json($result->data);
        }
      }
    }
    if (\Drupal::hasContainer()){
      if (isset($apim_rest_trace) && $apim_rest_trace == TRUE) {
        \Drupal::logger('ibm_apim_rest')->debug('REST Trace output: %data.', array(
          '%data' => serialize($result)
        ));
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    return $result;
  }

  /**
   * Turns a string of JSON into a PHP object.
   *
   * @param $string
   * @return mixed
   */
  private static function get_json($string) {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $decoded = json_decode($string, TRUE);
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $decoded;
  }

  /**
   * If the developer mode config parameter is true then sets options
   * on a curl resource to enable auto-accept of self-signed
   * certificates.
   * @param $resource
   * @param null $insecure
   * @param null $provided_certificate
   */
  private static function curl_set_accept_ssl($resource, $insecure = NULL, $provided_certificate = NULL) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    // Always set the defaults first
    curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, 2);

    if (is_null($insecure)) {
      $insecure = \Drupal::state()->get('ibm_apim.insecure');
    }

    // TODO force insecure to true until we've sorted out certs
    $insecure = TRUE;

    if ($insecure) {
      curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, 0);
    }
    else {
      if (is_null($provided_certificate)) {
        $provided_certificate = \Drupal::state()->get('ibm_apim.provided_certificate');
      }
      elseif ($provided_certificate == 'Default_CA') {
        $provided_certificate = NULL;
      }

      if ($provided_certificate) {
        // Tell curl to use the certificate the user provided
        curl_setopt($resource, CURLOPT_CAINFO, "/etc/apim.crt");
        if ($provided_certificate == 'mismatch') {
          // If the certificate is does not contain the correct server name
          // then tell curl to accept it anyway. The user gets a warning when
          // they provide a certificate like this so they understand this is
          // less secure than using a certificate with a matching server name.
          curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, 0);
        }
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * Where the real work to make a call to the IBM APIm API is done.
   *
   * @param $url
   *          The IBM APIm API URL
   *
   * @param $verb
   *          The HTTP verb to use, must be in the list: GET, PUT, DELETE, POST
   *
   * @param $auth
   *          The authorization string to use, the default is the current user. Other
   *          options are:
   *          clientid - which will use the catalog's client ID header
   *          admin - which will use the admin user registered in the module configuration settings
   *          NULL - use no authorization
   *          any other value - will be included in the Authorization: Basic header as is.
   *
   * @param $data
   *          A string containing the JSON data to submit to the IBM API
   *
   * @param bool $message_errors
   *          Should the function log errors?
   *
   * @param bool $return_result
   *          Normally only the result data is returned, if set to TRUE the entire
   *          result object will be returned.
   * @param bool $getting_config
   *
   * @return null|\stdClass|void
   * @throws \Exception
   */
  private static function call_base($url, $verb, $auth = 'user', $data = NULL, $message_errors = TRUE, $return_result = FALSE, $getting_config = FALSE) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array($url, $verb));
    $utils = \Drupal::service('ibm_apim.utils');
    $session_store = \Drupal::service('tempstore.private')->get('ibm_apim');
    $site_config = \Drupal::service('ibm_apim.site_config');

    $returnValue = NULL;
    if (mb_strpos($url, 'https://') !== 0) {
      $host_pieces = $site_config->parseApimHost();
      if (isset($host_pieces['url'])) {
        $url = $host_pieces['url'] . $url;
      }
      else {
        drupal_set_message(t('APIC Hostname not set. Aborting'), 'error');
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        return NULL;
      }
    }
    // remove any double /consumer-api calls
    if (mb_strpos($url, '/consumer-api/consumer-api') !== 0) {
      $url = str_replace('/consumer-api/consumer-api', '/consumer-api', $url);
    }

    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json'
    );
    $lang_name = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $lang_name = $utils->convert_lang_name($lang_name);
    if (isset($lang_name)) {
      $headers[] = 'Accept-Language: ' . $lang_name;
    }


    if ($getting_config == FALSE) {
      $headers[] = 'X-IBM-Consumer-Context: ' . $site_config->getOrgId() . '.' . $site_config->getEnvId();
    }

    if ($auth == 'user') {
      $bearer_token = $session_store->get('auth');

      if (isset($bearer_token)) {
        $headers[] = 'Authorization: Bearer ' . $bearer_token;
      }
    }
    else if($auth == 'clientid') {
      $headers[] = 'X-IBM-Client-Id: ' . $site_config->getClientId();
      $headers[] = 'X-IBM-Client-Secret: ' . $site_config->getClientSecret();
    }
    elseif ($auth != NULL) {
      $headers[] = 'Authorization: Bearer ' . $auth;
    }

    $secs = time();
    \Drupal::logger('ibm_apim')->info('call_base: START: %verb %url', array(
      '%verb' => $verb,
      '%url' => $url
    ));

    $result = ApicRest::json_http_request($url, $verb, $headers, $data, $return_result);

    $secs = time() - $secs;
    \Drupal::logger('ibm_apim')->info('call_base: %secs secs duration. END: %verb %url %code', array(
      '%secs' => $secs,
      '%verb' => $verb,
      '%url' => $url,
      '%code' => $result->code
    ));

    if ($getting_config && isset($result) && $result->code == 204) {
      $result->data = NULL;
      $returnValue = $result;
    }
    else {
      if (isset($result) && $result->code >= 200 && $result->code < 300) {
        if ($return_result != TRUE){
          $returnValue = $result;
        }
      }
      else {
        if ($message_errors) {
          if ($return_result) {
            // Need to convert to json if return_result was true as json_http_request()
            // will not have done it
            $result->data = ApicRest::get_json($result->data);
          }
          $response_reader = new RestResponseReader();
          $json_result = $response_reader->read($result);
          if($json_result !== NULL) {
            $errors = $json_result->getErrors();
            if ($errors) {
              foreach ($errors as $error) {
                drupal_set_message(Xss::filter($error), 'error');
                $returnValue = $result;
              }
            }
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($return_result) {
      return $result;
    }
    return $returnValue;
  }

  /**
   * Generic API download proxy, used for documents and wsdls
   * if node is passed in then it will save the content as the swagger doc for that api
   * @param $url
   * @param $verb
   * @param null $node
   * @param bool $filter
   * @param null $data
   * @param null $extraHeaders An array of headers to be added to the request of the form $array[] = "headerName: value";
   * @param null $mutualAuth An array with mutual authentication information (used in analytics)
   * @return null|array
   * @throws \Exception
   */
  public static function proxy($url, $verb = 'GET', $node = NULL, $filter = FALSE, $data = NULL, $extraHeaders = NULL, $mutualAuth = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array($url, $verb));

    $utils = \Drupal::service('ibm_apim.utils');
    $session_store = \Drupal::service('tempstore.private')->get('ibm_apim');
    $config = \Drupal::service('ibm_apim.site_config');

    if (empty($url)) {
      drupal_set_message(t('URL not specified. Specify a valid URL and try again.'), 'error');
      return NULL;
    }
    if (mb_strpos($url, 'https://') !== 0) {
      $siteconfig = \Drupal::service('ibm_apim.site_config');

      $host_pieces = $siteconfig->parseApimHost();
      if (isset($host_pieces['url'])) {
        $url = $host_pieces['url'] . $url;
      }
      else {
        drupal_set_message(t('APIC Hostname not set. Aborting'), 'error');
        return NULL;
      }
    }

    // remove any double /consumer-api calls
    if (mb_strpos($url, '/consumer-api/consumer-api') !== 0) {
      $url = str_replace('/consumer-api/consumer-api', '/consumer-api', $url);
    }

    $ch = curl_init($url);

    $headers = array();
    $headers[] = 'X-IBM-Consumer-Context: ' . $config->getOrgId() . '.' . $config->getEnvId();
    $lang_name = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $lang_name = $utils->convert_lang_name($lang_name);
    if (isset($lang_name)) {
      $headers[] = 'Accept-Language: ' . $lang_name;
    }

    $bearer_token = $session_store->get('auth');
    if (isset($bearer_token)) {
      $headers[] = "Authorization: Bearer " . $bearer_token;
    }

    if (isset($node)) {
      $headers[] = 'Accept: application/vnd.ibm-apim.swagger2+yaml';
      $headers[] = 'Content-Type: application/vnd.ibm-apim.swagger2+yaml';
    }
    if ($verb != 'GET') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
      $headers[] = 'Content-Type: application/json';
    }
    if ($verb == 'HEAD') {
      curl_setopt($ch, CURLOPT_NOBODY, TRUE);
      curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    }

    if (isset($extraHeaders)) {
      foreach ($extraHeaders as $key => $value) {
        $headers[] = $value;
      }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
    if (\Drupal::hasContainer()) {
      $apim_rest_trace = \Drupal::config('ibm_apim.settings')->get('apim_rest_trace');
      if (isset($apim_rest_trace) && $apim_rest_trace == TRUE) {
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        \Drupal::logger('ibm_apim_rest')->debug('Payload: %data', array('%data' => serialize($data)));
      }
    }

    if ($verb == 'PUT' || $verb == 'POST' || $verb == 'PATCH') {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    // set a custom UA string for the portal
    $apic_version = \Drupal::state()->get('ibm_apim.version');
    $hostname = gethostname();
    if (!isset($hostname)) {
      $hostname = '';
    }
    curl_setopt($ch, CURLOPT_USERAGENT, 'IBM API Connect Developer Portal/' . $apic_version['value'] . ' (' . $apic_version['description'] . ') ' . $hostname);

    // Enable auto-accept of self-signed certificates if this
    // has been set in the module config by an admin.
    ApicRest::curl_set_accept_ssl($ch);

    if (isset($mutualAuth) && !empty($mutualAuth)) {
      if (isset($mutualAuth['certFile'])) {
        $tempCertFile = tmpfile();
        fwrite($tempCertFile, $mutualAuth['certFile']);
        $tempCertPath = stream_get_meta_data($tempCertFile);
        $tempCertPath = $tempCertPath['uri'];
        curl_setopt($ch, CURLOPT_SSLCERT, $tempCertPath);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
      }
      if (isset($mutualAuth['keyFile'])) {
        $tempKeyFile = tmpfile();
        fwrite($tempKeyFile, $mutualAuth['keyFile']);
        $tempKeyPath = stream_get_meta_data($tempKeyFile);
        $tempKeyPath = $tempKeyPath['uri'];
        curl_setopt($ch, CURLOPT_SSLKEY, $tempKeyPath);
      }
    }

    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = mb_substr($response, 0, $header_size);
    $contents = mb_substr($response, $header_size);
    $status = curl_getinfo($ch);
    curl_close($ch);

    // preserve http response code from API call
    if (isset($status['http_code']) && !empty($status['http_code']) && is_int($status['http_code'])) {
      http_response_code($status['http_code']);
    }
    if (!isset($status['http_code'])) {
      $status['http_code'] = 200;
    }
    $response_headers = array();
    // Split header text into an array.
    $header_text = preg_split('/[\r\n]+/', $header);

    // Propagate headers to response.
    foreach ($header_text as $header) {
      if (preg_match('/^(?:kbn-version|Location|Content-Type|Content-Language|Set-Cookie|X-APIM):/i', $header)) {
        $response_headers[] = $header;
      }
    }
    // for YAML download force the filename, otherwise will default to version number
    if (isset($node)) {
      $response_headers[] = 'Content-Disposition: attachment; filename="apidownload.yaml"';
    }
    else {
      // use original filename if set
      foreach ($header_text as $header) {
        if (preg_match('/^(?:Content-Disposition):/i', $header)) {
          $response_headers[] = $header;
        }
      }
    }

    if (isset($node) && $node != 'dummy' && isset($contents)) {
      $data = $contents;
      if (!isset($node->api_resources->value) || $node->api_resources->value != $data) {
        $node->set('api_resources', $data);
        $node->save();
      }
    }
    $header_array = array();
    foreach ($response_headers as $response_header) {
      $parts = explode(':', $response_header);
      $header_array[$parts[0]] = $parts[1];
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($filter == TRUE) {
      return array('headers' => $header_array, 'content' => $contents, 'statusCode' => $status['http_code']);
    }
    else {
      print $contents;
      return NULL;
    }
  }
}
