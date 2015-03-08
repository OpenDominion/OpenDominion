Feature: Registration
  In order to start playing OpenDominion
  As a guest
  I need to be able to register into the website

  Background:
    Given I am not logged in
    And I am on "/auth/register"
    Then I should see "Register"

  Scenario: Pass: Successful registration
    When I fill in "email" with "test2@example.com"
    And I fill in "password" with "test"
    And I fill in "password_confirmation" with "test"
    And I fill in "display_name" with "Tester2"
    And I fill in "dominion_name" with "Test2 Dominion"
    And I press "Register"
    Then I should see "You have successfully registered"
    And user with email "test2@example.com" should exist
    And dominion with name "Test2 Dominion" should exist

  Scenario: Fail: Email already exists
    When I fill in "email" with "test@example.com"
    And I fill in "password" with "test"
    And I fill in "password_confirmation" with "test"
    And I fill in "display_name" with "Tester2"
    And I fill in "dominion_name" with "Test2 Dominion"
    And I press "Register"
    Then I should be on "/auth/register"
    And I should see "The email has already been taken."
    And dominion with name "Test2 Dominion" should not exist

  Scenario: Fail: Display name already exists
    When I fill in "email" with "test@example.com"
    And I fill in "password" with "test"
    And I fill in "password_confirmation" with "test"
    And I fill in "display_name" with "Tester"
    And I fill in "dominion_name" with "Test2 Dominion"
    And I press "Register"
    Then I should be on "/auth/register"
    And I should see "The display name has already been taken."
    And dominion with name "Test2 Dominion" should not exist

  Scenario: Fail: Dominion name already exists
    When I fill in "email" with "test2@example.com"
    And I fill in "password" with "test"
    And I fill in "password_confirmation" with "test"
    And I fill in "display_name" with "Tester2"
    And I fill in "dominion_name" with "Test Dominion"
    And I press "Register"
    Then I should be on "/auth/register"
    And I should see "The dominion name has already been taken."
    And user with email "test2@example.com" should not exist
