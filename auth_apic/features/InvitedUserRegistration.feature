Feature: Invited User Registration
  After being invited to the portal and I don't have an account in the user registry
  I need to be able to register a new user

  @mocked
  Scenario: Hit the user register route directly - anonymous user
    Given I am not logged in
    And I am at "/"
    And I am at "/user/invitation"
    Then there are errors
    And I should see "Missing invitation token. Unable to proceed."
    And there are no messages
    And there are no warnings

  @api
  Scenario: Hit the user register route directly - authenticated user
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/user/invitation"
    Then there are errors
    And I should see "Missing invitation token. Unable to proceed."
    And there are no messages
    And there are no warnings

  @mocked
  Scenario: Inviting a user with a very obviously invalid token
    Given I am not logged in
    And I am at "/"
    And I am at "/user/invitation/?activation=blah"
    Then there are errors
    And there are no warnings
    And there are no messages
    And I should see "Invalid invitation token. Contact the system administrator for assistance"

  @mocked
  Scenario: Inviting a user with a invalid token (too many JWT segments)
    Given I am not logged in
    And I am at "/"
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV3UUdkdFlXbHNMbU52YlNJc0ltbHpjeUk2SWtsQ1RTQkJVRWtnUTI5dWJtVmpkQ0lzSW5SdmEyVnVYM1I1Y0dVaU9pSjBaVzF3YjNKaGNua2lMQ0pwWVhRaU9qRTFNakUwTmpFNE16WXNJbVY0Y0NJNk1UVXlNVFl6TkRZek5pd2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WnkxcGJuWnBkR0YwYVc5dWN5ODRNMk5qTVdFMk5pMHpabVF6TFRRME1HWXRZVE5pWXkxa016YzNabU0zTlRNMVl6VWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVp5MXBiblpwZEdGMGFXOXVjeTg0TTJOak1XRTJOaTB6Wm1RekxUUTBNR1l0WVROaVl5MWtNemMzWm1NM05UTTFZelVpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZZamRqTWpObU9Ea3RaVEF5WmkwMFlUY3pMVGczWlRBdE16QTFNRGxsTjJFellUUTVPakkyWWpZNU5qTTFMVFpoT0RBdE5HTTVaQzFpTVRJMkxUVm1Zems0T0RCbE5UVmlZaUo5ZlEudU1BRmVxeUF4VURoSUZJLUNBOFdTMjE0dmFRSVcwRHc2bFVLS09hZ3pXdy51TUFGZXF5QXhVRGhJRkktQ0E4V1MyMTR2YVFJVzBEdzZsVUtLT2Fneld3"
    Then there are errors
    And there are no warnings
    And there are no messages
    And I should see "Invalid invitation token. Contact the system administrator for assistance"

  @mocked
  Scenario: Inviting a user with a valid token
    Given I am not logged in
    And I am at "/"
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVptRnJaUzExYzJWeVFHVjRZVzF3YkdVdVkyOXRJaXdpYVhOeklqb2lTVUpOSUVGUVNTQkRiMjV1WldOMElpd2lkRzlyWlc1ZmRIbHdaU0k2SW5SbGJYQnZjbUZ5ZVNJc0ltbGhkQ0k2TVRVeU1UUTJNVGd6Tml3aVpYaHdJam94TlRJeE5qTTBOak0yTENKelkyOXdaWE1pT25zaWFXNTJhWFJoZEdsdmJpSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0luVnliQ0k2SWk5amIyNXpkVzFsY2kxaGNHa3ZiM0puTFdsdWRtbDBZWFJwYjI1ekx6Z3pZMk14WVRZMkxUTm1aRE10TkRRd1ppMWhNMkpqTFdRek56ZG1ZemMxTXpWak5TSXNJbUZqZEdsdmJuTWlPbHNpY21WbmFYTjBaWElpTENKaFkyTmxjSFFpWFN3aWNtVmhiRzBpT2lKamIyNXpkVzFsY2pwaU4yTXlNMlk0T1MxbE1ESm1MVFJoTnpNdE9EZGxNQzB6TURVd09XVTNZVE5oTkRrNk1qWmlOamsyTXpVdE5tRTRNQzAwWXpsa0xXSXhNall0Tldaak9UZzRNR1UxTldKaUluMTkuTU5wYi1WODVUT3Y5cGVLbFhESU1MWUF3aUlFWEk5T2hjUmQ3QU9ZTkhuNA=="
    # can't validate for 'no errors' as password policy box is an error
    And there are no warnings
    And there are no messages
    And I should see "To complete your invitation, fill out any required fields below."
    And the "First Name" field should contain ""
    And the "Last Name" field should contain ""
    And the "Username" field should contain ""
    And the "Email address" field should contain "fake-user@example.com"
    And the "Consumer organization" field should contain ""
    And the "Password" field should contain ""

  @api
  Scenario: Inviting a user who is logged in when they open the link
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVptRnJaUzExYzJWeVFHVjRZVzF3YkdVdVkyOXRJaXdpYVhOeklqb2lTVUpOSUVGUVNTQkRiMjV1WldOMElpd2lkRzlyWlc1ZmRIbHdaU0k2SW5SbGJYQnZjbUZ5ZVNJc0ltbGhkQ0k2TVRVeU1UUTJNVGd6Tml3aVpYaHdJam94TlRJeE5qTTBOak0yTENKelkyOXdaWE1pT25zaWFXNTJhWFJoZEdsdmJpSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0luVnliQ0k2SWk5amIyNXpkVzFsY2kxaGNHa3ZiM0puTFdsdWRtbDBZWFJwYjI1ekx6Z3pZMk14WVRZMkxUTm1aRE10TkRRd1ppMWhNMkpqTFdRek56ZG1ZemMxTXpWak5TSXNJbUZqZEdsdmJuTWlPbHNpY21WbmFYTjBaWElpTENKaFkyTmxjSFFpWFN3aWNtVmhiRzBpT2lKamIyNXpkVzFsY2pwaU4yTXlNMlk0T1MxbE1ESm1MVFJoTnpNdE9EZGxNQzB6TURVd09XVTNZVE5oTkRrNk1qWmlOamsyTXpVdE5tRTRNQzAwWXpsa0xXSXhNall0Tldaak9UZzRNR1UxTldKaUluMTkuTU5wYi1WODVUT3Y5cGVLbFhESU1MWUF3aUlFWEk5T2hjUmQ3QU9ZTkhuNA=="
    Then there are errors
    And I should see "Unable to complete the invitation process as you are logged in. Please log out and click on the invitation link again to complete the invitation process."
    And there are no warnings
    And there are no messages
    And I should be on "/"

  @api
  Scenario: Inviting a user who exists
    Given I am not logged in
    Given users:
      | name           | mail                        | pass     | status |
      | invitationuser | andreinvitation@example.com | Qwert123 | 1      |
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVZwYm5acGRHRjBhVzl1UUdWNFlXMXdiR1V1WTI5dElpd2lhWE56SWpvaVNVSk5JRUZRU1NCRGIyNXVaV04wSWl3aWRHOXJaVzVmZEhsd1pTSTZJblJsYlhCdmNtRnllU0lzSW1saGRDSTZNVFV5TVRRMk1UZ3pOaXdpWlhod0lqb3hOVEl4TmpNME5qTTJMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMemd6WTJNeFlUWTJMVE5tWkRNdE5EUXdaaTFoTTJKakxXUXpOemRtWXpjMU16VmpOU0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjanBpTjJNeU0yWTRPUzFsTURKbUxUUmhOek10T0RkbE1DMHpNRFV3T1dVM1lUTmhORGs2TWpaaU5qazJNelV0Tm1FNE1DMDBZemxrTFdJeE1qWXROV1pqT1RnNE1HVTFOV0ppSW4xOS5aRURFRHQwVUtQOFFTbDc5dzRva0tncUFmZm9WdXZua1kyNTZtbUxWUXRz"
    Then there are no errors
    And there are no warnings
    And there are no messages
    And I should be on "/user/login"

  Scenario: Inviting a user who does not exist
    Given I am not logged in
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVptRnJaUzExYzJWeVFHVjRZVzF3YkdVdVkyOXRJaXdpYVhOeklqb2lTVUpOSUVGUVNTQkRiMjV1WldOMElpd2lkRzlyWlc1ZmRIbHdaU0k2SW5SbGJYQnZjbUZ5ZVNJc0ltbGhkQ0k2TVRVeU1UUTJNVGd6Tml3aVpYaHdJam94TlRJeE5qTTBOak0yTENKelkyOXdaWE1pT25zaWFXNTJhWFJoZEdsdmJpSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0luVnliQ0k2SWk5amIyNXpkVzFsY2kxaGNHa3ZiM0puTFdsdWRtbDBZWFJwYjI1ekx6Z3pZMk14WVRZMkxUTm1aRE10TkRRd1ppMWhNMkpqTFdRek56ZG1ZemMxTXpWak5TSXNJbUZqZEdsdmJuTWlPbHNpY21WbmFYTjBaWElpTENKaFkyTmxjSFFpWFN3aWNtVmhiRzBpT2lKamIyNXpkVzFsY2pwaU4yTXlNMlk0T1MxbE1ESm1MVFJoTnpNdE9EZGxNQzB6TURVd09XVTNZVE5oTkRrNk1qWmlOamsyTXpVdE5tRTRNQzAwWXpsa0xXSXhNall0Tldaak9UZzRNR1UxTldKaUluMTkuTU5wYi1WODVUT3Y5cGVLbFhESU1MWUF3aUlFWEk5T2hjUmQ3QU9ZTkhuNA=="
    #Then there are no errors - password policy is an error!
    And there are no warnings
    And there are no messages
    And I should be on "/user/register"
