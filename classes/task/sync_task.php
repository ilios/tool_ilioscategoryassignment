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
 * A scheduled task for syncing users with Ilios.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment\task;

use coding_exception;
use core\task\scheduled_task;
use core_course_category;
use dml_exception;
use Exception;
use local_iliosapiclient\ilios_client;
use moodle_exception;
use stdClass;
use tool_ilioscategoryassignment\manager;
use tool_ilioscategoryassignment\sync_job;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');

/**
 * The scheduled task class.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_task extends scheduled_task {
    /**
     * Returns the task name.
     *
     * @return string The task name.
     * @throws coding_exception
     */
    public function get_name() {
        return get_string('taskname', 'tool_ilioscategoryassignment');
    }

    /**
     * Executes this task.
     *
     * @return void
     * @throws dml_exception
     * @throws moodle_exception
     * @throws exception
     */
    public function execute(): void {
        // This may take a while and consume quite a bit of memory.
        @set_time_limit(0);
        raise_memory_limit(MEMORY_HUGE);

        mtrace('Started Ilios Category Assignment sync job.');
        $syncjobs = $this->get_enabled_sync_jobs();
        if (empty($syncjobs)) {
            mtrace('No sync jobs enabled.');
            return;
        }

        try {
            $iliosclient = manager::instantiate_ilios_client();
        } catch (Exception $e) {
            // Re-throw exception.
            throw new Exception('ERROR: Failed to instantiate Ilios client.' . 0, $e);
        }

        $accesstoken = manager::get_config('apikey', '');

        // Run enabled each sync job.
        foreach ($syncjobs as $syncjob) {
            $this->run_sync_job($syncjob, $accesstoken, $iliosclient);
        }

        mtrace('Finished Ilios Category Assignment sync job.');
    }

    /**
     * Retrieves the list of enabled sync jobs.
     *
     * @return array A list of enabled sync jobs.
     * @throws dml_exception
     */
    protected function get_enabled_sync_jobs(): array {
        return manager::get_sync_jobs(['enabled' => true]);
    }

    /**
     * Performs a given sync job.
     *
     * @param sync_job $syncjob The sync job.
     * @param string $accesstoken An Ilios API access token.
     * @param ilios_client $iliosclient The Ilios API client.
     * @return void
     * @throws moodle_exception
     * @throws Exception
     */
    protected function run_sync_job(sync_job $syncjob, string $accesstoken, ilios_client $iliosclient): void {
        $jobtitle = $syncjob->get_title();
        mtrace("Started sync job '$jobtitle'.");
        $coursecategory = core_course_category::get($syncjob->get_course_category_id(), IGNORE_MISSING);
        if (empty($coursecategory)) {
            mtrace('ERROR: Failed to load course category with ID = ' . $syncjob->get_course_category_id());
            return;
        }
        $iliosusers = $this->get_users_from_ilios($syncjob, $accesstoken,  $iliosclient);
        mtrace('Retrieved ' . count($iliosusers) . ' Ilios user(s) to sync.');
        $moodleusers = $this->get_moodle_users($iliosusers);
        $this->sync_category($syncjob, $coursecategory, $moodleusers);
        mtrace("Finished sync job '$jobtitle'.");
    }

    /**
     * Retrieves a list of campus IDs for users qualifying for the given sync job from Ilios.
     *
     * @param sync_job $syncjob The sync job.
     * @param string $accesstoken The Ilios API access token.
     * @param ilios_client $iliosclient The Ilios API client.
     * @return array A list of campus IDs.
     * @throws Exception
     */
    protected function get_users_from_ilios(sync_job $syncjob, string $accesstoken, ilios_client $iliosclient): array {
        $iliosusers = [];

        $filters = [
            'school' => $syncjob->get_school_id(),
            'enabled' => true,
        ];
        try {
            $records = $iliosclient->get(
                $accesstoken,
                'users',
                $filters,
                null,
                5000

            );
        } catch (Exception $e) {
            // Re-throw exception with a better error message.
            throw new Exception('Failed to retrieve users from Ilios with the following error message.', 0, $e);
        }

        // Filter out any users that do not fulfill a director or instructor function in ilios.
        $records = array_filter($records, function(stdClass $rec) {
            return ! empty($rec->directedCourses) ||
                   ! empty($rec->directedPrograms) ||
                   ! empty($rec->directedSchools) ||
                   ! empty($rec->instructedLearnerGroups) ||
                   ! empty($rec->instructedOfferings) ||
                   ! empty($rec->instructorIlmSessions) ||
                   ! empty($rec->instructorGroups);
        });

        foreach ($records as $rec) {
            if (object_property_exists($rec, 'campusId')
                && '' !== trim($rec->campusId)
            ) {
                $iliosusers[] = $rec->campusId;
            }
        }

        return array_unique($iliosusers);
    }

    /**
     * Retrieves the IDs of all users matching a given list of Ilios user campus IDs.
     *
     * @param array $iliosusers The campus IDs of users retrieved from Ilios.
     * @return array A list of Moodle user IDs.
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function get_moodle_users(array $iliosusers): array {
        global $DB;
        if (empty($iliosusers)) {
            return [];
        }
        list($insql, $params) = $DB->get_in_or_equal($iliosusers);
        $sql = "SELECT * FROM {user} WHERE idnumber $insql";
        $users = $DB->get_records_sql($sql, $params);
        if (count($users) < count($iliosusers)) {
            $idnumbers = array_column($users, 'idnumber');
            $excluded = array_diff($iliosusers, $idnumbers);
            mtrace('WARNING: Skipping non-matching user accounts with the following Ilios campus IDs: '
                . implode(', ', $excluded));
        }

        return array_column($users, 'id');
    }

    /**
     * Updates users role assignment in a given course category.
     *
     * @param sync_job $syncjob The sync job.
     * @param core_course_category $coursecategory The course category.
     * @param array $userids A list of IDs of users that were updated during this sync job.
     * @throws coding_exception
     */
    public function sync_category(sync_job $syncjob, core_course_category $coursecategory, array $userids): void {
        $formattedcategoryname = $coursecategory->get_formatted_name();
        mtrace("Started syncing course category '$formattedcategoryname'.");
        $role = new stdClass();
        $role->id = $syncjob->get_role_id();
        $ctx = $coursecategory->get_context();
        $roleassignments = get_users_from_role_on_context($role, $ctx);

        $allassignedusers = [];
        if (!empty($roleassignments)) {
            $allassignedusers = array_column($roleassignments, 'userid');
        }

        // Filter out any role assignments that weren't made by this plugin.
        $roleassignments = array_values(
            array_filter($roleassignments, function($role) {
                return $role->component === 'tool_ilioscategoryassignment';
            })
        );

        $assignedusers = [];
        if (!empty($roleassignments)) {
            $assignedusers = array_column($roleassignments, 'userid');
        }

        $outofbandassignments = array_diff($allassignedusers, $assignedusers);
        $addusers = array_diff($userids, $assignedusers);
        $removeusers = array_diff($assignedusers, $userids);

        $adduserstotal = count($addusers);
        $removeuserstotal = count($removeusers);

        if (!$adduserstotal && !$removeuserstotal) {
            mtrace('No user assignment/un-assignment necessary.');
        }

        $assignmentcounter = 0;
        if ($adduserstotal) {
            foreach ($addusers as $userid) {
                // Prevent double role assignment.
                if (in_array($userid, $outofbandassignments)) {
                    mtrace("User with id = $userid had this role assigned out-of-band, skipping.");
                    continue;
                }
                role_assign($syncjob->get_role_id(), $userid, $ctx->id, 'tool_ilioscategoryassignment');
                $assignmentcounter++;
            }
            mtrace("Assigned $assignmentcounter user(s) into category.");
        }

        $unassignmentcounter = 0;
        if ($removeuserstotal) {
            foreach ($removeusers as $userid) {
                role_unassign($syncjob->get_role_id(), $userid, $ctx->id, 'tool_ilioscategoryassignment');
                $unassignmentcounter++;
            }
            mtrace("Un-assigned $unassignmentcounter user(s) from category.");

        }
        mtrace("Finished syncing course category '$formattedcategoryname'.");
    }
}
