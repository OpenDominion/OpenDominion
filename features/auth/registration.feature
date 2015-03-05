Feature: Registration
  In order to start playing OpenDominion
  As a guest
  I need to be able to register into the website

  Background:
    Given I am not logged in
    And I am on "/auth/register"
    Then I should see "Register"

  Scenario: Successful registration
    When I fill in "email" with "test2@example.com"
    And I fill in "password" with "test"
    And I fill in "password_confirmation" with "test"
    And I press "Register"
    Then I should see "Success!"

  Scenario: Email already exists
    When I fill in "email" with "test@example.com"
    And I fill in "password" with "test"
    And I fill in "password_confirmation" with "test"
    And I press "Register"
    Then I should see "Email already exists"

  Scenario: Password confirmation does not match password
    When I fill in "email" with "test2@example.com"
    And I fill in "password" with "test"
    And I fill in "password_confirmation" with "1234"
    And I press "Register"
    Then I should see "Passwords do not match"
