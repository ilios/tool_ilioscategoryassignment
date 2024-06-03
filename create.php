<?php

/**
 * Create new sync job page.
 *
 * @package tool_ilioscategoryassignment
 */

use tool_ilioscategoryassignment\form\create_sync_job;
use tool_ilioscategoryassignment\manager;
use tool_ilioscategoryassignment\output\renderer;
use tool_ilioscategoryassignment\sync_job;
use tool_ilioscategoryassignment\sync_source;

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
$PAGE->set_url('/admin/tool/ilioscategoryassignment/create.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('newsyncjob', 'tool_ilioscategoryassignment');
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

$renderer = $PAGE->get_renderer('tool_ilioscategoryassignment');

$mform = new create_sync_job();

$data = $mform->get_data();
if ($data) {
    $syncsources = [];
    $iliosschoolid = (int) $data->iliosschoolid;

    $syncjob = new sync_job(
        null,
        $data->title,
        (int) $data->roleid,
        (int) $data->categoryid,
        true,
        $iliosschoolid
    );
    $syncjob = manager::create_job($syncjob);
    $redirecturl = new moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php");
    redirect($redirecturl, get_string('newjobcreated', 'tool_ilioscategoryassignment', $syncjob->get_title()), 10);
} else {
    /* @var core_renderer $OUTPUT */
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strheading);
    $mform->display();
    echo $OUTPUT->footer();
}



