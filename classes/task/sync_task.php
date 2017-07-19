<?php
/**
 * A scheduled task for syncing users with Ilios.
 *
 * @package  tool_ilioscategoryassignment
 * @category task
 */
namespace tool_ilioscategoryassignment\task;

use local_iliosapiclient\ilios_client;
use tool_ilioscategoryassignment\sync_job;
use tool_ilioscategoryassignment\sync_source;

/* @global $CFG */
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/accesslib.php');


/**
 * A scheduled task for syncing users with Ilios.
 *
 * @package  tool_ilioscategoryassignment
 * @category task
 */
class sync_task extends \core\task\scheduled_task {

    /**
     * @var \stdClass $config This plugin's configuration.
     */
    protected $config;

    /**
     * @var ilios_client $ilios_api_client
     */
    protected $ilios_api_client;

    public function __construct() {
        $this->load_config();
        $this->load_ilios_client();
    }

    public function __destruct() {
        $accesstoken = $this->ilios_api_client->getAccessToken();
        $apikey = $this->get_config('apikey');

        if (!empty($accesstoken) && ($apikey !== $accesstoken->token)) {
            $this->set_config('apikey', $accesstoken->token);
            $this->set_config('apikeyexpires', $accesstoken->expires);
        }
    }

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
        foreach ($sync_jobs as $sync_job) {
            $this->run_sync_job($sync_job);
        }
        mtrace('Finished Ilios Category Assignment sync job.');
    }

    /**
     * @return sync_job[]
     */
    protected function get_enabled_sync_jobs() {
        global $DB;
        $jobs = array();
        $job_recs = $DB->get_records('tool_ilioscatassignment', array('enabled' => 1));
        foreach ($job_recs as $job_rec) {
            $src_recs = $DB->get_records('tool_ilioscatassignment_src', array('jobid' => $job_rec->id));
            $src_map = array();
            foreach ($src_recs as $src_rec) {
                if (! array_key_exists($src_rec->schoolid, $src_map)) {
                    $src_map[$src_rec->schoolid] = array();
                }
                if (! in_array($src_rec->roleid, $src_map[$src_rec->schoolid])) {
                    $src_map[$src_rec->schoolid][] = $src_rec->roleid;
                }
            }

            $job_sources = array();
            foreach ($src_map as $school_id => $role_ids) {
                $job_sources[] = new sync_source($school_id, $role_ids);
            }
            $jobs[] = new sync_job($job_rec->id, $job_rec->title, $job_rec->roleid, $job_rec->coursecatid, (boolean) $job_rec->enabled, $job_rec->timecreated, $job_rec->timemodified, $job_rec->usermodified, $job_sources);
        }

        return $jobs;
    }

    /**
     * @param sync_job $sync_job
     */
    protected function run_sync_job(sync_job $sync_job) {
        $job_title = $sync_job->get_title();
        mtrace("Started sync job '$job_title'.");
        try {
            $categories = $this->get_categories_to_sync($sync_job->get_course_category_id());
            if (empty($categories)) {
                mtrace('No categories found to sync.');
                return;
            } else {
                mtrace('Retrieved '. count($categories) . ' to sync.');
            }
            $ilios_users = $this->get_users_from_ilios($sync_job);
            if (empty($ilios_users)) {
                mtrace('No Ilios users found to sync.');
                return;
            }
            $moodle_users = $this->get_moodle_users($ilios_users);
            if (empty($moodle_users)) {
                mtrace('After filtering, no user accounts remain to be synced.');
                return;
            } else {
                mtrace('Syncing ' . count($moodle_users) . ' user accounts.');
            }

            foreach($categories as $category) {
                $this->sync_category($sync_job, $category, $moodle_users);
            }
        } catch (\Exception $e) {
            mtrace('An error occurred: ' . $e->getMessage());
        } finally {
            mtrace("Finished sync job '$job_title'.");
        }
    }

    /**
     * @param sync_job $sync_job
     *
     * @return string[]
     */
    protected function get_users_from_ilios(sync_job $sync_job) {
        $ilios_users = array();
        foreach($sync_job->get_sources() as $source) {
            $records = $this->ilios_api_client->get(
                'users',
                array(
                    'school' => $source->get_school_id(),
                    'roles' => $source->get_role_ids(),
                    'enabled' => true
                ),
                null,
                5000

            );
            foreach($records as $rec) {
                if (object_property_exists($rec, 'campusId')
                    && '' !== trim($rec->campusId)) {
                    $ilios_users[] = $rec->campusId;
                }
            }
        }
        $ilios_users = array_unique($ilios_users);
        return $ilios_users;
    }

    /**
     * @param string[] $ilios_users
     *
     * @return int[]
     */
    protected function get_moodle_users(array $ilios_users) {
        global $DB;
        list($insql, $params) = $DB->get_in_or_equal($ilios_users);
        $sql = "SELECT * FROM {user} WHERE idnumber $insql";
        $users = $DB->get_records_sql($sql, $params);
        if (count($users) < count($ilios_users)) {
            $id_numbers = array_column($users, 'idnumber');
            $excluded = array_diff($ilios_users, $id_numbers);
            mtrace('Skipping non-matching user accounts with the following Ilios campus IDs: ' . implode(', ', $excluded));
        }
        return array_column($users, 'id');

    }

    /**
     * @param int $category_id
     *
     * @return int[]
     */
    protected function get_categories_to_sync($category_id) {

        $category_ids = array();
        $course_category = \coursecat::get($category_id);
        $category_ids[] = $category_id;
        if ($course_category->has_children()) {
            foreach($course_category->get_children() as $cat) {
                $category_ids = array_merge($category_ids, $this->get_categories_to_sync($cat->id));
            }
        }
        return($category_ids);
    }

    /**
     * @param sync_job $sync_job
     * @param int $category_id
     * @param int[] $user_ids
     */
    public function sync_category(sync_job $sync_job, $category_id, array $user_ids) {
        $course_category = \coursecat::get($category_id);
        $formatted_category_name = $course_category->get_formatted_name();
        mtrace("Started syncing course category '{$formatted_category_name}'.");
        $role = new \stdClass();
        $role->id = $sync_job->get_role_id();
        $ctx = $course_category->get_context();
        $role_assignments = get_users_from_role_on_context($role, $ctx);
        $assigned_users = array();
        if (! empty($role_assignments)) {
            $assigned_users = array_column($role_assignments, 'userid');
        }

        $add_users = array_diff($user_ids, $assigned_users);
        $remove_users = array_diff($assigned_users, $user_ids);

        $add_users_total = count($add_users);
        $remove_users_total = count($add_users);

        if (! $add_users_total && !$remove_users_total) {
            mtrace('No user assignment/un-assignment necessary.');
        }

        if ($add_users_total) {
            mtrace("Assigning ${add_users_total} user(s) into category.");
            foreach ($add_users as $user_id) {
                role_assign($sync_job->get_role_id(), $user_id, $ctx->id, "tool_ilioscategoryassignment");
            }
        }

        if ($remove_users_total) {
            foreach ($remove_users as $user_id) {
                mtrace("Assigning ${remove_users_total} user(s) from category.");
                role_unassign($sync_job->get_role_id(), $user_id, $ctx->id, "tool_ilioscategoryassignment");
            }
        }

        mtrace("Finished syncing course category '{$formatted_category_name}'.");
    }

    protected function load_ilios_client() {
        if (! $this->ilios_api_client) {
            $accesstoken = new \stdClass();
            $accesstoken->token = $this->get_config('apikey');
            $accesstoken->expires = $this->get_config('apikeyexpires');

            $this->ilios_api_client =  new ilios_client($this->get_config('host_url'),
                $this->get_config('userid'),
                $this->get_config('secret'),
                $accesstoken);
        }
    }

    /**
     * Returns plugin config value.
     * @param  string $name
     * @param  string $default value if config does not exist yet
     * @return string value or default
     */
    protected function get_config($name, $default = NULL) {
        return isset($this->config->$name) ? $this->config->$name : $default;
    }

    /**
     * Sets plugin config value
     * @param  string $name name of config
     * @param  string $value string config value, null means delete
     */
    public function set_config($name, $value) {
        $this->load_config();
        if ($value === NULL) {
            unset($this->config->$name);
        } else {
            $this->config->$name = $value;
        }
        set_config($name, $value, "tool_ilioscategoryassignment");
    }

    /**
     * Makes sure config is loaded and cached.
     * @return void
     */
    protected function load_config() {
        if (!isset($this->config)) {
            $this->config = get_config("tool_ilioscategoryassignment");
        }
    }
}
