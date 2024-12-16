@api
Feature: Product Fields
  As Admin I should not be able to edit core fields.

  Scenario: I can not see the Product interal fields
    Given I am not logged in
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(admin.name)    | @data(admin.mail)    | @data(admin.password)    | 1      |
    Given apis:
      | title    | id    | document      |
      | PetStore | 12345 | petstore.json |
    Given products:
      | name      | title     | id     | document      |
      | pet-store | Pet Store | 123456 | PetStoreProduct.json |
    Given I am not logged in
    Given I am logged in as "@data(admin.name)"
    And I am editing the "product" node "Pet Store"
    And I should not see the text "APIC Catalog ID"
    And I should not see the text "APIC Hostname"
    And I should not see the text "APIC Provider ID"
    And I should not see the text "Product document"
    And I should not see the text "Product ID"
    And I should not see the text "Subscribable"
    And I should not see the text "Visibility data"
    And I should not see the text "Visible only to authenticated users"
    And I should not see the text "Custom Organization Visibility"
    And I should not see the text "Custom tags visibility"
    And I should not see the text "Public visibility"