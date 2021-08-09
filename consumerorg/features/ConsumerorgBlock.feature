Feature: Consumer Organization Block
  In order to use the developer portal
  the Con Org drop-down menu must display the correct options for my role

  @api
  # Note that this a negative test for an admin user (uid=1) that should not see a con org menu
  Scenario: Sign in as the admin user (uid==1) and verify that the con org menu is not visible
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    Given self service onboarding is enabled
    Given I am on the homepage
    Then I should see the text "admin"
#    I cannot check for not seeing text "Organization" because there is an admin menu option of "Consumer organization"
    And I should not see the text "My organization"
    And I should not see the text "Create organization"
    And I should not see the text "Select organization"
    And there are no errors

  @api
  Scenario: Sign in as a con org owner and verify that the con org menu options are correct
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Given self service onboarding is enabled
    Given I am on the homepage
    Then I should see the text "@data(andre.consumerorg.title)"
    And I should see the text "My organization"
    And I should see the text "Create organization"
    And I should see the text "Select organization"
    And there are no errors

  @api
  Scenario: Sign in as a con org owner of multiple orgs, and verify that the con org menu options are correct
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
      | @data(andre[1].consumerorg.title) | @data(andre[1].consumerorg.name) | @data(andre[1].consumerorg.id) | @data(andre[0].mail) |
    Given I am logged in as "@data(andre[0].mail)"
    Given self service onboarding is enabled
    Given I am on the homepage
    Then I should see the text "@data(andre[0].consumerorg.title)"
    And I should see the text "@data(andre[1].consumerorg.title)"
    And I should see the text "My organization"
    And I should see the text "Create organization"
    And I should see the text "Select organization"
    And there are no errors


  @api
  Scenario: As owner of one con org, I can click on my con org and be taken to the myorg page
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Given I am on the homepage
    Then I should see the text "@data(andre.consumerorg.title)"
    And I should see the text "My organization"
    When I click "My organization"
    Then I should be on "/myorg"
    And I should see the text "@data(andre.consumerorg.title)"
    And I should see the text "@data(andre.mail)"
    And I should see the text "Edit organization"
    And I should see the text "Delete organization"
    And I should see the text "Invite"
    And there are no errors


  @api
  Scenario: As owner of multiple con orgs, I can click my con org and be taken to the myorg page
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
      | @data(andre[1].consumerorg.title) | @data(andre[1].consumerorg.name) | @data(andre[1].consumerorg.id) | @data(andre[0].mail) |
    Given I am logged in as "@data(andre[0].mail)"
    Given I am on the homepage
    Then I should see the text "@data(andre[0].consumerorg.title)"
    And I should see the text "@data(andre[1].consumerorg.title)"
    And I should see the text "My organization"
    When I click "My organization"
    Then I should be on "/myorg"
    And I should see the text "@data(andre[0].consumerorg.title)"
    And I should see the text "@data(andre[0].mail)"
    And I should see the text "Edit organization"
    And I should see the text "Delete organization"
    And I should see the text "Invite"
    And there are no errors


  @api
  Scenario: As a con org owner of multiple orgs, I can switch to a different org from the conorg block, and view changed org in /myorg page
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
      | @data(andre[1].consumerorg.title) | @data(andre[1].consumerorg.name) | @data(andre[1].consumerorg.id) | @data(andre[0].mail) |
    Given I am logged in as "@data(andre[0].mail)"
    Given I am on the homepage
    Then I should see the text "@data(andre[0].consumerorg.title)"
    And I should see the text "@data(andre[1].consumerorg.title)"
    When I click "@data(andre[1].consumerorg.title)"
    When I go to "/myorg"
    Then I should see the text "@data(andre[1].consumerorg.title)"
#   But this doesn't really verify that the conorg has changed... as you can see both conorg titles on this page (apimesh/devportal#4688)
    # commenting this out because its not working reliably - i assume caching issues
#    And I should see the text "Switched consumer organization to @data(andre[1].consumerorg.title)"
#   You only see this message here because of the caching issue with behat tests (it should be seen on the homepage apimesh/devportal#4657)
    And I should see the text "Edit organization"
    And I should see the text "Delete organization"
    And I should see the text "Invite"
    And there are no errors


#Commenting out this test - it doesn't work due to the caching issue: apimesh/devportal#4657
#  @api
#  Scenario: As a con org owner of multiple orgs, I can switch to a different org from the conorg block, and view changed org on the homepage
#    Given users:
#      | name              | mail              | pass                  | status |
#      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
#    Given consumerorgs:
#      | title                     | name                     | id                     | owner             |
#      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
#      | @data(andre[1].consumerorg.title) | @data(andre[1].consumerorg.name) | @data(andre[1].consumerorg.id) | @data(andre[0].mail) |
#    Given I am logged in as "@data(andre[0].mail)"
#    Given I am on the homepage
#    Then I should see the text "@data(andre[0].consumerorg.title)"
#    And I should see the text "@data(andre[1].consumerorg.title)"
#    When I click "@data(andre[1].consumerorg.title)"
#    Then print the current consumerorg
#    Then I should be on "/"
#    Given I am at "/ibm_apim/org/_consumer-orgs_1234_5678_982318735"
#    Then dump the current html
#    And there are messages
#    And I should see the text "Switched consumer organization to @data(andre[1].consumerorg.title)"
#    And I should see the text "My organization"
#    And I should see the text "Create organization"
#    And I should see the text "Select organization"
#    And there are no errors
