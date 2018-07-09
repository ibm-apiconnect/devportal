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
namespace Drupal\apictest\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Testwork\Hook\Scope\SuiteScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Behat\Behat\Hook\Scope\AfterFeatureScope;
use Behat\Gherkin\Node\TableNode;

use Drupal\DrupalExtension\Context\DrupalContext;

use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;

use Drupal\apictest\MockServiceHandler;
use Drupal\apictest\TestData\TestData;


/**
 * Defines application features from the specific context.
 */
class IBMPortalContext extends DrupalContext implements SnippetAcceptingContext {

  private $apicUsers = array();

  private $testData;

  private $minkContext;

  private $timestamp;

  private $debugDumpDir;

  private $htmlDumpNumber;

  private $localDrupalLoggedIn;

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct($siteDirectory, $modulesDirectory, $useMockServices, $dumpHtmlToScreen, $debugDumpDir = "/tmp", $testDataScenario = "mocked", $userRegistry = 'lur', $mockSiteConfig = TRUE) {

    if ($siteDirectory == NULL || $siteDirectory == '') {
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

    if ($debugDumpDir == "/tmp" || !isset($debugDumpDir) || empty($debugDumpDir)) {
      // Make the debug dump directory a little more unique
      $debugDumpDir = "/tmp/behat-html-dumps-" . uniqid();
    }

    if (!file_exists($debugDumpDir)) {
      mkdir($debugDumpDir, 0777, TRUE);
    }
    $this->debugDumpDir = $debugDumpDir;

    // This allows us to replace @now with a unique timestamp so tests can be run multiple times
    $this->timestamp = microtime(TRUE);

    // An incrementing counter so that we can create uniquely named HTML dump files for debugging
    $this->htmlDumpNumber = 1;

    // The data for use generally in this scenario
    $this->testData = new TestData($testDataScenario);
    $this->localDrupalLoggedIn = FALSE;
  }

  // ************************************************************************************************************
  // This section contains Before* and After* functions that handle setup and teardown of scenarios and steps
  // as well as utility functions used internally by our step definitions.
  // ************************************************************************************************************

  /** @BeforeSuite */
  public static function setup(BeforeSuiteScope $scope) {
    $params = self::getContextParameters($scope, 'Drupal\apictest\Context\IBMPortalContext');
    if ($params['useMockServices'] === TRUE) {
      MockServiceHandler::install($params['siteDirectory'], $params['modulesDirectory'], $params['userRegistry'], $params['mockSiteConfig']);
    }
  }

  /** @AfterSuite */
  public static function teardown(AfterSuiteScope $scope) {
    $params = self::getContextParameters($scope, 'Drupal\apictest\Context\IBMPortalContext');
    if ($params['useMockServices'] === TRUE) {
      MockServiceHandler::uninstall($params['siteDirectory']);
    }
  }

  /** @AfterFeature */
  public static function afterFeature(AfterFeatureScope $scope) {
    print "Clearing drupal caches\n";
    drupal_flush_all_caches();
  }

  /** @BeforeScenario */
  public function beforeScenario($event) {

    // If this is a @javascript test, start the zombie session if it isn't already running
    $session = $this->getSession();
    if (strpos(get_class($session->getDriver()), 'ZombieDriver')) {
      if (!$session->isStarted()) {
        print "Starting new zombie session\n";
        $session->start();
      }
    }

    // If we want to chain multiple steps together, we need access to the MinkContext. Grab that here.
    $environment = $event->getEnvironment();
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');

  }

  /** @AfterScenario */
  public function afterScenario($event) {

    // Prevent the drupal context cleanup code from deleting management node users
    foreach ($this->apicUsers as $nextUser) {
      $this->getUserManager()->removeUser($nextUser->name);
    }

    // Force log out if the test scenario didn't explicitly log out at the end
    $this->assertAnonymousUser();

    // NOTE - we are not shutting down zombie here. This is a deliberate choice!
    //        We already have code that detects zombie is started so we don't get multiple instances.
    //        Not shutting down between scenario is a slight performance boost.
    //        And the main reason - cleanup code fails if we shut down the session before all of behat
    //        has finished running whatever cleanup code exists in the DrupalContext / MinkContext files.

  }

  /** @AfterStep */
  public function afterEachStep(AfterStepScope $stepScope) {

    if (!$stepScope->getTestResult()->isPassed()) {
      // The previous step failed. Dump out the HTML of the page for debugging purposes.
      $this->dumpTheCurrentHtml();
    }

  }

  /**
   * Utility function to find and return an element or elements with an id or name matching the
   * specified string. If no element is found and $failifNotFound is set to TRUE, an Exception
   * will be thrown.
   **/
  public function findElementsByIdOrName($elementIdOrName, $failIfNotFound = FALSE) {
    $page = $this->getSession()->getPage();
    $field = $page->find('named', array('id_or_name', $elementIdOrName));

    if (isset($field) || sizeof($field) !== 0) {
      return $field;
    }
    else if ($failIfNotFound) {
      throw new \Exception("Field $elementIdOrName was not on the page.");
    }

    return NULL;

  }

  /**
   * Simple utility function to look up the tags attached to this scenario
   * and check if the tag specified is one of them.
   *
   * @param event
   *  BeforeScenario event object
   * @param tag
   *  String representing the name of the tag
   */
  public function scenarioHasTag($event, $tag) {
    return array_search($tag, $event->getScenario()->getTags()) !== TRUE;
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
  private static function getContextParameters(SuiteScope $scope, $name) {
    $contexts = $scope->getSuite()->getSetting('contexts');
    $settings = NULL;
    foreach ($contexts as $ctx) {
      if (gettype($ctx) === 'array') {
        $settings = $ctx[$name];
      }
    }
    if ($settings) {
      return $settings;
    }
    else {
      print "parameters not found for " . $name . "context\n";
      return NULL;
    }

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
      if (Database::getConnection()->schema()->tableExists("captcha_sessions")) {
        $captchaSolution = db_query("SELECT `solution`, `csid` FROM `captcha_sessions` ORDER BY csid DESC LIMIT 1;")->fetchField();
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
      if (!isset($current_user->uid) || $current_user->uid == NULL) {
        $ids = \Drupal::entityQuery('user')->execute();
        $users = User::loadMultiple($ids);

        foreach ($users as $drupal_user) {
          if ($drupal_user->getUsername() == $current_user->name) {
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
      $startofusername = strlen($parameter_check);
      $username = substr($chunktoreplace, $startofusername, -1);

      $ids = \Drupal::entityQuery('user')->execute();
      $users = User::loadMultiple($ids);

      print "searching for username: " . $username . "\n";

      foreach ($users as $drupal_user) {
        if ($drupal_user->getUsername() == $username) {
          $uid = $drupal_user->id();
          break;
        }
      }
    }

    print "replacing: " . $chunktoreplace . " with " . $uid . "\n";

    return str_replace($chunktoreplace, $uid, $argument);

  }

  /**
   * @Transform table:name,mail,status
   * @Transform table:name,mail,pass,status
   * @Transform table:title,name,owner,id
   * @Transform table:title,name,id,owner
   * @Transform table:name,consumerorgid,role
   * @Transform table:type,title,url,user_managed,default
   *
   * Annoyingly, this function will only match the specifically listed
   * tables. If you need a different table processing, add another row of:
   * @Transform table:<column1>,<column2>... etc
   */
  public function processTableArguments(TableNode $table) {

    $hash = $table->getTable();
    $newHash = array();

    foreach ($hash as $tableRow) {
      $newTableRow = array();
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
  public function printTheConsumerorg() {
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    $orgs = $userUtils->loadConsumerOrgs();
    print("Current org: " . serialize($org) . "\n");
    print("LoadConsumerOrgs: " . serialize($orgs) . "\n");
    $sessionStore = \Drupal::service('tempstore.private')->get('ibm_apim');
    $perms = $sessionStore->get('permissions');
    print("Permissions: " . serialize($perms) . "\n");

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');

    $orgObject = $consumerOrgService->get($org['url']);
    if (isset($orgObject)) {
      print("\nCurrent consumerorg node ID: " . $orgObject->getId() . "\n");
    }
    else {
      print("ERROR: Failed to load Consumerorg node.\n");
    }
  }

  /**
   * @Then Enable ACLDebug
   */
  public function enableACLDebug() {
    if (!\Drupal::config('ibm_apim.settings')->get('acl_debug')) {
      print("Setting 'acl_debug' config to true. ");
      \Drupal::service('config.factory')->getEditable('ibm_apim.settings')->set('acl_debug', TRUE)->save();
    }
  }

  /**
   * @Then Disable ACLDebug
   */
  public function disableACLDebug() {
    if (!\Drupal::config('ibm_apim.settings')->get('acl_debug')) {
      print("Setting 'acl_debug' config to false. ");
      \Drupal::service('config.factory')->getEditable('ibm_apim.settings')->set('acl_debug', FALSE)->save();
    }
  }

  /**
   * @Then dump the current html
   *
   * drupal-extension provides a "Then print last response" which prints the current HTML but it has nothing that
   * can store responses to a file. This is better for debugging on an appliance and would also be more useful
   * in automated runs if we could collect the files together at the end of the run. Travis doesn't seem to do this
   * so this step definition also prints the HTML to the screen if that option is set.
   */
  public function dumpTheCurrentHtml() {

    $this->minkContext->printCurrentUrl();
    print "\n";

    $html = $this->getSession()->getDriver()->getContent();
    $htmlFileName = $this->debugDumpDir . "/failure-html-dump-" . $this->timestamp . "_" . $this->htmlDumpNumber . ".html";
    file_put_contents($htmlFileName, $html);

    print sprintf("HTML failure dump available at %s \n", $htmlFileName);

    if ($this->dumpHtmlToScreen != FALSE) {
      print "Dumping HTML of current page to screen\n";
      print "**************************************\n";
      print $html;
      print "**************************************\n";
    }

    $this->htmlDumpNumber = $this->htmlDumpNumber + 1;

  }

  /**
   * @Then The :contentType content type is present
   *
   * Checks whether the provided content type exists
   */
  public function theContentTypeIsPresent($contentType) {
    $found = FALSE;
    $types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

    foreach ($types as $type) {
      if ($type->id() && $type->id() == $contentType) {
        $found = TRUE;
      }
    }
    if ($found != TRUE) {
      throw new \Exception("The content type $contentType was not found!");
    }
  }

  /**
   * @Given if the field :arg1 is present, enter the value :arg2
   *
   * Our tests need to be run both via travis and directly on the appliance. There are however differences between how the portal behaves
   * in travis vs the actual behaviour we ship to customers. For example, when testing auth_apic in isolation in travis, there is no 'captcha'
   * field as that module is installed on the appliance as part of the apim_profile. In order to make a test that works on the appliance, we must
   * provide a value for the captcha field. For travis, the field doesn't exist and any test that tries to interact with a non-existant field
   * will fail.
   *
   * That is where this function comes in. If there are fields that exist in travis but not on the appliance (or vice-versa), this step definition
   * allows a scenario to function in both cases.
   */
  public function ifTheFieldIsPresentEnterTheValue($fieldIdOrName, $value) {
    $result = $this->findElementsByIdOrName($fieldIdOrName, FALSE);

    if ($result !== NULL) {
      $this->minkContext->assertEnterField($fieldIdOrName, $value);
    }
  }



  /**
   * @Given the element :element is :enabledOrDisabled
   *
   * Asserts that a field identified by the name or id is enabled or disabled.
   **/
  public function theElementIsEnabledOrDisabled($elementIdOrName, $enabled) {

    if ($enabled !== 'enabled' && $enabled !== 'disabled') {
      throw new \Exception("Second argument to this step definition must be either \"enabled\" or \"disabled\"");
    }

    $element = $this->findElementsByIdOrName($elementIdOrName, TRUE);
    if ($element->hasAttribute('disabled')) {
      if ($enabled === 'enabled') {
        throw new \Exception("The element $elementIdOrName is disabled but it should have been enabled!");
      }
    }
    else {
      if ($enabled === 'disabled') {
        throw new \Exception("The element $elementIdOrName is enabled but it should have been disabled!");
      }
    }
  }

  /**
   * @Given I restart the session
   * @Given I start a new session
   *
   * Calls session->stop() and session->start() which kills and respawns the browser
   */
  public function iRestartTheSession() {
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
   */
  public function checkForDrupalMessages($noMessages = NULL, $type = NULL) {
    $page = $this->getSession()->getPage();

    if ($type === NULL) {
      throw new \Exception("You can't use check for message/error/warning without specifying a type of message to look for!!");
    }

    // Depending on the page or the type of user logged in, the css class for messages could differ
    // Allow for there possibly being multiple different classes to match on.
    $cssClasses = array();
    $xpathStrings = array();

    if ($type == "messages") {
      $cssClasses[] = ".alert-success";     // non admin users
      $cssClasses[] = ".messages--status"; // admin users
      $xpathStrings[] = "//div[@aria-label='Status message']"; // for travis
    }
    else if ($type == "warnings") {
      $cssClasses[] = ".alert-warning";
    }
    else if ($type == "errors") {
      $cssClasses[] = ".alert-danger"; // non admin users
      $xpathStrings[] = "//div[@aria-label='Error message']"; // for travis
    }
    else {
      throw new \Exception("Message type " . $type . " is not understood.");
    }

    $messages = array();

    // Process the css classes list
    foreach ($cssClasses as $cssClass) {
      $thisClassMessages = $page->find('css', $cssClass);

      if ($thisClassMessages !== NULL) {
        array_push($messages, $thisClassMessages);
      }
    }

    // Process the xpath selectors
    foreach ($xpathStrings as $xpathSelector) {
      $thisXpathMatches = $page->find('xpath', $xpathSelector);

      if ($thisXpathMatches !== NULL) {
        array_push($messages, $thisXpathMatches);
      }
    }

    if ($noMessages !== NULL && $noMessages !== "") {
      // Expected no messages
      if ($messages !== NULL && sizeof($messages) !== 0) {
        throw new \Exception("There were " . sizeof($messages) . " " . $type . " on the page but we expected none.");
      }
    }
    else {
      // Expected at least one message
      if ($messages === NULL || sizeof($messages) === 0) {
        throw new \Exception("There were no " . $type . " on the page but we expected at least one.");
      }
    }
  }

  /**
   * @Given /^the apim user "(.*)" is( not)? logged in$/
   *
   * Checking that a user is logged in is different in travis than Jenkins/the appliance
   * because our theme changes the behaviour. This step definition handles the differences.
   */
  public function theApimUserIsLoggedIn($username, $not = NULL) {

    // Detect if this test is running in travis
    $amInTravis = getenv("TRAVIS");

    if ($amInTravis) {
      // For travis, just look for text in the page
      if ($not === " not") {
        $this->minkContext->assertPageNotContainsText($username);
      }
      else {
        $this->minkContext->assertPageContainsText($username);
      }
    }
    else {
      // For jenkins / appliance look for the user profile menu
      if ($not === " not") {
        $this->minkContext->assertElementNotOnPage(".imageContainer [title='" . $username . "']");
      }
      else {
        $this->minkContext->assertElementOnPage(".imageContainer [title='" . $username . "']");
      }
    }

  }

  /**
   * @When I click on element :arg1
   */
  public function iClickOnElement($selector) {
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
   */
  public function createUsers(TableNode $table) {

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any user in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No users will be created in the database.\n";

      // drupal-extension will moan if we don't keep a record of who the users are
      foreach ($table as $row) {
        $basicUser = new \stdClass();
        $basicUser->name = $row['name'];
        $basicUser->mail = $row['mail'];
        $basicUser->pass = $row['pass'];
        $basicUser->status = $row['status'];
        if (isset($row['url'])) {
          $basicUser->apic_url = $row['url'];
        }
        else {
          $basicUser->apic_url = $row['name'];
        }
        $this->getUserManager()->addUser($basicUser);
        $this->getUserManager()->setCurrentUser($basicUser);
        $this->apicUsers[$basicUser->mail] = $basicUser;
      }

      return;
    }

    // Users from the database
    $ids = \Drupal::entityQuery('user')->execute();
    $users = User::loadMultiple($ids);

    // We may need to create some users and not others so we should create a new TableNode
    // that we can pass to the parent::createUsers() function if needed
    $makeUsersTableHash = array();
    $makeUsersTableHash[] = $table->getRows()[0]; // row[0] are the column headers

    // For each user we were given in the table, we need to look for a match in the database
    foreach ($table as $row) {
      $userAlreadyExists = FALSE;
      foreach ($users as $drupal_user) {

        if ($drupal_user->getUsername() == $row['name'] || $drupal_user->getEmail() == $row['mail']) {
          print "Found an existing user record for " . $drupal_user->getUsername() . " (email=" . $drupal_user->getEmail() . ") in the database.\n";
          $basicUser = new \stdClass();
          $basicUser->name = $row['name'];
          $basicUser->mail = $row['mail'];
          $basicUser->pass = $row['pass'];
          $basicUser->status = $row['status'];
          if (isset($row['url'])) {
            $basicUser->apic_url = $row['url'];
          }
          else {
            $basicUser->apic_url = $row['name'];
          }
          $basicUser->uid = $drupal_user->id();
          $this->getUserManager()->addUser($basicUser);
          $this->getUserManager()->setCurrentUser($basicUser);
          $this->apicUsers[$drupal_user->getUsername()] = $basicUser;
          $userAlreadyExists = TRUE;
        }
      }

      if (!$userAlreadyExists) {
        // If we get here, we need to create the user;

        // Add in other fields from the database that we don't add to the Users table in the tests.
        // This is required to give valid forms (for example the edit profile form).
        // If this is not sufficient then the tables at the tops of the tests will need to be updated
        // and this code can be removed.

        // Add the headers into the initial row of the table, if they aren't already there:
        $new_headers = ['first_name', 'last_name', 'apic_url'];
        foreach ($new_headers as $header) {
          if (!in_array($header, $makeUsersTableHash[0])) {
            array_push($makeUsersTableHash[0], $header);
          }
        }
        // Set the fields to be stored in the DB.
        $row['first_name'] = 'Andre';
        $row['last_name'] = 'Andresson';
        if (isset($row['url'])) {
          $row['apic_url'] = $row['url'];
        }
        else {
          $row['apic_url'] = $row['name'];
        }

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
   * "Given I am logged in as :name"
   *
   * Overrides DrupalContext::assertLoggedInByName. We need to extend the behaviour of
   * this function so that it doesn't just log in against the web UI but also logs in
   * the local drupal API instance so that we can run database queries from behat
   * as the user that we just logged in as.
   */
  public function assertLoggedInByName($name) {

    // First - log in to the UI by calling the default DrupalContext login function
    parent::assertLoggedInByName($name);

    // Next - log in the local behat drupal API core
    // This is a little trickier but still perfectly doable :)
    $user = $this->getUserManager()->getUser($name);

    $ids = \Drupal::entityQuery('user')->execute();
    $users = User::loadMultiple($ids);
    $foundMatch = FALSE;

    foreach ($users as $dbuser) {
      if ($dbuser->getUsername() == $user->name) {
        $foundMatch = TRUE;
        user_login_finalize($dbuser);
        $this->localDrupalLoggedIn = TRUE;
        break;
      }
    }
    if ($foundMatch != TRUE) {
      throw new \Exception("No user found with name: ('$name')");
    }

  }

  /**
   * Given I am an anonymous user
   * Given I am not logged in
   *
   * As with "I am logged in" above, we need to change the log out procedure
   * so that we log out of both the UI drupal instance and the local behat
   * drupal instance.
   */
  public function assertAnonymousUser() {

    // Cause the site UI user to be logged out
    parent::assertAnonymousUser();

    // Also log out the local behat drupal api user
    // A few checks needed here to prevent any code from blowing up
    // or printing a warning (both cause test failure) if we are not
    // actually logged in.
    if (\Drupal::currentUser()
        ->isAuthenticated() && $this->localDrupalLoggedIn === TRUE && session_status() == PHP_SESSION_ACTIVE
    ) {
      user_logout();
    }

  }

}
