<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
namespace Drupal\apic_app\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * The tests in this testcase are testing the actual functionality of the form
 * used for editing the details of an existing application.
 *
 * @group apic_app
 */
class ApplicationEditAppTest extends WebTestBase {

  /**
   * Our module dependencies.
   *
   * In Drupal 8's SimpleTest, we declare module dependencies in a public
   * static property called $modules. WebTestBase automatically enables these
   * modules for us.
   *
   * @var array
   */
  static public $modules = ['apic_app', 'ibm_apim'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * A valid user to carry out application actions
   */
  protected $validUser = NULL;

  /**
   * votingapi_widget seems to fail the strict validator
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Setup basic environment.
   */
  protected function setUp() {
    parent::setUp();
    $this->validUser = $this->createUser();
    //    $this->drupalLogin($this->validUser);

    // Create a new app for the test cases at this point ?
  }

  /**
   * REMOVE THIS EXAMPLE TEST WHEN ACTUAL TESTS ARE IMPLEMENTED
   */
  public function testTrue() {
    $this->assertTrue(TRUE);
  }

  /**
   * Tests that the page for the application renders
   */
  public function disabledtestRenderApplicationDetails() {
    // Get the applications details page

    // Assert the page loads correctly

  }

  /**
   * Tests that the application edit form renders
   */
  public function disabledtestRenderApplicationEditForm() {
    // Get the 'Edit application' form
    //$this->drupalGet('/application/<id>/edit');
    //$this->assertResponse('200');

    //$this->assertText(t('Edit application'), 'Expect to find the \'Edit application\' string on the page.');

    // Assert the form fields contain the existing details of the application
  }


  /**
   * Tests that the edit form fails to submit when the app title is empty
   */
  public function disabledtestEditApplicationWithEmptyTitle() {
    // Build up the form data with an empty title

    // Submit form with empty title filed

    // $this->assertText(t('ERROR: Title is a required field'), 'Expect to find the \'ERROR: Title is a required field\' string on the page.');
  }

  /**
   * Tests that an application can be successfully updated via the edit application form
   * and the updated details are displayed correctly
   */
  public function disabledtestEditApplication() {
    // Build up the updated form data with a populated title

    // Submit the form data

    // Assert a 200 response
    // Assert a success message on the page
    // Assert the fields on the page are populated with the response data
  }

  /**
   * Tests that the cancel button on the edit application form returns to
   * the applications details page
   */
  public function disabledtestCancelEditApplication() {
    // Build up some form data

    // Trigger the cancel button

    // Assert a 200 response
    // Assert the fields on the page are correctly populated
  }

}