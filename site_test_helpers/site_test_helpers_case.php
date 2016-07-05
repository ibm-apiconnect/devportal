<?php
/**
 * @file
 * Site Test helpers functionality.
 */

use Symfony\Component\CssSelector\CssSelectorConverter;
use Drupal\drupal_helpers\Random;

/**
 * Trait SiteTestHelpers.
 */
trait SiteTestHelpers {

  /**
   * @defgroup test_framework_internals Test framework internals
   * @{
   * Helper methods and overrides for testing framework itself. None of these
   * methods should be used from within tests.
   */

  /**
   * Constructor.
   */
  public function __construct($test_id = NULL) {
    parent::__construct($test_id);

    // Add current class to an array of skipped classes. This is used for
    // internal testing framework functionality.
    $this->skipClasses[__CLASS__] = TRUE;

    // Load Symfony classes.
    spl_autoload_register('site_test_helpers_symfony_class_loader');
  }

  /**
   * Refresh the in-memory set of variables.
   *
   * Refresh the in-memory set of variables. Useful after a page request is made
   * that changes a variable in a different thread.
   *
   * This is an overridden version of the function provided by the Drupal test
   * module. It maintains any settings created in settings.php (and it's
   * corresponding global.inc) file.
   *
   * In other words calling a settings page with $this->drupalPost() with a
   * changed value would update a variable to reflect that change, but in
   * the thread that made the call (thread running the test) the changed
   * variable would not be picked up.
   *
   * This method clears the variables cache and loads a fresh copy from
   * the database to ensure that the most up-to-date set of variables is loaded.
   */
  protected function refreshVariables() {
    global $conf;
    cache_clear_all('variables', 'cache_bootstrap');
    $variables = variable_initialize();
    // Merge updated database variables back into $conf.
    foreach ($variables as $name => $value) {
      $conf[$name] = $value;
    }

    return $conf;
  }

  /**
   * @} End of "Test framework internals"
   */

  /**
   * @defgroup parent_overrides Parent overrides
   * @{
   * Overrides of parent class methods. These methods are likely to be used
   * in most of tests.
   */

  /**
   * {@inheritdoc}
   */
  protected function drupalLogin(stdClass $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $edit = [
      'name' => $account->name,
      'pass' => $account->pass_raw,
    ];

    $this->drupalPost('user', $edit, t('Log in'));
    $this->verbose($this->drupalGetContent());

    // If the body has the logged in class, a logged in page is being served.
    $this->assertElementExistsByCss('body.logged-in', format_string('User with name !name (!uid) is logged in.', [
      '!name' => $account->name,
      '!uid' => $account->uid,
    ]));
    $this->loggedInUser = $account;

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  protected function drupalLogout() {
    // Overrides default logout functionality to avoid returning to the user
    // page.
    $this->drupalGet('user/logout');
    $this->drupalGet('<front>');
    $this->assertElementExistsByCss('body.not-logged-in', 'User is no longer logged in');
    $this->loggedInUser = FALSE;
  }

  /**
   * Generate random name.
   */
  public static function randomName($length = 8) {
    return Random::name($length);
  }

  /**
   * Generate random string.
   */
  public static function randomString($length = 8) {
    return Random::string($length);
  }

  /**
   * @} End of "Parent overrides"
   */

  /**
   * @defgroup utilities_and_helpers Utilities and helpers
   * @{
   * Utilities and helper methods to manipulate tests data before assertions.
   */

  /**
   * Finds elements using rudimentary css selectors.
   *
   * Currently only DOM elements with a single attribute, eg:
   *   - 'div.classname'
   *   - 'span#id'
   *   - 'a[href="omg"]'
   *
   * @param string $selector
   *   The XPath used to find the element.
   *
   * @return array
   *   Array of SimpleXMLElement objects found using the $selector parameter or
   *   FALSE if the elements were not found.
   */
  protected function findElementByCss($selector) {
    $elements = $this->xpath($this->cssToXpath($selector));

    return (!is_array($elements) || count($elements) == 0) ? FALSE : $elements;
  }

  /**
   * Helper to convert css path to xpath using Symfony component.
   *
   * @param string $selector
   *   CSS selector string.
   *
   * @return string
   *   Xpath string.
   */
  protected function cssToXpath($selector) {
    // Use CssSelector class from Symfony framework.
    if (!class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
      throw new Exception(t('Unable to load class Symfony\Component\CssSelector\CssSelectorConverter'));
    }

    $converter = new CssSelectorConverter();

    return $converter->toXpath($selector);
  }

  /**
   * Gets link's href attribute given link's CSS path is passed as argument.
   *
   * @param string $css_path
   *   CSS path to the link, e.g. '.link-container a.the_link'.
   *
   * @return string|bool
   *   String href attribute of the link or FALSE if none found.
   */
  protected function getLinkHrefByCss($css_path) {
    $link = (array) reset($this->findElementByCss($css_path));

    return !empty($link['@attributes']['href']) ? $link['@attributes']['href'] : FALSE;
  }

  /**
   * Helper to find element by href.
   *
   * Finds links that start with specified href.
   *
   * @param string $href
   *   Link's href.
   * @param string $root
   *   Optional CSS query root (context). Defaults to 'body'.
   *
   * @return array
   *   Array of found elements.
   */
  protected function findElementByHref($href, $root = NULL) {
    if (!$root) {
      $root = 'body';
    }

    return $this->findElementByCss($root . ' a[href^="' . $href . '"]');
  }

  /**
   * Get parent DOM node for provided element.
   */
  protected function getParentDomNode($element) {
    return current($element->xpath('parent::*'));
  }

  /**
   * Helper to retrieve field-agnostic value.
   *
   * @param object $field
   *   Field object returned from findElementByCss().
   *
   * @return mixed
   *   Field value.
   */
  protected function getFormElementValue($field) {
    $tag = $field->getName();
    switch ($tag) {
      case 'input':
        $attr = $field->attributes();
        $value = (string) $attr['value'];
        break;

      case 'textarea':
      default:
        $value = (string) $field;
        break;
    }

    return $value;
  }

  /**
   * Process data provider and invoke callback.
   *
   * Implements functionality similar to phpunit's data provider.
   *
   * @code
   * public function testAddition() {
   *   $this->processProvider('providerAddition', function ($a, $b, $expected, $assertion_number) {
   *     $this->assertEqual(myaddition($a, $b), $expected, format_string('Addition result is correct for assertion @number', [
   *       '@number' => $assertion_number + 1,
   *     ]));
   *   });
   * }
   *
   * public function providerAddition() {
   *   return [
   *     [0, 1, 1],
   *     [1, 1, 2],
   *   ];
   * }
   * @endcode
   *
   * @param string $provider_method
   *   Data provider method that should return an array of arrays that are
   *   callback arguments.
   * @param string $callback
   *   Callback to be called with each data row from provider.
   */
  protected function processProvider($provider_method, $callback) {
    if (!method_exists($this, $provider_method)) {
      throw new Exception(format_string('Unable to find provider method @method in class @class', [
        '@method' => $provider_method,
        '@class' => get_class($this),
      ]));
    }

    $provider_data = call_user_func([$this, $provider_method]);

    foreach ($provider_data as $index => $data) {
      $args = array_merge($data, [$index]);
      call_user_func_array($callback, $args);
    }
  }

  /**
   * @} End of "Utilities and helpers"
   */

  /**
   * @defgroup user User management
   * @{
   * Methods to manage user object.
   */

  /**
   * Helper to set user account password.
   *
   * Useful on custom registration forms.
   *
   * @param object $account
   *   Loaded user object.
   * @param string $password
   *   Password string.
   *
   * @return object
   *   Fully loaded user object wth filled in pass_raw property.
   */
  protected function setUserPassword($account, $password) {
    $edit['pass'] = $password;
    $account = user_save($account, $edit);
    $account->pass_raw = $password;

    return $account;
  }

  /**
   * Create a user with a particular role.
   *
   * @param array|string $role_names
   *   String role or array of role names to assign to user. Note that the user
   *   always has the default permissions derived from the "authenticated users"
   *   role.
   * @param string $password
   *   Preferred password to set for the user.
   * @param array $edit_overrides
   *   Values for user or user profile fields to override.
   *
   * @return object|bool
   *   A fully loaded user object with pass_raw property, or FALSE if account
   *   creation fails.
   */
  protected function drupalCreateUserWithRoles($role_names = [], $password = NULL, $edit_overrides = []) {
    // Create a user assigned to that role.
    $edit = [];
    $edit['mail'] = Random::email();
    $edit['name'] = $edit['mail'];
    $edit['pass'] = (is_null($password)) ? user_password() : $password;
    $edit['status'] = 1;
    $edit['roles'] = [];
    if (!empty($role_names)) {
      $role_names = is_array($role_names) ? $role_names : [$role_names];
      foreach ($role_names as $rolename) {
        $role = user_role_load_by_name($rolename);
        $edit['roles'][$role->rid] = $role->name;
      }
    }

    // Merge fields with provided $edit_overrides.
    $edit_overrides = array_merge($edit, $edit_overrides);

    // Build an empty user object, including all default fields.
    $account = drupal_anonymous_user();
    $account->roles = array_merge($account->roles, $edit_overrides['roles']);
    foreach (field_info_instances('user', 'user') as $field_name => $info) {
      if (!isset($account->{$field_name})) {
        $account->{$field_name} = [];
      }
    }

    $account = user_save($account, $edit_overrides);

    if (empty($account->uid)) {
      return FALSE;
    }

    $account->pass_raw = $edit_overrides['pass'];

    $this->assertTrue(
      !empty($account->uid),
      t('User created with name %name (%uid) and pass %pass and roles %roles',
        [
          '%roles' => implode(', ', $role_names),
          '%name' => $edit['name'],
          '%uid' => $account->uid,
          '%pass' => $edit['pass'],
        ]),
      t('User login')
    );

    return $account;
  }

  /**
   * @} End of "User management"
   */

  /**
   * @defgroup assertions Assertions
   * @{
   * Custom and existing overridden assertions.
   */

  /**
   * {@inheritdoc}
   *
   * Adds both values asserted to the assert equal test messages for easier
   * debugging.
   */
  protected function assertEqual($first, $second, $message = '', $group = 'Other') {
    if (is_array($first) || is_array($second)) {
      throw new Exception('Improper use of assertEqual() method with array arguments. Try using assertArray() instead.');
    }

    if (empty($message)) {
      $message = format_string('@message (First value was "@first", second value was "@second")', [
        '@message' => $message,
        '@first' => $first,
        '@second' => $second,
      ]);
    }

    return parent::assertEqual($first, $second, $message, $group);
  }

  /**
   * Assert equality of 2 arrays.
   */
  protected function assertArray($actual, $expected, $message = 'Array values are equal', $strict_keys = FALSE) {
    $fail_count = 0;

    // Make this assertion universal.
    if (is_scalar($actual) && is_scalar($expected)) {
      return $this->assertEqual($actual, $expected, $message);
    }

    $expected = (array) $expected;
    $actual = (array) $actual;
    if (count($actual) != count($expected)) {
      $fail_count++;
    }
    else {
      foreach ($expected as $expected_k => $expected_v) {
        foreach ($actual as $actual_k => $actual_v) {
          if ($expected_v == $actual_v) {
            if ($strict_keys) {
              if ($expected_k != $actual_k) {
                $fail_count++;
                // No need to proceed.
                break(2);
              }
            }

            continue(2);
          }
        }

        $fail_count++;
        // No need to proceed.
        break;
      }
    }

    $pass = $fail_count === 0;

    if (!$pass) {
      $message = empty($message) ? $message : rtrim($message, '.') . '. ';
      if (drupal_is_cli()) {
        $message .= 'Expected: ' . print_r($expected, TRUE) . ' Actual: ' . print_r($actual, TRUE);
      }
      else {
        $message .= 'Expected: <pre>' . print_r($expected, TRUE) . '</pre> Actual: <pre>' . print_r($actual, TRUE) . '</pre>';
      }
    }

    return $this->assertTrue($pass, $message);
  }

  /**
   * Assert that element has CSS class.
   */
  protected function assertCssClassExists($class, $element) {
    $attr = $element->attributes();
    if (empty($attr['class'])) {
      $this->fail(format_string('CSS class %class does not exist', ['%class' => $class]));

      return FALSE;
    }

    $classes = explode(' ', (string) $attr['class']);

    return $this->assertTrue(in_array($class, $classes), format_string('CSS class %class exists', ['%class' => $class]));
  }

  /**
   * Assert that element does not have CSS class.
   */
  protected function assertCssClassDoesNotExist($class, $element) {
    $attr = $element->attributes();
    if (empty($attr['class'])) {
      $this->pass(format_string('CSS class %class does not exist', ['%class' => $class]));

      return TRUE;
    }

    $classes = explode(' ', (string) $attr['class']);

    return $this->assertTrue(!in_array($class, $classes), format_string('CSS class %class does not exist', ['%class' => $class]));
  }

  /**
   * Checks to see if an element with rudimentary css selectors.
   *
   * Currently only dom elements with a single attribute, eg:
   *   - 'div.classname'
   *   - 'span#id'
   *   - 'a[href="omg"]' NOT YET IMPLEMENTED.
   *
   * @param string $selector
   *   The XPath used to find the element.
   * @param string $message
   *   The message to display.
   * @param string $group
   *   The group this message belongs to.
   *
   * @return bool
   *   Result of assertion.
   */
  protected function assertElementExistsByCss($selector, $message = 'Element exists', $group = 'Other') {
    $elements = $this->findElementByCss($selector);

    return $this->assertTrue(!empty($elements), $message, $group);
  }

  /**
   * Checks to see if an element with rudimentary css selectors.
   *
   * Currently only dom elements with a single attribute, eg:
   *   - 'div.classname'
   *   - 'span#id'
   *   - 'a[href="omg"]' NOT YET IMPLEMENTED.
   *
   * @param string $selector
   *   The XPath used to find the element.
   * @param string $message
   *   The message to display.
   * @param string $group
   *   The group this message belongs to.
   */
  protected function assertElementDoesntExistByCss($selector, $message = 'Element exists', $group = 'Other') {
    $elements = $this->findElementByCss($selector);

    return $this->assertTrue(empty($elements), $message, $group);
  }

  /**
   * Checks to see if an element exists in page markup by using an xPath query.
   *
   * @param string $xpath
   *   The XPath used to find the element.
   * @param array $arguments
   *   An array of arguments with keys in the form ':name' matching the
   *   placeholders in the query. The values may be either strings or numeric
   *   values.
   * @param string $message
   *   The message to display.
   * @param string $group
   *   The group this message belongs to.
   */
  protected function assertElementExistsByXpath($xpath, array $arguments = [], $message = 'Element exists', $group = 'Other') {
    $elements = $this->xpath($xpath, $arguments);
    $this->assertFalse(empty($elements), $message, $group);
  }

  /**
   * Assert that element can be found and has text.
   *
   * @param string $selector
   *   The XPath or CSS query path used to find the element.
   * @param string $message
   *   Assertion message.
   * @param string $group
   *   Assertion group.
   *
   * @return mixed
   *   TRUE if element can be found and has text, FALSE otherwise.
   */
  protected function assertElementHasTextByCss($selector, $message = 'Element with text exists', $group = 'Other') {
    $xpath = $this->cssToXpath($selector);
    $xpath .= '/descendant-or-self::*/text()';
    $elements = $this->xpath($xpath);

    return $this->assertTrue(!empty($elements), $message, $group);
  }

  /**
   * Checks to see if the title element contains specified text.
   *
   * @param string $pattern
   *   Pattern to search for.
   * @param string $message
   *   Verbose message titlte.
   * @param string $group
   *   Assertion group name.
   */
  protected function assertTitleContains($pattern, $message = 'Title contains', $group = 'Other') {
    $elements = $this->xpath('//title');
    $this->assertFalse(empty($elements), 'Title element not found.');
    $title = $elements[0]->asXML();

    $this->assertTrue(strpos($title, $pattern) !== FALSE, $message, $group);
  }

  /**
   * Assert that URL has specified parameter with value.
   */
  protected function assertUrlParameter($name, $value, $url = NULL) {
    if (!$url) {
      $url = $this->getUrl();
    }

    $url_parsed = drupal_parse_url($url);

    if (!empty($url_parsed['query'][$name])) {
      return $this->assertEqual($value, $url_parsed['query'][$name], format_string('Query parameter %parameter exists and is set to %value', [
        '%parameter' => $name,
        '%value' => $value,
      ]));
    }
    else {
      $this->fail(format_string('Query parameter %parameter does not exist in URL', ['%parameter' => $name]));

      return FALSE;
    }
  }

  /**
   * Assert that URL fragment exists.
   */
  protected function assertUrlFragment($fragment, $url = NULL) {
    if (!$url) {
      $url = $this->getUrl();
    }

    $url_parsed = drupal_parse_url($url);

    if (!empty($url_parsed['fragment'])) {
      return $this->assertEqual($fragment, $url_parsed['fragment'], format_string('URL fragment %fragment exists', ['%fragment' => $fragment]));
    }
    else {
      $this->fail(format_string('URL fragment %fragment does not exist', ['%fragment' => $fragment]));

      return FALSE;
    }
  }

  /**
   * Assert redirect.
   */
  protected function assertRedirect($source, $destination) {
    $this->drupalGet($source);

    $source_path = parse_url(url($destination, ['absolute' => TRUE]), PHP_URL_PATH);
    $destination_path = parse_url($this->getUrl(), PHP_URL_PATH);

    $this->assertEqual($destination_path, $source_path);
  }

  /**
   * Assert that DOM container element contains a set of child elements.
   *
   * @code
   * $containers = array(
   *   '.container .container-inner-left' => array(
   *     '.block-class-1' => 'Simple block in left content',
   *     '.block-class-2' => 'Another simple block in left content',
   *     '.block-class-3' => array(
   *       'title' => 'Another simple block',
   *       'optional' => '_string_callback_function',
   *       'another_property' => '_another_string_callback_class_method',
   *       'property_3' => array(
   *         '_string_callback_class_method_with_params',
   *          1,
   *          2,
   *          3,
   *       ),
   *     ),
   *   ),
   *   '.container .container-inner-right' => array(
   *     '.block-class-1' => 'Simple block in right content',
   *   ),
   * );
   * @endcode
   */
  protected function assertContainerContains($containers) {
    foreach ($containers as $container_path => $container) {
      // Skip incorrectly formatted containers.
      if (!is_array($container)) {
        continue;
      }

      // Find container.
      $container_exist = $this->assertElementExistsByCss($container_path, format_string('Container with CSS path !path exists', ['!path' => $container_path]));

      // Do not interrupt test if container does not exist.
      if (!$container_exist) {
        continue;
      }

      $position = 1;
      foreach ($container as $element_path => $element) {
        // Normalise element.
        $element = !is_array($element) ? ['title' => $element] : $element;
        // Merge with defaults.
        $element += [
          'excluded' => FALSE,
        ];

        // Invoke callbacks, if any.
        foreach ($element as $prop => $callback) {
          // Skip title.
          if ($prop == 'title') {
            continue;
          }

          // We assume that string is a method callback name or an array with
          // first element as a callback and the rest are callback parameters.
          if (is_string($callback) || is_array($callback)) {
            // Normalise callback.
            $callback = !is_array($callback) ? [$callback] : $callback;

            // Extract method and arguments.
            $method = array_shift($callback);
            $params = !empty($callback) ? $callback : [];

            // Notify developer that provided parameters are malformed.
            if (!method_exists($this, $method)) {
              throw new Exception(format_string('Class method !method does not exist in !class', [
                '!method' => $method,
                '!class' => get_class($this),
              ]));
            }

            $result = call_user_func_array([$this, $method], $params);
            $element[$prop] = $result;
          }
        }

        // Do not assert for this element if it is excluded.
        if ($element['excluded']) {
          continue;
        }

        $element_path_full = $container_path . ' ' . $element_path;

        // Checks that an element exists.
        $exists = $this->assertElementExistsByCss($element_path_full, format_string('Element !title (!path) found within container !container using full path !full_path', [
          '!title' => $element['title'],
          '!path' => $element_path,
          '!container' => $container_path,
          '!full_path' => $element_path_full,
        ]));

        if ($exists) {
          // Check order of an element.
          $siblings_path = $this->cssToXpath($element_path_full) . '/preceding-sibling::*';
          $siblings = $this->xpath($siblings_path);

          $found_position = count($siblings) + 1;

          $this->assertEqual($found_position, $position, format_string('Element !title (!path) is expected at position !position and found at position !found_position', [
            '!title' => $element['title'],
            '!path' => $element_path,
            '!position' => $position,
            '!found_position' => $found_position,
          ]));
          $position++;
        }
      }
    }
  }

  /**
   * Assert that an input has specified value.
   */
  protected function assertFormInputValue($selector, $value, $message = NULL) {
    $field = $this->findElementByCss($selector);
    $field_found = $this->assertTrue($field, 'Field found');

    if ($field_found) {
      $message = is_null($message) ? 'Actual field value is equal to expected' : $message;

      return $this->assertEqual($value, $this->getFormElementValue($field[0]), $message);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Assert that an input does not have specified value.
   */
  protected function assertFormInputNotValue($selector, $value, $message = NULL) {
    $field = $this->findElementByCss($selector);
    $field_found = $this->assertTrue($field, 'Field found');

    if ($field_found) {
      $message = is_null($message) ? 'Actual field value is not equal to expected' : $message;

      return $this->assertNotEqual($value, $this->getFormElementValue($field[0]), $message);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Assert validation messages.
   *
   * @param string $path
   *   Path to page where validation will be performed.
   * @param array $edit
   *   Array of fields to set values.
   * @param array $messages
   *   Array of expected messages.
   *
   * @return bool
   *   TRUE if all validation messages appear, FALSE otherwise.
   */
  protected function assertFormValidation($path, array $edit, array $messages) {
    $this->drupalPost($path, $edit, t('Save'));
    $valid = 0;
    foreach ($messages as $message) {
      $valid += $this->assertText($message);
    }

    return $valid == count($messages);
  }

  /**
   * Assert no validation messages.
   *
   * @param string $path
   *   Path to page where validation will be performed.
   * @param array $edit
   *   Array of fields to set values.
   * @param array $messages
   *   Array of not expected messages.
   *
   * @return bool
   *   TRUE if none of validation messages appear, FALSE otherwise.
   */
  protected function assertFormNoValidation($path, array $edit, array $messages) {
    $this->drupalPost($path, $edit, t('Save'));
    $valid = 0;
    foreach ($messages as $message) {
      $valid += $this->assertNoText($message);
    }

    return $valid == count($messages);
  }

}
