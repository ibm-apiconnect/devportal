@api
Feature: APICRUD
  In order to use the developer portal
  I need to be able to use apis

  Scenario: Content type exists
    Given I am at "/"
    Then The "api" content type is present

  Scenario: Create then update an api
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    Given I publish an api with the name "api_@now" and id "apiId_@now"
    When I update the api to have the url "https://anewurl.com"
    Then I should have an api with the id "apiId_@now" and url "https://anewurl.com"

  Scenario: Create then delete an api
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    Given I publish an api with the name "api_@now"
    When I delete the api with the name "api_@now"
    Then I should no longer have an api with the name "api_@now"

  Scenario: Create api and create forum
    Given I publish an api with the name "api_@now" and autocreate_apiforum is true
    Then I should have an api and a forum both with the name "api_@now"

  Scenario: Create api, do not create forum
    Given I publish an api with the name "api_@now" and autocreate_apiforum is false
    Then I should have an api name "api_@now" and no forum

  Scenario: Create api and create category taxonomies
    Given I publish an api with the name "api_@now", id "apiId_@now" and categories "Sport / Ball / Rugby"
    Then I should have an api with the name "api_@now", id "apiId_@now" and categories "Sport / Ball / Rugby"

  Scenario: Create api, do not create category taxonomies
    Given I publish an api with the name "api_@now" and categories "Animals / Fluffy / Cat" and create_taxonomies_from_categories is false
    Then I should have an api with name "api_@now" and no taxonomies for the categories "Animals / Fluffy / Cat"

  Scenario: Create api with phase based tagging enabled
    Given I publish an api with the name "api_@now" and the phase "Realized" and autotag_with_phase is true
    Then I should have an api with the name "api_@now" tagged with the phase "Realized"
