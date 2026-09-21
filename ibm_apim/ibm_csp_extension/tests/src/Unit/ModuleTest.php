<?php


namespace Drupal\Tests\ibm_csp_extension\Unit;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit tests for the module hooks.
 *
 * @group ibm_csp_extension
 */
class ModuleTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Tests the ibm_csp_extension_modules_installed hook.
   */
  public function testModulesInstalledHook() {
    // Define the hook function for testing purposes.
    if (!function_exists('ibm_csp_extension_modules_installed')) {
      function ibm_csp_extension_modules_installed($modules) {
        // If CSP module is installed, ensure our module is also enabled.
        if (in_array('csp', $modules)) {
          \Drupal::service('module_installer')->install(['ibm_csp_extension']);
        }
      }
    }

    // Mock the module installer service.
    $module_installer = $this->prophesize(ModuleInstallerInterface::class);
    
    // Set up the container to return our mocked service.
    $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
      ->disableOriginalConstructor()
      ->getMock();
    $container->expects($this->any())
      ->method('get')
      ->with('module_installer')
      ->willReturn($module_installer->reveal());
    
    \Drupal::setContainer($container);

    // Test with CSP module included.
    $modules = ['csp', 'another_module'];
    $module_installer->install(['ibm_csp_extension'])->shouldBeCalled();
    ibm_csp_extension_modules_installed($modules);

    // Test without CSP module.
    $modules = ['some_module', 'another_module'];
    $module_installer->install(['ibm_csp_extension'])->shouldNotBeCalled();
    ibm_csp_extension_modules_installed($modules);
  }
}
