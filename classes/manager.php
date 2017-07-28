<?php

/**
 * Handles sync job, configuration and Ilios client management.
 *
 * @package tool_ilioscategoryassignment
 */

namespace tool_ilioscategoryassignment;

/* @global $CFG */
use core\event\course_category_deleted;
use local_iliosapiclient\ilios_client;

require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/accesslib.php');

/**
 * Handles sync job, configuration and Ilios client management.
 *
 * In other words, this is kitchen sink.
 * This is obviously less than ideal, but still better than polluting the global namespace with functions in locallib.php.
 * [ST 2017/07/24]
 *
 * @package tool_ilioscategoryassignment
 */
class manager {

    const PLUGIN_NAME = 'tool_ilioscategoryassignment';

    /**
     * Instantiates and returns an Ilios API client.
     *
     * @return ilios_client
     * @throws \moodle_exception
     */
    public static function instantiate_ilios_client() {
        $accesstoken = new \stdClass();
        $accesstoken->token = manager::get_config('apikey');
        $accesstoken->expires = manager::get_config('apikeyexpires');

        return new ilios_client(manager::get_config('host_url'),
            manager::get_config('userid'),
            manager::get_config('secret'),
            $accesstoken);
    }

    /**
     * @param $id
     * @return sync_job|null
     */
    public static function get_sync_job($id) {
        $jobs = self::get_sync_jobs(array('id' => $id));
        return empty($jobs) ? null : $jobs[0];
    }

    /**
     * Loads, caches and returns the configuration for this plugin.
     *
     * @return \stdClass
     * @see get_config()
     */
    public static function get_plugin_config() {
        static $config = null;
        if (!isset($config)) {
            $config = get_config(self::PLUGIN_NAME);
        }
        return $config;
    }

    /**
     * Returns a configuration value by its given name or a given default value.
     *
     * @param string $name
     * @param string $default value if config does not exist yet
     *
     * @return string value or default
     */
    public static function get_config($name, $default = null) {
        $config = self::get_plugin_config();
        return isset($config->$name) ? $config->$name : $default;
    }

    /**
     * Sets and stores a given config value.
     *
     * @param string $name name of config
     * @param string $value string config value, null means delete
     */
    public static function set_config($name, $value) {
        $config = self::get_plugin_config();
        if ($value === null) {
            unset($config->$name);
        } else {
            $config->$name = $value;
        }
        set_config($name, $value, self::PLUGIN_NAME);
    }

    /**
     * @param array $filters
     * @return sync_job[]
     */
    public static function get_sync_jobs(array $filters = array()) {
        global $DB;
        $jobs = array();
        $job_recs = $DB->get_records('tool_ilioscatassignment', $filters);
        foreach ($job_recs as $job_rec) {
            $src_recs = $DB->get_records('tool_ilioscatassignment_src', array('jobid' => $job_rec->id));
            $src_map = array();
            foreach ($src_recs as $src_rec) {
                if (!array_key_exists($src_rec->schoolid, $src_map)) {
                    $src_map[$src_rec->schoolid] = array();
                }
                if (!in_array($src_rec->roleid, $src_map[$src_rec->schoolid])) {
                    $src_map[$src_rec->schoolid][] = $src_rec->roleid;
                }
            }

            $job_sources = array();
            foreach ($src_map as $school_id => $role_ids) {
                $job_sources[] = new sync_source($school_id, $role_ids);
            }
            $jobs[] = new sync_job($job_rec->id, $job_rec->title, $job_rec->roleid, $job_rec->coursecatid,
                (boolean) $job_rec->enabled, $job_sources);
        }

        return $jobs;
    }

    /**
     * @param int $job_id
     */
    public static function disable_job($job_id) {
        global $DB;
        $table_name = 'tool_ilioscatassignment';
        $job = $DB->get_record($table_name, array('id' => $job_id));
        if (!empty($job)) {
            $job->enabled = false;
            $DB->update_record($table_name, $job);
        }
    }

    /**
     * @param int $job_id
     */
    public static function enable_job($job_id) {
        global $DB;
        $table_name = 'tool_ilioscatassignment';
        $job = $DB->get_record($table_name, array('id' => $job_id));
        if (!empty($job)) {
            $job->enabled = true;
            $DB->update_record($table_name, $job);
        }
    }

    /**
     * @param sync_job $job
     * @return sync_job
     */
    public static function create_job(sync_job $job) {
        global $DB;
        $dto = new \stdClass();
        $dto->title = $job->get_title();
        $dto->roleid = $job->get_role_id();
        $dto->coursecatid = $job->get_course_category_id();
        $dto->enabled = $job->is_enabled();
        $job_id = $DB->insert_record('tool_ilioscatassignment', $dto);
        foreach ($job->get_sources() as $source) {
            $school_id = (int) $source->get_school_id();
            foreach ($source->get_role_ids() as $role_id) {
                $dto = new \stdClass();
                $dto->jobid = $job_id;
                $dto->roleid = (int) $role_id;
                $dto->schoolid = $school_id;
                $DB->insert_record('tool_ilioscatassignment_src', $dto);
            }
        }
        return self::get_sync_job($job_id);
    }

    /**
     * @param int $job_id
     */
    public static function delete_job($job_id) {
        global $DB;
        $job = self::get_sync_job($job_id);
        if (empty($job)) {
            return;
        }

        // delete the given job
        $DB->delete_records('tool_ilioscatassignment_src', array('jobid' => $job_id));
        $DB->delete_records('tool_ilioscatassignment', array('id' => $job_id));

        // remove any course category role assignments that were managed by the given job
        $category = \coursecat::get($job->get_course_category_id(), IGNORE_MISSING);
        if (empty($category)) {
            return;
        }
        $context = $category->get_context();
        role_unassign_all(array('component' => 'tool_ilioscategoryassignment', 'roleid' => $job->get_role_id(),
            'contextid' => $context->id));
    }

    /**
     * Event observer for the "course category deleted" event.
     * Removes any sync jobs and role assignments associated with that category.
     *
     * @param \core\event\course_category_deleted $event
     */
    public static function course_category_deleted(course_category_deleted $event) {
        $category = $event->get_coursecat();
        $jobs = self::get_sync_jobs(array('coursecatid' => $category->id));
        foreach ($jobs as $job) {
            self::delete_job($job->get_id());
        }
    }
}
