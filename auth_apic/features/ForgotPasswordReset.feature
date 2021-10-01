Feature: Forgotten Password Reset
  I have received a reset password link for the portal.
  I can reset the password.

  # Scenario: View password reset form with no resetPasswordToken
  #   Given I am not logged in
  #   # At this point we just need a token parameter, it doesn't need to be valid.
  #   And I am at "/user/forgot-password"
  #   Then I should see the text "Missing token. Contact the system administrator for assistance."

  # Scenario: View password reset form with invalid resetPasswordToken
  #   Given I am not logged in
  #   # At this point we just need a token parameter, it doesn't need to be valid.
  #   And I am at "/user/forgot-password?token=invalidToken"
  #   Then I should see the text "Invalid token. Contact the system administrator for assistance."

  # Other tests for password reset are not possible in behat for the time being give the way that the user id is encoded 
  # in the activation token, these will be handled in unit tests.
  # Tests ommitted:
  #   Scenario: View password reset form with valid resetPasswordToken
  #   Scenario: Request password reset for valid user
  #   Scenario: Request password reset for invalid user
  #   Scenario: Password request that causes a failure in the management server
