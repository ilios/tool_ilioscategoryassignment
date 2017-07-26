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
    $sync_sources = array();
    $ilios_school_id = (int) $data->iliosschoolid;
    $sync_sources[] = new sync_source($ilios_school_id, $data->iliosroleid);

    $sync_job = new sync_job(
            null,
            $data->title,
            (int) $data->roleid,
            (int) $data->categoryid,
            true,
            $sync_sources
    );
    $sync_job = manager::create_job($sync_job);
    $redirect_url = new moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php");
    redirect($redirect_url, get_string('newjobcreated', 'tool_ilioscategoryassignment', $sync_job->get_title()), 10);
} else {
    /* @var core_renderer $OUTPUT */
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strheading);
    $mform->display();
    echo $OUTPUT->footer();
}



