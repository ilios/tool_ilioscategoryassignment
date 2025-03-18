@tool @tool_ilioscategoryassignment @tool_ilioscategoryassignment_management
Feature: Sync job management
  In order to manage Ilios category user assignments
  As an admin
  I want to create, view, and delete category sync jobs.

  Background:
    Given I log in as "admin"
    And the following config values are set as admin:
      | host_url                   | http://ilios.demo | tool_ilioscategoryassignment |
      | ilios_mock_backend_enabled | 1                 | tool_ilioscategoryassignment |
    And a valid Ilios API access token has been configured for tool_ilioscategoryassignment

  Scenario: View list sync jobs
    Given the following "tool_ilioscategoryassignment > sync jobs" exist:
      | title    | schoolid | coursecatid | role          | enabled |
      | SoM sync | 1        | 1           | manager       | 1       |
      | SoP sync | 2        | 1           | coursecreator | 0       |
    When I navigate to "Plugins > Admin tools" in site administration
    And I follow "Category: Ilios category assignment"
    And I follow "Sync jobs"
    Then the following should exist in the "tool-ilioscategoryassignment-sync-jobs" table:
      | Title      | Course category | Role           | Ilios school |
      | SoM sync   | Category 1      | Manager        | Medicine     |
      | SoP sync   | Category 1      | Course creator | Pharmacy     |
    And the sync job in row 1 should be enabled
    And the sync job in row 2 should be disabled

  Scenario: Toggle sync job status
    Given the following "tool_ilioscategoryassignment > sync jobs" exist:
      | title    | schoolid | coursecatid | role          | enabled |
      | SoM sync | 1        | 1           | manager       | 1       |
    When I navigate to "Plugins > Admin tools" in site administration
    And I follow "Category: Ilios category assignment"
    And I follow "Sync jobs"
    Then the following should exist in the "tool-ilioscategoryassignment-sync-jobs" table:
      | Title      | Course category | Role           | Ilios school |
      | SoM sync   | Category 1      | Manager        | Medicine     |
    And the sync job in row 1 should be enabled
    When I toggle the sync job status in row 1
    Then the sync job in row 1 should be disabled
    When I toggle the sync job status in row 1
    Then the sync job in row 1 should be enabled

  Scenario: Delete a sync job
    Given the following "tool_ilioscategoryassignment > sync jobs" exist:
      | title    | schoolid | coursecatid | role          |
      | SoM sync | 1        | 1           | manager       |
      | SoP sync | 2        | 1           | coursecreator |
    When I navigate to "Plugins > Admin tools" in site administration
    And I follow "Category: Ilios category assignment"
    And I follow "Sync jobs"
    Then the following should exist in the "tool-ilioscategoryassignment-sync-jobs" table:
      | Title      | Course category | Role           | Ilios school |
      | SoM sync   | Category 1      | Manager        | Medicine     |
      | SoP sync   | Category 1      | Course creator | Pharmacy     |
    When I delete the sync job in row 1
    And I press "Delete"
    Then the following should exist in the "tool-ilioscategoryassignment-sync-jobs" table:
      | SoP sync   | Category 1      | Course creator | Pharmacy     |
    And the following should not exist in the "tool-ilioscategoryassignment-sync-jobs" table:
      | SoM sync   | Category 1      | Manager        | Medicine     |

  Scenario: Create sync jobs
    When I navigate to "Plugins > Admin tools" in site administration
    And I follow "Category: Ilios category assignment"
    And I follow "New sync job"
    And I set the following fields to these values:
    | Title               | SoM sync   |
    | Select category     | Category 1 |
    | Select role         | Manager    |
    | Select Ilios school | Medicine   |
    And I press "Submit"
    Then the following should exist in the "tool-ilioscategoryassignment-sync-jobs" table:
      | Title      | Course category | Role           | Ilios school |
      | SoM sync   | Category 1      | Manager        | Medicine     |
    When I navigate to "Plugins > Admin tools" in site administration
    And I follow "Category: Ilios category assignment"
    And I follow "New sync job"
    And I set the following fields to these values:
      | Title               | SoP sync       |
      | Select category     | Category 1     |
      | Select role         | Course creator |
      | Select Ilios school | Pharmacy       |
    And I press "Submit"
    Then the following should exist in the "tool-ilioscategoryassignment-sync-jobs" table:
      | Title      | Course category | Role           | Ilios school |
      | SoM sync   | Category 1      | Manager        | Medicine     |
      | SoP sync   | Category 1      | Course creator | Pharmacy     |
