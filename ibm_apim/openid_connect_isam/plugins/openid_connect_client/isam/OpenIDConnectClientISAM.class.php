<?php

/**
 * @file
 * ISAM OpenID Connect client.
 *
 * Used to perform SSO from an ISAM OP
 *
 * @author lmf
 */

class OpenIDConnectClientISAM extends OpenIDConnectClientBase {


	private function handle_rsp($curlHandle) {
		$response = curl_exec($curlHandle);

		// Throw exceptions on cURL errors.
		if (curl_errno($curlHandle) > 0) {
			syslog(LOG_ERR, 'ISAM OIDC: curl error ' . curl_errno($curlHandle) . ', ' . curl_strerror(curl_errno($curlHandle)));
			$err = curl_errno($curlHandle);
			curl_close($curlHandle);
			return [ 'error' => $err];
		}

		$parts = explode("\r\n\r\n", $response);
		$responseBody = array_pop($parts);


		$responseArr = [
			'code' => curl_getinfo($curlHandle, CURLINFO_HTTP_CODE),
			'data' => $responseBody
		];

		curl_close($curlHandle);
		return $responseArr;
	}

	private function userinfo_request($endpoint, $access_token) {
    $certFile = drupal_realpath('private://oidc-isam/cacert.pem');

		$options = [
			CURLOPT_VERBOSE => false,
			CURLOPT_CAINFO => $certFile, // The signer of the isam endpoints
			CURLOPT_CONNECTTIMEOUT => 15, 
			CURLOPT_HEADER => true,
			CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access_token],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => 0, // Must be set to 2 for production use. 0 if using a self signed cert
			CURLOPT_SSL_VERIFYPEER => false, 
			CURLOPT_TIMEOUT => 15,
			CURLOPT_URL => $endpoint,
		];

		$curlHandle = curl_init();
		curl_setopt_array($curlHandle, $options);
		return $this->handle_rsp($curlHandle);

	}

	private function token_request($endpoint, $data) {

		$postStr = drupal_http_build_query($data);
		$certFile = drupal_realpath('private://oidc-isam/cacert.pem');

		$options = [
			CURLOPT_VERBOSE => false,
			CURLOPT_CAINFO => $certFile,
			CURLOPT_CONNECTTIMEOUT => 15, 
			CURLOPT_HEADER => true,
			CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => 0, // Must be set to 2 for production use
			CURLOPT_SSL_VERIFYPEER => false, 
			CURLOPT_TIMEOUT => 15,
			CURLOPT_URL => $endpoint,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $postStr
		];

		$curlHandle = curl_init();
		curl_setopt_array($curlHandle, $options);
		return $this->handle_rsp($curlHandle);
	}


	/**
	 * {@inheritdoc}
	 */
	public function settingsForm() {
		$form = parent::settingsForm();

		//$default_site = 'https://isam.ibm.com/mga/sps/oauth/oauth20';
    $default_site = 'https://my.isam.endpoint/oauth/oauth20';

		$form['authorization_endpoint'] = array(
			'#title' => t('Authorization endpoint'),
			'#type' => 'textfield',
			'#default_value' => $this->getSetting('authorization_endpoint', $default_site . '/authorize'),
		);
		$form['token_endpoint'] = array(
			'#title' => t('Token endpoint'),
			'#type' => 'textfield',
			'#default_value' => $this->getSetting('token_endpoint', $default_site . '/token'),
		);
		$form['userinfo_endpoint'] = array(
			'#title' => t('UserInfo endpoint'),
			'#type' => 'textfield',
			'#default_value' => $this->getSetting('userinfo_endpoint', $default_site . '/userinfo'),
		);

    $form['ca_certificate'] = array(
      '#title' => t('CA Signer Certificate for the Endpoint Server (PEM file content)'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('ca_certificate'),
    );

		return $form;
	}

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($form, &$form_state) {
    parent::settingsFormSubmit($form, $form_state);
    // save ca cert data to file
    $data = $form_state['values']['ca_certificate'];
    $certFileDirectory = 'private://oidc-isam';
    file_prepare_directory($certFileDirectory, FILE_CREATE_DIRECTORY);
    $file = file_save_data($data, $certFileDirectory . '/cacert.pem', FILE_EXISTS_REPLACE);
  }

	/**
	 * {@inheritdoc}
	 */
	public function getEndpoints() {
		return array(
			'authorization' => $this->getSetting('authorization_endpoint'),
			'token' => $this->getSetting('token_endpoint'),
			'userinfo' => $this->getSetting('userinfo_endpoint'),
		);
	}

	/**
	 * Override the retrieveToken method so trust can be controlled
	 *
	 */
	public function retrieveTokens($authorization_code) {
		// Exchange `code` for access token and ID token.
		$redirect_uri = OPENID_CONNECT_REDIRECT_PATH_BASE . '/' . $this->name;
		$post_data = array(
			'code' => $authorization_code,
			'client_id' => $this->getSetting('client_id'),
			'client_secret' => $this->getSetting('client_secret'),
			'redirect_uri' => url($redirect_uri, array('absolute' => TRUE)),
			'grant_type' => 'authorization_code',
		);
		$endpoints = $this->getEndpoints();
		$response = $this->token_request($endpoints['token'], $post_data);
		if (!isset($response['error']) && $response['code'] == 200) {
			$response_data = drupal_json_decode($response['data']);
			$tokens = array(
				'id_token' => $response_data['id_token'],
				'access_token' => $response_data['access_token'],
			);
			if (array_key_exists('expires_in', $response_data)) {
				$tokens['expire'] = REQUEST_TIME + $response_data['expires_in'];
			}
			if (array_key_exists('refresh_token', $response_data)) {
				$tokens['refresh_token'] = $response_data['refresh_token'];
			}
			return $tokens;
		}
		else {
			openid_connect_log_request_error(__FUNCTION__, $this->name, implode("|",$response));
			return FALSE;
		}
	}

	public function retrieveUserInfo($access_token) {
		$endpoints = $this->getEndpoints();
		$response = $this->userinfo_request($endpoints['userinfo'], $access_token);
		if (!isset($response['error']) && $response['code'] == 200) {
			return drupal_json_decode($response['data']);
		} else {
			openid_connect_log_request_error(__FUNCTION__, $this->name, implode("|",$response));
		}
		return array();
	}

}
