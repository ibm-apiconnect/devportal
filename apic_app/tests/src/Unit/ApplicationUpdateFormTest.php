<?php

namespace Drupal\Tests\apic_app\Unit;

require_once __DIR__ . '/bootstrap.php';

use Drupal\apic_app\Form\ApplicationUpdateForm;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\Service\ApplicationService;
use Drupal\apic_app\Service\CertificateService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\apic_app\Form\ApplicationUpdateForm
 *
 * @group apic_app
 */
class ApplicationUpdateFormTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Drupal\apic_app\Form\ApplicationUpdateForm
   */
  protected ApplicationUpdateForm $form;

  /**
   * @var \Drupal\Core\Form\FormStateInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $formState;

  /**
   * @var \Drupal\apic_app\Service\ApplicationRestInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $restService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\Utils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $utils;

  /**
   * @var \Drupal\Core\Messenger\Messenger|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  /**
   * @var \Drupal\apic_app\Service\CertificateService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $certService;

  /**
   * @var \Drupal\apic_app\Service\ApplicationService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $applicationService;

  protected function setUp(): void {
    parent::setUp();
    $this->prophet = new Prophet();

    $container = new ContainerBuilder();
    $stringTranslation = $this->createMock(TranslationInterface::class);
    $stringTranslation->method('translate')
      ->willReturnCallback(function($string) {
        return $string;
      });
    $container->set('string_translation', $stringTranslation);
    \Drupal::setContainer($container);

    $this->restService = $this->prophet->prophesize(ApplicationRestInterface::class);
    $this->userUtils = $this->prophet->prophesize(UserUtils::class);
    $this->utils = $this->prophet->prophesize(Utils::class);
    $this->messenger = $this->prophet->prophesize(Messenger::class);
    $this->certService = $this->prophet->prophesize(CertificateService::class);
    $this->applicationService = $this->prophet->prophesize(ApplicationService::class);

    $this->form = new ApplicationUpdateForm(
      $this->restService->reveal(),
      $this->userUtils->reveal(),
      $this->utils->reveal(),
      $this->messenger->reveal(),
      $this->certService->reveal(),
      $this->applicationService->reveal()
    );

    $this->formState = $this->createMock(FormStateInterface::class);
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
    parent::tearDown();
  }

  /**
   * @covers ::getFormId
   */
  public function testGetFormId(): void {
    $formId = $this->form->getFormId();
    $this->assertEquals('application_update_form', $formId);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithEmptyName(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => '']]);
    $this->formState->expects($this->once())
      ->method('setErrorByName')
      ->with('title', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithNullName(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn(NULL);
    $this->formState->expects($this->once())
      ->method('setErrorByName')
      ->with('title', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithWhitespaceName(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => '   ']]);
    $this->formState->expects($this->once())
      ->method('setErrorByName')
      ->with('title', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithValidName(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => 'My Valid Application']]);
    $this->formState->expects($this->never())
      ->method('setErrorByName');

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithValidNameAsString(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn('Valid App Name');
    $this->formState->expects($this->never())
      ->method('setErrorByName');

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithSpecialCharacters(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => 'My-App_2024!']]);
    $this->formState->expects($this->never())
      ->method('setErrorByName');

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithNumericName(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => '12345']]);
    $this->formState->expects($this->never())
      ->method('setErrorByName');

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithUnicodeCharacters(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => 'Application 应用程序']]);
    $this->formState->expects($this->never())
      ->method('setErrorByName');

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithLongName(): void {
    $form = [];
    $longName = str_repeat('A', 255);
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => $longName]]);
    $this->formState->expects($this->never())
      ->method('setErrorByName');

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithMixedWhitespace(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => "  \t\n  "]]);
    $this->formState->expects($this->once())
      ->method('setErrorByName')
      ->with('title', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithLeadingTrailingSpaces(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => '  Valid App  ']]);
    $this->formState->expects($this->never())
      ->method('setErrorByName');

    $this->form->validateForm($form, $this->formState);
  }

}
