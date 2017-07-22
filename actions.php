<?php

use tool_ilioscategoryassignment\manager;

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

$job_id = required_param('job_id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

switch ($action) {
    case 'enable':
        manager::enable_job($job_id);
        break;
    case 'disable':
        manager::disable_job($job_id);
        break;
    case 'delete':
        manager::delete_job($job_id);
        break;
    default:
        // do nothing
}

/* @var $CFG */
redirect(new moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php"));
