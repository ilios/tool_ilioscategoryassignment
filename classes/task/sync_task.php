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
        $syncjobs = $this->get_enabled_sync_jobs();
        if (empty($syncjobs)) {
            mtrace('No sync jobs enabled.');
            return;
        }

        try {
            $iliosclient = manager::instantiate_ilios_client();
        } catch (\Exception $e) {
            // re-throw exception
            throw new \Exception('ERROR: Failed to instantiate Ilios client.' . 0, $e);
        }

        $accesstoken = manager::get_config('apikey', '');

        // run enabled each sync job
        foreach ($syncjobs as $syncjob) {
            $this->run_sync_job($syncjob, $accesstoken, $iliosclient);
        }

        mtrace('Finished Ilios Category Assignment sync job.');
    }

    /**
     * @return sync_job[]
     * @throws \dml_exception
     */
    protected function get_enabled_sync_jobs() {
        return manager::get_sync_jobs(['enabled' => true]);
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
    protected function run_sync_job(sync_job $syncjob, string $accesstoken, ilios_client $iliosclient) {
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
     * Retrieves a list of user campus IDs from Ilios qualifying for the given sync job.
     *
     * @param sync_job $sync_job
     * @param string $access_token
     * @param ilios_client $ilios_client
     *
     * @return string[] A list of campus IDs.
     * @throws \Exception
     */
    protected function get_users_from_ilios(sync_job $syncjob, string $accesstoken, ilios_client $iliosclient) {
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
                $iliosusers[] = $rec->campusId;
            }
        }

        $iliosusers = array_unique($iliosusers);

        return $iliosusers;
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
    protected function get_moodle_users(array $iliosusers) {
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
     * Updates users role assignment in a given category.
     *
     * @param sync_job $sync_job
     * @param core_course_category $course_category
     * @param int[] $user_ids
     *
     * @throws \coding_exception
     */
    public function sync_category(sync_job $syncjob, core_course_category $coursecategory, array $userids) {
        $formattedcategoryname = $coursecategory->get_formatted_name();
        mtrace("Started syncing course category '{$formattedcategoryname}'.");
        $role = new \stdClass();
        $role->id = $syncjob->get_role_id();
        $ctx = $coursecategory->get_context();
        $roleassignments = get_users_from_role_on_context($role, $ctx);

        $allassignedusers = [];
        if (!empty($roleassignments)) {
            $allassignedusers = array_column($roleassignments, 'userid');
        }

        // filter out any role assignments that weren't made by this plugin
        $roleassignments = array_values(array_filter($roleassignments, function($role) {
            return $role->component === 'tool_ilioscategoryassignment';
        }));

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
                    mtrace("User with id = ${user_id} had this role assigned out-of-band, skipping.");
                    continue;
                }
                role_assign($syncjob->get_role_id(), $userid, $ctx->id, 'tool_ilioscategoryassignment');
                $assignmentcounter++;
            }
            mtrace("Assigned ${assignment_counter} user(s) into category.");

        }

        $unassignmentcounter = 0;
        if ($removeuserstotal) {
            foreach ($removeusers as $userid) {
                role_unassign($syncjob->get_role_id(), $userid, $ctx->id, 'tool_ilioscategoryassignment');
                $unassignmentcounter++;
            }
            mtrace("Un-assigned {$unassignmentcounter} user(s) from category.");

        }

        mtrace("Finished syncing course category '{$formattedcategoryname}'.");
    }
}
