Feature: API Fields
  As Admin I should not be able to edit core fields.

  @api
  Scenario: I can not see the API interal fields
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
    And I am editing the "api" node "PetStore"
    And I should not see the text "APIC Catalog ID"
    And I should not see the text "APIC Hostname"
    And I should not see the text "APIC Provider ID"
    And I should not see the text "Open API Document"
    And I should not see the text "IBM Configuration"
    And I should not see the text "OAI Version"
    And I should not see the text "SOAP Version"
    And I should not see the text "Open API Tags"
    And I should not see the text "WSDL Content"
    And I should not see the text "X-IBM-Name"