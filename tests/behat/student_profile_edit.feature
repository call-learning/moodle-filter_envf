@filter @filter_envf @javascript
Feature: Render a form that allow student to edit some of their profile field.

  Background:
    Given the following "courses" exist:
      | shortname | fullname | idnumber |
      | C1        | Course 1 | C1       |
    And the following "users" exist:
      | username | firstname | lastname | email                | city  |
      | student1 | Student   | 1        | student1@example.com | Perth |
      | student2 | Student   | 2        | student2@example.com | Perth |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name     | intro    | introformat | course | content   | contentformat |
      | page     | PageTest | PageTest | 1           | C1     | Page Test | 1             |
    And the "envf" filter is "on"
    And I am on the PageTest "page activity editing" page logged in as admin
    And I set the field "Page content" to "<span>{userprofileform}</span>"
    And I press "Save and display"

  Scenario Outline: Allow an user to edit their profile anywhere.
    Given I am on the "PageTest" "page activity" page logged in as <user>
    And the field "<field>" matches value "<fieldvalue>"
    And the field "Username" matches value "<user>"
    And I set the field "<field>" to "<fieldnewvalue>"
    And I press "Update my profile"
    When I open my profile in edit mode
    Then the field "<field>" matches value "<fieldnewvalue>"
    Examples:
      | user  | field | fieldvalue | fieldnewvalue |
      | admin | City  | Perth      | New York      |

  Scenario: Disable some profile fields and make sure they cannot be modified by the API.
    Given the following config values are set as admin:
      | config                  | value                                          | plugin      |
      | disabled_profile_fields | auth,description,customfields,preferences,city | filter_envf |
    And I am on the "PageTest" "page activity" page logged in as "student1"
    And I set the field "City" to "New York"
    And I press "Update my profile"
    When I open my profile in edit mode
    Then the field "City" matches value "Perth"

  Scenario Outline: Check username change errors.
    Given I am on the "PageTest" "page activity" page logged in as "<user>"
    And I set the field "username" to "<newusername>"
    And I press "Update my profile"
    Then I should see "<error>"
    Examples:
      | user     | newusername | error                                        |
      | student1 | student2    | This username already exists, choose another |
      | student1 | student&    | The username can only contain alphanumeric   |
