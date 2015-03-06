Feature: Login
  In order to continue playing OpenDominion
  As a guest
  I need to be able to login

  Background:
    Given I am not logged in
    And I am on "/auth/login"
    Then I should see "Login"

  Scenario: Valid login
    When I fill in "email" with "test@example.com"
    And I fill in "password" with "test"
    And I press "Login"
    Then I should be on "/status"
    And I should be logged in

  Scenario: Invalid login
    When I fill in "email" with "test@example.com"
    And I fill in "password" with "badpassword"
    And I press "Login"
    Then I should be on "/auth/login"
    And I should see "Invalid email/password combination"
    And I should not be logged in
