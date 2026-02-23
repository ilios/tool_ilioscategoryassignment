<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Behat custom step definitions.
 *
 * @package tool_ilioscategoryassignment
 * @copyright The Regents of the University of California
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_ilioscategoryassignment\tests\helper;

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');


/**
 * Steps definitions for tool_ilioscategoryassignment.
 *
 * @package tool_ilioscategoryassignment
 * @copyright The Regents of the University of California
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_ilioscategoryassignment_behat_base extends behat_base {
    /**
     * Create and store a valid API access token in the plugin configuration.
     *
     * @Then /^a valid Ilios API access token has been configured for tool_ilioscategoryassignment$/
     */
    public function a_valid_ilios_api_access_token_has_been_configured_for_tool_ilioscategoryassignment(): void {
        $accesstoken = helper::create_valid_ilios_api_access_token();
        set_config('apikey', $accesstoken, 'tool_ilioscategoryassignment');
    }

    /**
     * Asserts that the sync job on the given table row is enabled.
     *
     * @Then /^the sync job in row (\d+) should be enabled$/
     * @param string $rownumber The table row number.
     */
    public function sync_job_in_row_should_be_enabled(string $rownumber): void {
        $this->assert_sync_job_in_row_status($rownumber, true);
    }

    /**
     * Asserts that the sync job on the given table row is enabled.
     *
     * @Then /^the sync job in row (\d+) should be disabled/
     * @param string $rownumber The table row number.
     */
    public function sync_job_in_row_should_be_disabled(string $rownumber): void {
        $this->assert_sync_job_in_row_status($rownumber, false);
    }

    /**
     * Disable the sync job in the given table row.
     *
     * @Then /^I toggle the sync job status in row (\d+)$/
     * @param string $rownumber The table row number.
     */
    public function toggle_sync_job_in_row(string $rownumber): void {

        $xpath = $this->get_xpath_to_status_action_for_sync_job_in_row($rownumber);
        $params = [$xpath, "xpath_element"];
        $this->execute("behat_general::i_click_on", $params);
    }

    /**
     * Delete the sync job in the given table row.
     *
     * @Then /^I delete the sync job in row (\d+)$/
     * @param string $rownumber The table row number.
     */
    public function delete_sync_job_in_row(string $rownumber): void {
        $xpath = $this->get_xpath_to_delete_action_for_sync_job_in_row($rownumber);
        $params = [$xpath, "xpath_element"];
        $this->execute("behat_general::i_click_on", $params);
    }

    /**
     * Asserts that the sync job has the given status in the given row.
     * @param string $rownumber The table row number.
     * @param bool $enabled The sync job status. TRUE if enabled, FALSE if disabled.
     * @return void
     * @throws Exception
     */
    protected function assert_sync_job_in_row_status(string $rownumber, bool $enabled): void {
        $arialabel = $enabled ? "Disable" : "Enable";
        $xpath = $this->get_xpath_to_status_action_for_sync_job_in_row($rownumber);
        $xpath .= "/i[contains(@aria-label, '$arialabel')]";
        $params = [$xpath, "xpath_element"];
        $this->execute("behat_general::should_exist", $params);
    }

    /**
     * Returns the xpath selector to the status action element in a given table row.
     *
     * @param string $rownumber The table row number.
     * @return string The xpath selector.
     */
    protected function get_xpath_to_status_action_for_sync_job_in_row(string $rownumber): string {
        $xpath = $this->get_xpath_to_action_cell_for_sync_job_in_row($rownumber);
        $xpath .= '/a[1]';
        return $xpath;
    }

    /**
     * Returns the xpath selector to the delete action element in a given table row.
     *
     * @param string $rownumber The table row number.
     * @return string The xpath selector.
     */
    protected function get_xpath_to_delete_action_for_sync_job_in_row(string $rownumber): string {
        $xpath = $this->get_xpath_to_action_cell_for_sync_job_in_row($rownumber);
        $xpath .= '/a[2]';
        return $xpath;
    }

    /**
     * Returns the xpath selector to the table cell containing the action elements in a given table row.
     *
     * @param string $rownumber The table row number.
     * @return string The xpath selector.
     */
    protected function get_xpath_to_action_cell_for_sync_job_in_row(string $rownumber): string {
        $xpath = '//table[contains(@class, "tool-ilioscategoryassignment-sync-jobs")]';
        $xpath .= '/tbody';
        $xpath .= "/tr[$rownumber]";
        $xpath .= '/td[4]';
        return $xpath;
    }
}
