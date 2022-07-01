<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\ibm_apim\JsonStreamingParser\CollectionListener;
use Drupal\Tests\UnitTestCase;
use JsonStreamingParser\Parser;
use Throwable;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\ApicUserService
 *
 * @group ibm_apim
 */
class CollectionListenerTest extends UnitTestCase {

  /**
   * @throws \Exception
   */
  public function testMockSnapshot(): void {
    $filePath = __DIR__ . '/data/snapshot.json';
    $UUID = '';
    $listener = new CollectionListener([$this, 'processContent'], $UUID);
    $stream = fopen($filePath, 'rb');
    try {
      $parser = new Parser($stream, $listener);
      $parser->parse();
      fclose($stream);
    } catch (Throwable $e) {
      fclose($stream);
      throw $e;
    }

  }

  /**
   * @param $content
   * @param $UUID
   *
   * @return mixed
   */
  public function processContent($content, $UUID) {
    $expectedJson = [
      'configured_catalog_user_registries' => [
        [
          'type' => 'configured_catalog_user_registry',
          'api_version' => '2.0.0',
          'id' => '3e57a787-db9a-4b3b-89b2-65bb2cd2f50d',
          'name' => 'sandbox-catalog',
          'title' => 'Sandbox Catalog User Registry',
          'summary' => 'Sandbox Catalog User Registry',
          'shadow_id' => '66777471-b1e2-11e8-8e7d-cde849e5e887',
          'shadow' => FALSE,
          'original_id' => 'dc6ef649-1224-4ef4-9e6c-22259642cf32',
          'owned' => TRUE,
          'integration_url' => '/api/cloud/integrations/user-registry/d313d4e2-39c6-4b1b-8b6c-15d9d5334cc2',
          'registry_type' => 'lur',
          'user_managed' => TRUE,
          'user_registry_managed' => TRUE,
          'onboarding' => 'active',
          'case_sensitive' => TRUE,
          'identity_providers' => [
            [
              'name' => 'sandbox-idp',
              'title' => 'Sandbox Identity Provider',
            ],
          ],
          'metadata' => [
            'id' => '33803e54-e161-40ff-988f-c51f681f7cef:e4afff0b-dbad-4e2b-b44e-db4b0a857937',
            'name' => 'sandbox-catalog',
          ],
          'created_at' => '2018-09-06T14:37:35.669Z',
          'updated_at' => '2018-09-06T14:37:35.669Z',
          'org_url' => '/consumer-api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
          'catalog_url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002',
          'user_registry_url' => '/consumer-api/user-registries/3e57a787-db9a-4b3b-89b2-65bb2cd2f50d',
          'url' => '/consumer-api/user-registries/3e57a787-db9a-4b3b-89b2-65bb2cd2f50d',
        ],
      ],
      'catalogs' => [
        [
          'type' => 'catalog',
          'api_version' => '2.0.0',
          'id' => 'aff3eada-4a04-450a-bcc7-f22518bad002',
          'name' => 'test',
          'title' => 'Test',
          'summary' => 'Test Catalog',
          'shadow_id' => '6328bea0-b1e2-11e8-ae4a-e99be9ee7c0d',
          'shadow' => FALSE,
          'owner_url' => '/api/user-registries/d44611a3-48e9-40ac-a9ea-2b731193bed3/7893665d-15a2-4d44-a8c0-76bcea979b76/users/5d503356-a74f-4df4-98c4-659e8f9e753b',
          'app_urls' => NULL,
          'metadata' => NULL,
          'created_at' => '2018-09-06T14:37:31.551Z',
          'updated_at' => '2018-09-06T14:37:31.551Z',
          'org_url' => '/api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
          'url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002',
        ],
      ],
      'orgs' => [
        [
          'type' => 'org',
          'api_version' => '2.0.0',
          'id' => '33803e54-e161-40ff-988f-c51f681f7cef',
          'name' => 'test',
          'title' => 'test',
          'summary' => NULL,
          'state' => 'enabled',
          'state_pending' => NULL,
          'shadow_id' => '60dc3692-b1e2-11e8-8c10-5ee295bc3094',
          'shadow' => FALSE,
          'org_type' => 'provider',
          'owner_url' => '/api/user-registries/d44611a3-48e9-40ac-a9ea-2b731193bed3/7893665d-15a2-4d44-a8c0-76bcea979b76/users/5d503356-a74f-4df4-98c4-659e8f9e753b',
          'metadata' => NULL,
          'created_at' => '2018-09-06T14:37:27.877Z',
          'updated_at' => '2018-09-06T14:37:27.877Z',
          'url' => '/api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
        ],
      ],
      'permissions' => [
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '41cca046-e825-47b0-b80b-3d2a39bcc8c3',
          'name' => 'member:manage',
          'title' => 'member:manage',
          'shadow_id' => 'db0cfc60-b025-11e8-b63d-672d75d43b0a',
          'shadow' => FALSE,
          'permission_type' => 'org',
          'created_at' => '2018-09-04T09:35:27.004Z',
          'updated_at' => '2018-09-04T09:35:27.004Z',
          'url' => '/consumer-api/consumer/permissions/org/41cca046-e825-47b0-b80b-3d2a39bcc8c3',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '2d60261d-7986-4443-9256-1e35fc1eaaec',
          'name' => 'member:view',
          'title' => 'member:view',
          'shadow_id' => 'db08ddb0-b025-11e8-8a47-591b5ebce0a9',
          'shadow' => FALSE,
          'permission_type' => 'org',
          'created_at' => '2018-09-04T09:35:26.976Z',
          'updated_at' => '2018-09-04T09:35:26.976Z',
          'url' => '/consumer-api/consumer/permissions/org/2d60261d-7986-4443-9256-1e35fc1eaaec',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '3b79bfb4-3da0-4b37-8f65-d5daa81acc3a',
          'name' => 'settings:manage',
          'title' => 'settings:manage',
          'shadow_id' => 'db050d20-b025-11e8-9924-91a494316dfe',
          'shadow' => FALSE,
          'permission_type' => 'org',
          'created_at' => '2018-09-04T09:35:26.953Z',
          'updated_at' => '2018-09-04T09:35:26.953Z',
          'url' => '/consumer-api/consumer/permissions/org/3b79bfb4-3da0-4b37-8f65-d5daa81acc3a',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '0efcfcd9-6ccc-47fb-986f-adff8e42c006',
          'name' => 'settings:view',
          'title' => 'settings:view',
          'shadow_id' => 'db01d8d0-b025-11e8-91f8-c52f473c4e35',
          'shadow' => FALSE,
          'permission_type' => 'org',
          'created_at' => '2018-09-04T09:35:26.931Z',
          'updated_at' => '2018-09-04T09:35:26.931Z',
          'url' => '/consumer-api/consumer/permissions/org/0efcfcd9-6ccc-47fb-986f-adff8e42c006',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '3ae30a11-3734-4b33-85af-0d4232ff22de',
          'name' => 'topology:manage',
          'title' => 'topology:manage',
          'shadow_id' => 'db1512b0-b025-11e8-b22c-c5eb31a96ca0',
          'shadow' => FALSE,
          'permission_type' => 'org',
          'created_at' => '2018-09-04T09:35:27.054Z',
          'updated_at' => '2018-09-04T09:35:27.054Z',
          'url' => '/consumer-api/consumer/permissions/org/3ae30a11-3734-4b33-85af-0d4232ff22de',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => 'c24ba03f-30cb-4b6b-a1e7-e0910e1fd8d7',
          'name' => 'topology:view',
          'title' => 'topology:view',
          'shadow_id' => 'db10f400-b025-11e8-b990-36421135ae49',
          'shadow' => FALSE,
          'permission_type' => 'org',
          'created_at' => '2018-09-04T09:35:27.027Z',
          'updated_at' => '2018-09-04T09:35:27.027Z',
          'url' => '/consumer-api/consumer/permissions/org/c24ba03f-30cb-4b6b-a1e7-e0910e1fd8d7',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => 'f417e2f1-a71c-4312-a531-493318b7c8e0',
          'name' => 'view',
          'title' => 'view',
          'shadow_id' => 'db197f80-b025-11e8-a006-cc5677b062f1',
          'shadow' => FALSE,
          'permission_type' => 'org',
          'created_at' => '2018-09-04T09:35:27.085Z',
          'updated_at' => '2018-09-04T09:35:27.085Z',
          'url' => '/consumer-api/consumer/permissions/org/f417e2f1-a71c-4312-a531-493318b7c8e0',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '6e5db5be-0ac4-4426-bef3-c7bb4db73a4b',
          'name' => 'app-analytics:view',
          'title' => 'app-analytics:view',
          'shadow_id' => 'db982560-b025-11e8-adf5-cdcb1e41a227',
          'shadow' => FALSE,
          'permission_type' => 'consumer',
          'created_at' => '2018-09-04T09:35:27.916Z',
          'updated_at' => '2018-09-04T09:35:27.916Z',
          'url' => '/consumer-api/consumer/permissions/consumer/6e5db5be-0ac4-4426-bef3-c7bb4db73a4b',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '324cc1ca-d50a-44fa-90cf-5c9287e3afaf',
          'name' => 'app-dev:manage',
          'title' => 'app-dev:manage',
          'shadow_id' => 'db8b2d10-b025-11e8-a107-888c0a967d44',
          'shadow' => FALSE,
          'permission_type' => 'consumer',
          'created_at' => '2018-09-04T09:35:27.831Z',
          'updated_at' => '2018-09-04T09:35:27.831Z',
          'url' => '/consumer-api/consumer/permissions/consumer/324cc1ca-d50a-44fa-90cf-5c9287e3afaf',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '901f9467-3f99-4e30-af69-bf0e83420a2f',
          'name' => 'app:manage',
          'title' => 'app:manage',
          'shadow_id' => 'db8e6160-b025-11e8-95c6-6cbc4fdea07a',
          'shadow' => FALSE,
          'permission_type' => 'consumer',
          'created_at' => '2018-09-04T09:35:27.851Z',
          'updated_at' => '2018-09-04T09:35:27.851Z',
          'url' => '/consumer-api/consumer/permissions/consumer/901f9467-3f99-4e30-af69-bf0e83420a2f',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '2aded18d-cada-4ed8-835a-444cdd1a96c4',
          'name' => 'app:view',
          'title' => 'app:view',
          'shadow_id' => 'db87d1b0-b025-11e8-8758-e15edac5c4d2',
          'shadow' => FALSE,
          'permission_type' => 'consumer',
          'created_at' => '2018-09-04T09:35:27.809Z',
          'updated_at' => '2018-09-04T09:35:27.809Z',
          'url' => '/consumer-api/consumer/permissions/consumer/2aded18d-cada-4ed8-835a-444cdd1a96c4',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => 'a1d3b340-547e-48a9-af64-653b10f93e22',
          'name' => 'product:view',
          'title' => 'product:view',
          'shadow_id' => 'db84eb80-b025-11e8-8fb4-9810983eb26e',
          'shadow' => FALSE,
          'permission_type' => 'consumer',
          'created_at' => '2018-09-04T09:35:27.791Z',
          'updated_at' => '2018-09-04T09:35:27.791Z',
          'url' => '/consumer-api/consumer/permissions/consumer/a1d3b340-547e-48a9-af64-653b10f93e22',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => 'ec2889d7-bea2-4675-9b5f-3c5f29608fa7',
          'name' => 'subscription:manage',
          'title' => 'subscription:manage',
          'shadow_id' => 'db953f30-b025-11e8-af09-df4f093ae249',
          'shadow' => FALSE,
          'permission_type' => 'consumer',
          'created_at' => '2018-09-04T09:35:27.897Z',
          'updated_at' => '2018-09-04T09:35:27.897Z',
          'url' => '/consumer-api/consumer/permissions/consumer/ec2889d7-bea2-4675-9b5f-3c5f29608fa7',
        ],
        [
          'type' => 'permission',
          'api_version' => '2.0.0',
          'id' => '4afc7137-9100-4b06-b707-8370e51a1d58',
          'name' => 'subscription:view',
          'title' => 'subscription:view',
          'shadow_id' => 'db9231f0-b025-11e8-b8de-00d1896d80fd',
          'shadow' => FALSE,
          'permission_type' => 'consumer',
          'created_at' => '2018-09-04T09:35:27.873Z',
          'updated_at' => '2018-09-04T09:35:27.873Z',
          'url' => '/consumer-api/consumer/permissions/consumer/4afc7137-9100-4b06-b707-8370e51a1d58',
        ],
      ],
      'consumer_orgs' => [
        [
          'type' => 'consumer_org',
          'id' => 'a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
          'setting' => [
            'type' => 'consumer_org_setting',
            'api_version' => '2.0.0',
            'name' => 'consumer-org-setting',
            'title' => NULL,
            'summary' => NULL,
            'shadow_id' => '68a88540-b1e2-11e8-8e63-75fe456556eb',
            'shadow' => FALSE,
            'metadata' => NULL,
            'created_at' => '2018-09-06T14:37:40.683Z',
            'updated_at' => '2018-09-06T14:37:40.683Z',
            'url' => '/api/consumer-orgs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/settings',
          ],
          'consumer_org' => [
            'name' => 'sandbox-test-org',
            'title' => 'Sandbox Test Organization',
            'summary' => 'Sandbox Test Organization Summary',
            'owner_url' => '/consumer-api/user-registries/3e57a787-db9a-4b3b-89b2-65bb2cd2f50d/users/9db5a27c-1b81-4e55-ba09-72a0709b77a1',
            'type' => 'consumer_org',
            'api_version' => '2.0.0',
            'id' => 'a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
            'state' => 'enabled',
            'created_at' => '2018-09-06T14:37:37.184Z',
            'updated_at' => '2018-09-06T14:37:37.184Z',
            'url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
            'org_url' => '/consumer-api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
            'catalog_url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002',
            'state_pending' => NULL,
            'shadow_id' => '6652ae60-b1e2-11e8-9d82-36626b9fbe07',
          ],
          'roles' => [
            [
              'type' => 'role',
              'api_version' => '2.0.0',
              'id' => 'ddf7d6a0-d63d-4de7-b733-d220596fb905',
              'name' => 'administrator',
              'title' => 'Administrator',
              'summary' => 'Administers the app developer organization',
              'permission_urls' => [
                '/consumer-api/consumer/permissions/org/2d60261d-7986-4443-9256-1e35fc1eaaec',
                '/consumer-api/consumer/permissions/org/41cca046-e825-47b0-b80b-3d2a39bcc8c3',
                '/consumer-api/consumer/permissions/org/0efcfcd9-6ccc-47fb-986f-adff8e42c006',
                '/consumer-api/consumer/permissions/org/3b79bfb4-3da0-4b37-8f65-d5daa81acc3a',
                '/consumer-api/consumer/permissions/org/f417e2f1-a71c-4312-a531-493318b7c8e0',
                '/consumer-api/consumer/permissions/consumer/a1d3b340-547e-48a9-af64-653b10f93e22',
                '/consumer-api/consumer/permissions/consumer/2aded18d-cada-4ed8-835a-444cdd1a96c4',
                '/consumer-api/consumer/permissions/consumer/324cc1ca-d50a-44fa-90cf-5c9287e3afaf',
                '/consumer-api/consumer/permissions/consumer/901f9467-3f99-4e30-af69-bf0e83420a2f',
                '/consumer-api/consumer/permissions/consumer/4afc7137-9100-4b06-b707-8370e51a1d58',
                '/consumer-api/consumer/permissions/consumer/ec2889d7-bea2-4675-9b5f-3c5f29608fa7',
                '/consumer-api/consumer/permissions/consumer/6e5db5be-0ac4-4426-bef3-c7bb4db73a4b',
              ],
              'shadow_id' => '6677c290-b1e2-11e8-bd65-2fba6d5d7eac',
              'shadow' => FALSE,
              'created_at' => '2018-09-06T14:37:37.358Z',
              'updated_at' => '2018-09-06T14:37:37.358Z',
              'org_url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
              'url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/roles/ddf7d6a0-d63d-4de7-b733-d220596fb905',
            ],
            [
              'type' => 'role',
              'api_version' => '2.0.0',
              'id' => 'c38953f5-bbbb-4c70-a2b3-85ca407dabe3',
              'name' => 'developer',
              'title' => 'Developer',
              'summary' => 'Builds and manages apps in the developer organization',
              'permission_urls' => [
                '/consumer-api/consumer/permissions/org/2d60261d-7986-4443-9256-1e35fc1eaaec',
                '/consumer-api/consumer/permissions/org/0efcfcd9-6ccc-47fb-986f-adff8e42c006',
                '/consumer-api/consumer/permissions/org/f417e2f1-a71c-4312-a531-493318b7c8e0',
                '/consumer-api/consumer/permissions/consumer/a1d3b340-547e-48a9-af64-653b10f93e22',
                '/consumer-api/consumer/permissions/consumer/2aded18d-cada-4ed8-835a-444cdd1a96c4',
                '/consumer-api/consumer/permissions/consumer/324cc1ca-d50a-44fa-90cf-5c9287e3afaf',
                '/consumer-api/consumer/permissions/consumer/901f9467-3f99-4e30-af69-bf0e83420a2f',
                '/consumer-api/consumer/permissions/consumer/4afc7137-9100-4b06-b707-8370e51a1d58',
                '/consumer-api/consumer/permissions/consumer/ec2889d7-bea2-4675-9b5f-3c5f29608fa7',
                '/consumer-api/consumer/permissions/consumer/6e5db5be-0ac4-4426-bef3-c7bb4db73a4b',
              ],
              'shadow_id' => '6712a300-b1e2-11e8-8537-8e5acbc25c76',
              'shadow' => FALSE,
              'created_at' => '2018-09-06T14:37:38.433Z',
              'updated_at' => '2018-09-06T14:37:38.433Z',
              'org_url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
              'url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/roles/c38953f5-bbbb-4c70-a2b3-85ca407dabe3',
            ],
            [
              'type' => 'role',
              'api_version' => '2.0.0',
              'id' => 'a52dad46-bb16-45e6-b559-abff49aae679',
              'name' => 'member',
              'title' => 'Member',
              'summary' => 'Minumum role',
              'permission_urls' => [
                '/consumer-api/consumer/permissions/org/f417e2f1-a71c-4312-a531-493318b7c8e0',
              ],
              'shadow_id' => '675d4090-b1e2-11e8-ae54-61761e30c9bf',
              'shadow' => FALSE,
              'created_at' => '2018-09-06T14:37:38.917Z',
              'updated_at' => '2018-09-06T14:37:38.917Z',
              'org_url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
              'url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/roles/a52dad46-bb16-45e6-b559-abff49aae679',
            ],
            [
              'type' => 'role',
              'api_version' => '2.0.0',
              'id' => 'aeb976ed-d960-4629-bd27-5d19ccdf6bd4',
              'name' => 'owner',
              'title' => 'Owner',
              'summary' => 'Owns and administers the app developer organization',
              'permission_urls' => [
                '/consumer-api/consumer/permissions/org/2d60261d-7986-4443-9256-1e35fc1eaaec',
                '/consumer-api/consumer/permissions/org/41cca046-e825-47b0-b80b-3d2a39bcc8c3',
                '/consumer-api/consumer/permissions/org/0efcfcd9-6ccc-47fb-986f-adff8e42c006',
                '/consumer-api/consumer/permissions/org/3b79bfb4-3da0-4b37-8f65-d5daa81acc3a',
                '/consumer-api/consumer/permissions/org/f417e2f1-a71c-4312-a531-493318b7c8e0',
                '/consumer-api/consumer/permissions/consumer/a1d3b340-547e-48a9-af64-653b10f93e22',
                '/consumer-api/consumer/permissions/consumer/2aded18d-cada-4ed8-835a-444cdd1a96c4',
                '/consumer-api/consumer/permissions/consumer/324cc1ca-d50a-44fa-90cf-5c9287e3afaf',
                '/consumer-api/consumer/permissions/consumer/901f9467-3f99-4e30-af69-bf0e83420a2f',
                '/consumer-api/consumer/permissions/consumer/4afc7137-9100-4b06-b707-8370e51a1d58',
                '/consumer-api/consumer/permissions/consumer/ec2889d7-bea2-4675-9b5f-3c5f29608fa7',
                '/consumer-api/consumer/permissions/consumer/6e5db5be-0ac4-4426-bef3-c7bb4db73a4b',
              ],
              'shadow_id' => '67bf84d0-b1e2-11e8-8261-d9139a2143a4',
              'shadow' => FALSE,
              'created_at' => '2018-09-06T14:37:39.521Z',
              'updated_at' => '2018-09-06T14:37:39.521Z',
              'org_url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
              'url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/roles/aeb976ed-d960-4629-bd27-5d19ccdf6bd4',
            ],
            [
              'type' => 'role',
              'api_version' => '2.0.0',
              'id' => 'b7f162ba-2b30-4b79-be13-2b4298690fae',
              'name' => 'viewer',
              'title' => 'Viewer',
              'summary' => 'Views the app developer organization',
              'permission_urls' => [
                '/consumer-api/consumer/permissions/org/2d60261d-7986-4443-9256-1e35fc1eaaec',
                '/consumer-api/consumer/permissions/org/0efcfcd9-6ccc-47fb-986f-adff8e42c006',
                '/consumer-api/consumer/permissions/org/f417e2f1-a71c-4312-a531-493318b7c8e0',
                '/consumer-api/consumer/permissions/consumer/a1d3b340-547e-48a9-af64-653b10f93e22',
                '/consumer-api/consumer/permissions/consumer/2aded18d-cada-4ed8-835a-444cdd1a96c4',
                '/consumer-api/consumer/permissions/consumer/4afc7137-9100-4b06-b707-8370e51a1d58',
                '/consumer-api/consumer/permissions/consumer/6e5db5be-0ac4-4426-bef3-c7bb4db73a4b',
              ],
              'shadow_id' => '68226550-b1e2-11e8-8541-75feeca275f9',
              'shadow' => FALSE,
              'created_at' => '2018-09-06T14:37:40.211Z',
              'updated_at' => '2018-09-06T14:37:40.211Z',
              'org_url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
              'url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/roles/b7f162ba-2b30-4b79-be13-2b4298690fae',
            ],
          ],
          'members' => [
            [
              'type' => 'member',
              'api_version' => '2.0.0',
              'id' => 'f7b301bb-e338-4b51-b409-477e81aa2d2a',
              'name' => 'test-user',
              'title' => 'Test User',
              'state' => 'enabled',
              'shadow_id' => '68a88544-b1e2-11e8-a638-186c2afcd229',
              'shadow' => FALSE,
              'user' => [
                'type' => 'user',
                'api_version' => '2.0.0',
                'id' => '9db5a27c-1b81-4e55-ba09-72a0709b77a1',
                'shadow' => FALSE,
                'shadow_id' => '66779b87-b1e2-11e8-aeea-09cbd1caaa7a',
                'name' => '9db5a27c-1b81-4e55-ba09-72a0709b77a1',
                'title' => '9db5a27c-1b81-4e55-ba09-72a0709b77a1',
                'url' => '/consumer-api/user-registries/3e57a787-db9a-4b3b-89b2-65bb2cd2f50d/users/9db5a27c-1b81-4e55-ba09-72a0709b77a1',
                'created_at' => '2018-09-06T14:37:36.963Z',
                'updated_at' => '2018-09-06T14:37:36.963Z',
                'metadata' => [
                  'id' => '3fbe1373-bb2f-4374-9fb0-dcf7e4732c1c',
                  'name' => '9db5a27c-1b81-4e55-ba09-72a0709b77a1',
                  'username' => 'test-user',
                ],
                'state' => 'enabled',
                'identity_provider' => 'sandbox-idp',
                'username' => 'test-user',
                'email' => '',
                'first_name' => 'Test User',
                'last_name' => ' ',
                'user_registry_url' => '/consumer-api/user-registries/3e57a787-db9a-4b3b-89b2-65bb2cd2f50d',
              ],
              'user_url' => '/consumer-api/user-registries/3e57a787-db9a-4b3b-89b2-65bb2cd2f50d/users/9db5a27c-1b81-4e55-ba09-72a0709b77a1',
              'role_urls' => [
                '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/roles/aeb976ed-d960-4629-bd27-5d19ccdf6bd4',
              ],
              'created_at' => '2018-09-06T14:37:40.931Z',
              'updated_at' => '2018-09-06T14:37:40.931Z',
              'org_url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
              'user_registry_url' => '/consumer-api/user-registries/3e57a787-db9a-4b3b-89b2-65bb2cd2f50d',
              'url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/members/f7b301bb-e338-4b51-b409-477e81aa2d2a',
            ],
          ],
          'memberInvitations' => [],
        ],
      ],
      'credentials' => [
        [
          'type' => 'credential',
          'api_version' => '2.0.0',
          'id' => '14cff214-3c18-40e1-a2dc-d201d58c465d',
          'name' => 'sandbox-test-app-credentials',
          'title' => 'Sandbox Test App Credentials',
          'summary' => 'Sandbox Test App Credentials',
          'client_id' => 'efcfda1e230db20dcf9e732d0a6a2107',
          'shadow_id' => '6985ecf0-b1e2-11e8-a0a0-92998d902e7c',
          'created_at' => '2018-09-06T14:37:42.469Z',
          'updated_at' => '2018-09-06T14:37:42.469Z',
          'org_url' => '/consumer-api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
          'catalog_url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002',
          'consumer_org_url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
          'app_url' => '/consumer-api/apps/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/13db67bf-7370-4fbc-a9d4-11db9b13e7ee',
          'url' => '/consumer-api/apps/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/13db67bf-7370-4fbc-a9d4-11db9b13e7ee/credentials/14cff214-3c18-40e1-a2dc-d201d58c465d',
        ],
      ],
      'apps' => [
        [
          'type' => 'app',
          'api_version' => '2.0.0',
          'id' => '13db67bf-7370-4fbc-a9d4-11db9b13e7ee',
          'name' => 'sandbox-test-app',
          'title' => 'Test App Title',
          'summary' => 'Test App Summary',
          'state' => 'enabled',
          'lifecycle_state' => 'production',
          'shadow_id' => '69723de0-b1e2-11e8-9cb3-f7a071a1a24c',
          'app_credential_urls' => [
            '/consumer-api/apps/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/13db67bf-7370-4fbc-a9d4-11db9b13e7ee/credentials/14cff214-3c18-40e1-a2dc-d201d58c465d',
          ],
          'created_at' => '2018-09-06T14:37:42.389Z',
          'updated_at' => '2018-09-06T14:37:42.847Z',
          'org_url' => '/consumer-api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
          'catalog_url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002',
          'consumer_org_url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
          'url' => '/consumer-api/apps/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/13db67bf-7370-4fbc-a9d4-11db9b13e7ee',
          'app_credentials' => [
            [
              'name' => 'sandbox-test-app-credentials',
              'title' => 'Sandbox Test App Credentials',
              'summary' => 'Sandbox Test App Credentials',
              'type' => 'credential',
              'api_version' => '2.0.0',
              'id' => '14cff214-3c18-40e1-a2dc-d201d58c465d',
              'created_at' => '2018-09-06T14:37:42.469Z',
              'updated_at' => '2018-09-06T14:37:42.469Z',
              'url' => '/consumer-api/apps/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/13db67bf-7370-4fbc-a9d4-11db9b13e7ee/credentials/14cff214-3c18-40e1-a2dc-d201d58c465d',
              'org_url' => '/consumer-api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
              'catalog_url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002',
              'consumer_org_url' => '/consumer-api/orgs/a4a67ab9-37f2-47a1-90c7-7340c1900dfb',
              'app_url' => '/consumer-api/apps/a4a67ab9-37f2-47a1-90c7-7340c1900dfb/13db67bf-7370-4fbc-a9d4-11db9b13e7ee',
              'client_id' => 'efcfda1e230db20dcf9e732d0a6a2107',
              'shadow_id' => '6985ecf0-b1e2-11e8-a0a0-92998d902e7c',
            ],
          ],
        ],
      ],
      'catalog_settings' => [
        [
          'type' => 'catalog_setting',
          'api_version' => '2.0.0',
          'name' => 'catalog-setting',
          'title' => NULL,
          'summary' => NULL,
          'shadow_id' => '66779b82-b1e2-11e8-910b-5e4fb9974d8e',
          'shadow' => FALSE,
          'application_lifecycle' => [
            'enabled' => FALSE,
          ],
          'consumer_self_service_onboarding' => TRUE,
          'custom_notification_templates_enabled' => FALSE,
          'email_sender' => [
            'custom' => FALSE,
            'name' => NULL,
            'address' => NULL,
          ],
          'hash_client_secret' => FALSE,
          'invitation_ttl' => 172800,
          'portal' => [
            'type' => 'drupal',
            'endpoint' => 'https://portal.mf7.argo2-sl.dev.ciondemand.com/test/sandbox',
            'portal_service_url' => '/api/orgs/33803e54-e161-40ff-988f-c51f681f7cef/portal-services/0a2dde8d-9c0d-42c5-b1a5-2ad6452cc9c7',
            'webhook_url' => NULL,
          ],
          'product_lifecycle_approvals' => NULL,
          'production_mode' => FALSE,
          'spaces_enabled' => FALSE,
          'task_self_approval' => FALSE,
          'user_registry_default_url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002/configured-catalog-user-registries/3e57a787-db9a-4b3b-89b2-65bb2cd2f50d',
          'vanity_api_endpoint' => [
            'enabled' => FALSE,
            'endpoint_preference' => NULL,
            'vanity_api_base_endpoints' => [],
          ],
          'metadata' => NULL,
          'created_at' => '2018-09-06T14:37:36.263Z',
          'updated_at' => '2018-09-06T14:44:16.331Z',
          'org_url' => '/api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
          'catalog_url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002',
          'url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002/settings',
        ],
      ],
      'apis' => [
        [
          'type' => 'api',
          'api_version' => '2.0.0',
          'id' => '580af3e4-00d8-4e88-aa99-ccee8c58dd6f',
          'name' => 'echo-api',
          'version' => '0.0.1',
          'title' => 'Echo API',
          'state' => 'online',
          'shadow_id' => '48696780-b1e3-11e8-92c2-af06da5a7610',
          'scope' => 'catalog',
          'gateway_service_urls' => [
            '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002/configured-gateway-services/87e5fab4-fefb-4063-a7f2-7381608c7b0a',
          ],
          'user_registry_urls' => [],
          'oauth_provider_urls' => [],
          'tls_client_profile_urls' => [],
          'extension_urls' => [],
          'policy_urls' => [
            '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002/configured-gateway-services/87e5fab4-fefb-4063-a7f2-7381608c7b0a/policies/523e8bee-6867-4ae1-a071-0bb6dfe32b76',
            '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002/configured-gateway-services/87e5fab4-fefb-4063-a7f2-7381608c7b0a/policies/97ad8a2d-3adf-4597-9419-6a9cadb38002',
            '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002/configured-gateway-services/87e5fab4-fefb-4063-a7f2-7381608c7b0a/policies/67d66392-2124-477d-90f1-04c577eb7609',
          ],
          'consumer_api' => [
            'swagger' => '2.0',
            'info' => [
              'version' => '0.0.1',
              'title' => 'Echo API',
              'x-ibm-name' => 'echo-api',
              'contact' => [
                'name' => 'Ricky Moorhouse',
                'email' => 'moorh@uk.ibm.com',
              ],
            ],
            'host' => 'mf7-gw.argo2-sl.dev.ciondemand.com',
            'basePath' => '/test/sandbox/echo',
            'paths' => [
              '/get' => [
                'get' => [
                  'responses' => [
                    200 => [
                      'description' => '200 OK',
                      'schema' => [
                        '$ref' => '#/definitions/responseObject',
                      ],
                    ],
                  ],
                  'parameters' => [
                    [
                      '$ref' => '#/parameters/text',
                    ],
                  ],
                ],
              ],
            ],
            'security' => [
              [
                'api-key-2' => [],
                'api-key-1' => [],
              ],
            ],
            'x-ibm-configuration' => [
              'gateway' => 'datapower-gateway',
              'enforced' => TRUE,
              'testable' => TRUE,
              'phase' => 'realized',
              'cors' => [
                'enabled' => TRUE,
              ],
              'type' => 'rest',
              'servers' => [
                [
                  'url' => 'https://mf7-gw.argo2-sl.dev.ciondemand.com/test/sandbox/echo',
                  'description' => '',
                  'type' => [
                    'production',
                    'development',
                  ],
                ],
              ],
            ],
            'produces' => [
              'application/json',
            ],
            'parameters' => [
              'text' => [
                'name' => 'text',
                'type' => 'string',
                'required' => FALSE,
                'in' => 'query',
                'description' => 'Text passed in',
              ],
            ],
            'definitions' => [
              'responseObject' => [
                'properties' => [
                  'text' => [
                    'type' => 'string',
                  ],
                ],
              ],
            ],
            'securityDefinitions' => [
              'api-key-2' => [
                'type' => 'apiKey',
                'description' => '',
                'in' => 'header',
                'name' => 'X-IBM-Client-Secret',
              ],
              'api-key-1' => [
                'type' => 'apiKey',
                'description' => '',
                'in' => 'header',
                'name' => 'X-IBM-Client-Id',
              ],
            ],
            'schemes' => [
              'https',
            ],
          ],
          'created_at' => '2018-09-06T14:43:56.320Z',
          'updated_at' => '2018-09-06T14:43:57.202Z',
          'org_url' => '/consumer-api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
          'catalog_url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002',
          'url' => '/consumer-api/apis/580af3e4-00d8-4e88-aa99-ccee8c58dd6f',
        ],
      ],
      'products' => [
        [
          'type' => 'product',
          'api_version' => '2.0.0',
          'id' => 'b1e9fb34-2bd3-4544-84e8-ec6ca291eff3',
          'name' => 'echo-product',
          'version' => '1.0.0',
          'title' => 'Echo Product',
          'summary' => 'This is a product containing an echo api',
          'state' => 'published',
          'shadow_id' => '48881310-b1e3-11e8-b7f0-f0630d8d6915',
          'scope' => 'catalog',
          'gateway_service_urls' => [
            '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002/configured-gateway-services/87e5fab4-fefb-4063-a7f2-7381608c7b0a',
          ],
          'visibility' => [
            'view' => [
              'type' => 'public',
              'enabled' => TRUE,
            ],
            'subscribe' => [
              'type' => 'authenticated',
              'enabled' => TRUE,
            ],
          ],
          'api_urls' => [
            '/consumer-api/apis/580af3e4-00d8-4e88-aa99-ccee8c58dd6f',
          ],
          'oauth_provider_urls' => [],
          'catalog_product' => [
            'info' => [
              'version' => '1.0.0',
              'title' => 'Echo Product',
              'name' => 'echo-product',
              'summary' => 'This is a product containing an echo api',
            ],
            'gateways' => [
              'datapower-gateway',
            ],
            'plans' => [
              'default-plan' => [
                'rate-limits' => [
                  'default' => [
                    'value' => '100/1hour',
                  ],
                ],
                'title' => 'Default Plan',
                'description' => 'Default Plan',
                'approval' => FALSE,
              ],
            ],
            'apis' => [
              'Echo API' => [
                'name' => 'echo-api:0.0.1',
              ],
            ],
            'visibility' => [
              'view' => [
                'type' => 'public',
                'enabled' => TRUE,
              ],
              'subscribe' => [
                'type' => 'authenticated',
                'enabled' => TRUE,
              ],
            ],
            'product' => '1.0.0',
          ],
          'task_urls' => [],
          'created_at' => '2018-09-06T14:43:56.057Z',
          'updated_at' => '2018-09-06T14:43:57.028Z',
          'org_url' => '/consumer-api/orgs/33803e54-e161-40ff-988f-c51f681f7cef',
          'catalog_url' => '/api/catalogs/33803e54-e161-40ff-988f-c51f681f7cef/aff3eada-4a04-450a-bcc7-f22518bad002',
          'url' => '/consumer-api/products/b1e9fb34-2bd3-4544-84e8-ec6ca291eff3',
        ],
      ],
    ];
    self::assertEquals($expectedJson, $content);

    return $content;
  }


}
