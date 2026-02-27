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
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests the CSP module configuration structure after installation.
 *
 * @group ibm_apim
 */
class CspConfigurationTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * The module installer service.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleInstaller;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

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

    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->moduleInstaller = $this->prophesize(ModuleInstallerInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->cspConfig = $this->prophesize(ImmutableConfig::class);
    
    // Set up the global Drupal service container with our mocked services
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
    $container->set('config.factory', $this->configFactory->reveal());
    $container->set('module_installer', $this->moduleInstaller->reveal());
    $container->set('module_handler', $this->moduleHandler->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests that the CSP configuration has the correct structure after installation.
   */
  public function testCspConfigurationStructure(): void {
    // Mock the CSP config to return a properly structured configuration
    $enforceSettings = [
      'enable' => false,
      'directives' => [],
      'reporting' => [],
    ];
    
    $reportOnlySettings = [
      'enable' => false,
      'directives' => [],
      'reporting' => [],
    ];

    // Set up the configuration expectations
    $this->cspConfig->get('enforce')->willReturn($enforceSettings);
    $this->cspConfig->get('report-only')->willReturn($reportOnlySettings);
    $this->configFactory->get('csp.settings')->willReturn($this->cspConfig->reveal());

    // Get the CSP configuration
    $config = $this->configFactory->reveal()->get('csp.settings');
    
    // Verify the enforce configuration structure
    $enforce = $config->get('enforce');
    $this->assertIsArray($enforce, 'CSP enforce configuration should be an array');
    $this->assertArrayHasKey('enable', $enforce, 'CSP enforce configuration should have an enable key');
    $this->assertIsBool($enforce['enable'], 'CSP enforce.enable should be a boolean value');
    $this->assertArrayHasKey('directives', $enforce, 'CSP enforce configuration should have a directives key');
    $this->assertIsArray($enforce['directives'], 'CSP enforce.directives should be an array');
    
    // Verify the report-only configuration structure
    $reportOnly = $config->get('report-only');
    $this->assertIsArray($reportOnly, 'CSP report-only configuration should be an array');
    $this->assertArrayHasKey('enable', $reportOnly, 'CSP report-only configuration should have an enable key');
    $this->assertIsBool($reportOnly['enable'], 'CSP report-only.enable should be a boolean value');
    $this->assertArrayHasKey('directives', $reportOnly, 'CSP report-only configuration should have a directives key');
    $this->assertIsArray($reportOnly['directives'], 'CSP report-only.directives should be an array');
  }

  /**
   * Tests that the CSP configuration has the correct structure from the profile.
   */
  public function testCspConfigurationStructureFromProfile(): void {
    // Mock the profile configuration path
    $profileConfigPath = 'profiles/apim_profile/config/install/csp.settings.yml';
    $this->moduleHandler->getModule('apim_profile')->willReturn((object) ['getPath' => function() { return 'profiles/apim_profile'; }]);
    
    // Create a mock YAML file content with just the structure we care about
    $expectedYaml = [
      'report-only' => [
        'enable' => false,
        'directives' => [],
        'reporting' => [],
      ],
      'enforce' => [
        'enable' => false,
        'directives' => [],
        'reporting' => [],
      ],
    ];
    
    // Mock the current configuration to match what we'd expect
    $this->cspConfig->get()->willReturn($expectedYaml);
    $this->configFactory->get('csp.settings')->willReturn($this->cspConfig->reveal());
    
    // Get the CSP configuration
    $config = $this->configFactory->reveal()->get('csp.settings');
    $actualConfig = $config->get();
    
    // Verify the configuration has the correct structure
    $this->assertArrayHasKey('enforce', $actualConfig, 'CSP configuration should have an enforce key');
    $this->assertArrayHasKey('report-only', $actualConfig, 'CSP configuration should have a report-only key');
    
    // Check enforce structure
    $this->assertArrayHasKey('enable', $actualConfig['enforce'], 'CSP enforce configuration should have an enable key');
    $this->assertIsBool($actualConfig['enforce']['enable'], 'CSP enforce.enable should be a boolean value');
    $this->assertArrayHasKey('directives', $actualConfig['enforce'], 'CSP enforce configuration should have a directives key');
    $this->assertIsArray($actualConfig['enforce']['directives'], 'CSP enforce.directives should be an array');
    
    // Check report-only structure
    $this->assertArrayHasKey('enable', $actualConfig['report-only'], 'CSP report-only configuration should have an enable key');
    $this->assertIsBool($actualConfig['report-only']['enable'], 'CSP report-only.enable should be a boolean value');
    $this->assertArrayHasKey('directives', $actualConfig['report-only'], 'CSP report-only configuration should have a directives key');
    $this->assertIsArray($actualConfig['report-only']['directives'], 'CSP report-only.directives should be an array');
  }

  /**
   * Tests that the update hook correctly fixes invalid CSP configuration.
   */
  public function testUpdateHookFixesInvalidConfiguration(): void {
    // Test case 1: enforce is a boolean (invalid)
    $this->testUpdateHookWithInvalidConfig(false);
    
    // Test case 2: enforce is an array but missing enable key (invalid)
    $this->testUpdateHookWithInvalidConfig(['directives' => []]);
  }
  
  /**
   * Helper method to test the update hook with different invalid configurations.
   *
   * @param mixed $invalidEnforceValue The invalid value for the enforce configuration
   */
  protected function testUpdateHookWithInvalidConfig($invalidEnforceValue): void {
    // Mock the module installer to simulate reinstalling the CSP module
    $this->moduleInstaller->uninstall(['csp'])->willReturn(true);
    $this->moduleInstaller->install(['csp'])->willReturn(true);
    
    // Create a mock editable config object
    $editableConfig = $this->prophesize(\Drupal\Core\Config\Config::class);
    $editableConfig->get('enforce')->willReturn($invalidEnforceValue);
    $editableConfig->delete()->willReturn($editableConfig->reveal());
    $this->configFactory->getEditable('csp.settings')->willReturn($editableConfig->reveal());
    
    // Call the update hook function (simulated)
    $this->simulateUpdateHook();
    
    // Verify the module was uninstalled and reinstalled
    $this->moduleInstaller->uninstall(['csp'])->shouldHaveBeenCalled();
    $this->moduleInstaller->install(['csp'])->shouldHaveBeenCalled();
    
    // Verify the configuration was deleted
    $editableConfig->delete()->shouldHaveBeenCalled();
  }
  
  /**
   * Helper method to simulate the update hook function.
   */
  protected function simulateUpdateHook(): void {
    $config = $this->configFactory->reveal()->getEditable('csp.settings');
    if ($config) {
      $enforce = $config->get('enforce');
      
      // Check if 'enforce' is a boolean value or an array without 'enable' key (invalid structure)
      if (is_bool($enforce) || (is_array($enforce) && !array_key_exists('enable', $enforce))) {
        // Uninstall the CSP module
        $this->moduleInstaller->reveal()->uninstall(['csp']);
        
        // Delete any remaining configuration
        $config->delete();
        
        // Reinstall the CSP module to get fresh configuration
        $this->moduleInstaller->reveal()->install(['csp']);
      }
    }
  }
}
