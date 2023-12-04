<?php

/**
 * Sync jobs admin pages.
 *
 * @package    tool_ilioscategoryassignment
 */

use core\output\notification;
use tool_ilioscategoryassignment\manager;
use tool_ilioscategoryassignment\output\renderer;

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

/* @var $CFG */
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$action = optional_param('action', 'list', PARAM_ALPHA);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);

$returnurl = new moodle_url('/admin/tool/ilioscategoryassignment/index.php');

/* @var moodle_page $PAGE */
/* @var renderer $renderer */
/* @var core_renderer $OUTPUT */

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/admin/tool/ilioscategoryassignment/index.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('syncjobs', 'tool_ilioscategoryassignment');
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

$renderer = $PAGE->get_renderer('tool_ilioscategoryassignment');

if (in_array($action, array('enable', 'disable', 'delete'))) {
    require_sesskey();
    $job_id = required_param('job_id', PARAM_INT);
    $job = manager::get_sync_job($job_id);
    if (!empty($job)) {
        if ('enable' === $action && confirm_sesskey()) {
            manager::enable_job($job_id);
            $returnmsg = get_string('jobenabled', 'tool_ilioscategoryassignment', $job->get_title());
            redirect($returnurl, $returnmsg, null, notification::NOTIFY_SUCCESS);
        } elseif ('disable' === $action && confirm_sesskey()) {
            manager::disable_job($job_id);
            $returnmsg = get_string('jobdisabled', 'tool_ilioscategoryassignment', $job->get_title());
            redirect($returnurl, $returnmsg, null, notification::NOTIFY_SUCCESS);
        } elseif ('delete' === $action && confirm_sesskey())  {
            if ($confirm !== md5($action)) {
                echo $OUTPUT->header();
                echo $OUTPUT->heading(get_string('deletejob', 'tool_ilioscategoryassignment'));
                $deleteurl = new moodle_url(
                    $returnurl,
                    array('action' => $action, 'job_id' => $job->get_id(), 'confirm' => md5($action), 'sesskey' => sesskey())
                );
                $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

                $a = new stdClass();
                $a->jobtitle = $job->get_title();

                $roleid = $job->get_role_id();
                $roles = role_get_names();
                $a->roletitle = get_string('notfound', 'tool_ilioscategoryassignment', $roleid);
                if (array_key_exists($roleid, $roles)) {
                    $a->roletitle = $roles[$roleid]->localname;
                }

                $coursecatid = $job->get_course_category_id();
                $coursecat = core_course_category::get($coursecatid, IGNORE_MISSING);
                $a->coursecattitle = get_string('notfound', 'tool_ilioscategoryassignment', $coursecatid);
                if (! empty($coursecat)) {
                    $a->coursecattitle = $coursecat->get_nested_name(false);
                }

                echo $OUTPUT->confirm(
                    get_string('deletejobconfirm','tool_ilioscategoryassignment', $a),
                    $deletebutton,
                    $returnurl
                );
                echo $OUTPUT->footer();
                die;
            } elseif (data_submitted()) {
                manager::delete_job($job_id);
                $returnmsg = get_string('jobdeleted', 'tool_ilioscategoryassignment', $job->get_title());
                redirect($returnurl, $returnmsg, null, notification::NOTIFY_SUCCESS);

            }

        }
    }
}

$jobs = manager::get_sync_jobs();

$course_categories = array();
$roles = array();
$ilios_schools = array();

if (!empty($jobs)) {
    $course_category_ids = array_column($jobs, 'course_category_id');
    $course_categories = core_course_category::get_many($course_category_ids);
    $roles = role_get_names();
}

try {
    $access_token = manager::get_config('apikey', '');
    $ilios_client = manager::instantiate_ilios_client();
    $ilios_schools = $ilios_client->get($access_token, 'schools');
    $ilios_schools = array_column($ilios_schools, 'title', 'id');
} catch (Exception $e) {
    echo $renderer->notify_error(get_string('ilioserror', 'tool_ilioscategoryassignment'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('syncjobscount', 'tool_ilioscategoryassignment', count($jobs)));
echo $renderer->sync_jobs_table($jobs, $course_categories, $roles, $ilios_schools);
echo $OUTPUT->footer();
