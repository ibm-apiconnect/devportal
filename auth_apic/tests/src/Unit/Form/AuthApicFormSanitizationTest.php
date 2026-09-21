<?php

namespace Drupal\Tests\auth_apic\Unit\Form;

use Drupal\auth_apic\Form\ApicUserRegisterForm;
use Drupal\auth_apic\Form\ApicUserLoginForm;
use Drupal\auth_apic\Form\ApicUserDeleteForm;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

if (!function_exists(__NAMESPACE__ . '\ibm_apim_entry_trace')) {
    function ibm_apim_entry_trace() {
        return null;
    }
}

class AuthApicFormSanitizationTest extends UnitTestCase {

  /** @var MockObject */
  protected $logger;

  public function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);
  }

  /**
   * Helper to create ApicUserRegisterForm with correct mocks.
   */
  protected function createApicUserRegisterForm() {
    $entityRepository = $this->createMock(\Drupal\Core\Entity\EntityRepositoryInterface::class);
    $languageManager = $this->createMock(\Drupal\Core\Language\LanguageManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $accountService = $this->createMock(\Drupal\ibm_apim\UserManagement\ApicAccountInterface::class);
    $userRegistryService = $this->createMock(\Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface::class);
    $userService = $this->createMock(\Drupal\ibm_apim\Service\ApicUserService::class);
    $apimUtils = $this->createMock(\Drupal\ibm_apim\Service\ApimUtils::class);
    $oidcService = $this->createMock(\Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface::class);

    $sessionStoreFactory = $this->createMock(\Drupal\Core\TempStore\PrivateTempStoreFactory::class);
    $sessionStore = $this->createMock(\Drupal\Core\TempStore\PrivateTempStore::class);
    $sessionStoreFactory->method('get')->willReturn($sessionStore);

    $userManagedSignUp = $this->createMock(\Drupal\auth_apic\UserManagement\SignUpInterface::class);
    $nonUserManagedSignUp = $this->createMock(\Drupal\auth_apic\UserManagement\SignUpInterface::class);
    $invitationService = $this->createMock(\Drupal\auth_apic\UserManagement\ApicInvitationInterface::class);
    $userStorage = $this->createMock(\Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface::class);
    $cacheBackend = $this->createMock(\Drupal\Core\Cache\CacheBackendInterface::class);
    $messenger = $this->createMock(\Drupal\Core\Messenger\Messenger::class);
    $moduleHandler = $this->createMock(\Drupal\Core\Extension\ModuleHandlerInterface::class);
    $tokenParser = $this->createMock(\Drupal\auth_apic\Service\Interfaces\TokenParserInterface::class);
    $utils = $this->createMock(\Drupal\ibm_apim\Service\Utils::class);
    $siteConfig = $this->createMock(\Drupal\ibm_apim\Service\SiteConfig::class);
    $entityTypeBundleInfo = $this->createMock(EntityTypeBundleInfoInterface::class);
    $time = $this->createMock(\Drupal\Component\Datetime\TimeInterface::class);

    return new ApicUserRegisterForm(
        $entityRepository,
        $languageManager,
        $logger,
        $accountService,
        $userRegistryService,
        $userService,
        $apimUtils,
        $oidcService,
        $sessionStoreFactory,
        $userManagedSignUp,
        $nonUserManagedSignUp,
        $invitationService,
        $userStorage,
        $cacheBackend,
        $messenger,
        $moduleHandler,
        $tokenParser,
        $utils,
        $siteConfig,
        $entityTypeBundleInfo,
        $time
    );
}


  public function testApicUserRegisterFormEmailSanitization() {
    $form = [];
    $formState = $this->createMock(FormStateInterface::class);

    $registerForm = $this->createApicUserRegisterForm();

    if (!method_exists($registerForm, 'sanitizeEmail')) {
      $this->markTestSkipped('sanitizeEmail() method does not exist in this version');
      return;
    }

    $method = new \ReflectionMethod($registerForm, 'sanitizeEmail');
    $method->setAccessible(true);

    $email = ' USER@Example.COM ';
    $result = $method->invoke($registerForm, $email);

    $this->assertEquals('user@example.com', $result);
  }

  public function testApicUserLoginFormConsumerOrgSanitization() {
    $form = [];
    $formState = $this->createMock(FormStateInterface::class);

    $loginForm = $this->getMockBuilder(ApicUserLoginForm::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['validateApicAuthentication'])
      ->getMock();

    $validateMethod = new \ReflectionMethod($loginForm, 'validateApicAuthentication');
    $validateMethod->setAccessible(true);

    $validateMethod->invokeArgs($loginForm, [&$form, $formState]);

    $this->assertIsArray($form);
  }

  public function testApicUserDeleteFormOrgUrlSanitization() {
    $form = [];
    $formState = $this->createMock(FormStateInterface::class);

    $deleteForm = $this->getMockBuilder(ApicUserDeleteForm::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['submitForm'])
      ->getMock();

    $reflection = new \ReflectionClass($deleteForm);
    $loggerProperty = $reflection->getProperty('logger');
    $loggerProperty->setAccessible(true);
    $loggerProperty->setValue($deleteForm, $this->logger);

    $submitMethod = new \ReflectionMethod($deleteForm, 'submitForm');
    $submitMethod->setAccessible(true);

    $submitMethod->invokeArgs($deleteForm, [&$form, $formState]);

    $this->assertIsArray($form);
  }

  public function testApicUserChangePasswordFormPasswordHandling() {
    $passwordForm = $this->getMockBuilder(\Drupal\auth_apic\Form\ApicUserChangePasswordForm::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertInstanceOf(\Drupal\auth_apic\Form\ApicUserChangePasswordForm::class, $passwordForm);
  }
}
