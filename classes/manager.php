<?php

namespace tool_ilioscategoryassignment;

/**
 * Sync job definition model.
 *
 * @package tool_ilioscategoryassignment
 * @category model
 */
class manager {

    public static function get_sync_job($id) {
        $jobs = self::get_sync_jobs(array('id' => $id));
        return empty($jobs) ? null : $jobs[0];
    }

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

    public static function disable_job($job_id) {
        global $DB;
        $table_name = 'tool_ilioscatassignment';
        $job = $DB->get_record($table_name, array('id' => $job_id));
        if (! empty($job)) {
            $job->enabled = false;
            $DB->update_record($table_name, $job);
        }
    }

    public static function enable_job($job_id) {
        global $DB;
        $table_name = 'tool_ilioscatassignment';
        $job = $DB->get_record($table_name, array('id' => $job_id));
        if (! empty($job)) {
            $job->enabled = true;
            $DB->update_record($table_name, $job);
        }
    }

    public static function delete_job($job_id) {
        global $DB;
        // @todo implement [ST 2017/07/21]
    }
}
