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
 * The tests in this testcase are testing the actual functionality of the forms
 * used in creating, editing and deleting application credentials.
 *
 * @group apic_app
 */
class ApplicationCredentialsTest extends WebTestBase {

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
   * Tests that the application add credentials form renders
   */
  public function disabledtestRenderCredentialsCreateForm() {
    // Get the 'Request additional client credentials' form
    //$this->drupalGet('/application/<id>/add-clientcreds');
    //$this->assertResponse('200');

    //$this->assertText(t('Request additional client credentials'), 'Expect to find the \'Request additional client credentials\' string on the page.');

    // Assert the form fields contain the existing details of the application
  }

  /**
   * Tests that additional credentials can be added
   * and the new credentials are displayed appropriately
   */
  public function disabledtestAddCredentials() {
    // Build up the form data

    // Submit the form data

    // Assert a 200 response
    // Assert a success message on the page with populated client id / secret that can be viewed via toggling the checkboxes ?
    // Assert the fields on the page are populated with the response data
  }

  /**
   * Tests that the edit credentials form renders
   */
  public function disabledtestRenderEditCredentialsForm() {
    // Get the 'Update client credentials' form
    //$this->drupalGet('/application/<id>/update-clientcreds/<creds_id>');
    //$this->assertResponse('200');

    //$this->assertText(t('Update client credentials'), 'Expect to find the \'Update client credentials\' string on the page.');

    // Assert the form fields contains the credentials description
  }

  /**
   * Tests that the application credentials (description) can be updated
   * and the updated description is displayed
   */
  public function disabledtestEditCredentials() {
    // Update the description and submit the form data

    // Assert a 200 response
    //$this->assertText(t('Application credentials updated'), 'Expect to find the \'Application credentials updated\' string on the page.');

    // Assert the credentials description field has been updated
  }

  /**
   * Tests that the reset client ID form renders correctly
   */
  public function disabledtestRenderResetClientIdForm() {
    // Get the reset client id form
    //$this->drupalGet('/application/<id>/reset-clientid/<creds_id>');
    //$this->assertResponse('200');

    //$this->assertText(t('Are you sure you want to reset your client ID?'), 'Expect to find the \'Are you sure you want to reset your client ID?\' string on the page.');

    // Assert the form contains the Reset button
  }

  /**
   * Tests that the client ID can be reset
   */
  public function disabledtestResetClientId() {
    // Trigger the reset button

    // Assert a 200 response
    //$this->assertText(t('Application Client ID reset'), 'Expect to find the \'Application Client ID reset\' string on the page.');

    // Assert the client id has been updated by clicking the the show check box in the notification and making sure it is the same the
    // id when clicking the show check box in the credentials section
  }

  /**
   * Tests the cancel button on the reset client ID form
   */
  public function disabledtestCancelResetClientId() {
    // Trigger the cancel button

    // Assert the application details page is loaded
  }

  /**
   * Tests that the reset client secret form renders correctly
   */
  public function disabledtestRenderResetClientSecretForm() {
    // Get the reset client secret form
    //$this->drupalGet('/application/<id>/reset-secret/<creds_id>');
    //$this->assertResponse('200');

    //$this->assertText(t('Are you sure you want to reset your client secret?'), 'Expect to find the \'Are you sure you want to reset your client secret?\' string on the page.');

    // Assert the form contains the Reset button
  }

  /**
   * Tests the cancel button on the reset client secret form
   */
  public function disabledtestCancelResetClientSecret() {
    // Trigger the cancel button

    // Assert the application details page is loaded
  }

  /**
   * Tests that the client secret can be reset
   */
  public function disabledtestResetClientSecret() {
    // Trigger the reset button

    // Assert a 200 response
    //$this->assertText(t('Application secret reset'), 'Expect to find the \'Application secret reset\' string on the page.');

    // Assert the client secret has been updated by clicking the the show check box in the notification and making sure it is the same the
    // secret when clicking the show check box in the credentials section
  }

  /**
   * Tests that the verify client secret form renders correctly
   */
  public function disabledRenderVerifyApplicationSecretForm() {
    // Get the verify client secret form
    //$this->drupalGet('/application/<id>/verify/<creds_id>');
    //$this->assertResponse('200');

    //$this->assertText(t('Verify application secret?'), 'Expect to find the \'Verify application secret?\' string on the page.');

    // Assert the form contains the client secret field
  }

  /**
   * Tests that the client secret can be verified
   */
  public function disabledtestVerifyClientSecret() {
    // Build up the form data with the client secret

    // Submit the form

    // Assert a 200 response
    //$this->assertText(t('Application secret verified successfully'), 'Expect to find the \'Application secret verified successfully\' string on the page.');
  }

  /**
   * Tests that the delete credentials form renders correctly
   */
  public function disabledtestRenderDeleteCredentialsForm() {
    // Get the verify client secret form
    //$this->drupalGet('/application/<id>/delete-clientcreds/<creds_id>');
    //$this->assertResponse('200');

    //$this->assertText(t('Verify application secret?'), 'Expect to find the \'Verify application secret?\' string on the page.');

    // Assert the form contains the delete button
  }

  /**
   * Tests that the application credentials can be deleted
   */
  public function disabledtestDeleteCredentials() {
    // Trigger the delete button

    // Assert a 200 response
    //$this->assertText(t('Application credentials deleted'), 'Expect to find the \'Application credentials deleted\' string on the page.');
  }

}