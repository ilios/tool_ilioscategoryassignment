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
 * Handles sync job, configuration and Ilios client management.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment;

use coding_exception;
use core\event\course_category_deleted;
use core_course_category;
use curl;
use dml_exception;
use local_iliosapiclient\ilios_client;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');

/**
 * Handler class for sync job, configuration and Ilios client management.
 *
 * In other words, this is the kitchen sink.
 * This is obviously less than ideal, but still better than polluting the global namespace with functions in locallib.php.
 * [ST 2017/07/24]
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * Instantiates and returns an Ilios API client.
     *
     * @return ilios_client
     * @throws moodle_exception
     */
    public static function instantiate_ilios_client(): ilios_client {
        return new ilios_client(self::get_config('host_url', ''), new curl());
    }

    /**
     * Retrieves a sync job by its given ID.
     *
     * @param int $id The sync job ID.
     * @return sync_job|null The sync job, or NULL if none were found.
     * @throws dml_exception
     */
    public static function get_sync_job($id) {
        $jobs = self::get_sync_jobs(['id' => $id]);
        return empty($jobs) ? null : $jobs[0];
    }

    /**
     * Loads, caches and returns the configuration for this plugin.
     *
     * @return stdClass The plugin configuration object.
     * @see get_config()
     * @throws dml_exception
     */
    public static function get_plugin_config() {
        static $config = null;
        if (!isset($config)) {
            $config = get_config('tool_ilioscategoryassignment');
        }
        return $config;
    }

    /**
     * Returns a configuration item by its given name or a given default value.
     *
     * @param string $name The config item name.
     * @param string $default A default value if the config item does not exist.
     * @return mixed The config value or the given default value.
     * @throws dml_exception
     */
    public static function get_config($name, $default = null) {
        $config = self::get_plugin_config();
        return isset($config->$name) ? $config->$name : $default;
    }

    /**
     * Sets and stores a given config value.
     *
     * @param string $name The config item name.
     * @param string $value string The config item's value, NULL means unset the config item.
     * @return void
     * @throws dml_exception
     */
    public static function set_config($name, $value): void {
        $config = self::get_plugin_config();
        if ($value === null) {
            unset($config->$name);
        } else {
            $config->$name = $value;
        }
        set_config($name, $value, 'tool_ilioscategoryassignment');
    }

    /**
     * Retrieves all sync jobs matching the given filters.
     *
     * @param array $filters The filter criteria.
     * @return array A list of sync jobs.
     * @throws dml_exception
     */
    public static function get_sync_jobs(array $filters = []): array {
        global $DB;
        $jobs = [];
        $jobrecs = $DB->get_records('tool_ilioscatassignment', $filters);
        foreach ($jobrecs as $jobrec) {
            $jobs[] = new sync_job($jobrec->id, $jobrec->title, $jobrec->roleid, $jobrec->coursecatid,
                (boolean) $jobrec->enabled, $jobrec->schoolid);
        }

        return $jobs;
    }

    /**
     * Disables a given sync job.
     *
     * @param int $jobid The job ID.
     * @return void
     * @throws dml_exception
     */
    public static function disable_job($jobid): void {
        global $DB;
        $tablename = 'tool_ilioscatassignment';
        $job = $DB->get_record($tablename, ['id' => $jobid]);
        if (!empty($job)) {
            $job->enabled = false;
            $DB->update_record($tablename, $job);
        }
    }

    /**
     * Enables a given sync job.
     *
     * @param int $jobid The job ID.
     * @return void
     * @throws dml_exception
     */
    public static function enable_job($jobid): void {
        global $DB;
        $tablename = 'tool_ilioscatassignment';
        $job = $DB->get_record($tablename, ['id' => $jobid]);
        if (!empty($job)) {
            $job->enabled = true;
            $DB->update_record($tablename, $job);
        }
    }

    /**
     * Creates and returns a new sync job.
     * @param sync_job $job The new sync job to create.
     * @return sync_job The created sync job.
     * @throws dml_exception
     */
    public static function create_job(sync_job $job) {
        global $DB;

        $dto = new stdClass();
        $dto->title = $job->get_title();
        $dto->roleid = $job->get_role_id();
        $dto->coursecatid = $job->get_course_category_id();
        $dto->enabled = $job->is_enabled();
        $dto->schoolid = $job->get_school_id();

        $jobid = $DB->insert_record('tool_ilioscatassignment', $dto);
        return self::get_sync_job($jobid);
    }

    /**
     * Deletes a given sync job.
     *
     * @param int $jobid The sync job ID.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function delete_job($jobid): void {
        global $DB;
        $job = self::get_sync_job($jobid);
        if (empty($job)) {
            return;
        }

        // Delete the given job.
        $DB->delete_records('tool_ilioscatassignment', ['id' => $jobid]);

        // Remove any course category role assignments that were managed by the given job.
        $category = core_course_category::get($job->get_course_category_id(), IGNORE_MISSING);
        if (empty($category)) {
            return;
        }
        $context = $category->get_context();
        role_unassign_all(['component' => 'tool_ilioscategoryassignment', 'roleid' => $job->get_role_id(),
            'contextid' => $context->id]);
    }

    /**
     * Event observer for the "course category deleted" event.
     * Removes any sync jobs and role assignments associated with that category.
     *
     * @param course_category_deleted $event
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function course_category_deleted(course_category_deleted $event): void {
        $category = $event->get_coursecat();
        $jobs = self::get_sync_jobs(['coursecatid' => $category->id]);
        foreach ($jobs as $job) {
            self::delete_job($job->get_id());
        }
    }
}
