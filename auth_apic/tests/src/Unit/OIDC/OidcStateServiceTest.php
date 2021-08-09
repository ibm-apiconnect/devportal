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

namespace Drupal\Tests\auth_apic\Unit;

use Drupal\auth_apic\Service\OidcStateService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\auth_apic\Service\OidcStateService
 *
 * @group auth_apic
 */
class OidcStateServiceTest extends UnitTestCase {

  private $prophet;

  /*
   Dependencies of OidcStateService.
   */
  protected $state;
  protected $logger;
  protected $encryptService;
  protected $encryptionProfileManager;
  protected $session;
  protected $time;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->state = $this->prophet->prophesize('Drupal\Core\State\StateInterface');
    $this->logger = $this->prophet->prophesize('Psr\Log\LoggerInterface');
    $this->encryptService = $this->prophet->prophesize('Drupal\encrypt\EncryptServiceInterface');
    $this->encryptionProfileManager = $this->prophet->prophesize('Drupal\encrypt\EncryptionProfileManagerInterface');
    $this->session = $this->prophet->prophesize('Symfony\Component\HttpFoundation\Session\Session');
    $this->time = $this->prophet->prophesize('Drupal\Component\Datetime\Time');
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * store($data) tests
   */
  public function testStoreValid(): void {

    $data = array('registry_url' => '/registry/url');
    $key = '12345678:/registry/url:testsession123';
    $encrypted_key = 'ENCRYPTED_KEY';
    $encrypted_data = 'ENCRYPTED_DATA';

    $state_key =  'auth_apic.oidc_state';
    $encryption_profile_name = 'socialblock';
    $encryptionProfile = $this->prophet->prophesize('Drupal\encrypt\Entity\EncryptionProfile')->reveal();

    $initial_state = array('one' => array());
    // note - storing with unencrypted key as this is within the service.
    $updated_state = \array_merge($initial_state, array($key => $encrypted_data));

    // required for the key.
    $this->session->getId()->willReturn('testsession123');
    $this->time->getCurrentTime()->willReturn('12345678');

    $this->state->get($state_key)->willReturn(serialize($initial_state));
    $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_name)->willReturn($encryptionProfile);
    $this->encryptService->encrypt($key, $encryptionProfile)->willReturn($encrypted_key)->shouldBeCalled();
    $this->encryptService->encrypt(serialize($data), $encryptionProfile)->willReturn($encrypted_data)->shouldBeCalled();
    $this->state->set($state_key, serialize($updated_state))->shouldBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();

    $service = $this->getServiceUnderTest();
    $key = $service->store($data);

    $this->assertNotNull($key, 'expected a key to be returned from store()');
    $this->assertEquals($key, 'ENCRYPTED_KEY', 'unexpected encrypted key returned.');

  }


  /**
   * get(string $key) tests
   */
  public function testGetValid(): void {
    $encrypted_key = 'ENCRYPTED_KEY';
    $encrypted_data = 'ENCRYPTED_DATA';
    $initial_state = array('one' => $encrypted_data);
    $initial_state_value_decrypted = array('registry_url' => '/registry/url');
    $state_key =  'auth_apic.oidc_state';
    $encryption_profile_name = 'socialblock';

    $encryptionProfile = $this->prophet->prophesize('Drupal\encrypt\Entity\EncryptionProfile')->reveal();
    $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_name)->willReturn($encryptionProfile);

    $this->state->get($state_key)->willReturn(serialize($initial_state));
    $this->encryptService->decrypt($encrypted_key, $encryptionProfile)->willReturn('one')->shouldBeCalled();
    $this->encryptService->decrypt($encrypted_data, $encryptionProfile)->willReturn(serialize($initial_state_value_decrypted))->shouldBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();

    $service = $this->getServiceUnderTest();
    $data = $service->get($encrypted_key);
    $this->assertNotNull($data, 'expected data to be returned from get()');
    $this->assertEquals($data, $initial_state_value_decrypted, 'unexpected data returned from get()');
  }


  /**
   * delete(string $key) tests
   */
  public function testDeleteValid(): void {
    $encrypted_key = 'ENCRYPTED_KEY';
    $initial_state = array('one' => 'encrypted_data');
    $updated_state = array();

    $state_key =  'auth_apic.oidc_state';
    $encryption_profile_name = 'socialblock';

    $encryptionProfile = $this->prophet->prophesize('Drupal\encrypt\Entity\EncryptionProfile')->reveal();
    $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_name)->willReturn($encryptionProfile);

    $this->state->get($state_key)->willReturn(serialize($initial_state));
    $this->encryptService->decrypt($encrypted_key, $encryptionProfile)->willReturn('one')->shouldBeCalled();

    $this->state->set($state_key, serialize($updated_state))->shouldBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();

    $service = $this->getServiceUnderTest();
    $result = $service->delete($encrypted_key);

    $this->assertTrue($result, 'expected success from delete()');

  }


  /**
   * prune() tests
   */
  public function testPruneValid(): void {
    $initial_state = array('one' => 'encrypted_data', 'two' => 'more_encrypted_data');
    $updated_state = array('two' => 'more_encrypted_data');
    $data1 = array('registry_url' => '/user/reg1', 'created' => 1);
    $data2 = array('registry_url' => '/user/reg2', 'created' => 3);
    $state_key =  'auth_apic.oidc_state';
    $encryption_profile_name = 'socialblock';

    $encryptionProfile = $this->prophet->prophesize('Drupal\encrypt\Entity\EncryptionProfile')->reveal();
    $this->encryptionProfileManager->getEncryptionProfile($encryption_profile_name)->willReturn($encryptionProfile);

    $this->state->get($state_key)->willReturn(serialize($initial_state));
    $this->time->getCurrentTime()->willReturn(86400 + 2);
    $this->encryptService->decrypt('encrypted_data', $encryptionProfile)->willReturn($data1);
    $this->encryptService->decrypt('more_encrypted_data', $encryptionProfile)->willReturn($data2);

    $this->state->set($state_key, serialize($updated_state))->shouldBeCalled();

    $service = $this->getServiceUnderTest();
    $count = $service->prune();

    $this->assertEquals($count, 1, 'unexpected number of items pruned from state');
  }



  /**
   * @return \Drupal\auth_apic\Service\OidcRegistryService
   */
  private function getServiceUnderTest(): OidcStateService {
    $service = new OidcStateService($this->state->reveal(),
      $this->encryptService->reveal(),
      $this->encryptionProfileManager->reveal(),
      $this->logger->reveal(),
      $this->session->reveal(),
      $this->time->reveal());

    return $service;
  }


}
