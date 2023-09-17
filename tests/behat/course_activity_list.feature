@filter @filter_envf
Feature: Render course activity list for a given course with an action button

  Background:
    Given the following "courses" exist:
      | shortname | fullname | idnumber | enablecompletion |
      | C1        | Course 1 | C1       | 1                |
      | C2        | Course 2 | C2       | 0                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 1        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student1 | C2     | student |
      | student2 | C1     | student |
      | student2 | C2     | student |
    And the following "activities" exist:
      | activity   | name                 | intro                      | introformat | course | content     | contentformat | completion | completionview |
      | page       | PageName1            | PageDesc1                  | 1           | C1     | Page 1 test | 1             | 1          | 1              |
      | page       | PageName2            | PageDesc2                  | 1           | C1     | Page 2 test | 1             | 1          | 1              |
      | customcert | Custom certificate 1 | Custom certificate 1 intro | 1           | C1     | customcert1 | 1             | 1          | 1              |
      | page       | PageTest             | PageTest                   | 1           | C2     | Page Test   | 1             | 0          | 0              |
    And the "envf" filter is "on"
    And I am on the PageTest "page activity editing" page logged in as admin
    And I set the field "Page content" to "<span>{courseprogress courseidnumber=\"C1\"}</span>"
    And I press "Save and display"
    And I log out
    # Complete the activity for student 1.
    And I am on the "PageName1" "page activity" page logged in as "student1"
    And I am on the "PageName2" "page activity" page
    And I am on the "Custom certificate 1" "customcert activity" page
    And I click on "View certificate" "button"
    And I log out

  Scenario Outline: Render the course content so that the user can see the course progress.
    Given I am on the "PageTest" "page activity" page logged in as <user>
    Then <shouldsee>
    Examples:
      | user     | shouldsee                                                           |
      | admin    | I should see "Not enrolled in course , please contact us."          |
      | student1 | "Download" "link" should appear after "Custom certificate 1" "text" |
      | student2 | "Complete" "link" should appear after "PageName1" "text"            |
