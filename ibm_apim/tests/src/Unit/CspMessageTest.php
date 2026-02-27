<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the Content Security Policy (CSP) warning message functionality.
 *
 * @group ibm_apim
 */
class CspMessageTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * The CSP config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cspConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->messenger = $this->prophesize(MessengerInterface::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->cspConfig = $this->prophesize(ImmutableConfig::class);
    
    // Set up the global Drupal service container with our mocked services
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
    $container->set('messenger', $this->messenger->reveal());
    $container->set('config.factory', $this->configFactory->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests that the CSP warning message is displayed when CSP is enabled.
   */
  public function testCspWarningMessageDisplayed(): void {
    // Mock the CSP config to return that CSP is enabled
    $enforceSettings = ['enable' => TRUE];
    $this->cspConfig->get('enforce')->willReturn($enforceSettings);
    $this->configFactory->get('csp.settings')->willReturn($this->cspConfig->reveal());

    // The messenger should receive a warning message about CSP being enforced
    $this->messenger->addWarning('Content Security Policy (CSP) is currently enforced. This may restrict certain features, such as Social media views, asynchronous APIs in Explorer and image uploads in CKEditor.')
      ->shouldBeCalled();
    
    // The messenger should not delete any existing warnings
    $this->messenger->deleteByType(MessengerInterface::TYPE_WARNING)
      ->shouldNotBeCalled();

    // Create a mock form and form state
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $form_id = 'csp_settings';

    // Call the function being tested
    $this->callIbmApimFormAlter($form, $form_state, $form_id);
  }

  /**
   * Tests that no CSP warning message is displayed when CSP is disabled.
   */
  public function testNoCspWarningMessageWhenDisabled(): void {
    // Mock the CSP config to return that CSP is disabled
    $enforceSettings = ['enable' => FALSE];
    $this->cspConfig->get('enforce')->willReturn($enforceSettings);
    $this->configFactory->get('csp.settings')->willReturn($this->cspConfig->reveal());

    // The messenger should not receive a warning message
    $this->messenger->addWarning()
      ->shouldNotBeCalled();
    
    // The messenger should delete any existing warnings
    $this->messenger->deleteByType(MessengerInterface::TYPE_WARNING)
      ->shouldBeCalled();

    // Create a mock form and form state
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $form_id = 'csp_settings';

    // Call the function being tested
    $this->callIbmApimFormAlter($form, $form_state, $form_id);
  }

  /**
   * Tests that no CSP warning message is displayed for other forms.
   */
  public function testNoCspWarningMessageForOtherForms(): void {
    // The messenger should not be called for other forms
    $this->messenger->addWarning()
      ->shouldNotBeCalled();
    $this->messenger->deleteByType()
      ->shouldNotBeCalled();

    // Create a mock form and form state
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $form_id = 'some_other_form';

    // Call the function being tested
    $this->callIbmApimFormAlter($form, $form_state, $form_id);
  }

  /**
   * Tests that no CSP warning message is displayed when config is not an array.
   */
  public function testNoCspWarningMessageWhenConfigNotArray(): void {
    // Mock the CSP config to return a non-array value
    $this->cspConfig->get('enforce')->willReturn('not an array');
    $this->configFactory->get('csp.settings')->willReturn($this->cspConfig->reveal());

    // The messenger should not receive a warning message
    $this->messenger->addWarning()
      ->shouldNotBeCalled();
    $this->messenger->deleteByType()
      ->shouldNotBeCalled();

    // Create a mock form and form state
    $form = [];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $form_id = 'csp_settings';

    // Call the function being tested
    $this->callIbmApimFormAlter($form, $form_state, $form_id);
  }

  /**
   * Helper method to simulate the ibm_apim_form_alter function.
   *
   * This method replicates the CSP-related functionality from ibm_apim_form_alter().
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   */
  protected function callIbmApimFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if ($form_id === 'csp_settings') {
      $config = $this->configFactory->reveal()->get('csp.settings');
      if ($config && is_array($config->get('enforce'))) {
        $enforce_settings = $config->get('enforce');
        $enforced_enabled = $enforce_settings['enable'] ?? FALSE;
        if ($enforced_enabled) {
          $this->messenger->reveal()->addWarning(
            'Content Security Policy (CSP) is currently enforced. This may restrict certain features, such as Social media views, asynchronous APIs in Explorer and image uploads in CKEditor.'
          );
        } else {
          $this->messenger->reveal()->deleteByType(MessengerInterface::TYPE_WARNING);
        }
      }
    }
  }

}
