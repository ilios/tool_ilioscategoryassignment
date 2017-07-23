<?php

namespace tool_ilioscategoryassignment;

/* @global $CFG */
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/accesslib.php');

/**
 * Sync job definition model.
 *
 * @package tool_ilioscategoryassignment
 * @category model
 */
class manager {

    /**
     * @param $id
     * @return sync_job|null
     */
    public static function get_sync_job($id) {
        $jobs = self::get_sync_jobs(array('id' => $id));
        return empty($jobs) ? null : $jobs[0];
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
