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

namespace Drupal\Tests\auth_apic\Unit {

  use Drupal\auth_apic\UserManagement\ApicUserDeleteService;
  use Drupal\Core\Entity\EntityInterface;
  use Drupal\ibm_apim\ApicType\ApicUser;
  use Drupal\ibm_apim\Rest\RestResponse;
  use Drupal\Tests\auth_apic\Unit\UserManagement\AuthApicUserManagementBaseTestClass;
  use Prophecy\Argument;
  use Prophecy\Prophet;

  /**
   * @group auth_apic
   */
  class ApicUserDeleteServiceTest extends AuthApicUserManagementBaseTestClass {

    /**
     * @var \Drupal\ibm_apim\Service\APIMServer|\Prophecy\Prophecy\ObjectProphecy 
     */
    protected $mgmtServer;

    /**
     * @var \Drupal\ibm_apim\Service\ApicUserStorage|\Prophecy\Prophecy\ObjectProphecy 
     */
    protected $userStorage;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface 
     */
    protected $logger;

    /**
     * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy 
     */
    protected $currentUser;

    protected function setup(): void {
      $this->prophet = new Prophet();
      $this->mgmtServer = $this->prophet->prophesize(\Drupal\ibm_apim\Service\APIMServer::class);
      $this->userStorage = $this->prophet->prophesize(\Drupal\ibm_apim\Service\ApicUserStorage::class);
      $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
      $this->currentUser = $this->prophet->prophesize(\Drupal\Core\Session\AccountProxyInterface::class);
    }

    protected function tearDown(): void {
      $this->prophet->checkPredictions();
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testDeleteUser(): void {

      $mgmtResponse = new RestResponse();
      $mgmtResponse->setCode(200);
      $this->mgmtServer->deleteMe()->willReturn($mgmtResponse);

      $this->currentUser->getAccountName()->willReturn('andre');
      $this->currentUser->id()->willReturn(2);

      $this->logger->error(Argument::any())->shouldNotBeCalled();
      $this->logger->notice('Account deleted in apim by @username', ['@username' => 'andre'])->shouldBeCalled();
      $this->logger->notice('Deleting user - id = @id', ['@id' => 2])->shouldBeCalled();

      $service = new ApicUserDeleteService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->logger->reveal(),
        $this->currentUser->reveal());

      $response = $service->deleteUser();
      self::assertTrue($response->success());

    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testDeleteUserMgmtFail(): void {

      $mgmtResponse = new RestResponse();
      $mgmtResponse->setCode(400);
      $this->mgmtServer->deleteMe()->willReturn($mgmtResponse);

      $this->currentUser->getAccountName()->shouldNotBeCalled();
      $this->currentUser->id()->shouldNotBeCalled();

      $this->logger->error('Error deleting user account in apim')->shouldBeCalled();
      $this->logger->notice(Argument::any())->shouldNotBeCalled();

      $service = new ApicUserDeleteService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->logger->reveal(),
        $this->currentUser->reveal());

      $response = $service->deleteUser();
      self::assertFalse($response->success());

    }

    public function testDeleteLocalAccountCurrentUser(): void {

      $this->currentUser->id()->willReturn(2);
      $this->logger->notice('Deleting user - id = @id', ['@id'=> 2])->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $service = new ApicUserDeleteService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->logger->reveal(),
        $this->currentUser->reveal());

      $response = $service->deleteLocalAccount();
      self::assertTrue($response);

    }

    /**
     * @throws \Exception
     */
    public function testDeleteLocalAccountProvidedUser(): void {

      $user = new ApicUser();
      $user->setUsername('andre');
      $user->setApicUserRegistryUrl('/reg/lur');

      $account = $this->prophet->prophesize(EntityInterface::class);
      $account->id()->willReturn(3);

      $this->userStorage->load($user)->willReturn($account->reveal())->shouldBeCalled();

      $this->currentUser->id()->shouldNotBeCalled();
      $this->logger->notice('Deleting user - id = @id', ['@id'=> 3])->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $service = new ApicUserDeleteService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->logger->reveal(),
        $this->currentUser->reveal());

      $response = $service->deleteLocalAccount($user);
      self::assertTrue($response);

    }

    /**
     * @throws \Exception
     */
    public function testDeleteLocalAccountProvidedUserNoAccount(): void {

      $user = new ApicUser();
      $user->setUsername('andre');
      $user->setApicUserRegistryUrl('/reg/lur');

      $this->userStorage->load($user)->willReturn(NULL)->shouldBeCalled();

      $this->currentUser->id()->shouldNotBeCalled();
      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('Unable to load user account to be deleted.')->shouldBeCalled();
      $this->logger->error('Unable to delete local account.')->shouldBeCalled();

      $service = new ApicUserDeleteService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->logger->reveal(),
        $this->currentUser->reveal());

      $response = $service->deleteLocalAccount($user);
      self::assertFalse($response);

    }

    /**
     * @throws \Exception
     */
    public function testDeleteLocalAccountProvidedUserNoIdForAccount(): void {

      $user = new ApicUser();
      $user->setUsername('andre');
      $user->setApicUserRegistryUrl('/reg/lur');

      $account = $this->prophet->prophesize(EntityInterface::class);
      $account->id()->willReturn(NULL);

      $this->userStorage->load($user)->willReturn($account->reveal())->shouldBeCalled();

      $this->currentUser->id()->shouldNotBeCalled();
      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('Unable to load user account to be deleted.')->shouldNotBeCalled();
      $this->logger->error('Unable to delete local account.')->shouldBeCalled();

      $service = new ApicUserDeleteService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->logger->reveal(),
        $this->currentUser->reveal());

      $response = $service->deleteLocalAccount($user);
      self::assertFalse($response);

    }

  }
}
