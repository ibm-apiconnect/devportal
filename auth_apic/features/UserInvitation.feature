Feature: User Invitation Controller
  After being invited to the portal the provided token will be processed and I
  will be routed accordingly.

  # @mocked
  # Scenario: Hit the user register route directly - anonymous user
  #   Given I am not logged in
  #   And I am at "/"
  #   And I am at "/user/invitation"
  #   Then there are errors
  #   And I should see "Missing invitation token. Unable to proceed."
  #   And there are no messages
  #   And there are no warnings

  @api
  Scenario: Hit the user register route directly - authenticated user
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/user/invitation"
    Then there are errors
    And I should see "Missing invitation token. Unable to proceed."
    And there are no messages
    And there are no warnings

  # @mocked
  # Scenario: Inviting a user with a very obviously invalid token
  #   Given I am not logged in
  #   And I am at "/"
  #   And I am at "/user/invitation/?activation=blah"
  #   Then there are errors
  #   And there are no warnings
  #   And there are no messages
  #   And I should see "Invalid invitation token. Contact the system administrator for assistance"

  # @mocked
  # Scenario: Inviting a user with a invalid token (too many JWT segments)
  #   Given I am not logged in
  #   And I am at "/"
  #   And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpJVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV3UUdkdFlXbHNMbU52YlNJc0ltbHpjeUk2SWtsQ1RTQkJVRWtnUTI5dWJtVmpkQ0lzSW5SdmEyVnVYM1I1Y0dVaU9pSjBaVzF3YjNKaGNua2lMQ0pwWVhRaU9qRTFNakUwTmpFNE16WXNJbVY0Y0NJNk1UVXlNVFl6TkRZek5pd2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WnkxcGJuWnBkR0YwYVc5dWN5ODRNMk5qTVdFMk5pMHpabVF6TFRRME1HWXRZVE5pWXkxa016YzNabU0zTlRNMVl6VWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVp5MXBiblpwZEdGMGFXOXVjeTg0TTJOak1XRTJOaTB6Wm1RekxUUTBNR1l0WVROaVl5MWtNemMzWm1NM05UTTFZelVpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZZamRqTWpObU9Ea3RaVEF5WmkwMFlUY3pMVGczWlRBdE16QTFNRGxsTjJFellUUTVPakkyWWpZNU5qTTFMVFpoT0RBdE5HTTVaQzFpTVRJMkxUVm1Zems0T0RCbE5UVmlZaUo5ZlEudU1BRmVxeUF4VURoSUZJLUNBOFdTMjE0dmFRSVcwRHc2bFVLS09hZ3pXdy51TUFGZXF5QXhVRGhJRkktQ0E4V1MyMTR2YVFJVzBEdzZsVUtLT2Fneld3"
  #   Then there are errors
  #   And there are no warnings
  #   And there are no messages
  #   And I should see "Invalid invitation token. Contact the system administrator for assistance"

  @mocked
  Scenario: Inviting a user with a valid token
    Given I am not logged in
    And I have apim public keys stored
    And I am at "/"
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVptRnJaUzExYzJWeVFHVjRZVzF3YkdVdVkyOXRJaXdpYVhOeklqb2lTVUpOSUVGUVNTQkRiMjV1WldOMElpd2lkRzlyWlc1ZmRIbHdaU0k2SW5SbGJYQnZjbUZ5ZVNJc0ltbGhkQ0k2TVRVeU1UUTJNVGd6Tml3aVpYaHdJam81TlRJeE5qTTBOak0yTENKelkyOXdaWE1pT25zaWFXNTJhWFJoZEdsdmJpSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0luVnliQ0k2SWk5amIyNXpkVzFsY2kxaGNHa3ZiM0puTFdsdWRtbDBZWFJwYjI1ekx6Z3pZMk14WVRZMkxUTm1aRE10TkRRd1ppMWhNMkpqTFdRek56ZG1ZemMxTXpWak5TSXNJbUZqZEdsdmJuTWlPbHNpY21WbmFYTjBaWElpTENKaFkyTmxjSFFpWFN3aWNtVmhiRzBpT2lKamIyNXpkVzFsY2pwaU4yTXlNMlk0T1MxbE1ESm1MVFJoTnpNdE9EZGxNQzB6TURVd09XVTNZVE5oTkRrNk1qWmlOamsyTXpVdE5tRTRNQzAwWXpsa0xXSXhNall0Tldaak9UZzRNR1UxTldKaUluMTkuYm5BNTNfaFRQcHJINmtjZ2R3OXhEZmZBcFp3aTZDaG10WUhwUXpZeUd2RTVmMTJsNVNvVGN5Y090UTdoSE1JSnV4TERmekNSUmJpcUVSUTRDcEFKcGVRdXRkUmstMUl6cG1XclRnS0x2WUdaVk1hLWgzVnQ0dzBickhYczR4b1ZwYTY5N2FKMFlQNjdvcGFSMkU2TEVQOGExZzVZUzh0TGhHc0ItaDEtc0dmLTRiWm9taG12NmluTFRTT1lZWkZNa2dRcXpaNG1mZU9nTkpvWWFlYl9FRmVWV0F2WnJRQnA0bkJpTVAxYVNNbzl1ZVlGSWFndlVieWN6TmF3ZmcwOUdYaTVFMmRERWtSRnd0Sl9qbEJocHdPR2p5MlFCYjFVbEtCRGJDR1I5YVZZbjl4YVdObGRDTl9ZR3pTMDhtcjl1eTlYSDRkdmN1Y080aVAwdldEQW5R"
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
    And I have apim public keys stored
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVptRnJaUzExYzJWeVFHVjRZVzF3YkdVdVkyOXRJaXdpYVhOeklqb2lTVUpOSUVGUVNTQkRiMjV1WldOMElpd2lkRzlyWlc1ZmRIbHdaU0k2SW5SbGJYQnZjbUZ5ZVNJc0ltbGhkQ0k2TVRVeU1UUTJNVGd6Tml3aVpYaHdJam81TlRJeE5qTTBOak0yTENKelkyOXdaWE1pT25zaWFXNTJhWFJoZEdsdmJpSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0luVnliQ0k2SWk5amIyNXpkVzFsY2kxaGNHa3ZiM0puTFdsdWRtbDBZWFJwYjI1ekx6Z3pZMk14WVRZMkxUTm1aRE10TkRRd1ppMWhNMkpqTFdRek56ZG1ZemMxTXpWak5TSXNJbUZqZEdsdmJuTWlPbHNpY21WbmFYTjBaWElpTENKaFkyTmxjSFFpWFN3aWNtVmhiRzBpT2lKamIyNXpkVzFsY2pwaU4yTXlNMlk0T1MxbE1ESm1MVFJoTnpNdE9EZGxNQzB6TURVd09XVTNZVE5oTkRrNk1qWmlOamsyTXpVdE5tRTRNQzAwWXpsa0xXSXhNall0Tldaak9UZzRNR1UxTldKaUluMTkuYm5BNTNfaFRQcHJINmtjZ2R3OXhEZmZBcFp3aTZDaG10WUhwUXpZeUd2RTVmMTJsNVNvVGN5Y090UTdoSE1JSnV4TERmekNSUmJpcUVSUTRDcEFKcGVRdXRkUmstMUl6cG1XclRnS0x2WUdaVk1hLWgzVnQ0dzBickhYczR4b1ZwYTY5N2FKMFlQNjdvcGFSMkU2TEVQOGExZzVZUzh0TGhHc0ItaDEtc0dmLTRiWm9taG12NmluTFRTT1lZWkZNa2dRcXpaNG1mZU9nTkpvWWFlYl9FRmVWV0F2WnJRQnA0bkJpTVAxYVNNbzl1ZVlGSWFndlVieWN6TmF3ZmcwOUdYaTVFMmRERWtSRnd0Sl9qbEJocHdPR2p5MlFCYjFVbEtCRGJDR1I5YVZZbjl4YVdObGRDTl9ZR3pTMDhtcjl1eTlYSDRkdmN1Y080aVAwdldEQW5R"
    Then there are errors
    And I should see "Unable to complete the invitation process as you are logged in. Please log out and click on the invitation link again to complete the invitation process."
    And there are no warnings
    And there are no messages
    And I should be on "/"

  @api
  Scenario: Inviting a user who exists
    Given I am not logged in
    And I have apim public keys stored
    Given users:
      | name           | mail                        | pass     | status |
      | invitationuser | andreinvitation@example.com | Qwert123IsBadPassword! | 1      |
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVZwYm5acGRHRjBhVzl1UUdWNFlXMXdiR1V1WTI5dElpd2lhWE56SWpvaVNVSk5JRUZRU1NCRGIyNXVaV04wSWl3aWRHOXJaVzVmZEhsd1pTSTZJblJsYlhCdmNtRnllU0lzSW1saGRDSTZNVFV5TVRRMk1UZ3pOaXdpWlhod0lqbzVOVEl4TmpNME5qTTJMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMemd6WTJNeFlUWTJMVE5tWkRNdE5EUXdaaTFoTTJKakxXUXpOemRtWXpjMU16VmpOU0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjanBpTjJNeU0yWTRPUzFsTURKbUxUUmhOek10T0RkbE1DMHpNRFV3T1dVM1lUTmhORGs2TWpaaU5qazJNelV0Tm1FNE1DMDBZemxrTFdJeE1qWXROV1pqT1RnNE1HVTFOV0ppSW4xOS5GUWotQ3RjYVpOSDVlS2c1SlhGMnBfdjBqakJ2SDJ3S0F4RWFaVkJPd3dMN0NrS1dlMXdiY0dWUnZ1SVdMOWJ4bFZFUjBrMGlSTF9MLTUzMldPSkZBQ3EyVFVxU20yNVVBWER4Qmc0YjlUWGVacklBcHpJdmlWT1JnZ1lmSWJoNFRsemN2bXVOSHdhSV9GYnlfR0JRQnF5T2lPdjNpb0dYUlFxSU96cUpQY0xMN3RqdnBhQUhfVWktYi02ZFZZUTBKMW9BM0tYMG1sRVhCelNOeV9Bb0pQaEp0Qi10NGZsS2RwODdGZWhyTTBFMFR2YXZTb2xJNlpoellpX3JBenBZUFNLT1c3VXhaT1JaZGNsc2YwNjVaaXVmenJGNU00WDRLcGVDbFpwVWx5RVFpdW9nbmdvOGZyUHJBYjBrZGtFTjZadHdNZ2RLNksxM3dmMjQwYXVGZ3c="
    Then there are no errors
    And there are no warnings
    And there are no messages
    And I should be on "/user/login"

  Scenario: Inviting a user who does not exist
    Given I am not logged in
    And I have apim public keys stored
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVptRnJaUzExYzJWeVFHVjRZVzF3YkdVdVkyOXRJaXdpYVhOeklqb2lTVUpOSUVGUVNTQkRiMjV1WldOMElpd2lkRzlyWlc1ZmRIbHdaU0k2SW5SbGJYQnZjbUZ5ZVNJc0ltbGhkQ0k2TVRVeU1UUTJNVGd6Tml3aVpYaHdJam81TlRJeE5qTTBOak0yTENKelkyOXdaWE1pT25zaWFXNTJhWFJoZEdsdmJpSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0luVnliQ0k2SWk5amIyNXpkVzFsY2kxaGNHa3ZiM0puTFdsdWRtbDBZWFJwYjI1ekx6Z3pZMk14WVRZMkxUTm1aRE10TkRRd1ppMWhNMkpqTFdRek56ZG1ZemMxTXpWak5TSXNJbUZqZEdsdmJuTWlPbHNpY21WbmFYTjBaWElpTENKaFkyTmxjSFFpWFN3aWNtVmhiRzBpT2lKamIyNXpkVzFsY2pwaU4yTXlNMlk0T1MxbE1ESm1MVFJoTnpNdE9EZGxNQzB6TURVd09XVTNZVE5oTkRrNk1qWmlOamsyTXpVdE5tRTRNQzAwWXpsa0xXSXhNall0Tldaak9UZzRNR1UxTldKaUluMTkuYm5BNTNfaFRQcHJINmtjZ2R3OXhEZmZBcFp3aTZDaG10WUhwUXpZeUd2RTVmMTJsNVNvVGN5Y090UTdoSE1JSnV4TERmekNSUmJpcUVSUTRDcEFKcGVRdXRkUmstMUl6cG1XclRnS0x2WUdaVk1hLWgzVnQ0dzBickhYczR4b1ZwYTY5N2FKMFlQNjdvcGFSMkU2TEVQOGExZzVZUzh0TGhHc0ItaDEtc0dmLTRiWm9taG12NmluTFRTT1lZWkZNa2dRcXpaNG1mZU9nTkpvWWFlYl9FRmVWV0F2WnJRQnA0bkJpTVAxYVNNbzl1ZVlGSWFndlVieWN6TmF3ZmcwOUdYaTVFMmRERWtSRnd0Sl9qbEJocHdPR2p5MlFCYjFVbEtCRGJDR1I5YVZZbjl4YVdObGRDTl9ZR3pTMDhtcjl1eTlYSDRkdmN1Y080aVAwdldEQW5R"
    #Then there are no errors - password policy is an error!
    And there are no warnings
    And there are no messages
    And I should be on "/user/register"

  @api
  Scenario: Inviting a user who exists check admin is not present
    Given I am not logged in
    And I have apim public keys stored
    And ibm_apim settings config boolean property "hide_admin_registry" value is "false"
    Given users:
      | name           | mail                        | pass     | status |
      | invitationuser | andreinvitation@example.com | Qwert123IsBadPassword! | 1      |
    And I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lKbFltUXpNREE1WkMwNE9URmpMVFJqTWpNdE9HRXdOeTB6TlRVNVlXSmhZVEV5TVdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVZwYm5acGRHRjBhVzl1UUdWNFlXMXdiR1V1WTI5dElpd2lhWE56SWpvaVNVSk5JRUZRU1NCRGIyNXVaV04wSWl3aWRHOXJaVzVmZEhsd1pTSTZJblJsYlhCdmNtRnllU0lzSW1saGRDSTZNVFV5TVRRMk1UZ3pOaXdpWlhod0lqbzVOVEl4TmpNME5qTTJMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMemd6WTJNeFlUWTJMVE5tWkRNdE5EUXdaaTFoTTJKakxXUXpOemRtWXpjMU16VmpOU0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6THpnelkyTXhZVFkyTFRObVpETXRORFF3WmkxaE0ySmpMV1F6TnpkbVl6YzFNelZqTlNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjanBpTjJNeU0yWTRPUzFsTURKbUxUUmhOek10T0RkbE1DMHpNRFV3T1dVM1lUTmhORGs2TWpaaU5qazJNelV0Tm1FNE1DMDBZemxrTFdJeE1qWXROV1pqT1RnNE1HVTFOV0ppSW4xOS5GUWotQ3RjYVpOSDVlS2c1SlhGMnBfdjBqakJ2SDJ3S0F4RWFaVkJPd3dMN0NrS1dlMXdiY0dWUnZ1SVdMOWJ4bFZFUjBrMGlSTF9MLTUzMldPSkZBQ3EyVFVxU20yNVVBWER4Qmc0YjlUWGVacklBcHpJdmlWT1JnZ1lmSWJoNFRsemN2bXVOSHdhSV9GYnlfR0JRQnF5T2lPdjNpb0dYUlFxSU96cUpQY0xMN3RqdnBhQUhfVWktYi02ZFZZUTBKMW9BM0tYMG1sRVhCelNOeV9Bb0pQaEp0Qi10NGZsS2RwODdGZWhyTTBFMFR2YXZTb2xJNlpoellpX3JBenBZUFNLT1c3VXhaT1JaZGNsc2YwNjVaaXVmenJGNU00WDRLcGVDbFpwVWx5RVFpdW9nbmdvOGZyUHJBYjBrZGtFTjZadHdNZ2RLNksxM3dmMjQwYXVGZ3c="
    Then there are no errors
    And there are no warnings
    And there are no messages
    And I should be on "/user/login"
    And I should see "To complete your invitation, sign in to an existing account or sign up to create a new account."
    And I should not see the link "admin"

  @api
  Scenario: Member invitation sign in with lur registry
    Given I am not logged in
    And I have apim public keys stored
    Given users:
      | name                     | mail                                   | pass     | status | first_time_login |
      | andre_one                | andre_one@example.com                  | Qwert123IsBadPassword! | 1      | 0                |
      | andre_member_exists      | andre.member@example.com               | Qwert123IsBadPassword! | 1      | 0                |
    Given consumerorgs:
      | title          | name           | id                       | owner     |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk9UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuSEc0V1VGdndfdlVrSGxHMWVnZktYd0l3UFUwcjVYYzBhMWI0dlNCZDJCSUtDODE2a0dtTS1BZDgxRXJKT3RjX25PdzctbDM4OHlkeVBDLU41RGdDQmJGWjRscF9rZDRuWlExMzJhdUJ0TzlPODVyTzRHTFJ4LWE2OXltVDV5Q3BNOUtfWWo2b1NCREZjVWxuQnJ6WkVoU2VhX3FsNmJsZl8xcHlpQ1dwZEh5TXBEOE11NUx1R2UxcXRaNnVWWmc3czJYNGJWMkFyZmFmRkRqOEJlWTRlaGhnU3c3RHlicm1wMGNvWi1NQ2czTGRXWi1YM2JFemdGM1M3SHhKcmo3R0FQY3dXOUVtUmhMUjZqbnBXWUpmeVlYVGtLUjdxaWYzaEV4WExBSDNYZ1ZzYXBsbXYxdDBhYjF0a19YYjVGUmpzcVd5bWt4UDdKcVdqNklvWTlnSU9R"
    Then I should be on "/user/login"
    Given I enter "andre_member_exists for "Username"
    And I enter "Qwert123IsBadPassword!" for "Password"
    When I press the "Sign in" button
#    Then I should be on the homepage - this is where the user should be, c
    Then I should see "Invitation process complete."
    And there are no errors
    And there are no warnings

      @api
  Scenario: Org Owner invitation sign in with lur registry
    Given I am not logged in
    And I have apim public keys stored
    Given users:
      | name                     | mail                                   | pass     | status | first_time_login |
      | andre_owner_exists      | andre.orgowner@example.com              | Qwert123IsBadPassword! | 1      | 0                |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqbzVOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5Ud3U0Q0lEaDJvNmRUdDBRbW1ObVVzRWZmeXFhM1VrREpkRjBWd0VmWVNxMDIxTzdxUzB2MlVPQWhycGtKc0swa1dHMkFxRmVpUWFlR3dxVlZhN3RJOTM4RFBjd1JxeS1sMDVDX241WU9mdHhfM0F1YWFSSzZIeVJjSkFQUmpaUGRSUUJNNlJuYzVLaXJDWTRWVkQ3ZWpxTnFWYVRjc005R1kydHJKdUREWnhwSFhaVi16dDZVUFYxZ2NQS1ZyOW1RcllZaktNRGxIM2piUHZmTlJVTEhLcktYVFI1SnBiYzAyUlFFdEhyU1VybFRiQUtZMEg3ZERHQjhDSUgyYnJJS2tTNjV4R0dGZEtKcWVMXzh6bmJKWEdPTHozX182SXZhZ1BCYkU1N0tVNFAzWndHdzR0Mk43Q0FUZFg3UTBKaFAxc1JsNnllenZ2M1VXTDZRa2YzY1E="
    Then I should be on "/user/login"
    Given I enter "andre_owner_exists for "Username"
    And I enter "Qwert123IsBadPassword!" for "Password"
    And I enter "Consorg" for "Consumer organization"
    When I press the "Sign in" button
#    Then I should be on the homepage - this is where the user should be, c
    Then I should see "Invitation process complete."
    And there are no errors
    And there are no warnings

  Scenario: Member invitation sign up with lur registry
    Given I am not logged in
    And I have apim public keys stored
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlMbWx1ZG1sMFpVQmxlR0Z0Y0d4bExtTnZiU0lzSW1semN5STZJa2xDVFNCQlVFa2dRMjl1Ym1WamRDSXNJblJ2YTJWdVgzUjVjR1VpT2lKcGJuWnBkR0YwYVc5dUlpd2lhV0YwSWpveE5UVTVNekE1TkRrM0xDSmxlSEFpT2prMU5UazBPREl5T1Rjc0luTmpiM0JsY3lJNmV5SnBiblpwZEdGMGFXOXVJam9pTDJOdmJuTjFiV1Z5TFdGd2FTOXZjbWR6THpnMk9UUmpNV1JtTFRNMk1qUXRORGc0T1MxaE1XRTNMVFUyTVRVeE1ESTVZV1l4Wmk5dFpXMWlaWEl0YVc1MmFYUmhkR2x2Ym5Ndk1XTXpaamhsWm1VdE5UYzFZaTAwTURsaUxXRTFaamd0T0dFek5ERXdOall4TVRoa0lpd2lkWEpzSWpvaUwyTnZibk4xYldWeUxXRndhUzl2Y21kekx6ZzJPVFJqTVdSbUxUTTJNalF0TkRnNE9TMWhNV0UzTFRVMk1UVXhNREk1WVdZeFppOXRaVzFpWlhJdGFXNTJhWFJoZEdsdmJuTXZNV016WmpobFptVXROVGMxWWkwME1EbGlMV0UxWmpndE9HRXpOREV3TmpZeE1UaGtJaXdpWVdOMGFXOXVjeUk2V3lKeVpXZHBjM1JsY2lJc0ltRmpZMlZ3ZENKZExDSnlaV0ZzYlNJNkltTnZibk4xYldWeU9qQmpNR0ZsTTJZeUxXSTBOelF0TkRBek5DMDRNemRpTFdFeU5HTTJaalEyWkRWaE5qb3dOMlU1T1dFME55MWhaRE15TFRSaU1qY3RZbVV5T0MxbFpUQmtNakU1WXpJNU5tWWlmWDAuYlVtanBTbWVQYzJ1ZVZ1X2x6RVUyRDZ0dElfZ1BYTG5xZWJvRlVUOUZiWlQ5MkFLOVhYVllWeTBRZ1ZDRFhiaUJZak91aEtXRlliX0pYd1NFNXE5RVh0MWx6T21HckM4WUlsZWVGVTVpdk1qb29vMV9ZdjJmel9Ob0FWRkJMbHlBV3h2Mkl5WXVmbTZCVlhwaXZJNC1zd1YwUDNEUXB3aDBiaVJrYldXNW5PLWFucGEyTl83Nm1SR3gwUGxOZFh6RzZIUXBSZnJtcUF6QXRCMUdXbnlaZnRHdU9YY0ptemN6d0hIS2RYZGpBcXkwR1FCTXRhUzFPRmZ3RmRrZTR6SjZNbVZoSFBTSWh2LTZSVWZjNkRkMDNaYUdXWmQ1eTJ5S3Jhak1NVEk3TEdmdHkwdzhMUG0zRTlKY3RSOTlWVXkyT190LWZvTUNjSXgwNHllR0ZUQXRn"
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Username"
    And I should see the text "Email address"
    And I should see the text "First Name"
    And I should see the text "Last Name"
    #no consumer org field on member invite
    And I should not see the text "Consumer organization"
    And I should see the text "Password"
    And I should see the text "Confirm password"
    Given I enter "andre_member" for "Username"
    And I enter "andre.member.invite@example.com" for "Email address"
    And I enter "Andre" for "First Name"
    And I enter "Member" for "Last Name"
    And I enter "Qwert123IsBadPassword!" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123IsBadPassword!"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    # Password policy is an error :(
    # Then there are no errors
    Then I should see "Your registration request has been received. You may now sign in if your request has been successful."
    And there are no warnings
    And there are no errors

  Scenario: Org owner invitation sign up with lur registry
    Given I am not logged in
    And I have apim public keys stored
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNkdVpYSXVhVzUyYVhSbFFHVjRZVzF3YkdVdVkyOXRJaXdpYVhOeklqb2lTVUpOSUVGUVNTQkRiMjV1WldOMElpd2lkRzlyWlc1ZmRIbHdaU0k2SW1sdWRtbDBZWFJwYjI0aUxDSnBZWFFpT2pFMU5Ua3pNRGt5T1RBc0ltVjRjQ0k2T1RVMU9UUTRNakE1TUN3aWMyTnZjR1Z6SWpwN0ltbHVkbWwwWVhScGIyNGlPaUl2WTI5dWMzVnRaWEl0WVhCcEwyOXlaeTFwYm5acGRHRjBhVzl1Y3k5aE9UVTNNVGt3TXkwNE5UUmlMVFJoTm1VdE9EWXpaaTA0TmpRM1pqRXpNamswTmpRaUxDSjFjbXdpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WnkxcGJuWnBkR0YwYVc5dWN5OWhPVFUzTVRrd015MDROVFJpTFRSaE5tVXRPRFl6WmkwNE5qUTNaakV6TWprME5qUWlMQ0poWTNScGIyNXpJanBiSW5KbFoybHpkR1Z5SWl3aVlXTmpaWEIwSWwwc0luSmxZV3h0SWpvaVkyOXVjM1Z0WlhJNk1HTXdZV1V6WmpJdFlqUTNOQzAwTURNMExUZ3pOMkl0WVRJMFl6Wm1ORFprTldFMk9qQTNaVGs1WVRRM0xXRmtNekl0TkdJeU55MWlaVEk0TFdWbE1HUXlNVGxqTWprMlppSjlmUS5KQ1hldU9vanR0NVRhMVFLaDVjNjhQOWd4d3BkMkgtMXZkUW5hV3REak5fci1nakJRUHRSZF93OVJlV1dzN1o2bG9PM29jdVB3ZWlSajVqN0g2Ujg5QTJuSXhUdDRaVzZoR3dQLXE2Uk9UcnZNU2RIaEhUbzJyU3VjaEg5T1NiVnlkN2NpTEROQ2pJLVBMQk5NQVdETFRxN0JKYVFVMzZINGQ3RjhMWFdodGRYWkdVOFlBY2wtTGNNX2IxSFRwMGhXSVlENWNaRWlzYkFXNUlTcHpXQjV3R29Mbm12SjJvNGZrR2lkRXIwVktoY0I0cF9aa25QQjRmaGJ2VG9XMUFFNS1BWWc3SUhFemRvcXBlaFdULWpWTFQ2TVVnY1pXQlh2S3hNTEgxY1VOdlpvLUI3NmVrQ1QyNEp2azZZNWFkUzFXRUxpUWFacmhmWWNkbkFOMXVaanc="
    And I should see "To complete your invitation, fill out any required fields below."
    And I should see the text "Username"
    And I should see the text "Email address"
    And I should see the text "First Name"
    And I should see the text "Last Name"
    # consumer org field on org owner invite
    And I should see the text "Consumer organization"
    And I should see the text "Password"
    And I should see the text "Confirm password"
    Given I enter "andre_owner" for "Username"
    And I enter "andre.owner.invite@example.com" for "Email address"
    And I enter "Andre" for "First Name"
    And I enter "Owner" for "Last Name"
    And I enter "Consorg" for "Consumer organization"
    And I enter "Qwert123IsBadPassword!" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123IsBadPassword!"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    # Password policy is an error :(
    Then I should see "Your registration request has been received. You may now sign in if your request has been successful."
    And there are no warnings
    And there are no errors

  Scenario: Member invitation sign up with ldap registry
    Given I am not logged in
    And I have apim public keys stored
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk9UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuSEc0V1VGdndfdlVrSGxHMWVnZktYd0l3UFUwcjVYYzBhMWI0dlNCZDJCSUtDODE2a0dtTS1BZDgxRXJKT3RjX25PdzctbDM4OHlkeVBDLU41RGdDQmJGWjRscF9rZDRuWlExMzJhdUJ0TzlPODVyTzRHTFJ4LWE2OXltVDV5Q3BNOUtfWWo2b1NCREZjVWxuQnJ6WkVoU2VhX3FsNmJsZl8xcHlpQ1dwZEh5TXBEOE11NUx1R2UxcXRaNnVWWmc3czJYNGJWMkFyZmFmRkRqOEJlWTRlaGhnU3c3RHlicm1wMGNvWi1NQ2czTGRXWi1YM2JFemdGM1M3SHhKcmo3R0FQY3dXOUVtUmhMUjZqbnBXWUpmeVlYVGtLUjdxaWYzaEV4WExBSDNYZ1ZzYXBsbXYxdDBhYjF0a19YYjVGUmpzcVd5bWt4UDdKcVdqNklvWTlnSU9R"
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
    And I have apim public keys stored
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqbzVOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5Ud3U0Q0lEaDJvNmRUdDBRbW1ObVVzRWZmeXFhM1VrREpkRjBWd0VmWVNxMDIxTzdxUzB2MlVPQWhycGtKc0swa1dHMkFxRmVpUWFlR3dxVlZhN3RJOTM4RFBjd1JxeS1sMDVDX241WU9mdHhfM0F1YWFSSzZIeVJjSkFQUmpaUGRSUUJNNlJuYzVLaXJDWTRWVkQ3ZWpxTnFWYVRjc005R1kydHJKdUREWnhwSFhaVi16dDZVUFYxZ2NQS1ZyOW1RcllZaktNRGxIM2piUHZmTlJVTEhLcktYVFI1SnBiYzAyUlFFdEhyU1VybFRiQUtZMEg3ZERHQjhDSUgyYnJJS2tTNjV4R0dGZEtKcWVMXzh6bmJKWEdPTHozX182SXZhZ1BCYkU1N0tVNFAzWndHdzR0Mk43Q0FUZFg3UTBKaFAxc1JsNnllenZ2M1VXTDZRa2YzY1E="
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
    And I have apim public keys stored
    # Enabled is the default but set it again to make sure
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "true"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk9UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuSEc0V1VGdndfdlVrSGxHMWVnZktYd0l3UFUwcjVYYzBhMWI0dlNCZDJCSUtDODE2a0dtTS1BZDgxRXJKT3RjX25PdzctbDM4OHlkeVBDLU41RGdDQmJGWjRscF9rZDRuWlExMzJhdUJ0TzlPODVyTzRHTFJ4LWE2OXltVDV5Q3BNOUtfWWo2b1NCREZjVWxuQnJ6WkVoU2VhX3FsNmJsZl8xcHlpQ1dwZEh5TXBEOE11NUx1R2UxcXRaNnVWWmc3czJYNGJWMkFyZmFmRkRqOEJlWTRlaGhnU3c3RHlicm1wMGNvWi1NQ2czTGRXWi1YM2JFemdGM1M3SHhKcmo3R0FQY3dXOUVtUmhMUjZqbnBXWUpmeVlYVGtLUjdxaWYzaEV4WExBSDNYZ1ZzYXBsbXYxdDBhYjF0a19YYjVGUmpzcVd5bWt4UDdKcVdqNklvWTlnSU9R"
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
    And I have apim public keys stored
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "false"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk9UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuSEc0V1VGdndfdlVrSGxHMWVnZktYd0l3UFUwcjVYYzBhMWI0dlNCZDJCSUtDODE2a0dtTS1BZDgxRXJKT3RjX25PdzctbDM4OHlkeVBDLU41RGdDQmJGWjRscF9rZDRuWlExMzJhdUJ0TzlPODVyTzRHTFJ4LWE2OXltVDV5Q3BNOUtfWWo2b1NCREZjVWxuQnJ6WkVoU2VhX3FsNmJsZl8xcHlpQ1dwZEh5TXBEOE11NUx1R2UxcXRaNnVWWmc3czJYNGJWMkFyZmFmRkRqOEJlWTRlaGhnU3c3RHlicm1wMGNvWi1NQ2czTGRXWi1YM2JFemdGM1M3SHhKcmo3R0FQY3dXOUVtUmhMUjZqbnBXWUpmeVlYVGtLUjdxaWYzaEV4WExBSDNYZ1ZzYXBsbXYxdDBhYjF0a19YYjVGUmpzcVd5bWt4UDdKcVdqNklvWTlnSU9R"
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
    And I have apim public keys stored
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "false"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk9UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuSEc0V1VGdndfdlVrSGxHMWVnZktYd0l3UFUwcjVYYzBhMWI0dlNCZDJCSUtDODE2a0dtTS1BZDgxRXJKT3RjX25PdzctbDM4OHlkeVBDLU41RGdDQmJGWjRscF9rZDRuWlExMzJhdUJ0TzlPODVyTzRHTFJ4LWE2OXltVDV5Q3BNOUtfWWo2b1NCREZjVWxuQnJ6WkVoU2VhX3FsNmJsZl8xcHlpQ1dwZEh5TXBEOE11NUx1R2UxcXRaNnVWWmc3czJYNGJWMkFyZmFmRkRqOEJlWTRlaGhnU3c3RHlicm1wMGNvWi1NQ2czTGRXWi1YM2JFemdGM1M3SHhKcmo3R0FQY3dXOUVtUmhMUjZqbnBXWUpmeVlYVGtLUjdxaWYzaEV4WExBSDNYZ1ZzYXBsbXYxdDBhYjF0a19YYjVGUmpzcVd5bWt4UDdKcVdqNklvWTlnSU9R"
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
    And I have apim public keys stored
    # Enabled is the default but set it again to make sure
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "true"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqbzVOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5Ud3U0Q0lEaDJvNmRUdDBRbW1ObVVzRWZmeXFhM1VrREpkRjBWd0VmWVNxMDIxTzdxUzB2MlVPQWhycGtKc0swa1dHMkFxRmVpUWFlR3dxVlZhN3RJOTM4RFBjd1JxeS1sMDVDX241WU9mdHhfM0F1YWFSSzZIeVJjSkFQUmpaUGRSUUJNNlJuYzVLaXJDWTRWVkQ3ZWpxTnFWYVRjc005R1kydHJKdUREWnhwSFhaVi16dDZVUFYxZ2NQS1ZyOW1RcllZaktNRGxIM2piUHZmTlJVTEhLcktYVFI1SnBiYzAyUlFFdEhyU1VybFRiQUtZMEg3ZERHQjhDSUgyYnJJS2tTNjV4R0dGZEtKcWVMXzh6bmJKWEdPTHozX182SXZhZ1BCYkU1N0tVNFAzWndHdzR0Mk43Q0FUZFg3UTBKaFAxc1JsNnllenZ2M1VXTDZRa2YzY1E="
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
    And I have apim public keys stored
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "false"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqbzVOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5Ud3U0Q0lEaDJvNmRUdDBRbW1ObVVzRWZmeXFhM1VrREpkRjBWd0VmWVNxMDIxTzdxUzB2MlVPQWhycGtKc0swa1dHMkFxRmVpUWFlR3dxVlZhN3RJOTM4RFBjd1JxeS1sMDVDX241WU9mdHhfM0F1YWFSSzZIeVJjSkFQUmpaUGRSUUJNNlJuYzVLaXJDWTRWVkQ3ZWpxTnFWYVRjc005R1kydHJKdUREWnhwSFhaVi16dDZVUFYxZ2NQS1ZyOW1RcllZaktNRGxIM2piUHZmTlJVTEhLcktYVFI1SnBiYzAyUlFFdEhyU1VybFRiQUtZMEg3ZERHQjhDSUgyYnJJS2tTNjV4R0dGZEtKcWVMXzh6bmJKWEdPTHozX182SXZhZ1BCYkU1N0tVNFAzWndHdzR0Mk43Q0FUZFg3UTBKaFAxc1JsNnllenZ2M1VXTDZRa2YzY1E="
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
    And I have apim public keys stored
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJM1lqSTBORGt4TkMwMk16UmpMVFJtTTJNdE9ERTJZUzFoTXpFd1lUQmpaR1JtTUdNaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YldWdFltVnlRR1Y0WVcxd2JHVXVZMjl0SWl3aWFYTnpJam9pU1VKTklFRlFTU0JEYjI1dVpXTjBJaXdpZEc5clpXNWZkSGx3WlNJNkltbHVkbWwwWVhScGIyNGlMQ0pwWVhRaU9qRTFOVGt6TURrME9UY3NJbVY0Y0NJNk9UVTFPVFE0TWpJNU55d2ljMk52Y0dWeklqcDdJbWx1ZG1sMFlYUnBiMjRpT2lJdlkyOXVjM1Z0WlhJdFlYQnBMMjl5WjNNdk9EWTVOR014WkdZdE16WXlOQzAwT0RnNUxXRXhZVGN0TlRZeE5URXdNamxoWmpGbUwyMWxiV0psY2kxcGJuWnBkR0YwYVc5dWN5OHhZek5tT0dWbVpTMDFOelZpTFRRd09XSXRZVFZtT0MwNFlUTTBNVEEyTmpFeE9HUWlMQ0oxY213aU9pSXZZMjl1YzNWdFpYSXRZWEJwTDI5eVozTXZPRFk1TkdNeFpHWXRNell5TkMwME9EZzVMV0V4WVRjdE5UWXhOVEV3TWpsaFpqRm1MMjFsYldKbGNpMXBiblpwZEdGMGFXOXVjeTh4WXpObU9HVm1aUzAxTnpWaUxUUXdPV0l0WVRWbU9DMDRZVE0wTVRBMk5qRXhPR1FpTENKaFkzUnBiMjV6SWpwYkluSmxaMmx6ZEdWeUlpd2lZV05qWlhCMElsMHNJbkpsWVd4dElqb2lZMjl1YzNWdFpYSTZNR013WVdVelpqSXRZalEzTkMwME1ETTBMVGd6TjJJdFlUSTBZelptTkRaa05XRTJPakEzWlRrNVlUUTNMV0ZrTXpJdE5HSXlOeTFpWlRJNExXVmxNR1F5TVRsak1qazJaaUo5ZlEuSEc0V1VGdndfdlVrSGxHMWVnZktYd0l3UFUwcjVYYzBhMWI0dlNCZDJCSUtDODE2a0dtTS1BZDgxRXJKT3RjX25PdzctbDM4OHlkeVBDLU41RGdDQmJGWjRscF9rZDRuWlExMzJhdUJ0TzlPODVyTzRHTFJ4LWE2OXltVDV5Q3BNOUtfWWo2b1NCREZjVWxuQnJ6WkVoU2VhX3FsNmJsZl8xcHlpQ1dwZEh5TXBEOE11NUx1R2UxcXRaNnVWWmc3czJYNGJWMkFyZmFmRkRqOEJlWTRlaGhnU3c3RHlicm1wMGNvWi1NQ2czTGRXWi1YM2JFemdGM1M3SHhKcmo3R0FQY3dXOUVtUmhMUjZqbnBXWUpmeVlYVGtLUjdxaWYzaEV4WExBSDNYZ1ZzYXBsbXYxdDBhYjF0a19YYjVGUmpzcVd5bWt4UDdKcVdqNklvWTlnSU9R"
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
    And I have apim public keys stored
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | no      |
    When I am at "/user/invitation?activation=ZXlKaGJHY2lPaUpTVXpJMU5pSXNJblI1Y0NJNklrcFhWQ0lzSW10cFpDSTZJbU5qT1dVNVpHVmpMVEkyTTJFdE5ETmpNaTA1WVdZNUxUUmxOemhsWkRoak5ERXhaQ0o5LmV5SnFkR2tpT2lJd04yUmtNMkl3TkMwNU9HSTRMVFF4TW1JdE9HWTNOQzB6TVRFMU9UbGpZV1U1TkdJaUxDSnVZVzFsYzNCaFkyVWlPaUpqYkc5MVpDSXNJbUYxWkNJNkltNHZZU0lzSW5OMVlpSTZJbTR2WVNJc0ltVnRZV2xzSWpvaVlXNWtjbVV1YjNKbmIzZHVaWEpBWlhoaGJYQnNaUzVqYjIwaUxDSnBjM01pT2lKSlFrMGdRVkJKSUVOdmJtNWxZM1FpTENKMGIydGxibDkwZVhCbElqb2lhVzUyYVhSaGRHbHZiaUlzSW1saGRDSTZNVFUxT1RNd09USTVNQ3dpWlhod0lqbzVOVFU1TkRneU1Ea3dMQ0p6WTI5d1pYTWlPbnNpYVc1MmFYUmhkR2x2YmlJNklpOWpiMjV6ZFcxbGNpMWhjR2t2YjNKbkxXbHVkbWwwWVhScGIyNXpMMkU1TlRjeE9UQXpMVGcxTkdJdE5HRTJaUzA0TmpObUxUZzJORGRtTVRNeU9UUTJOQ0lzSW5WeWJDSTZJaTlqYjI1emRXMWxjaTFoY0drdmIzSm5MV2x1ZG1sMFlYUnBiMjV6TDJFNU5UY3hPVEF6TFRnMU5HSXROR0UyWlMwNE5qTm1MVGcyTkRkbU1UTXlPVFEyTkNJc0ltRmpkR2x2Ym5NaU9sc2ljbVZuYVhOMFpYSWlMQ0poWTJObGNIUWlYU3dpY21WaGJHMGlPaUpqYjI1emRXMWxjam93WXpCaFpUTm1NaTFpTkRjMExUUXdNelF0T0RNM1lpMWhNalJqTm1ZME5tUTFZVFk2TURkbE9UbGhORGN0WVdRek1pMDBZakkzTFdKbE1qZ3RaV1V3WkRJeE9XTXlPVFptSW4xOS5Ud3U0Q0lEaDJvNmRUdDBRbW1ObVVzRWZmeXFhM1VrREpkRjBWd0VmWVNxMDIxTzdxUzB2MlVPQWhycGtKc0swa1dHMkFxRmVpUWFlR3dxVlZhN3RJOTM4RFBjd1JxeS1sMDVDX241WU9mdHhfM0F1YWFSSzZIeVJjSkFQUmpaUGRSUUJNNlJuYzVLaXJDWTRWVkQ3ZWpxTnFWYVRjc005R1kydHJKdUREWnhwSFhaVi16dDZVUFYxZ2NQS1ZyOW1RcllZaktNRGxIM2piUHZmTlJVTEhLcktYVFI1SnBiYzAyUlFFdEhyU1VybFRiQUtZMEg3ZERHQjhDSUgyYnJJS2tTNjV4R0dGZEtKcWVMXzh6bmJKWEdPTHozX182SXZhZ1BCYkU1N0tVNFAzWndHdzR0Mk43Q0FUZFg3UTBKaFAxc1JsNnllenZ2M1VXTDZRa2YzY1E="
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
