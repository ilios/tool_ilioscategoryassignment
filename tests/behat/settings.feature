@tool @tool_ilioscategoryassignment @tool_ilioscategoryassignment_settings
Feature: Plugin administration
  In order to manage category syncs
  As an admin
  I want to configure plugin settings and create/update/delete sync instances

  Background:
    Given I log in as "admin"

  @javascript
  Scenario: Links to sync management and settings forms are visible under plugins
    When I select "Site administration" from primary navigation
    And I select "Plugins" from secondary navigation
    Then I should see "Ilios category assignment"
    And I should see "Sync jobs"
    And I should see "New sync job"
    And I should see "Ilios API client configuration"

  Scenario: Links to sync management and settings forms are visible under admin tools
    Given I navigate to "Plugins > Admin tools" in site administration
    When I follow "Category: Ilios category assignment"
    Then I should see "Sync jobs"
    And I should see "New sync job"
    And I should see "Ilios API client configuration"
