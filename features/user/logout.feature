Feature: Logout
  In order to clear my authentication session
  As a logged in user
  I need to be able to logout

  Scenario:
    Given I am logged in
    And I am on "/status"
    Then I should see "Logout"
    When I follow "Logout"
    Then I should be on the homepage
    And I should not be logged in
