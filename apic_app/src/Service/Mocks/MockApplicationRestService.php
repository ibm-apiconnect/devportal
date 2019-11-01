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

namespace Drupal\apic_app\Service\Mocks;

use Drupal\apic_app\Application;
use Drupal\apic_app\Service\ApplicationRestInterface;

class MockApplicationRestService implements ApplicationRestInterface {

  /**
   * List of mock data to use in tests - the key is the application ID, the data is in the form returned by the portal API
   *
   * @var array
   */
  protected $appsList = [
    '12345' => [
      'orgID' => '123456',
      'id' => '12345',
      'url' => 'https://example.com/apps/123456/12345',
      'name' => 'app12345xyz',
      'oauthRedirectURI' => '',
      'promoteTo' => '',
      'description' => 'some text',
      'client_id' => 'myclientid',
      'client_secret' => 'myclientsecret',
      'app_credentials' => [
        [
          'client_id' => 'myclientid',
          'client_secret' => 'myclientsecret',
          'id' => '12345-1234567890',
          'description' => '',
        ],
      ],
      'public' => TRUE,
      'enabled' => TRUE,
      'type' => 'PRODUCTION',
      'state' => 'PUBLISHED',
      'consumer_org_url' => '/consumer-orgs/1234/5678/123456',
      'org_url' => '/consumer-orgs/1234/5678/123456'
    ],
    '23456' => [
      'orgID' => '123456',
      'id' => '23456',
      'url' => 'https://example.com/apps/123456/23456',
      'name' => 'app23456',
      'oauthRedirectURI' => '',
      'promoteTo' => '',
      'type' => 'PRODUCTION',
      'state' => 'PUBLISHED',
      'description' => 'some text',
      'app_credentials' => [
        [
          'client_id' => 'myclientid',
          'client_secret' => 'myclientsecret',
          'id' => '23456-1234567890',
          'description' => 'first creds',
        ],
        [
          'client_id' => 'myclientid2',
          'client_secret' => 'myclientsecret2',
          'id' => '23456-2345678901',
          'description' => 'second creds',
        ]
      ],
      'public' => TRUE,
      'enabled' => TRUE,
      'consumer_org_url' => '/consumer-orgs/1234/5678/123456',
      'org_url' => '/consumer-orgs/1234/5678/123456'
    ],
    '34567' => [
      'orgID' => '123456',
      'id' => '34567',
      'url' => 'https://example.com/apps/123456/34567',
      'oauthRedirectURI' => '',
      'promoteTo' => '',
      'type' => 'PRODUCTION',
      'state' => 'PUBLISHED',
      'name' => 'app34567',
      'description' => 'some text',
      'app_credentials' => [
        [
          'client_id' => 'myclientid',
          'client_secret' => 'myclientsecret',
          'id' => '34567-1234567890',
          'description' => '',
        ],
      ],
      'public' => TRUE,
      'enabled' => TRUE,
      'consumer_org_url' => '/consumer-orgs/1234/5678/123456',
      'org_url' => '/consumer-orgs/1234/5678/123456'
    ],
    '45678' => [
      'orgID' => '123456',
      'id' => '45678',
      'url' => 'https://example.com/apps/123456/45678',
      'oauthRedirectURI' => '',
      'promoteTo' => '',
      'type' => 'PRODUCTION',
      'state' => 'PUBLISHED',
      'name' => 'app45678',
      'description' => 'some text',
      'app_credentials' => [
        [
          'client_id' => 'myclientid',
          'client_secret' => 'myclientsecret',
          'id' => '45678-1234567890',
          'description' => '',
        ],
      ],
      'public' => TRUE,
      'enabled' => TRUE,
      'consumer_org_url' => '/consumer-orgs/1234/5678/123456',
      'org_url' => '/consumer-orgs/1234/5678/123456'
    ],
  ];

  /**
   * @inheritDoc
   */
  public function getApplicationDetails($url) {
    \Drupal::logger('mock')->info('getApplicationDetails input: %var.', ['%var' => serialize($url)]);

    $data = [];
    $appList = \Drupal::state()->get('mock.appList');
    if (!isset($appList) || empty($appList)) {
      $appList = [];
    }
    foreach ($appList as $key => $app) {
      // need the cast to string here or it doesnt work
      if (\Drupal::service('ibm_apim.utils')->endsWith($url, (string) $key) === TRUE) {
        $data = $appList[$key];
      }
    }
    return $this->doREST($data);
  }

  /**
   * find the right application from the list based on the app name and then fake
   * a portal api response
   *
   * @param $url
   * @param $requestBody
   * @return object
   */
  public function postApplication($url, $requestBody) {
    \Drupal::logger('mock')->info('postApplication input: %url, %var.', [
      '%url' => serialize($url),
      '%var' => serialize($requestBody),
    ]);
    $data = [];
    $requestBody = json_decode($requestBody, TRUE);
    $apps = \Drupal::state()->get('mock.appList');
    if (!isset($apps) || empty($apps)) {
      $apps = [];
    }
    foreach ($this->appsList as $key => $app) {
      if ($requestBody['name'] === $app['name']) {
        $apps[$key] = $this->appsList[$key];
        $data = $this->appsList[$key];
      }
    }
    \Drupal::state()->set('mock.appList', $apps);

    return $this->doREST($data);
  }

  /**
   * Delete the app from our state version of the app list
   *
   * @param $url
   * @return object
   */
  public function deleteApplication($url) {
    \Drupal::logger('mock')->info('deleteApplication input: %url.', ['%url' => serialize($url)]);
    $data = [];
    $appList = \Drupal::state()->get('mock.appList');
    if (!isset($appList) || empty($appList)) {
      $appList = [];
    }
    $apps = [];
    foreach ($appList as $key => $app) {
      if (strpos($url, (string) $key) === FALSE) {
        $apps[$key] = $appList[$key];
      }
    }
    \Drupal::state()->set('mock.appList', $apps);

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function promoteApplication($url, $requestBody) {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function patchApplication($url, $requestBody) {
    \Drupal::logger('mock')->info('putApplication input: %url, %var.', [
      '%url' => serialize($url),
      '%var' => serialize($requestBody),
    ]);
    $data = [];
    $requestBody = json_decode($requestBody, TRUE);
    $appList = \Drupal::state()->get('mock.appList');
    if ($appList === null) {
      $appList = [];
    }
    foreach ($appList as $key => $app) {
      // need the cast to string here or it doesn't work
      if (\Drupal::service('ibm_apim.utils')->endsWith($url, (string) $key) === TRUE) {
        if (array_key_exists('name', $requestBody)) {
          $appList[$key]['name'] = $requestBody['name'];
        }
        if (array_key_exists('description', $requestBody)) {
          $appList[$key]['description'] = $requestBody['description'];
        }
        $data = $appList[$key];
      }
      \Drupal::state()->set('mock.appList', $appList);
    }

    return $this->doREST($data);
  }

  /**
   * add new credentials for the given app
   *
   * @param $url
   * @param $requestBody
   * @return object
   */
  public function postCredentials($url, $requestBody) {
    \Drupal::logger('mock')->info('postCredentials input: %url, %var.', [
      '%url' => serialize($url),
      '%var' => serialize($requestBody),
    ]);
    $data = [];
    $requestBody = json_decode($requestBody, TRUE);
    $appList = \Drupal::state()->get('mock.appList');
    if (!isset($appList) || empty($appList)) {
      $appList = $this->appsList;
    }
    foreach ($appList as $key => $app) {
      // need the cast to string here or it doesnt work
      if (strpos($url, (string) $key) !== FALSE) {
        $data = [
          'client_id' => 'newcreds',
          'client_secret' => 'blahSecretBlah',
          'id' => time(),
          'description' => $requestBody['description'],
        ];
        $appList[$key]['app_credentials'] = $data;
        \Drupal::state()->set('mock.appList', $appList);
      }
    }

    return $this->doREST($data);
  }

  /**
   * delete a set of creds
   *
   * @param $url
   * @return object
   */
  public function deleteCredentials($url) {
    \Drupal::logger('mock')->info('deleteCredentials input: %url.', ['%url' => serialize($url)]);
    $data = [];
    $appList = \Drupal::state()->get('mock.appList');
    if (!isset($appList) || empty($appList)) {
      $appList = $this->appsList;
    }
    foreach ($appList as $key => $app) {
      // need the cast to string here or it doesnt work
      if (strpos($url, (string) $key) !== FALSE) {
        $creds = [];
        foreach ($app['app_credentials'] as $cred) {
          // need the cast to string here or it doesnt work
          if (strpos($url, (string) $cred['id']) === FALSE) {
            $creds[] = $cred;
          }
        }
        $appList[$key]['app_credentials'] = $creds;
        \Drupal::state()->set('mock.appList', $appList);
      }
    }

    return $this->doREST($data);
  }

  /**
   * update description for a given credential
   *
   * @param $url
   * @param $requestBody
   * @return object
   */
  public function patchCredentials($url, $requestBody) {
    \Drupal::logger('mock')->info('putCredentials input: %url, %var.', [
      '%url' => serialize($url),
      '%var' => serialize($requestBody),
    ]);
    $data = [];
    $appList = \Drupal::state()->get('mock.appList');
    if (!isset($appList) || empty($appList)) {
      $appList = $this->appsList;
    }
    foreach ($appList as $key => $app) {
      // need the cast to string here or it doesnt work
      if (strpos($url, (string) $key) !== FALSE) {
        $creds = [];
        foreach ($app['app_credentials'] as &$cred) {
          // need the cast to string here or it doesnt work
          if (strpos($url, (string) $cred['id']) !== FALSE) {
            $cred['description'] = $requestBody['description'];
          }
        }
        unset($cred);
        \Drupal::state()->set('mock.appList', $appList);
      }
    }

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function patchSubscription($url, $requestBody) {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function postClientId($url, $requestBody) {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function postClientSecret($url, $requestBody) {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function postSubscription($url, $requestBody) {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function deleteSubscription($url) {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function postSecret($url, $requestBody) {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * Fakes a REST Response
   *
   * @param $data
   * @param int $code
   *
   * @return object
   */
  private function doREST($data, $code = 200) {
    return (object) [
      'data' => $data,
      'code' => $code,
      'headers' => [],
    ];
  }

  /**
   * Registers a new application in the management appliance
   *
   * @param $name
   * @param $description
   * @param $oauthUrl
   * @param null $formState
   *
   * @return object
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createApplication($name, $description, $oauthUrl, $formState = NULL) {

    $data = array(
      'name' => $name,
      'description' => $description,
      'oauthRedirectURI' => $oauthUrl
    );

    $result = $this->postApplication('randomstring', json_encode($data));

    // Insert nid in to results so that callers don't have to do a db query to find it
    $app_data = Application::fetchFromAPIC($result->data['url']);
    $nid = Application::create($app_data, 'create');

    $result->data['nid'] = $nid;

    return $result;

  }

}
