<?php

/**
 * Handles sync job, configuration and Ilios client management.
 *
 * @package tool_ilioscategoryassignment
 */
namespace tool_ilioscategoryassignment;

/* @global $CFG */
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/accesslib.php');

/**
 * The kitchen sink.
 * Handles sync job, configuration and Ilios client management.
 *
 * This is obviously less than ideal, but still better than having functions in locallib.php,
 * polluting the global namespace.
 * [ST 2017/07/24]
 *
 * @package tool_ilioscategoryassignment
 */
class manager {

    const PLUGIN_NAME = 'tool_ilioscategoryassignment';

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
                    (boolean) $job_rec->enabled, $job_rec->timecreated, $job_rec->timemodified, $job_rec->usermodified,
                    $job_sources);
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
        if (! empty($job)) {
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
        if (! empty($job)) {
            $job->enabled = true;
            $DB->update_record($table_name, $job);
        }
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
        role_unassign_all(array('component' => 'tool_ilioscategoryassignment', 'roleid' => $job->get_role_id(), 'contextid' => $context->id));
    }
}
