<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apictest\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Testwork\Hook\Scope\SuiteScope;
use Drupal\apictest\MockServiceHandler;
use Drupal\apictest\TestData\TestData;
use Drupal\Core\Database\Database;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines application features from the specific context.
 */
class IBMPortalContext extends DrupalContext implements SnippetAcceptingContext {

  private array $apicUsers = [];

  private TestData $testData;

  private $minkContext;

  private $timestamp;

  private $debugDumpDir;

  private int $htmlDumpNumber;

  private $siteDirectory;

  private $modulesDirectory;

  /**
   * @var bool
   */
  private bool $useMockServices;

  private $dumpHtmlToScreen;

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   *
   * @throws \Exception
   */
  public function __construct($siteDirectory, $modulesDirectory, $useMockServices, $dumpHtmlToScreen, $debugDumpDir = '/tmp', $testDataScenario = 'mocked', $userRegistry = 'lur', $mockSiteConfig = TRUE) {

    if ($siteDirectory === NULL || $siteDirectory === '') {
      throw new \Exception("No value or empty value specified for 'siteDirectory' parameter. Check your behat.yml configuration.");
    }
    if (!file_exists($siteDirectory)) {
      throw new \Exception("The directory '$siteDirectory' does not exist. Check your behat.yml configuration.");
    }

    // Parameters passed in via behat.yml
    $this->siteDirectory = $siteDirectory;
    $this->modulesDirectory = $modulesDirectory;
    $this->useMockServices = $useMockServices;
    $this->dumpHtmlToScreen = $dumpHtmlToScreen;

    if ($debugDumpDir === '/tmp' || $debugDumpDir === NULL || empty($debugDumpDir)) {
      // Make the debug dump directory a little more unique
      $debugDumpDir = '/tmp/behat-html-dumps-' . uniqid("", FALSE);
    }

    if (!is_dir($debugDumpDir) && !mkdir($debugDumpDir, 0777, TRUE) && !is_dir($debugDumpDir)) {
      print "ERROR: Failed to create $debugDumpDir\n";
    }
    $this->debugDumpDir = $debugDumpDir;

    // This allows us to replace @now with a unique timestamp so tests can be run multiple times
    $this->timestamp = microtime(TRUE);

    // An incrementing counter so that we can create uniquely named HTML dump files for debugging
    $this->htmlDumpNumber = 1;

    // The data for use generally in this scenario
    $this->testData = new TestData($testDataScenario);

    // Ensure captcha is always presented for deterministic behat tests
    // captcha persistence 0 = CAPTCHA_PERSISTENCE_SHOW_ALWAYS
    if ((int) \Drupal::config('captcha.settings')->get('persistence') !== 0) {
      print('Setting captcha persistence to always present it.\n\n');
      \Drupal::service('config.factory')->getEditable('captcha.settings')->set('persistence', 0)->save();
    }
  }

  // ************************************************************************************************************
  // This section contains Before* and After* functions that handle setup and teardown of scenarios and steps
  // as well as utility functions used internally by our step definitions.
  // ************************************************************************************************************

  /** @BeforeSuite */
  public static function setup(BeforeSuiteScope $scope): void {
    $params = self::getContextParameters($scope, IBMPortalContext::class);
    if ($params['useMockServices'] === TRUE) {
      MockServiceHandler::install($params['siteDirectory'], $params['modulesDirectory'], $params['userRegistry'], TRUE);
    }
  }

  /** @AfterSuite */
  public static function teardown(AfterSuiteScope $scope): void {
    $params = self::getContextParameters($scope, IBMPortalContext::class);
    if ($params['useMockServices'] === TRUE) {
      MockServiceHandler::uninstall($params['siteDirectory']);
    }
  }

  /** @AfterFeature */
  public static function afterFeature(AfterFeatureScope $scope): void {
    print "Clearing drupal caches\n";
    drupal_flush_all_caches();
  }

  /** @BeforeScenario */
  public function beforeScenario($event): void {

    // If we want to chain multiple steps together, we need access to the MinkContext. Grab that here.
    $environment = $event->getEnvironment();
    $this->minkContext = $environment->getContext(MinkContext::class);

  }

  /** @AfterScenario */
  public function afterScenario($event): void {

    // Prevent the drupal context cleanup code from deleting management node users
    foreach ($this->apicUsers as $nextUser) {
      $this->getUserManager()->removeUser($nextUser->name);
    }

    // Force log out if the test scenario didn't explicitly log out at the end
    $this->assertAnonymousUser();

    // reset registries
    print "Resetting to default user registry.";
    $this->resetToDefaultRegistry();


    // NOTE - we are not shutting down zombie here. This is a deliberate choice!
    //        We already have code that detects zombie is started so we don't get multiple instances.
    //        Not shutting down between scenario is a slight performance boost.
    //        And the main reason - cleanup code fails if we shut down the session before all of behat
    //        has finished running whatever cleanup code exists in the DrupalContext / MinkContext files.

  }

  /** @AfterStep */
  public function afterEachStep(AfterStepScope $stepScope): void {

    if (!$stepScope->getTestResult()->isPassed()) {
      // The previous step failed. Dump out the HTML of the page for debugging purposes.
      $this->dumpTheCurrentHtml();
    }

  }

  /**
   * Utility function to find and return an element or elements with an id or name matching the
   * specified string. If no element is found and $failifNotFound is set to TRUE, an Exception
   * will be thrown.
   **
   *
   * @throws \Exception
   */
  public function findElementsByIdOrName($elementIdOrName, $failIfNotFound = FALSE) {
    $page = $this->getSession()->getPage();
    $field = $page->find('named', ['id_or_name', $elementIdOrName]);

    if ($field !== NULL || ((is_array($field) || is_countable($field)) && sizeof($field) !== 0)) {
      return $field;
    }

    if ($failIfNotFound) {
      throw new \Exception("Field $elementIdOrName was not on the page.");
    }

    return NULL;

  }

  /**
   * Simple utility function to look up the tags attached to this scenario
   * and check if the tag specified is one of them.
   *
   * @param $event
   *  BeforeScenario event object
   * @param $tag
   *  String representing the name of the tag
   *
   * @return bool
   */
  public function scenarioHasTag($event, $tag): bool {
    return array_search($tag, $event->getScenario()->getTags(), TRUE);
  }

  /**
   * Get the parameters defined in behat.yml for a context.
   *
   * Note this is static to be used in the before|afterSuite hooks.
   *
   * @param SuiteScope $scope
   *   Suite to be executed (as defined in behat.yml).
   * @param string $name
   *   Name of the context to get parameters for.
   *
   * @return array
   *   Associative array of parameters.
   */
  private static function getContextParameters(SuiteScope $scope, string $name): ?array {
    $contexts = $scope->getSuite()->getSetting('contexts');
    $settings = NULL;
    foreach ($contexts as $ctx) {
      if (\is_array($ctx)) {
        $settings = $ctx[$name];
      }
    }
    if ($settings) {
      return $settings;
    }

    print 'parameters not found for ' . $name . 'context\n';
    return NULL;

  }

  // ************************************************************************************************************
  // This section contains custom step definitions for use in our feature files
  // ************************************************************************************************************

  /**
   * @Transform /.+/
   *
   * The @Transform annotation allows us to transform arguments in step definitions before they are used in that
   * step definition. In this way we can replace placeholder values with generated values at runtime.
   *
   * Currently supported are :
   *
   * @now        -    timestamp from the point where the scenario began. Unique for each scenario.
   * @captcha    -    fish the last captcha solution out of the database.
   * @uid        -    uid of specified user or current user if no parameter provided.
   */
  public function processArgument($argument) {

    // The very first replacement we need to do is a test data lookup. This lookup will leave any other
    // @placeholders unchanged so that they can be replaced as well.
    if (strpos($argument, '@data') !== FALSE) {
      $argument = $this->testData->insertTestData($argument);
      print "Inject test data in to argument resulted in : $argument\n";
    }

    // Replace @now with a timestamp
    if (strpos($argument, '@now') !== FALSE) {
      $argument = str_replace('@now', $this->timestamp, $argument);
    }

    // Replace @captcha with the correct captcha solution from the database
    if (strpos($argument, '@captcha') !== FALSE) {
      $db = Database::getConnection();
      if ($db->schema()->tableExists("captcha_sessions")) {
        $captchaSolution = $db->query("SELECT `solution`, `csid` FROM `captcha_sessions` ORDER BY csid DESC LIMIT 1;")->fetchField();
        print "The captcha solution is $captchaSolution\n";
        $argument = str_replace('@captcha', $captchaSolution, $argument);
      }

      print "There is no captcha_sessions database table. Can't replace @captcha placeholder\n";
    }

    if (strpos($argument, '@uid') !== FALSE) {
      $argument = $this->processUid($argument);
    }

    return $argument;

  }

  /**
   * Process the @uid annotation.
   * Without a parameter this returns the uid of the current user.
   * With a parameter (e.g. @uid(@data(andre.name))) it will look up that users uid.
   *
   * @param $argument
   *
   * @return array|string|string[]
   */
  private function processUid($argument) {
    $parameter_check = '@uid(';

    $uid = NULL;
    $chunktoreplace = NULL;

    if (strpos($argument, $parameter_check) === FALSE) {
      // no parameter so use current user id
      $chunktoreplace = '@uid';

      // if this is a real stack test, the user->id field may not be set yet
      // so we need to go and get if from the db.
      $current_user = $this->getUserManager()->getCurrentUser();

      if (!isset($current_user->uid) || $current_user->uid === NULL) {
        $ids = \Drupal::entityQuery('user')->execute();
        $users = User::loadMultiple($ids);

        $current_user_registry_url = $current_user->registry_url ?? NULL;

        foreach ($users as $drupal_user) {
          if ($drupal_user->get('registry_url') !== NULL) {
            $drupal_user_registry_url = $drupal_user->get('registry_url')->value;
          }
          else {
            $drupal_user_registry_url = NULL;
          }

          if ($drupal_user->getAccountName() === $current_user->name && $current_user_registry_url === $drupal_user_registry_url) {
            $current_user->uid = $drupal_user->id();
            break;
          }
        }
      }

      $uid = $current_user->uid;

    }
    else {

      // get the parameter which will be desired username.
      // format = @uid(username)
      // need to get both the full parameter to replace...
      $startofparam = strpos($argument, $parameter_check);
      $endofparam = strpos($argument, ')') + 1;
      $lengthofparam = $endofparam - $startofparam;

      $chunktoreplace = substr($argument, $startofparam, $lengthofparam);

      // and extract the username to search on.
      $startofusername = \strlen($parameter_check);
      $username = substr($chunktoreplace, $startofusername, -1);

      $ids = \Drupal::entityQuery('user')->execute();
      $users = User::loadMultiple($ids);

      print 'searching for username: ' . $username . "\n";

      foreach ($users as $drupal_user) {
        if ($drupal_user->getAccountName() === $username) {
          $uid = $drupal_user->id();
          break;
        }
      }
    }

    print 'replacing: ' . $chunktoreplace . ' with ' . $uid . "\n";

    return str_replace($chunktoreplace, $uid, $argument);

  }

  /**
   * @Transform table:name,mail,status
   * @Transform table:name,mail,pass,status
   * @Transform table:name,mail,pass,status,first_time_login
   * @Transform table:name,mail,pass,status,registry_url
   * @Transform table:uid,name,mail,pass,registry_url
   * @Transform table:title,name,owner,id
   * @Transform table:title,name,id,owner
   * @Transform table:title,name,id,owner_uid
   * @Transform table:type,title,url,user_managed,default
   * @Transform table:consumerorgid,username,roles
   * @Transform table:title,id,document
   * @Transform table:name,title,id,document
   * @Transform table:title,id,org_id
   * @Transform table:org_id,app_id,sub_id,product,plan
   * @Transform table:consumerorgid,mail,roles
   *
   * Annoyingly, this function will only match the specifically listed
   * tables. If you need a different table processing, add another row of:
   * @Transform table:<column1>,<column2>... etc
   */
  public function processTableArguments(TableNode $table): TableNode {

    $hash = $table->getTable();
    $newHash = [];

    foreach ($hash as $tableRow) {
      $newTableRow = [];
      foreach ($tableRow as $value) {
        $newTableRow[] = $this->processArgument($value);
      }
      $newHash[] = $newTableRow;
    }

    return new TableNode($newHash);
  }

  /**
   * @Then print the current consumerorg
   *
   * Useful for debugging purposes
   */
  public function printTheConsumerorg(): void {
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    $orgs = $userUtils->loadConsumerOrgs();
    print('Current org: ' . serialize($org) . '\n');
    print('LoadConsumerOrgs: ' . serialize($orgs) . '\n');
    $sessionStore = \Drupal::service('tempstore.private')->get('ibm_apim');
    $perms = $sessionStore->get('permissions');
    print('Permissions: ' . serialize($perms) . '\n');

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
    $orgObject = NULL;
    if ($org['url'] !== NULL) {
      $orgObject = $consumerOrgService->get($org['url']);
    }
    if ($orgObject !== NULL) {
      print('\nCurrent consumerorg node ID: ' . $orgObject->getId() . '\n');
    }
    else {
      print('ERROR: Failed to load Consumerorg node.\n');
    }
  }

  /**
   * @Then Enable ACLDebug
   */
  public function enableACLDebug(): void {
    if (!(boolean) \Drupal::config('ibm_apim.devel_settings')->get('acl_debug')) {
      print('Setting \'acl_debug\' config to true. ');
      \Drupal::service('config.factory')->getEditable('ibm_apim.devel_settings')->set('acl_debug', TRUE)->save();
    }
  }

  /**
   * @Then Disable ACLDebug
   */
  public function disableACLDebug(): void {
    if (!(boolean) \Drupal::config('ibm_apim.devel_settings')->get('acl_debug')) {
      print('Setting \'acl_debug\' config to false. ');
      \Drupal::service('config.factory')->getEditable('ibm_apim.devel_settings')->set('acl_debug', FALSE)->save();
    }
  }

  /**
   * @Given I have an analytics service
   */
  public function enableAnalytics(): void {
    $analyticsService = \Drupal::service('ibm_apim.analytics');
    $analyticsObject = [
      'type' => 'analytics_service',
      'api_version' => '2.0.0',
      'id' => '5391d980-6a0a-449e-bd68-6b21abf1d826',
      'name' => 'analytics-service-1',
      'title' => 'Analytics Service 1',
      'summary' => 'Analytics Service 1',
      'client_endpoint' => 'https://9.7.7.7:4046',
      'client_endpoint_tls_client_profile_url' => '/api/orgs/6f66dfed-e4c8-4a32-9831-a91c8d7113f3/tls-client-profiles/0f7fef97-1404-4d9f-8733-e878dc230f82',
      'endpoint' => 'https://9.7.7.7:4046',
      'ingestion_endpoint' => 'https://9.7.7.7:4046/ingestion',
      'ingestion_endpoint_tls_client_profile_url' => '/api/orgs/6f66dfed-e4c8-4a32-9831-a91c8d7113f3/tls-client-profiles/b2c8fb7b-e757-47a6-a310-5c7cb33ddff1',
      'shadow' => FALSE,
      'metadata' => NULL,
      'created_at' => '2018-02-26T21:51:52.246Z',
      'updated_at' => '2018-02-26T22:32:48.979Z',
      'org_url' => '/api/orgs/6f66dfed-e4c8-4a32-9831-a91c8d7113f3',
      'url' => '/api/orgs/6f66dfed-e4c8-4a32-9831-a91c8d7113f3/availability-zones/f9e0d1d0-1ee9-4544-ab20-7b6788b66dac/analytics-services/5391d980-6a0a-449e-bd68-6b21abf1d826',
    ];
    $analyticsService->updateAll([$analyticsObject]);
  }

    /**
   * @Given :arg1 entities have :arg2 type custom fields
   */
  public function addEntityCustomField($entity, $type): void
  {
    $singleName = 'field_single' . ($type);
    $multiName = 'field_multi' . ($type);
    $bundle = 'node';
    $entityType = '';
    if ($entity == 'user') {
      $bundle = 'user';
      $entityType = 'user';
    } else if ($entity == 'application') {
      $entityType = 'node';
      $bundle = 'application';
    } else if ($entity == 'consumerorg') {
      $entityType = 'node';
      $bundle = 'consumerorg';
    }
    if (FieldStorageConfig::load($entityType . '.' . $singleName)) {
      FieldStorageConfig::load($entityType . '.' . $singleName)->delete();
    }

      $fieldStorageConfig = FieldStorageConfig::create([
        'field_name' => $singleName,
        'entity_type' => $entityType,
        'type' => $type,
        'required' => FALSE,
        'cardinality' => 1,
      ]);
      $fieldStorageConfig->save();

    if (FieldConfig::load($entityType . '.' . $bundle . '.' . $singleName)) {
      FieldConfig::load($entityType . '.' . $bundle . '.' . $singleName)->delete();
    }
      $fieldConfig = FieldConfig::create([
        'field_name' => $singleName,
        'entity_type' => $entityType,
        'bundle' => $bundle,
        'label' => 'Single ' .  ($type),
      ]);
      $fieldConfig->save();

    if (FieldStorageConfig::load($entityType . '.' .  $multiName)) {
      FieldStorageConfig::load($entityType . '.' .  $multiName)->delete();
    }
      $fieldStorageConfig = FieldStorageConfig::create([
        'field_name' => $multiName,
        'entity_type' => $entityType,
        'type' => $type,
        'required' => FALSE,
        'cardinality' => 2,
      ]);
      $fieldStorageConfig->save();
    if (FieldConfig::load($entityType . '.' . $bundle . '.' . $multiName)) {
      FieldConfig::load($entityType . '.' . $bundle . '.' . $multiName)->delete();
    }
      $fieldConfig = FieldConfig::create([
        'field_name' => $multiName,
        'entity_type' => $entityType,
        'bundle' => $bundle,
        'label' => 'Multi  ' .  ($type),
      ]);
      $fieldConfig->save();


    $formDisplay = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load($entityType . '.' . $bundle . '.default');
    if ($formDisplay !== NULL) {
      $formDisplay->setComponent($singleName, []);
      $formDisplay->setComponent($multiName, []);
      $formDisplay->save();
    }
    if ($entity == 'user') {
      $formDisplay = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('user.user.register');
      if ($formDisplay !== NULL) {
        $formDisplay->setComponent($singleName, []);
        $formDisplay->setComponent($multiName, []);
        $formDisplay->save();
      }
    }
    \Drupal::service('entity_display.repository')->getViewDisplay($entityType, $bundle, 'default')->setComponent($singleName, [])->save();
    \Drupal::service('entity_display.repository')->getViewDisplay($entityType, $bundle, 'default')->setComponent($multiName, [])->save();
  }

  /**
   * @Given I delete the :arg1 type custom fields for :arg2 entities
   */
  public function deleteCustomField($type, $entity): void
  {
    $bundle = 'node';
    $entityType = '';
    if ($entity == 'user') {
      $bundle = 'user';
      $entityType = 'user';
    } else if ($entity == 'application') {
      $entityType = 'node';
      $bundle = 'application';
    } else if ($entity == 'consumerorg') {
      $entityType = 'node';
      $bundle = 'consumerorg';
    }
    $singleName = 'field_single' . ($type);
    $multiName = 'field_multi' . ($type);
    if (FieldConfig::load($entityType . '.' . $bundle . '.' . $singleName)) {
      FieldConfig::load($entityType . '.' . $bundle . '.' . $singleName)->delete();
    }
    if (FieldStorageConfig::load($entityType . '.' .$singleName)) {
      FieldStorageConfig::load($entityType . '.' . $singleName)->delete();
    }
    if (FieldConfig::load($entityType . '.' . $bundle . $multiName)) {
      FieldConfig::load($entityType . '.' . $bundle . $multiName)->delete();
    }
    if (FieldStorageConfig::load($entityType . '.' . $multiName)) {
      FieldStorageConfig::load($entityType . '.' . $multiName)->delete();
    }
  }
  /**
   * @Given I do not have an analytics service
   */
  public function disableAnalytics(): void {
    $analyticsService = \Drupal::service('ibm_apim.analytics');
    $analyticsService->deleteAll();
  }

  /**
   * @Given I have a billing service
   */
  public function enableBilling(): void {
    $integrationService = \Drupal::service('ibm_apim.payment_method_schema');
    $integrationObject = [
      'type' => 'integration',
      'integration_type' => 'payment_method',
      'api_version' => '2.0.0',
      'id' => '802725f9-feb8-424a-a82b-6e2c5307feef',
      'name' => 'creditcard',
      'title' => 'Credit Card',
      'summary' => 'Credit Card',
      'state' => 'enabled',
      'remote' => TRUE,
      'integration' => [
        'integration' => '1.0.0',
        'integration_type' => 'payment_method',
        'info' => [
          'name' => 'creditcard',
          'version' => '1.0.0',
          'title' => 'Credit Card',
          'summary' => 'Credit Card',
        ],
        'configuration_schema' => [
          'required' => [
            'token',
            'owner_email',
          ],
          'token' => '********',
          'owner_email' => [
            'type' => 'string',
          ],
          'owner_name' => [
            'type' => 'string',
            'readOnly' => TRUE,
          ],
          'expiration_year' => [
            'type' => 'string',
            'readOnly' => TRUE,
          ],
          'expiration_month' => [
            'type' => 'string',
            'readOnly' => TRUE,
          ],
          'last_4' => [
            'type' => 'string',
            'readOnly' => TRUE,
          ],
        ],
      ],
      'created_at' => '2020-06-15T17:32:10.000Z',
      'updated_at' => '2020-06-15T17:39:43.462Z',
      'url' => '/api/cloud/integrations/payment-method/802725f9-feb8-424a-a82b-6e2c5307feef',
    ];
    $integrationService->updateAll([$integrationObject]);

    $billingService = \Drupal::service('ibm_apim.billing');
    $billingObject = [
      'type' => 'configured_billing',
      'api_version' => '2.0.0',
      'id' => 'e30e74c1-38d7-4e2f-87c5-1c522de70ff3',
      'name' => '7c751639-36af-417d-8956-1a8dab69dd27',
      'title' => '7c751639-36af-417d-8956-1a8dab69dd27',
      'summary' => NULL,
      'billing_url' => '/consumer-api/orgs/b33d299b-7eca-45ad-bca1-151323974cea/billings/7c751639-36af-417d-8956-1a8dab69dd27',
      'scope' => 'catalog',
      'original_id' => '7c751639-36af-417d-8956-1a8dab69dd27',
      'payment_method_type_url' => '/api/cloud/integrations/billing/3860269e-f565-4ca1-951f-8a24d5c5b847',
      'payment_method_integration_urls' => [
        '/api/cloud/integrations/payment-method/802725f9-feb8-424a-a82b-6e2c5307feef',
      ],
      'configuration' => [
        'full_access_key' => '********',
        'publishable_key' => 'pk_test_d9gRFVrEcAkTFEeZ1tDoNMwO',
      ],
      'owned' => NULL,
      'custom_endpoint' => NULL,
      'metadata' => [
        'testmode' => 'true',
        'account_id' => 'acct_1B3S4PE4V1V7vQNs',
      ],
      'created_at' => '2018-02-26T21:51:52.246Z',
      'updated_at' => '2018-02-26T22:32:48.979Z',
      'org_url' => '/consumer-api/orgs/b33d299b-7eca-45ad-bca1-151323974cea',
      'catalog_url' => '/api/catalogs/b33d299b-7eca-45ad-bca1-151323974cea/f3149806-a4b4-4e80-ae99-683ac7c98047',
      'url' => '/api/catalogs/b33d299b-7eca-45ad-bca1-151323974cea/f3149806-a4b4-4e80-ae99-683ac7c98047/configured-billings/e30e74c1-38d7-4e2f-87c5-1c522de70ff3',
    ];
    $billingProviders = [$billingObject];
    $billingService->updateAll($billingProviders);
    $newMapping = [$billingObject['name'] => 'ibm_create_payment_method'];
    \Drupal::configFactory()->getEditable('ibm_apim.settings')
      ->set('billing_providers', serialize($newMapping))
      ->save();
  }

    /**
   * @Given I have a payment method
   */
  public function addPaymentMethod() {
    $paymentMethodObject = [
      'id' => "paymentMethodId",
      'title' => "paymentMethod",
      'billing_url' => "/api/catalogs/b33d299b-7eca-45ad-bca1-151323974cea/f3149806-a4b4-4e80-ae99-683ac7c98047/configured-billings/e30e74c1-38d7-4e2f-87c5-1c522de70ff3",
      'payment_method_type_url' => "/api/cloud/integrations/payment-method/802725f9-feb8-424a-a82b-6e2c5307feef",
      'org_url' => "/consumer-orgs/1234/5678/982318734",
      'configuration' => [
        "owner_email" => "andre@example.com",
        "owner_name" => "Andre",
        "token" => "myToken"
      ]
    ];
    $paymentMethodObject['created_by'] = "";
    $paymentMethodObject['updated_by'] = "";

    \Drupal::service('consumerorg.paymentmethod')->createOrUpdate($paymentMethodObject);
  }

  /**
   * @Given I do not have a billing service
   */
  public function disableBilling(): void {
    $billingService = \Drupal::service('ibm_apim.billing');
    $billingService->deleteAll();
    $integrationService = \Drupal::service('ibm_apim.payment_method_schema');
    $integrationService->deleteAll();
  }

  /**
   * @Then dump the current html
   *
   * drupal-extension provides a "Then print last response" which prints the current HTML but it has nothing that
   * can store responses to a file. This is better for debugging on an appliance and would also be more useful
   * in automated runs if we could collect the files together at the end of the run. Travis doesn't seem to do this
   * so this step definition also prints the HTML to the screen if that option is set.
   */
  public function dumpTheCurrentHtml(): void {

    $this->minkContext->printCurrentUrl();
    print '\n';

    $html = $this->getSession()->getDriver()->getContent();
    $htmlFileName = $this->debugDumpDir . '/failure-html-dump-' . $this->timestamp . '_' . $this->htmlDumpNumber . '.html';
    file_put_contents($htmlFileName, $html);

    print sprintf("HTML failure dump available at %s \n", $htmlFileName);

    if ($this->dumpHtmlToScreen !== FALSE) {
      print "Dumping HTML of current page to screen\n";
      print "**************************************\n";
      print $html;
      print "**************************************\n";
    }

    ++$this->htmlDumpNumber;

  }

  /**
   * @Then The :contentType content type is present
   *
   * Checks whether the provided content type exists
   *
   * @param $contentType
   *
   * @throws \Exception
   */
  public function theContentTypeIsPresent($contentType): void {
    $found = FALSE;
    $types = \Drupal::service('entity_type.manager')->getStorage('node_type')->loadMultiple();

    foreach ($types as $type) {
      if ($type->id() && (string) $type->id() === $contentType) {
        $found = TRUE;
      }
    }
    if ($found !== TRUE) {
      throw new \Exception("The content type $contentType was not found!");
    }
  }

  /**
   * @Given if the field :arg1 is present, enter the value :arg2
   *
   * Our tests need to be run both via travis and directly on the appliance. There are however differences between how
   *   the portal behaves in travis vs the actual behaviour we ship to customers. For example, when testing auth_apic
   *   in isolation in travis, there is no 'captcha' field as that module is installed on the appliance as part of the
   *   apim_profile. In order to make a test that works on the appliance, we must provide a value for the captcha
   *   field. For travis, the field doesn't exist and any test that tries to interact with a non-existant field will
   *   fail.
   *
   * That is where this function comes in. If there are fields that exist in travis but not on the appliance (or
   *   vice-versa), this step definition allows a scenario to function in both cases.
   *
   * @param $fieldIdOrName
   * @param $value
   */
  public function ifTheFieldIsPresentEnterTheValue($fieldIdOrName, $value): void {
    try {
      $result = $this->findElementsByIdOrName($fieldIdOrName, FALSE);

      if ($result !== NULL) {
        $this->minkContext->assertEnterField($fieldIdOrName, $value);
      }
    } catch (\Exception $exception) {
      // do nothing
    }
  }


  /**
   * @Given the element :element is :enabledOrDisabled
   *
   * Asserts that a field identified by the name or id is enabled or disabled.
   *
   * @throws \Exception
   */
  public function theElementIsEnabledOrDisabled($elementIdOrName, $enabled): void {

    if ($enabled !== 'enabled' && $enabled !== 'disabled') {
      throw new \Exception('Second argument to this step definition must be either \"enabled\" or \"disabled\"');
    }

    $element = $this->findElementsByIdOrName($elementIdOrName, TRUE);
    if ($element->hasAttribute('disabled')) {
      if ($enabled === 'enabled') {
        throw new \Exception("The element $elementIdOrName is disabled but it should have been enabled!");
      }
    }
    elseif ($enabled === 'disabled') {
      throw new \Exception("The element $elementIdOrName is enabled but it should have been disabled!");
    }
  }

  /**
   * @Given I restart the session
   * @Given I start a new session
   *
   * Calls session->stop() and session->start() which kills and respawns the browser
   */
  public function iRestartTheSession(): void {
    $this->getSession()->stop();
    $this->getSession()->start();
  }

  /**
   * @Given /^there are( no)? (errors|warnings|messages)?$/
   *
   * Looks through the DOM for css classes representing drupal messages (the area at the top of the page).
   * Either asserts that this is expected if you wanted there to be messages or that this is unexpected
   * if you didn't want there to be any.
   *
   * Checks specifically for 'messages' (green), 'warnings' (yellow) and 'errors' (red).
   *
   * @param null $noMessages
   * @param null $type
   *
   * @throws \Exception
   */
  public function checkForDrupalMessages($noMessages = NULL, $type = NULL): void {
    $page = $this->getSession()->getPage();

    if ($type === NULL) {
      throw new \Exception("You can't use check for message/error/warning without specifying a type of message to look for!!");
    }

    // Depending on the page or the type of user logged in, the css class for messages could differ
    // Allow for there possibly being multiple different classes to match on.
    $cssClasses = [];
    $xpathStrings = [];

    if ($type === 'messages') {
      $cssClasses[] = '.alert-success';     // non admin users
      $cssClasses[] = '.messages--status'; // admin users
      $xpathStrings[] = '//div[@aria-label=\'Status message\']'; // for travis
    }
    elseif ($type === 'warnings') {
      $cssClasses[] = '.alert-warning';
    }
    elseif ($type === 'errors') {
      $cssClasses[] = '.alert-danger'; // non admin users
      $xpathStrings[] = '//div[@aria-label=\'Error message\']'; // for travis
    }
    else {
      throw new \Exception('Message type ' . $type . ' is not understood.');
    }

    $messages = [];

    // Process the css classes list
    foreach ($cssClasses as $cssClass) {
      $thisClassMessages = $page->find('css', $cssClass);

      if ($thisClassMessages !== NULL) {
        $messages[] = $thisClassMessages;
      }
    }

    // Process the xpath selectors
    foreach ($xpathStrings as $xpathSelector) {
      $thisXpathMatches = $page->find('xpath', $xpathSelector);

      if ($thisXpathMatches !== NULL) {
        $messages[] = $thisXpathMatches;
      }
    }

    if ($noMessages !== NULL && $noMessages !== '') {
      // Expected no messages
      if ($messages !== NULL && sizeof($messages) !== 0) {
        throw new \Exception('There were ' . sizeof($messages) . ' ' . $type . ' on the page but we expected none.');
      }
    }
    elseif ($messages === NULL || sizeof($messages) === 0) {
      // Expected at least one message
      throw new \Exception('There were no ' . $type . ' on the page but we expected at least one.');
    }
  }

  /**
   * @Given /^the apim user "(.*)" is( not)? logged in$/
   *
   * Checking that a user is logged in is different in travis than Jenkins/the appliance
   * because our theme changes the behaviour. This step definition handles the differences.
   *
   * @param $username
   * @param null $not
   */
  public function theApimUserIsLoggedIn($username, $not = NULL): void {

    // Detect if this test is running in travis
    $amInTravis = getenv('TRAVIS');

    if ($amInTravis) {
      // For travis, just look for text in the page
      if ($not === ' not') {
        $this->minkContext->assertPageNotContainsText($username);
      }
      else {
        $this->minkContext->assertPageContainsText($username);
      }
    }
    elseif ($not === ' not') {
      // For jenkins / appliance look for the user profile menu
      $this->minkContext->assertElementNotOnPage(".imageContainer [title='" . $username . "']");
    }
    else {
      $this->minkContext->assertElementOnPage(".imageContainer [title='" . $username . "']");
    }

  }

  /**
   * @When I click on element :arg1
   *
   * @param $selector
   *
   * @throws \Exception
   */
  public function iClickOnElement($selector): void {
    $page = $this->getSession()->getPage();
    $element = $page->find('css', $selector);

    if (empty($element)) {
      throw new \Exception("No html element found for the selector ('$selector')");
    }

    $element->click();
  }

  // ************************************************************************************************************
  // This section contains overrides for step definitions defined in DrupalContext.
  // ************************************************************************************************************

  /**
   * The drupal-extension built-in version of "Given users:" doesn't first check to see if
   * a user exists. It blindly goes ahead and creates it which causes the database to barf it
   * the username is not unique. In the case where our users come from the management appliance,
   * we don't want to create a user if it already exists. This custom "Given apic users" function
   * will first check if a user exists. If it doesn't, it will call to the "Given users:" step
   * definition to cause the user to be created.
   *
   * "Given users:"
   *
   * @param \Behat\Gherkin\Node\TableNode $usersTable
   */
  public function createUsers(TableNode $usersTable): void {
    $apimUtils = \Drupal::service('ibm_apim.apim_utils');

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any user in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No users will be created in the database.\n";

      // drupal-extension will moan if we don't keep a record of who the users are
      foreach ($usersTable as $row) {
        $basicUser = new \stdClass();
        $basicUser->name = $row['name'];
        $basicUser->mail = $row['mail'];
        $basicUser->pass = $row['pass'];
        $basicUser->status = $row['status'];
        if (isset($row['url'])) {
          $basicUser->apic_url = $row['url'];
        }
        else {
          $basicUser->apic_url = $apimUtils->removeFullyQualifiedUrl($row['name']);
        }
        if (isset($row['registry_url'])) {
          $basicUser->registry_url = $row['registry_url'];
        }
        $this->getUserManager()->addUser($basicUser);
        $this->getUserManager()->setCurrentUser($basicUser);
        $this->apicUsers[$basicUser->name] = $basicUser;
      }

      return;
    }

    // running with mocks, but we still need to be aware of what is in our database ...

    // We may need to create some users and not others so we should create a new TableNode
    // that we can pass to the parent::createUsers() function if needed
    $makeUsersTableHash = [];
    $makeUsersTableHash[] = $usersTable->getRows()[0]; // row[0] are the column headers

    foreach ($usersTable as $row) {
      if ($user = $this->checkDatabaseForUser($row)) {

        if ($user->getAccountName() === 'admin') {
          print 'Admin user - loaded uid from the database' . \PHP_EOL;
        }
        elseif (isset($row['registry_url'])) {
          print 'Found an existing user record for ' . $user->getAccountName() . ' (mail=' . $user->getEmail() . ', registry_url=' . $user->get('registry_url')->value . ') in the database.' . \PHP_EOL;
        }
        else {
          print 'Found an existing user record for ' . $user->getAccountName() . ' (mail=' . $user->getEmail() . ') in the database.' . \PHP_EOL;
        }

        $basicUser = new \stdClass();
        $basicUser->name = $row['name'];
        $basicUser->mail = $row['mail'];
        $basicUser->pass = $row['pass'];
        if (isset($row['status'])) {
          $basicUser->status = $row['status'];
        }
        else {
          $basicUser->status = 1;
        }
        if (isset($row['url'])) {
          $basicUser->apic_url = $row['url'];
        }
        else {
          $basicUser->apic_url = $apimUtils->removeFullyQualifiedUrl($row['name']);
        }
        if (isset($row['registry_url'])) {
          $basicUser->registry_url = $row['registry_url'];
        }
        else {
          $basicUser->registry_url = '/registry/test';
        }

        if (!isset($row['first_time_login'])) {
          $row['first_time_login'] = 0;
        }
        $basicUser->uid = $user->id();
        if (!isset($row['delete'])) {
          $this->getUserManager()->addUser($basicUser);
        }
        $this->getUserManager()->setCurrentUser($basicUser);
        $this->apicUsers[$user->getAccountName()] = $basicUser;
      }
      else {
        if (isset($row['registry_url'])) {
          print 'No existing user record for ' . $row['name'] . ' (mail=' . $row['mail'] . ", registry_url=" . $row['registry_url'] . ") in the database. Creating...\n";
        }
        else {
          print 'No existing user record for ' . $row['name'] . ' (mail=' . $row['mail'] . ") in the database. Creating...\n";
        }
        // If we get here, we need to create the user;

        // Add in other fields from the database that we don't add to the Users table in the tests.
        // This is required to give valid forms (for example the edit profile form).
        // If this is not sufficient then the tables at the tops of the tests will need to be updated
        // and this code can be removed.

        // Add the headers into the initial row of the table, if they aren't already there:
        $new_headers = ['first_name', 'last_name', 'apic_url', 'first_time_login'];
        // sometimes we will be passed a registry_url, if not make sure we have one
        if (!\in_array('registry_url', $makeUsersTableHash[0], TRUE)) {
          $new_headers[] = 'registry_url';
        }

        foreach ($new_headers as $header) {
          if (!\in_array($header, $makeUsersTableHash[0], FALSE)) {
            $makeUsersTableHash[0][] = $header;
          }
        }

        // to mimic user registries where there is no email address - we need to add something to the db to be valid
        if ($row['mail'] === '' || $row['mail'] === NULL) {
          $random_prefix = \Drupal::service('ibm_apim.utils')->random_num();
          $row['mail'] = $random_prefix . 'noemailinregistry@example.com';
        }

        // Set the fields to be stored in the DB.
        $row['first_name'] = 'Andre';
        $row['last_name'] = 'Andresson';
        if (isset($row['url'])) {
          $row['apic_url'] = $row['url'];
        }
        else {
          $row['apic_url'] = $apimUtils->removeFullyQualifiedUrl($row['name']);
        }

        if (!isset($row['first_time_login'])) {
          $row['first_time_login'] = 0;
        }

        if (!isset($row['registry_url'])) {
          $row['registry_url'] = '/registry/test';
        }


        print 'creating a user from following data: ' . serialize($row) . "\n";

        $makeUsersTableHash[] = $row;
      }
    }

    if (sizeof($makeUsersTableHash) !== 1) {
      // Call DrupalContext::createUsers with our potentially cut-down table
      $newUsersTable = new TableNode($makeUsersTableHash);
      parent::createUsers($newUsersTable);
    }

  }

  /**
   * @param $row
   *
   * @return \Drupal\user\Entity\User|null
   */
  private function checkDatabaseForUser($row): ?User {

    $query_for = ['name'];

    if ($row['name'] === 'admin') {
      $users = [1 => User::load(1)];
    }
    else {
      $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');


      //      $query = ['name' => $row['name'], 'mail' => $row['mail']];
      // constraint on user with matching username + registry_url - ignoring email
      $query = ['name' => $row['name']];

      if (isset($row['registry_url'])) {
        $query_for[] = 'registry_url';
        $query['registry_url'] = $row['registry_url'];
      }

      $users = $user_storage->loadByProperties($query);
    }

    print __FUNCTION__ . ': query for ' . \serialize($query_for) . ' returning ' . serialize($users) . \PHP_EOL;

    return \sizeof($users) > 0 ? reset($users) : NULL;

  }

  /**
   * @Then there is no :arg1 response header
   * @throws \Exception
   */
  public function thereIsNoResponseHeader($arg1): void {
    $session = $this->getSession();
    $responseHeaders = $session->getResponseHeaders();
    $responseHeaders = array_change_key_case($responseHeaders, CASE_LOWER);
    $responseHeader = strtolower($arg1);

    if (array_key_exists($responseHeader, $responseHeaders)) {
      throw new \Exception("Response header $arg1 found but not expected");
    }
  }


  /**
   * "Given I am logged in as :name"
   *
   * Overrides DrupalContext::assertLoggedInByName.
   *
   * @throws \Exception
   */
  public function assertLoggedInByName($name): void {

    // log in to the UI by calling the default DrupalContext login function
    parent::assertLoggedInByName($name);

    $session = $this->getSession();
    $page = $session->getPage();

    if (!$page->findLink('Sign out')) {
      throw new \Exception("Log out link not found for user name: ('$name'), assuming login failed");
    }

  }


  /**
   * @When I am logged in as :name from :registry with :password
   *
   * Overrides DrupalContext::assertLoggedInByName. We need to extend the behaviour of
   * this function so that it doesn't just log in against the web UI but also logs in
   * the local drupal API instance so that we can run database queries from behat
   * as the user that we just logged in as.
   *
   * @param $name
   * @param $registry
   * @param $password
   *
   * @throws \Exception
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function assertLoggedInByNameFromRegistry($name, $registry, $password): void {

    $manager = $this->getUserManager();

    // Change internal current user.
    $basicUser = $this->createBehatUserFromDBUser($name, $registry, $password);
    $manager->addUser($basicUser);
    $manager->setCurrentUser($basicUser);

    // Login.
    if ($basicUser !== NULL) {
      $this->loginViaRegistry($basicUser, $registry);
    }
    else {
      throw new \Exception("Basic User not found for user name: ('$name'), assuming login failed");
    }

    $session = $this->getSession();
    $page = $session->getPage();

    if (!$page->findLink('Sign out')) {
      throw new \Exception("Log out link not found for user name: ('$name'), assuming login failed");
    }

  }

  /**
   * Creates and authenticates a user with the given role(s).
   * Overriding the one from the base class since we need to set the UR.
   *
   * @Given I am logged in as a registry user with the :role role(s)
   */
  public function assertAuthenticatedByRoleRegistry($role): void {
    // Check if a user with this role is already logged in.
    if (!$this->loggedInWithRole($role)) {
      // Create user (and project)
      $user = (object) [
        'name' => $this->getRandom()->name(8),
        'pass' => $this->getRandom()->name(16),
        'role' => $role,
      ];
      $user->mail = "{$user->name}@example.com";
      $service = \Drupal::service('ibm_apim.user_registry');
      $defaultUR = $service->getDefaultRegistry();
      if ($defaultUR !== NULL) {
        $user->registry_url = $defaultUR->getUrl();
      }
      $this->userCreate($user);

      $roles = explode(',', $role);
      $roles = array_map('trim', $roles);
      foreach ($roles as $individualRole) {
        if (!in_array(strtolower($individualRole), ['authenticated', 'authenticated user'])) {
          // Only add roles other than 'authenticated user'.
          $this->getDriver()->userAddRole($user, $individualRole);
        }
      }

      // Login.
      $this->login($user);
    }
  }

  /**
   * @param $name
   * @param $registry_url
   * @param $password
   *
   * @return \stdClass|NULL
   */
  private function createBehatUserFromDBUser($name, $registry_url, $password): ?\stdClass {

    $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');
    $query = ['name' => $name, 'registry_url' => $registry_url];
    $users = $user_storage->loadByProperties($query);

    $user = \sizeof($users) > 0 ? reset($users) : NULL;
    $basicUser = NULL;
    if ($user !== NULL) {
      $basicUser = new \stdClass();
      $basicUser->name = $name;
      $basicUser->registry_url = $registry_url;

      $basicUser->mail = $user->get('mail')->value;
      $basicUser->pass = $password;
      //$basicUser->status = $row['status'];
      if ($user->get('apic_url') !== NULL) {
        $basicUser->apic_url = $user->get('apic_url')->value;
      }
      else {
        $basicUser->apic_url = $user->getAccountName();
      }

    }
    return $basicUser;
  }

  /**
   * Log-in the given user in a specific registry.
   * Updated version of RawDrupalContext::login()
   *
   * @param \stdClass $user
   *   The user to log in.
   * @param string $registry_url
   *   registry to log in to.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Exception
   */
  private function loginViaRegistry(\stdClass $user, string $registry_url): void {
    $manager = $this->getUserManager();

    // Check if logged in.
    if ($this->loggedIn()) {
      $this->logout();
    }

    $this->getSession()->visit($this->locatePath('/user/login?registry_url=' . $registry_url));
    $element = $this->getSession()->getPage();
    $element->fillField($this->getDrupalText('username_field'), $user->name);
    $element->fillField($this->getDrupalText('password_field'), $user->pass);
    $submit = $element->findButton('op');
    if (empty($submit)) {
      throw new \Exception(sprintf("No submit button at %s", $this->getSession()->getCurrentUrl()));
    }

    // Log in.
    $submit->click();

    if (!$this->loggedIn()) {
      if (isset($user->role)) {
        throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s' with role '%s'", $user->name, $user->role));
      }
      else {
        throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s'", $user->name));
      }
    }

    $manager->setCurrentUser($user);
  }


  /**
   * Check whether a link on the page has a link with a specific href location.
   *
   * @Then I should see a link with href including :arg1
   * @throws \Exception
   */
  public function iShouldSeeALinkWithHrefIncluding($url_segment): void {
    $page = $this->getSession()->getPage();
    $links = $page->findAll('xpath', '//a/@href');

    $foundMatch = FALSE;

    foreach ($links as $link) {

      // If element or tag is empty...
      if (empty($link->getParent())) {
        continue;
      }

      $href = $link->getParent()->getAttribute('href');

      // Skip if empty
      if (empty($href)) {
        continue;
      }

      // Skip remote links
      if (strpos($href, $url_segment) !== FALSE) {
        //print "Found link with $url_segment -> $href  \n";
        $foundMatch = TRUE;
        continue;
      }
    }

    if (!$foundMatch) {
      throw new \Exception("No link found with href including: $url_segment");
    }

  }

  /**
   * Check no link on the page has a link with a specific href location.
   *
   * @Then I should not see a link with href including :arg1
   *
   * @param $url_segment
   *
   * @throws \Exception
   */
  public function iShouldNotSeeALinkWithHrefIncluding($url_segment): void {
    $page = $this->getSession()->getPage();
    $links = $page->findAll('xpath', '//a/@href');

    $foundMatch = FALSE;

    foreach ($links as $link) {

      // If element or tag is empty...
      if (empty($link->getParent())) {
        continue;
      }

      $href = $link->getParent()->getAttribute('href');

      // Skip if empty
      if (empty($href)) {
        continue;
      }

      // Skip remote links
      if (strpos($href, $url_segment) !== FALSE) {
        //print "Found link with $url_segment -> $href  \n";
        $foundMatch = TRUE;
        continue;
      }
    }

    if ($foundMatch) {
      throw new \Exception("Link found with href including: $url_segment");
    }

  }

  /**
   * @Given ibm_apim settings config boolean property :propname value is :value
   *
   * @param $propname
   * @param $value
   */
  public function ibmApimSettingsConfigBooleanPropertyValueIs($propname, $value): void {
    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    $config = \Drupal::service('config.factory')->getEditable('ibm_apim.settings');
    $config->set($propname, $value);
    $config->save();

    print " config: set $propname to $value - resulted in value of " . $config->get($propname) . "\n";
  }

  /**
   * @Then ibm_apim settings config property :propname value should be :value
   *
   * @param $propname
   * @param $value
   *
   * @throws \Exception
   */
  public function ibmApimSettingsConfigPropertyValueShouldBe($propname, $value) {
    $config = \Drupal::service('config.factory')->get('ibm_apim.settings');
    $actualValue = $config->get($propname);
    print "Config: $propname is set to $actualValue";
    if ($value !== $actualValue) {
      throw new \Exception("Config: $propname was set to $actualValue instead of $value");
    }
  }

  /**
   * @Then ibm_apim settings config property :propname boolean value should be :value
   *
   * @param $propname
   * @param $value
   *
   * @throws \Exception
   */
  public function ibmApimSettingsConfigPropertyBooleanValueShouldBe($propname, $value): void {
    $config = \Drupal::service('config.factory')->get('ibm_apim.settings');
    $actualValue = $config->get($propname);
    $value = (bool) $value;
    print "Config: $propname is set to $actualValue";
    if ($value !== $actualValue) {
      throw new \Exception("Config: $propname was set to $actualValue instead of $value");
    }
  }

  /**
   * @Then ibm_apim settings config property :propname integer value should be :value
   *
   * @param $propname
   * @param $value
   *
   * @throws \Exception
   */
  public function ibmApimSettingsConfigPropertyIntegerValueShouldBe($propname, $value): void {
    $config = \Drupal::service('config.factory')->get('ibm_apim.settings');
    $actualValue = $config->get($propname);
    $value = (int) $value;
    print "Config: $propname is set to $actualValue";
    if ($value !== $actualValue) {
      throw new \Exception("Config: $propname was set to $actualValue instead of $value");
    }
  }

  /**
   * @Then restore ibm_apim settings default values
   */
  public function ibmApimDefaultSettings(): void {
    $codesnippets = [
      'curl' => TRUE,
      'ruby' => TRUE,
      'python' => TRUE,
      'php' => TRUE,
      'java' => TRUE,
      'node' => TRUE,
      'go' => TRUE,
      'swift' => TRUE,
      'c' => TRUE,
      'csharp' => TRUE,
    ];
    $categories = [
      'enabled' => TRUE,
      'create_taxonomies_from_categories' => FALSE,
    ];
    \Drupal::service('config.factory')->getEditable('ibm_apim.settings')
      ->set('autocreate_apiforum', TRUE)
      ->set('show_placeholder_images', TRUE)
      ->set('show_register_app', TRUE)
      ->set('show_versions', TRUE)
      ->set('enable_api_test', TRUE)
      ->set('validate_apis', TRUE)
      ->set('autotag_with_phase', FALSE)
      ->set('show_cors_warnings', TRUE)
      ->set('show_analytics', TRUE)
      ->set('soap_swagger_download', FALSE)
      ->set('render_api_schema_view', TRUE)
      ->set('optimise_oauth_ux', TRUE)
      ->set('show_mtls_header', TRUE)
      ->set('email_as_username', TRUE)
      ->set('application_image_upload', TRUE)
      ->set('hide_admin_registry', FALSE)
      ->set('render_api_schema_view', TRUE)
      ->set('allow_consumerorg_creation', TRUE)
      ->set('allow_consumerorg_rename', TRUE)
      ->set('allow_consumerorg_delete', TRUE)
      ->set('allow_consumerorg_change_owner', TRUE)
      ->set('enable_oidc_register_form', TRUE)
      ->set('enable_oidc_login_form', FALSE)
      ->set('allow_user_delete', TRUE)
      ->set('allow_new_credentials', TRUE)
      ->set('allow_clientid_reset', TRUE)
      ->set('allow_clientsecret_reset', TRUE)
      ->set('categories', $categories)
      ->set('soap_codesnippets', FALSE)
      ->set('codesnippets', $codesnippets)
      ->set('router_type', 'hash')
      ->set('certificate_strip_newlines', TRUE)
      ->set('certificate_strip_prefix', TRUE)
      ->set('payment_method_encryption_profile', 'socialblock')
      ->set('api_max_depth', 9)
      ->set('example_array_items', 3)
      ->save();
    \Drupal::service('config.factory')->getEditable('ibm_apim.devel_settings')
      ->set('entry_exit_trace', FALSE)
      ->set('apim_rest_trace', FALSE)
      ->set('acl_debug', FALSE)
      ->set('webhook_debug', FALSE)->save();
  }

  /**
   * Only to be used for tests where user has already been deleted from the db
   * @Then clear users
   */
  public function clearUsers(): void {
    $this->getUserManager()->clearUsers();
  }

  private function resetToDefaultRegistry(): void {
    $lur = new UserRegistry();
    $lur->setName('lur1');
    $lur->setUrl('/reg/lur1');
    $lur->setRegistryType('lur');
    $lur->setUserManaged(TRUE);
    $urs = ['/reg/lur1' => $lur];
    \Drupal::state()->set('ibm_apim.user_registries', $urs);

  }

  /**
   * @Given I am viewing the :arg1 node :arg2
   *
   * @param $nodeType
   * @param $nodeTitle
   *
   * @throws \Exception
   */
  public function iAmViewingNode($nodeType, $nodeTitle): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', $nodeType);
    $query->condition('title.value', $nodeTitle);
    $results = $query->execute();

    if ($results !== NULL && !empty($results)) {
      $nid = array_shift($results);

      $this->getMink()->getSession()->visit($this->locatePath('/node/' . $nid));
    }
    else {
      throw new \Exception($nodeType . ' could not be found with title: ' . $nodeTitle);
    }
  }

  /**
   * @Given I am editing the :arg1 node :arg2
   *
   * @param $nodeType
   * @param $nodeTitle
   *
   * @throws \Exception
   */
  public function iAmEditingNode($nodeType, $nodeTitle): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', $nodeType);
    $query->condition('title.value', $nodeTitle);
    $results = $query->execute();

    if ($results !== NULL && !empty($results)) {
      $nid = array_shift($results);

      $this->getMink()->getSession()->visit($this->locatePath('/node/' . $nid . '/edit'));
    }
    else {
      throw new \Exception($nodeType . ' could not be found with title: ' . $nodeTitle);
    }
  }

  /**
   * @Given My active consumerorg is :arg1
   *
   * @param $orgName
   *
   * @throws \Exception
   */
  public function myActiveConsumerOrg($orgName): void {
    $page = $this->getSession()->getPage();
    $found = $page->findAll('css', '.consumerorgSelectBlock .orgmenu li a[title="Current organization: ' . $orgName . '"]');
    $num = \sizeof($found);
    if ($num !== 1) {
      throw new \Exception('Unexpected response from finding the active consumer org. Expected 1, found ' . $num);
    }
  }

  /**
   * check for is disabled or not
   *
   * @Then The field :arg1 should be disabled
   *
   * @param $selector
   *
   * @throws \Exception
   */
  public function isDisabled($selector): void {
    try {
      $disabled = $this->getDisabled($selector);
      if ($disabled !== TRUE) {
        throw new \Exception('Field should have been disabled, it isnt. selector: ' . $selector);
      }
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * check for is disabled or not
   *
   * @Then The field :arg1 should not be disabled
   *
   * @param $selector
   *
   * @throws \Exception
   */
  public function isNotDisabled($selector): void {
    try {
      $disabled = $this->getDisabled($selector);
      if ($disabled !== FALSE) {
        throw new \Exception('Field should not have been disabled, it is. selector: ' . $selector);
      }
    } catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * @param $selector
   *
   * @return bool|null
   * @throws \Exception
   */
  private function getDisabled($selector): ?bool {
    $disabled = NULL;
    $session = $this->getSession();
    $page = $session->getPage();
    $element = $page->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('css', $selector) // just changed xpath to css
    );
    if ($element !== NULL) {
      if ($element->getAttribute('disabled') !== NULL) {
        $disabled = TRUE;
      }
      else {
        $disabled = FALSE;
      }
    }
    else {
      throw new \Exception('No element found for selector: ' . $selector);
    }
    return $disabled;
  }

  /**
   * @Given self service onboarding is enabled
   */
  public function selfServiceOnboardingIsEnabled(): void {
    print "self service onboarding enabled \n";
    \Drupal::state()->set('ibm_apim.selfSignUpEnabled', TRUE);
  }

  /**
   * @Given self service onboarding is disabled
   */
  public function selfServiceOnboardingIsDisabled(): void {
    print "self service onboarding disabled \n";
    \Drupal::state()->set('ibm_apim.selfSignUpEnabled', FALSE);
  }

  /**
   * @Given consumer org invitation is enabled
   */
  public function consumerOrgInvitationIsEnabled(): void {
    print "consumer org invitation enabled \n";
    \Drupal::state()->set('ibm_apim.consumerOrgInvitationEnabled', TRUE);
  }

  /**
   * @Given consumer org invitation is disabled
   */
  public function consumerOrgInvitationIsDisabled(): void {
    print "consumer org invitation disabled \n";
    \Drupal::state()->set('ibm_apim.consumerOrgInvitationEnabled', FALSE);
  }

  /**
   * @Given consumer org invitation roles are :roles
   */
  public function consumerOrgInvitationRoles($roles): void {
    $roleNames = explode(',', $roles);
    print "consumer org invitation roles are: " . serialize($roleNames) . "\n";
    \Drupal::state()->set('ibm_apim.consumerOrgInvitationRoles', $roleNames);
  }

  /**
   * @Given application certificates are enabled
   */
  public function appCertificatesAreEnabled(): void {
    print "application certificates enabled \n";
    \Drupal::state()->set('ibm_apim.application_certificates', TRUE);
  }

  /**
   * @Given I have apim public keys stored
   */
  public function storeApimKeys(): void {
    $publicKeyJWK = ['keys' => [unserialize(base64_decode(getenv('JWK_B64_KEY')))]];
    \Drupal::state()->set('ibm_apim.apim_keys', $publicKeyJWK);
  }

  /**
   * @Given application certificates are disabled
   */
  public function appCertificatesAreDisabled(): void {
    print "application certificates disabled \n";
    \Drupal::state()->set('ibm_apim.application_certificates', FALSE);
  }

  /**
   * Check whether a tooltip on the page contains the following text.
   *
   * @Then I should see the tooltip text :arg1
   *
   * @param $arg1
   *
   * @throws \Exception
   */
  public function iShouldSeeTheTooltipText($arg1): void {
    $page = $this->getSession()->getPage();
    $tooltip = $page->findAll('css', '[data-original-title*="' . $arg1 . '"]');

    if ($tooltip === NULL) {
      throw new \Exception('cannot find expected tooltip');
    }

  }


}