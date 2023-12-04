<?php
/**
 * A scheduled task for syncing users with Ilios.
 *
 * @package  tool_ilioscategoryassignment
 * @category task
 */

namespace tool_ilioscategoryassignment\task;

use core\task\scheduled_task;
use core_course_category;
use local_iliosapiclient\ilios_client;
use tool_ilioscategoryassignment\manager;
use tool_ilioscategoryassignment\sync_job;

/* @global $CFG */
require_once($CFG->libdir . '/accesslib.php');

/**
 * A scheduled task for syncing users with Ilios.
 *
 * @package  tool_ilioscategoryassignment
 * @category task
 */
class sync_task extends scheduled_task {

    /**
     * @inheritdoc
     */
    public function get_name() {
        return get_string('taskname', 'tool_ilioscategoryassignment');
    }

    /**
     * @inheritdoc
     */
    public function execute() {

        // this may take a while and consume quite a bit of memory...
        @set_time_limit(0);
        raise_memory_limit(MEMORY_HUGE);

        mtrace('Started Ilios Category Assignment sync job.');
        $sync_jobs = $this->get_enabled_sync_jobs();
        if (empty($sync_jobs)) {
            mtrace('No sync jobs enabled.');
            return;
        }

        try {
            $ilios_client = manager::instantiate_ilios_client();
        } catch (\Exception $e) {
            // re-throw exception
            throw new \Exception('ERROR: Failed to instantiate Ilios client.' . 0, $e);
        }

        $access_token = manager::get_config('apikey', '');

        // run enabled each sync job
        foreach ($sync_jobs as $sync_job) {
            $this->run_sync_job($sync_job, $access_token, $ilios_client);
        }

        mtrace('Finished Ilios Category Assignment sync job.');
    }

    /**
     * @return sync_job[]
     * @throws \dml_exception
     */
    protected function get_enabled_sync_jobs() {
        return manager::get_sync_jobs(array('enabled' => true));
    }

    /**
     * Performs a given sync job.
     *
     * @param sync_job $sync_job
     * @param string $access_token
     * @param $ilios_client $ilios_client
     *
     * @throws \moodle_exception
     * @throws \Exception
     */
    protected function run_sync_job(sync_job $sync_job, string $access_token, ilios_client $ilios_client) {
        $job_title = $sync_job->get_title();
        mtrace("Started sync job '$job_title'.");
        $course_category = core_course_category::get($sync_job->get_course_category_id(), IGNORE_MISSING);
        if (empty($course_category)) {
            mtrace('ERROR: Failed to load course category with ID = ' . $sync_job->get_course_category_id());

            return;
        }
        $ilios_users = $this->get_users_from_ilios($sync_job, $access_token,  $ilios_client);
        mtrace('Retrieved ' . count($ilios_users) . ' Ilios user(s) to sync.');
        $moodle_users = $this->get_moodle_users($ilios_users);
        $this->sync_category($sync_job, $course_category, $moodle_users);
        mtrace("Finished sync job '$job_title'.");
    }

    /**
     * Retrieves a list of user campus IDs from Ilios qualifying for the given sync job.
     *
     * @param sync_job $sync_job
     * @param string $access_token
     * @param ilios_client $ilios_client
     *
     * @return string[] A list of campus IDs.
     * @throws \Exception
     */
    protected function get_users_from_ilios(sync_job $sync_job, string $access_token, ilios_client $ilios_client) {
        $ilios_users = array();

        $filters = array(
            'school' => $sync_job->get_school_id(),
            'enabled' => true
        );
        try {
            $records = $ilios_client->get(
                $access_token,
                'users',
                $filters,
                null,
                5000

            );
        } catch (\Exception $e) {
            // re-throw exception with a better error message
            throw new \Exception('Failed to retrieve users from Ilios with the following parameters: ' .
                print_r($filters, true), 0, $e);
        }

        // filter out any users that do not fulfill a director or instructor function in ilios.
        $records = array_filter($records, function(\stdClass $rec) {
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
                $ilios_users[] = $rec->campusId;
            }
        }


        $ilios_users = array_unique($ilios_users);

        return $ilios_users;
    }

    /**
     * Retrieves the IDs of all users matching a given list of Ilios user campus IDs.
     *
     * @param string[] $ilios_users The campus IDs of users retrieved from Ilios.
     *
     * @return int[] A list of Moodle user IDs.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_moodle_users(array $ilios_users) {
        global $DB;
        if (empty($ilios_users)) {
            return array();
        }
        list($insql, $params) = $DB->get_in_or_equal($ilios_users);
        $sql = "SELECT * FROM {user} WHERE idnumber $insql";
        $users = $DB->get_records_sql($sql, $params);
        if (count($users) < count($ilios_users)) {
            $id_numbers = array_column($users, 'idnumber');
            $excluded = array_diff($ilios_users, $id_numbers);
            mtrace('WARNING: Skipping non-matching user accounts with the following Ilios campus IDs: '
                . implode(', ', $excluded));
        }

        return array_column($users, 'id');

    }

    /**
     * Updates users role assignment in a given category.
     *
     * @param sync_job $sync_job
     * @param core_course_category $course_category
     * @param int[] $user_ids
     *
     * @throws \coding_exception
     */
    public function sync_category(sync_job $sync_job, core_course_category $course_category, array $user_ids) {
        $formatted_category_name = $course_category->get_formatted_name();
        mtrace("Started syncing course category '{$formatted_category_name}'.");
        $role = new \stdClass();
        $role->id = $sync_job->get_role_id();
        $ctx = $course_category->get_context();
        $role_assignments = get_users_from_role_on_context($role, $ctx);

        $all_assigned_users = array();
        if (!empty($role_assignments)) {
            $all_assigned_users = array_column($role_assignments, 'userid');
        }

        // filter out any role assignments that weren't made by this plugin
        $role_assignments = array_values(array_filter($role_assignments, function($role) {
            return $role->component === 'tool_ilioscategoryassignment';
        }));

        $assigned_users = array();
        if (!empty($role_assignments)) {
            $assigned_users = array_column($role_assignments, 'userid');
        }

        $out_of_band_assignments = array_diff($all_assigned_users, $assigned_users);
        $add_users = array_diff($user_ids, $assigned_users);
        $remove_users = array_diff($assigned_users, $user_ids);

        $add_users_total = count($add_users);
        $remove_users_total = count($remove_users);

        if (!$add_users_total && !$remove_users_total) {
            mtrace('No user assignment/un-assignment necessary.');
        }

        $assignment_counter = 0;
        if ($add_users_total) {
            foreach ($add_users as $user_id) {
                // Prevent double role assignment.
                if (in_array($user_id, $out_of_band_assignments)) {
                    mtrace("User with id = ${user_id} had this role assigned out-of-band, skipping.");
                    continue;
                }
                role_assign($sync_job->get_role_id(), $user_id, $ctx->id, 'tool_ilioscategoryassignment');
                $assignment_counter++;
            }
            mtrace("Assigned ${assignment_counter} user(s) into category.");

        }

        $unassignment_counter = 0;
        if ($remove_users_total) {
            foreach ($remove_users as $user_id) {
                role_unassign($sync_job->get_role_id(), $user_id, $ctx->id, 'tool_ilioscategoryassignment');
                $unassignment_counter++;
            }
            mtrace("Un-assigned {$unassignment_counter} user(s) from category.");

        }

        mtrace("Finished syncing course category '{$formatted_category_name}'.");
    }
}
