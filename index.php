<?php

/**
 * Sync jobs admin pages.
 *
 * @package    tool_ilioscategoryassignment
 */

use tool_ilioscategoryassignment\manager;
use tool_ilioscategoryassignment\output\renderer;

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

/* @var $CFG */
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$action = optional_param('action', 'list', PARAM_ALPHA);

/* @var moodle_page $PAGE */
/* @var renderer $renderer */
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/admin/tool/ilioscategoryassignment/index.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('syncjobs', 'tool_ilioscategoryassignment');
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

$renderer = $PAGE->get_renderer('tool_ilioscategoryassignment');

/* @var core_renderer $OUTPUT */
echo $OUTPUT->header();

if (in_array($action, array('enable', 'disable', 'delete'))) {
    require_sesskey();
    $job_id = required_param('job_id', PARAM_INT);
    $job = manager::get_sync_job($job_id);
    if (!empty($job)) {
        if ('enable' === $action && confirm_sesskey()) {
            manager::enable_job($job_id);
            echo $renderer->notify_success(
                get_string('jobenabled', 'tool_ilioscategoryassignment', $job->get_title())
            );
        } elseif ('disable' === $action && confirm_sesskey()) {
            manager::disable_job($job_id);
            echo $renderer->notify_success(
                get_string('jobdisabled', 'tool_ilioscategoryassignment', $job->get_title())
            );
        } elseif ('delete' === $action && confirm_sesskey())  {
            manager::delete_job($job_id);
            echo $renderer->notify_success(
                get_string('jobdeleted', 'tool_ilioscategoryassignment', $job->get_title())
            );
        }
    }
}

$jobs = manager::get_sync_jobs();

$course_categories = array();
$roles = array();
$ilios_schools = array();

if (!empty($jobs)) {
    $course_category_ids = array_column($jobs, 'course_category_id');
    $course_categories = \core_course_category::get_many($course_category_ids);
    $roles = \role_get_names();
}

try {
    $ilios_client = manager::instantiate_ilios_client();
    $ilios_schools = $ilios_client->get('schools');
    $ilios_schools = array_column($ilios_schools, 'title', 'id');
} catch (\Exception $e) {
    echo $renderer->notify_error(get_string('ilioserror', 'tool_ilioscategoryassignment'));
}

$title = get_string('syncjobs', 'tool_ilioscategoryassignment') . ' (' . count($jobs) . ')';
echo $OUTPUT->heading($title);
echo $renderer->sync_jobs_table($jobs, $course_categories, $roles, $ilios_schools);
echo $OUTPUT->footer();



