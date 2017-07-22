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
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');



$PAGE->set_url('/admin/tool/ilioscategoryassignment/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$strheading = get_string('scheduledtasks', 'tool_task');
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

/* @var renderer $renderer */
$renderer = $PAGE->get_renderer('tool_ilioscategoryassignment');

/* @var core_renderer $OUTPUT */
echo $OUTPUT->header();
$jobs = manager::get_sync_jobs();

$course_categories = array();
$roles = array();
$ilios_schools = array();
$ilios_roles = array();


if (!empty($jobs)) {
    $course_category_ids = array_column($jobs, 'course_category_id');
    $course_categories = \coursecat::get_many($course_category_ids);
    $roles = \role_get_names();
}

$title = get_string('syncjobs', 'tool_ilioscategoryassignment') . ' (' . count($jobs) . ')';
echo $OUTPUT->heading($title);
echo $renderer->sync_jobs_table($jobs, $course_categories,$roles, $ilios_schools, $ilios_roles);
echo $OUTPUT->footer();
