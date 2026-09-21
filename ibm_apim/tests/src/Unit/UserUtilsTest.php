<?php


namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\State;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\Tests\auth_apic\Unit\Base\AuthApicTestBaseClass;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\Utils
 *
 * @group ibm_apim
 */
class UserUtilsTest extends AuthApicTestBaseClass {

  /*
    Dependencies of UserUtils.
  */

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $current_user;

  /**
   * @var \Drupal\Core\State\State|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $state;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entity_type_manager;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore|\Prophecy\Prophecy\ObjectProphecy
   */
  private $sessionStore;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $tempStore;

  /**
   * @var \Drupal\user\UserStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $userStorage;
  
  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService|\Prophecy\Prophecy\ObjectProphecy
   */
  private $consumerOrgService;

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setup(): void {

    parent::setup();
    $this->current_user = $this->prophet->prophesize(AccountProxyInterface::class);
    $this->tempStore = $this->prophet->prophesize(PrivateTempStoreFactory::class);
    $this->state = $this->prophet->prophesize(State::class);
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->entity_type_manager = $this->prophet->prophesize(EntityTypeManagerInterface::class);
    $this->sessionStore = $this->prophet->prophesize(PrivateTempStore::class);
    $this->userStorage = $this->prophet->prophesize(UserStorageInterface::class);
    $this->consumerOrgService = $this->prophet->prophesize(\Drupal\consumerorg\Service\ConsumerOrgService::class);
    $this->tempStore->get('ibm_apim')->willReturn($this->sessionStore);
    $this->entity_type_manager->getStorage('user')->willReturn($this->userStorage->reveal());
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
    \Drupal::unsetContainer();
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testCheckHasPermission(): void {
    $utils = new UserUtils($this->current_user->reveal(),
      $this->tempStore->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal());
    $result = $utils->checkHasPermission();
    self::assertEquals(FALSE, $result);
  }


  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testAddConsumerOrgToUser(): void {

    $orgUrl = "/consumer-orgs/1234/5678/9abc";
    $userAccount = $this->createAccountBase();

    $this->current_user->isAnonymous()->willReturn(FALSE);
    $this->current_user->id()->willReturn(2);
    $this->logger->debug('updating consumerorg urls list set on the user object')->shouldBeCalled();
    $this->logger->debug('adding org to consumerorg urls list %orgUrl', ['%orgUrl' => $orgUrl])->shouldNotBeCalled();
    $utils = new UserUtils($this->current_user->reveal(),
      $this->tempStore->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal());

    $result = $utils->addConsumerOrgToUser($orgUrl, $userAccount->reveal());

    self::assertTrue($result);

  }

  /**
   * Tests that consumer_organization field is updated when adding a consumer org to user
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testAddConsumerOrgToNewUser(): void {
    $orgUrl = "/consumer-orgs/1234/5678/9abc";
    $orgTitle = "org1";

    $userAccount = $this->createAccountBase();
    $userAccount->id()->willReturn(2);
    $userAccount->getFieldDefinitions()->willReturn([]);

    $consumerorg_url_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);
    $consumerorg_url_field->getValue()->willReturn([]);
    $userAccount->get('consumerorg_url')->willReturn($consumerorg_url_field);
    
    $consumer_organization_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);
    $consumer_organization_field->getValue()->willReturn([]);
    $userAccount->get('consumer_organization')->willReturn($consumer_organization_field);
    
    $userAccount->set('consumerorg_url', [['value' => $orgUrl]])->willReturn(NULL);
    $userAccount->set('consumer_organization', [['value' => $orgTitle]])->willReturn(NULL);
    $userAccount->save()->shouldBeCalled();
    
    $mockOrg = new ConsumerOrg();
    $mockOrg->setTitle($orgTitle);
    $this->consumerOrgService->get($orgUrl)->willReturn($mockOrg);
    
    $container = $this->prophesize(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    $container->get('ibm_apim.consumerorg')->willReturn($this->consumerOrgService->reveal());
    \Drupal::setContainer($container->reveal());

    $this->current_user->isAnonymous()->willReturn(FALSE);
    $this->current_user->id()->willReturn(2);

    $this->logger->debug('updating consumerorg urls list set on the user object')->shouldNotBeCalled();
    $this->logger->debug('adding org to consumerorg urls list %orgUrl', ['%orgUrl' => $orgUrl])->shouldBeCalled();
    
    $utils = new UserUtils(
      $this->current_user->reveal(),
      $this->tempStore->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal()
    );

    $result = $utils->addConsumerOrgToUser($orgUrl, $userAccount->reveal());

    self::assertTrue($result);
  }

  /**
   * Tests that consumer_organization field is updated when removing a consumer org from user
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testRemoveConsumerOrgFromUser(): void
  {
    $orgUrl = "/consumer-orgs/1234/5678/9abc";
    $orgTitle = "org1";

    $userAccount = $this->createAccountBase();
    $userAccount->id()->willReturn(2);
    $userAccount->getFieldDefinitions()->willReturn([]);

    $consumerorg_url_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);
    $consumerorg_url_field->getValue()->willReturn([
      ['value' => $orgUrl],
      ['value' => '/consumer-orgs/another']
    ]);
    $userAccount->consumerorg_url = $consumerorg_url_field->reveal();
    $userAccount->get('consumerorg_url')->willReturn($consumerorg_url_field);

    $consumer_organization_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);
    $consumer_organization_field->getValue()->willReturn([
      ['value' => $orgTitle],
      ['value' => 'Another Org']
    ]);
    $userAccount->get('consumer_organization')->willReturn($consumer_organization_field);

    $userAccount->set('consumer_organization', [['value' => 'Another Org']])->willReturn(NULL);
    $userAccount->set('consumerorg_url', [['value' => '/consumer-orgs/another']])->willReturn(NULL);
    $userAccount->save()->shouldBeCalled();

    $mockOrg = new ConsumerOrg();
    $mockOrg->setTitle($orgTitle);
    $this->consumerOrgService->get($orgUrl)->willReturn($mockOrg);

    $container = $this->prophesize(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    $container->get('ibm_apim.consumerorg')->willReturn($this->consumerOrgService->reveal());
    \Drupal::setContainer($container->reveal());

    $this->current_user->isAnonymous()->willReturn(FALSE);
    $this->current_user->id()->willReturn(2);

    $utils = new UserUtils(
      $this->current_user->reveal(),
      $this->tempStore->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal()
    );

    $utils->removeConsumerOrgFromUser($orgUrl, $userAccount->reveal());

    $userAccount->set('consumer_organization', [['value' => 'Another Org']])->shouldHaveBeenCalled();
    $userAccount->set('consumerorg_url', [['value' => '/consumer-orgs/another']])->shouldHaveBeenCalled();
    
    $this->expectNotToPerformAssertions();
  }
}
