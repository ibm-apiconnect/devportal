<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apictest\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Context for entity autocomplete access checks.
 */
class EntityAutocompleteContext implements Context {

  /**
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  private MinkContext $minkContext;

  /**
   * Captured data-autocomplete-path.
   *
   * @var string
   */
  private string $lastAutocompletePath = '';

  /**
   * Grab MinkContext.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope): void {
    $environment = $scope->getEnvironment();
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

  /**
   * Assert the current response is JSON and well-formed.
   *
   * @Then the response should be in JSON format
   */
  public function theResponseShouldBeInJsonFormat(): void {
    $session = $this->minkContext->getSession();
    $headers = array_change_key_case($session->getResponseHeaders(), CASE_LOWER);

    if (!isset($headers['content-type'][0])) {
      throw new \Exception('No Content-Type header found in response');
    }

    $contentType = strtolower($headers['content-type'][0]);
    if (strpos($contentType, 'application/json') === false) {
      throw new \Exception(sprintf(
        'Response content type is "%s", expected application/json',
        $contentType
      ));
    }

    // Validate JSON body.
    $content = $session->getDriver()->getContent();
    json_decode($content);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception('Response is not valid JSON: ' . json_last_error_msg());
    }
  }

  /**
   * @Given I am logged out
   */
  public function iAmLoggedOut(): void {
    $this->minkContext->getSession()->restart();
  }

  /**
   * Visit a path without following redirects (BrowserKit required).
   *
   * @When I request without following redirects :path
   */
  public function iRequestWithoutFollowingRedirects(string $path): void {
    $session = $this->minkContext->getSession();
    $driver = $session->getDriver();

    if (!method_exists($driver, 'getClient')) {
      throw new \RuntimeException('Driver does not support getClient(); BrowserKit is required for this step.');
    }

    $client = $driver->getClient();
    $client->followRedirects(false);

    try {
      // Resolves relative paths against base_url.
      $this->minkContext->visitPath($path);
    }
    finally {
      $client->followRedirects(true);
    }
  }

  /**
   * Capture the data-autocomplete-path from a field and store it.
   *
   * @When I capture the autocomplete path from the :field field
   */
  public function captureAutocompletePathFromField(string $field): void {
    $page = $this->minkContext->getSession()->getPage();

    // Try by field name/id/label; fallback to data-drupal-selector="...".
    $el = $page->findField($field) ?: $page->find('css', sprintf('[data-drupal-selector="%s"]', $field));
    if (!$el) {
      throw new \RuntimeException("Field '$field' not found.");
    }

    $path = $el->getAttribute('data-autocomplete-path');
    if (!$path) {
      throw new \RuntimeException("Field '$field' is missing data-autocomplete-path.");
    }

    $this->lastAutocompletePath = $path;
  }

  /**
   * Assert an autocomplete path has been captured.
   *
   * @Then the autocomplete path should be captured
   */
  public function assertAutocompletePathCaptured(): void {
    if (empty($this->lastAutocompletePath)) {
      throw new \RuntimeException('No autocomplete path has been captured.');
    }
  }

  /**
   * Request the captured autocomplete path with a query, no redirects.
   *
   * @When I request the captured autocomplete path with query :query without following redirects
   */
  public function requestCapturedAutocompletePathWithQueryNoRedirects(string $query): void {
    $this->assertAutocompletePathCaptured();

    $path = $this->lastAutocompletePath;
    $sep = (strpos($path, '?') === false) ? '?' : '&';
    $pathWithQuery = $path . $sep . $query;

    $this->iRequestWithoutFollowingRedirects($pathWithQuery);
  }

  /**
   * @Given I have the :permission permission
   */
  public function iHaveThePermission($permission) {
    $account = \Drupal::currentUser();
    if (!$account || $account->id() == 0) {
      throw new \Exception('No user is logged in');
    }

    $user = \Drupal\user\Entity\User::load($account->id());
    if (!$user) {
      throw new \Exception('Could not load user account');
    }

    foreach ($user->getRoles() as $role_id) {
      $role = \Drupal\user\Entity\Role::load($role_id);
      if ($role && !$role->hasPermission($permission)) {
        $role->grantPermission($permission);
        $role->save();
      }
    }
  }
}
