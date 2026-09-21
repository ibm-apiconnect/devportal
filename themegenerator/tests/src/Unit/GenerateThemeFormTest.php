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

namespace Drupal\Tests\themegenerator\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\themegenerator\Form\GenerateTheme;
use Prophecy\Argument;

/**
 * Tests for GenerateTheme form validation.
 *
 * @group themegenerator
 * @coversDefaultClass \Drupal\themegenerator\Form\GenerateTheme
 */
class GenerateThemeFormTest extends UnitTestCase {

  /**
   * The form object.
   *
   * @var \Drupal\themegenerator\Form\GenerateTheme
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    
    $messenger = $this->prophesize(Messenger::class);
    $this->form = new GenerateTheme($messenger->reveal());
  }

  /**
   * Test that valid theme names pass validation.
   *
   * @dataProvider validThemeNameProvider
   * @covers ::validateForm
   */
  public function testValidThemeNames($name): void {
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->getValue('name')->willReturn($name);
    $form_state->setErrorByName(Argument::any(), Argument::any())->shouldNotBeCalled();
    
    $this->form->validateForm($form, $form_state->reveal());
  }

  /**
   * Test that theme names starting with numbers fail validation.
   *
   * @dataProvider invalidStartCharacterProvider
   * @covers ::validateForm
   */
  public function testThemeNamesStartingWithNumbersFail($name): void {
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->getValue('name')->willReturn($name);
    $form_state->setErrorByName('name', Argument::type(TranslatableMarkup::class))->shouldBeCalled();
    
    $this->form->validateForm($form, $form_state->reveal());
  }

  /**
   * Test that theme names with invalid characters fail validation.
   *
   * @dataProvider invalidCharactersProvider
   * @covers ::validateForm
   */
  public function testThemeNamesWithInvalidCharactersFail($name): void {
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->getValue('name')->willReturn($name);
    $form_state->setErrorByName('name', Argument::type(TranslatableMarkup::class))->shouldBeCalled();
    
    $this->form->validateForm($form, $form_state->reveal());
  }

  /**
   * Test that reserved theme names fail validation.
   *
   * @dataProvider reservedNamesProvider
   * @covers ::validateForm
   */
  public function testReservedThemeNamesFail($name): void {
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->getValue('name')->willReturn($name);
    $form_state->setErrorByName('name', Argument::type(TranslatableMarkup::class))->shouldBeCalled();
    
    $this->form->validateForm($form, $form_state->reveal());
  }

  /**
   * Test that theme names exceeding length limit fail validation.
   *
   * @covers ::validateForm
   */
  public function testThemeNameTooLongFails(): void {
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->getValue('name')->willReturn('this_name_is_way_too_long_for_validation');
    $form_state->setErrorByName('name', Argument::type(TranslatableMarkup::class))->shouldBeCalled();
    
    $this->form->validateForm($form, $form_state->reveal());
  }

  /**
   * Test that empty theme names fail validation.
   *
   * @covers ::validateForm
   */
  public function testEmptyThemeNameFails(): void {
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->getValue('name')->willReturn('');
    $form_state->setErrorByName('name', Argument::type(TranslatableMarkup::class))->shouldBeCalled();
    
    $this->form->validateForm($form, $form_state->reveal());
  }

  /**
   * Data provider for valid theme names.
   */
  public function validThemeNameProvider(): array {
    return [
      'lowercase letters only' => ['mytheme'],
      'letters and numbers' => ['my_theme_123'],
      'with underscores' => ['my_custom_theme'],
      'single letter' => ['a'],
      'starts with letter' => ['a123'],
      'max length' => ['abcdefghij1234567890'], // 20 characters
    ];
  }

  /**
   * Data provider for invalid start characters.
   */
  public function invalidStartCharacterProvider(): array {
    return [
      'starts with number' => ['123theme'],
      'starts with zero' => ['0theme'],
      'starts with underscore' => ['_mytheme'],
    ];
  }

  /**
   * Data provider for invalid characters.
   */
  public function invalidCharactersProvider(): array {
    return [
      'uppercase letters' => ['MyTheme'],
      'spaces' => ['my theme'],
      'hyphens' => ['my-theme'],
      'special characters' => ['my@theme'],
      'dots' => ['my.theme'],
    ];
  }

  /**
   * Data provider for reserved names.
   */
  public function reservedNamesProvider(): array {
    return [
      ['src'],
      ['lib'],
      ['vendor'],
      ['assets'],
      ['css'],
      ['files'],
      ['images'],
      ['js'],
      ['misc'],
      ['templates'],
      ['includes'],
      ['fixtures'],
      ['drupal'],
    ];
  }

}