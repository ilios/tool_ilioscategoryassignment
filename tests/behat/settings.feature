@tool @tool_ilioscategoryassignment @tool_ilioscategoryassignment_settings
Feature: Plugin settings
  In order to add and update Ilios connection details
  As an admin
  I want to configure plugin settings

  Background:
    Given I log in as "admin"

  Scenario: Settings form with default values
    When I select "Site administration" from primary navigation
    And I follow "Ilios API client configuration"
    Then the following fields match these values:
      | Host URL      | localhost |
      | Ilios API key |           |

  Scenario: Settings form with customized values
    Given the following config values are set as admin:
      | host_url | http://ilios.demo | tool_ilioscategoryassignment |
      | apikey   | XXXXXX            | tool_ilioscategoryassignment |
    When I select "Site administration" from primary navigation
    And I follow "Ilios API client configuration"
    Then the following fields match these values:
      | Host URL      | http://ilios.demo |
      | Ilios API key | XXXXXX            |

  Scenario: Update settings
    When I select "Site administration" from primary navigation
    And I follow "Ilios API client configuration"
    Then the following fields match these values:
      | Host URL      | localhost |
      | Ilios API key |           |
    When I set the following fields to these values:
      | Host URL      | http://ilios.demo |
      | Ilios API key | XXXXXX            |
    And I press "Save changes"
    Then the following fields match these values:
      | Host URL      | http://ilios.demo |
      | Ilios API key | XXXXXX            |
