Feature: Login
  In order to use the developer portal
  I need to be able to Sign in

  Scenario: Viewing the login page
    Given I am not logged in
    And I am at "/user/login"
    Then I should see the text "Sign in"
    And I should see the text "Create new account"
    And I should see the text "Reset your password"
    And I should see the text "Username"
    And I should see the text "Password"

@api
  Scenario: Sign in as a non-admin user
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "sign in"
    Given the apim user "@data(andre.mail)" is logged in
    Given I am at "/user/logout"
    Then I should see the text "Sign in"

@api
  Scenario: Attempt Sign in with incorrect password
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    When I am at "/user/login"
    Given I enter "@data(andre.mail)" for "Username"
    And I enter "invalidPassword" for "Password"
    When I press the "Sign in" button
    Then there are errors
    And I should see the text "Forgot your password? Click here to reset it."
    And the apim user "@data(andre.mail)" is not logged in

@api
  Scenario: Login form loads with multiple user registries
  Given the cache has been cleared
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[0].title)"
  And I should see "or" in the ".apic-user-form-or" element
  And I should see the text "or"
  And I should see the text "Continue with"
  And I should see the link "@data(user_registries[2].title)"
  And I should see the link "Forgot password?"
  And I should see the text "Don't have an account?"
  And I should see the link "Sign up"

@api
  Scenario: Login form changes user registry via link
  Given the cache has been cleared
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[0].title)"
  And I should see the link "@data(user_registries[2].title)"
  When I click "@data(user_registries[2].title)"
  And I should see the text "Sign in with @data(user_registries[2].title)"
  And I should see the link "@data(user_registries[0].title)"

@api
Scenario: Login form loads non default registry directly
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | lur  | @data(user_registries[1].title)   | @data(user_registries[1].url)     | yes           | no      |
  When I am at "/user/login?registry_url=@data(user_registries[1].url)"
  Then I should see the text "Sign in with @data(user_registries[1].title)"

@api
  Scenario: Login form handles invalid user registry url query parameter by using default registry
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
  When I am at "/user/login?registry_url=thisisnotvalid"
  Then I should see the text "Sign in with @data(user_registries[0].title)"

@api
Scenario: Login form handles valid but incorrect user registry url query parameter by using default registry
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
  When I am at "/user/login?registry_url=/consumer-api/user-registries/aaaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee"
  Then I should see the text "Sign in with @data(user_registries[0].title)"

  @api
Scenario: Login form loads with oidc registry link (non-default)
  Given the cache has been cleared
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | oidc | @data(user_registries[3].title)   | @data(user_registries[3].url)     | no           | no      |
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[0].title)"
  And I should see "or" in the ".apic-user-form-or" element
  And I should see the text "or"
  And I should see the text "Continue with"
  And I should see the link "@data(user_registries[3].title)"
  And I should see a link with href including "/consumer-api/oauth2/authorize"
  And I should see the link "Forgot password?"
  And I should see the text "Don't have an account?"
  And I should see the link "Sign up"
