<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\apic_app\Unit;

require_once __DIR__ . '/bootstrap.php';

use Drupal\apic_app\Form\ApplicationCreateForm;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\apic_app\Form\ApplicationCreateForm
 *
 * @group apic_app
 */
class ApplicationCreateFormTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Drupal\apic_app\Form\ApplicationCreateForm
   */
  protected ApplicationCreateForm $form;

  /**
   * @var \Drupal\Core\Form\FormStateInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $formState;

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

    $this->form = new ApplicationCreateForm();
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
    $this->assertEquals('application_create_form', $formId);
  }

  /**
   * @covers ::getCancelUrl
   */
  public function testGetCancelUrl(): void {
    $url = $this->form->getCancelUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertEquals('view.applications.page_1', $url->getRouteName());
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
      ->with('Name', $this->anything());

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
      ->with('Name', $this->anything());

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
      ->with('Name', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithURLInName(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => 'My App http://example.com']]);
    $this->formState->expects($this->once())
      ->method('setErrorByName')
      ->with('title', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithHTTPSURLInName(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => 'App https://test.com']]);
    $this->formState->expects($this->once())
      ->method('setErrorByName')
      ->with('title', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithFTPURLInName(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => 'ftp://files.example.com/app']]);
    $this->formState->expects($this->once())
      ->method('setErrorByName')
      ->with('title', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithWWWURLInName(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => 'Check www.example.com']]);
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
  public function testValidateFormWithFileURL(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => 'file:///path/to/file']]);
    $this->formState->expects($this->once())
      ->method('setErrorByName')
      ->with('title', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

  /**
   * @covers ::validateForm
   */
  public function testValidateFormWithMixedCaseURL(): void {
    $form = [];
    $this->formState->method('getValue')
      ->with('title')
      ->willReturn([['value' => 'App HTTP://EXAMPLE.COM']]);
    $this->formState->expects($this->once())
      ->method('setErrorByName')
      ->with('title', $this->anything());

    $this->form->validateForm($form, $this->formState);
  }

}
