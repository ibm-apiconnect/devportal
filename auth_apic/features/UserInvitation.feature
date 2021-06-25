Feature: User Invitation Controller
  After being invited to the portal the provided token will be processed and I
  will be routed accordingly.

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

  @api
  Scenario: Inviting a user who exists check admin is not present
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "false"
    Given users:
      | name           | mail                        | pass     | status |
      | invitationuser | andreinvitation@example.com | Qwert123 | 1      |
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVZwYm5acGRHRjBhVzl1UUdWNFlXMXdiR1V1WTI5dElpd2lhWE56SWpvaVNVSk5JRUZRU1NCRGIyNXVaV04wSWl3aWRHOXJaVzVmZEhsd1pTSTZJblJsYlhCdmNtRnllU0lzSW1saGRDSTZNVFV5TVRRMk1UZ3pOaXdpWlhod0lqb3hOVEl4TmpNME5qTTJMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMemd6WTJNeFlUWTJMVE5tWkRNdE5EUXdaaTFoTTJKakxXUXpOemRtWXpjMU16VmpOU0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjanBpTjJNeU0yWTRPUzFsTURKbUxUUmhOek10T0RkbE1DMHpNRFV3T1dVM1lUTmhORGs2TWpaaU5qazJNelV0Tm1FNE1DMDBZemxrTFdJeE1qWXROV1pqT1RnNE1HVTFOV0ppSW4xOS5aRURFRHQwVUtQOFFTbDc5dzRva0tncUFmZm9WdXZua1kyNTZtbUxWUXRz"
    Then there are no errors
    And there are no warnings
    And there are no messages
    And I should be on "/user/login"
    And I should see "To complete your invitation, sign in to an existing account or sign up to create a new account."
    And I should not see the link "admin"

  Scenario: Member invitation sign up with lur registry
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk1UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuQkl5V0JFOGhiU25RdHk4NlJzNHRVMnhtdjN6ZVd0MmlGNjZJaXlDUUo3bw=="
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Username"
    And I should see the text "Email address"
    And I should see the text "First Name"
    And I should see the text "Last Name"
    #no consumer org field on member invite
    And I should not see the text "Consumer organization"
    And I should see the text "Password"
    And I should see the text "Confirm password"
    # Password policy is an error :(
    # Then there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Org owner invitation sign up with lur registry
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqb3hOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5GalJkNzZUUFZjdDhVaU5IRjJBamdXQzdNMDEwMldOWU8zMk5McGw1ajhz"
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Username"
    And I should see the text "Email address"
    And I should see the text "First Name"
    And I should see the text "Last Name"
    # consumer org field on org owner invite
    And I should see the text "Consumer organization"
    And I should see the text "Password"
    And I should see the text "Confirm password"
    # Password policy is an error :(
    # Then there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Member invitation sign up with ldap registry
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk1UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuQkl5V0JFOGhiU25RdHk4NlJzNHRVMnhtdjN6ZVd0MmlGNjZJaXlDUUo3bw=="
    When I click "@data(user_registries[2].title)"
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Username"
    And I should see the text "Password"
    # no consumer org field on member invite
    And I should not see the text "Consumer organization"
    # check we are not on an LUR form
    And I should not see the text "Email address"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should not see the text "Confirm password"
    Then there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Org owner invitation sign up with ldap registry
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqb3hOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5GalJkNzZUUFZjdDhVaU5IRjJBamdXQzdNMDEwMldOWU8zMk5McGw1ajhz"
    When I click "@data(user_registries[2].title)"
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Username"
    And I should see the text "Password"
    # consumer org field on org owner invite
    And I should see the text "Consumer organization"
    # check we are not on an LUR form
    And I should not see the text "Email address"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should not see the text "Confirm password"
    Then there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Member invitation sign up with oidc registry
    Given I am not logged in
    # Enabled is the default but set it again to make sure
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "true"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk1UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuQkl5V0JFOGhiU25RdHk4NlJzNHRVMnhtdjN6ZVd0MmlGNjZJaXlDUUo3bw=="
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the "Sign up" button
    And I should not see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the link "@data(user_registries[3].title)"
    And I should not see the text "Username"
    # no consumer org field member invitation
    And I should not see the text "Consumer organization"
    # check we are not on an LUR form
    And I should not see the text "Email address"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should not see the text "Confirm password"
    Then there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Member invitation sign up with oidc registry with OIDC register form disabled
    Given I am not logged in
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "false"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk1UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuQkl5V0JFOGhiU25RdHk4NlJzNHRVMnhtdjN6ZVd0MmlGNjZJaXlDUUo3bw=="
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the link "@data(user_registries[3].title)"
    And I should see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the "Sign up" button
    And I should not see the text "Username"
    # no consumer org field member invitation
    And I should not see the text "Consumer organization"
    # check we are not on an LUR form
    And I should not see the text "Email address"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should not see the text "Confirm password"
    Then there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Member invitation sign up with oidc registry with OIDC register form disabled
    Given I am not logged in
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "false"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk1UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuQkl5V0JFOGhiU25RdHk4NlJzNHRVMnhtdjN6ZVd0MmlGNjZJaXlDUUo3bw=="
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the link "@data(user_registries[3].title)"
    And I should see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the text "Username"
    # no consumer org field for member invitation
    And I should not see the text "Consumer organization"
    # check we are not on an LUR form
    And I should not see the text "Email address"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should not see the text "Confirm password"
    Then there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Org owner invitation sign up with oidc registry and oidc register form option enabled
    Given I am not logged in
    # Enabled is the default but set it again to make sure
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "true"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqb3hOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5GalJkNzZUUFZjdDhVaU5IRjJBamdXQzdNMDEwMldOWU8zMk5McGw1ajhz"
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Consumer organization"
    And I should see the "Sign up" button
    And I should not see the link "@data(user_registries[3].title)"
    And I should not see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the text "Username"
    # check we are not on an LUR form
    And I should not see the text "Email address"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should not see the text "Confirm password"
    Then there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Org owner invitation sign up with oidc registry and oidc register form option disabled
    Given I am not logged in
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "false"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqb3hOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5GalJkNzZUUFZjdDhVaU5IRjJBamdXQzdNMDEwMldOWU8zMk5McGw1ajhz"
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Consumer organization"
    And I should see the "Sign up" button
    And I should not see the link "@data(user_registries[3].title)"
    And I should not see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the text "Username"
    # check we are not on an LUR form
    And I should not see the text "Email address"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should not see the text "Confirm password"
    Then there are no errors
    And there are no warnings
    And there are no messages


  Scenario: Member invitation sign up with writable ldap registry
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk1UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuQkl5V0JFOGhiU25RdHk4NlJzNHRVMnhtdjN6ZVd0MmlGNjZJaXlDUUo3bw=="
    When I click "@data(user_registries[2].title)"
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Username"
    And I should see the text "Email address"
    And I should see the text "First Name"
    And I should see the text "Last Name"
    #no consumer org field on member invite
    And I should not see the text "Consumer organization"
    And I should see the text "Password"
    And I should see the text "Confirm password"
    # Password policy is an error :(
    # Then there are no errors
    And there are no warnings
    And there are no messages


  Scenario: Org owner invitation sign up with writable ldap registry
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqb3hOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5GalJkNzZUUFZjdDhVaU5IRjJBamdXQzdNMDEwMldOWU8zMk5McGw1ajhz"
    When I click "@data(user_registries[2].title)"
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Username"
    And I should see the text "Email address"
    And I should see the text "First Name"
    And I should see the text "Last Name"
    # consumer org field is on org owner invite
    And I should see the text "Consumer organization"
    And I should see the text "Password"
    And I should see the text "Confirm password"
    # Password policy is an error :(
    # Then there are no errors
    And there are no warnings
    And there are no messages
