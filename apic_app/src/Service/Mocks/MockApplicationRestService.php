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

namespace Drupal\apic_app\Service\Mocks;

use Drupal\apic_app\Service\ApplicationRestInterface;

class MockApplicationRestService implements ApplicationRestInterface {

  /**
   * List of mock data to use in tests - the key is the application ID, the data is in the form returned by the portal API
   *
   * @var array
   */
  protected array $appsList = [
    '12345' => [
      'orgID' => '123456',
      'id' => '12345',
      'updated_at' => '2021-02-26T12:18:58.995Z',
      'created_at' => '2021-02-26T12:18:58.995Z',
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
      'org_url' => '/consumer-orgs/1234/5678/123456',
    ],
    '23456' => [
      'orgID' => '123456',
      'id' => '23456',
      'updated_at' => '2021-02-26T12:18:58.995Z',
      'created_at' => '2021-02-26T12:18:58.995Z',
      'url' => 'https://example.com/apps/123456/23456',
      'name' => 'app23456',
      'oauthRedirectURI' => '',
      'promoteTo' => '',
      'client_id' => 'myclientid',
      'client_secret' => 'myclientsecret',
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
        ],
      ],
      'public' => TRUE,
      'enabled' => TRUE,
      'consumer_org_url' => '/consumer-orgs/1234/5678/123456',
      'org_url' => '/consumer-orgs/1234/5678/123456',
    ],
    '34567' => [
      'orgID' => '123456',
      'id' => '34567',
      'updated_at' => '2021-02-26T12:18:58.995Z',
      'created_at' => '2021-02-26T12:18:58.995Z',
      'url' => 'https://example.com/apps/123456/34567',
      'oauthRedirectURI' => '',
      'promoteTo' => '',
      'type' => 'PRODUCTION',
      'state' => 'PUBLISHED',
      'name' => 'app34567',
      'client_id' => 'myclientid',
      'client_secret' => 'myclientsecret',
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
      'org_url' => '/consumer-orgs/1234/5678/123456',
    ],
    '45678' => [
      'orgID' => '123456',
      'id' => '45678',
      'updated_at' => '2021-02-26T12:18:58.995Z',
      'created_at' => '2021-02-26T12:18:58.995Z',
      'url' => 'https://example.com/apps/123456/45678',
      'oauthRedirectURI' => '',
      'promoteTo' => '',
      'type' => 'PRODUCTION',
      'state' => 'PUBLISHED',
      'name' => 'app45678',
      'client_id' => 'myclientid',
      'client_secret' => 'myclientsecret',
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
      'org_url' => '/consumer-orgs/1234/5678/123456',
    ],
  ];

  /**
   * @inheritDoc
   */
  public function getApplicationDetails($url): ?\stdClass {
    \Drupal::logger('mock')->info('getApplicationDetails input: %var.', ['%var' => serialize($url)]);

    $data = [];
    $appList = \Drupal::state()->get('mock.appList');
    if (!isset($appList) || empty($appList)) {
      $appList = [];
    }
    foreach ($appList as $key => $app) {
      // need the cast to string here or it doesnt work
      if (\Drupal::service('ibm_apim.utils')->endsWith($url, (string) $key) === TRUE) {
        $data = $app;
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
   *
   * @return object
   * @throws \JsonException
   */
  public function postApplication($url, $requestBody): ?\stdClass {
    \Drupal::logger('mock')->info('postApplication input: %url, %var.', [
      '%url' => serialize($url),
      '%var' => serialize($requestBody),
    ]);
    $data = [];
    $requestBody = json_decode($requestBody, TRUE, 512, JSON_THROW_ON_ERROR);
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
   *
   * @return object
   */
  public function deleteApplication($url): ?\stdClass {
    \Drupal::logger('mock')->info('deleteApplication input: %url.', ['%url' => serialize($url)]);
    $data = [];
    $appList = \Drupal::state()->get('mock.appList');
    if (!isset($appList) || empty($appList)) {
      $appList = [];
    }
    $apps = [];
    foreach ($appList as $key => $app) {
      if (strpos($url, (string) $key) === FALSE) {
        $apps[$key] = $app;
      }
    }
    \Drupal::state()->set('mock.appList', $apps);

    return $this->doREST($data);
  }

  public function subscribeToPlan($appUrl = NULL, $planId = NULL): ?\stdClass {
    $result = new \stdClass();
    $result->data = ['id' => 'x',
                     'plan' => 'default:plan',
    ];
    $result->code = 201;
    return $result;
  }

  /**
   * @inheritDoc
   */
  public function promoteApplication($url, $requestBody): ?\stdClass {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   * @throws \JsonException
   */
  public function patchApplication($url, $requestBody): ?\stdClass {
    \Drupal::logger('mock')->info('putApplication input: %url, %var.', [
      '%url' => serialize($url),
      '%var' => serialize($requestBody),
    ]);
    $data = [];
    $requestBody = json_decode($requestBody, TRUE, 512, JSON_THROW_ON_ERROR);
    $appList = \Drupal::state()->get('mock.appList');
    if ($appList === NULL) {
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
   *
   * @return object
   * @throws \JsonException
   */
  public function postCredentials($url, $requestBody): ?\stdClass {
    \Drupal::logger('mock')->info('postCredentials input: %url, %var.', [
      '%url' => serialize($url),
      '%var' => serialize($requestBody),
    ]);
    $data = [];
    $requestBody = json_decode($requestBody, TRUE, 512, JSON_THROW_ON_ERROR);
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
   *
   * @return object
   */
  public function deleteCredentials($url): ?\stdClass {
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
   *
   * @return object
   */
  public function patchCredentials($url, $requestBody): ?\stdClass {
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
  public function patchSubscription($url, $requestBody): ?\stdClass {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function postClientId($url, $requestBody): ?\stdClass {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function postClientSecret($url, $requestBody): ?\stdClass {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function postSubscription($url, $requestBody): ?\stdClass {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function deleteSubscription($url): ?\stdClass {
    $data = [];

    return $this->doREST($data);
  }

  /**
   * @inheritDoc
   */
  public function postSecret($url, $requestBody): ?\stdClass {
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
  private function doREST($data, $code = 200): object {
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
   * @param $summary
   * @param $oauthUrls
   * @param null $certificate
   * @param null $formState
   *
   * @return \stdClass|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function createApplication($name, $summary, $oauthUrls, $certificate = NULL, $formState = NULL): ?\stdClass {

    $data = [
      'name' => $name,
      'summary' => $summary
    ];
    if ($oauthUrls === NULL) {
      $oauthUrls = [];
    }
    if (!\is_array($oauthUrls)) {
      $oauthUrls = [$oauthUrls];
    }

    if (!empty($oauthUrls)) {
      $data['redirect_endpoints'] = $oauthUrls;
    }

    $result = $this->postApplication('randomstring', json_encode($data, JSON_THROW_ON_ERROR));

    // Insert nid in to results so that callers don't have to do a db query to find it
    $app_data = \Drupal::service('apic_app.rest_service')->fetchFromAPIC($result->data['url']);
    $nid = \Drupal::service('apic_app.application')->create($app_data, 'create', $formState);

    $result->data['nid'] = $nid;

    return $result;

  }

  public function fetchFromAPIC(?string $appUrl = NULL) {
    $data = [];
    $appList = \Drupal::state()->get('mock.appList');
    if (!isset($appList) || empty($appList)) {
      $appList = [];
    }
    foreach ($appList as $key => $app) {
      // need the cast to string here or it doesnt work
      if (\Drupal::service('ibm_apim.utils')->endsWith($appUrl, (string) $key) === TRUE) {
        $data = $app;
      }
    }
    return $data;
  }

}
