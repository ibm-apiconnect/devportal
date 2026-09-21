<?php


namespace Drupal\Tests\auth_apic\Unit\Hook;

use Drupal\auth_apic\Hook\AuthApicHooks;
use Drupal\Tests\auth_apic\Unit\Base\AuthApicTestBaseClass;
use Drupal\user\Entity\User;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\auth_apic\Hook\AuthApicHooks
 * @group auth_apic
 */
class AuthApicHooksTest extends AuthApicTestBaseClass {

  /**
   * @var \Drupal\auth_apic\Hook\AuthApicHooks
   */
  protected $authApicHooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->authApicHooks = new AuthApicHooks();
  }

  protected function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Test tokenInfo() returns correct token definitions.
   *
   * @covers ::tokenInfo
   */
  public function testTokenInfo(): void {
    $tokenInfo = $this->authApicHooks->tokenInfo();

    // Check that tokens array exists and has user type
    $this->assertArrayHasKey('tokens', $tokenInfo);
    $this->assertArrayHasKey('user', $tokenInfo['tokens']);

    // Check that all expected tokens are registered
    $expectedTokens = [
      'first_name',
      'last_name',
      'apic_realm',
      'apic_state',
      'apic_url',
      'consumerorg_url',
      'apic_user_registry_url',
      'apic_idp',
      'apic_catalog_id',
      'avatars_avatar_generator',
      'codesnippet',
      'consumer_organization',
      'first_time_login',
      'language',
      'field_last_password_reset',
      'avatars_user_picture',
      'field_password_expiration',
      'field_pending_expire_sent',
    ];

    foreach ($expectedTokens as $token) {
      $this->assertArrayHasKey($token, $tokenInfo['tokens']['user'], "Token '$token' should be registered");
      $this->assertArrayHasKey('name', $tokenInfo['tokens']['user'][$token]);
      $this->assertArrayHasKey('description', $tokenInfo['tokens']['user'][$token]);
    }

    // Verify current-user type is NOT registered (it should only be 'user')
    $this->assertArrayNotHasKey('current-user', $tokenInfo['tokens']);
  }

  /**
   * Test tokens() returns correct replacements for direct tokens.
   *
   * @covers ::tokens
   * @covers ::getTokenValue
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testTokensDirectReplacement(): void {
    // Set up container with token service
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
    $tokenService = $this->prophet->prophesize(\Drupal\Core\Utility\Token::class);
    $tokenService->findWithPrefix(Argument::any(), 'original')->willReturn([]);
    $container->set('token', $tokenService->reveal());
    \Drupal::setContainer($container);

    // Create mock user
    $account = $this->prophet->prophesize(User::class);
    $account->hasField('first_name')->willReturn(TRUE);
    $account->get('first_name')->willReturn($this->createFieldItem('John'));
    $account->hasField('last_name')->willReturn(TRUE);
    $account->get('last_name')->willReturn($this->createFieldItem('Doe'));
    $account->hasField('apic_state')->willReturn(TRUE);
    $account->get('apic_state')->willReturn($this->createFieldItem('enabled'));
    $account->hasField('apic_idp')->willReturn(TRUE);
    $account->get('apic_idp')->willReturn($this->createFieldItem('test-idp'));
    $account->getDisplayName()->willReturn('John Doe');
    $account->getEmail()->willReturn('john@example.com');

    $tokens = [
      'first_name' => '[user:first_name]',
      'last_name' => '[user:last_name]',
      'apic_state' => '[user:apic_state]',
      'apic_idp' => '[user:apic_idp]',
      'display-name' => '[user:display-name]',
      'mail' => '[user:mail]',
    ];

    $data = ['user' => $account->reveal()];
    $replacements = $this->authApicHooks->tokens('user', $tokens, $data, []);

    $this->assertEquals('John', $replacements['[user:first_name]']);
    $this->assertEquals('Doe', $replacements['[user:last_name]']);
    $this->assertEquals('enabled', $replacements['[user:apic_state]']);
    $this->assertEquals('test-idp', $replacements['[user:apic_idp]']);
    $this->assertEquals('John Doe', $replacements['[user:display-name]']);
    $this->assertEquals('john@example.com', $replacements['[user:mail]']);
  }

  /**
   * Test tokens() handles empty fields correctly.
   *
   * @covers ::tokens
   * @covers ::getTokenValue
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testTokensEmptyFields(): void {
    // Set up container with token service
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
    $tokenService = $this->prophet->prophesize(\Drupal\Core\Utility\Token::class);
    $tokenService->findWithPrefix(Argument::any(), 'original')->willReturn([]);
    $container->set('token', $tokenService->reveal());
    \Drupal::setContainer($container);

    $account = $this->prophet->prophesize(User::class);
    $account->hasField('first_name')->willReturn(TRUE);

    // Create empty field
    $emptyField = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemListInterface::class);
    $emptyField->isEmpty()->willReturn(TRUE);
    $account->get('first_name')->willReturn($emptyField->reveal());

    $tokens = ['first_name' => '[user:first_name]'];
    $data = ['user' => $account->reveal()];
    $replacements = $this->authApicHooks->tokens('user', $tokens, $data, []);

    // Empty fields should not be in replacements
    $this->assertArrayNotHasKey('[user:first_name]', $replacements);
  }

  /**
   * Test tokens() returns empty array for non-user types.
   *
   * @covers ::tokens
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testTokensNonUserType(): void {
    $tokens = ['title' => '[node:title]'];
    $replacements = $this->authApicHooks->tokens('node', $tokens, [], []);

    $this->assertEmpty($replacements);
  }

  /**
   * Test tokens() handles missing user data.
   *
   * @covers ::tokens
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testTokensMissingUserData(): void {
    // Mock current user service and entity type repository
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
    $currentUser = $this->prophet->prophesize(\Drupal\Core\Session\AccountProxyInterface::class);
    $currentUser->id()->willReturn(0);
    $container->set('current_user', $currentUser->reveal());

    // Mock entity type repository
    $entityTypeRepository = $this->prophet->prophesize(\Drupal\Core\Entity\EntityTypeRepositoryInterface::class);
    $container->set('entity_type.repository', $entityTypeRepository->reveal());

    // Mock entity type manager with user storage
    $userStorage = $this->prophet->prophesize(\Drupal\Core\Entity\EntityStorageInterface::class);
    $userStorage->load(0)->willReturn(NULL);
    $userStorage->load(Argument::any())->willReturn(NULL);

    $entityTypeManager = $this->prophet->prophesize(\Drupal\Core\Entity\EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('user')->willReturn($userStorage->reveal());
    $entityTypeManager->getStorage(Argument::any())->willReturn($userStorage->reveal());
    $container->set('entity_type.manager', $entityTypeManager->reveal());

    // Mock token service
    $tokenService = $this->prophet->prophesize(\Drupal\Core\Utility\Token::class);
    $tokenService->findWithPrefix(Argument::any(), 'original')->willReturn([]);
    $container->set('token', $tokenService->reveal());

    \Drupal::setContainer($container);

    $tokens = ['first_name' => '[user:first_name]'];
    $replacements = $this->authApicHooks->tokens('user', $tokens, [], []);

    // Should return empty array when user can't be loaded
    $this->assertEmpty($replacements);
  }

  /**
   * Helper method to create a field item with a value.
   */
  private function createFieldItem($value) {
    // Create a mock object that supports both method calls and property access
    $field = new class($value) {
      public $value;
      private $val;

      public function __construct($value) {
        $this->value = $value;
        $this->val = $value;
      }

      public function isEmpty() {
        return FALSE;
      }

      public function getValue() {
        return [['value' => $this->val]];
      }
    };

    return $field;
  }

}
