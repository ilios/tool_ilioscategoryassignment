<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Create new sync job page.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_ilioscategoryassignment\form\create_sync_job;
use tool_ilioscategoryassignment\sync_job;

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$action = optional_param('action', 'list', PARAM_ALPHA);

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

    $syncjob = new sync_job();
    $syncjob->set('title', $data->title);
    $syncjob->set('roleid', (int) $data->roleid);
    $syncjob->set('coursecatid', (int) $data->categoryid);
    $syncjob->set('enabled', true);
    $syncjob->set('schoolid', $iliosschoolid);
    $syncjob->save();
    $redirecturl = new moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php");
    redirect($redirecturl, get_string('newjobcreated', 'tool_ilioscategoryassignment', $syncjob->get('title')), 10);
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strheading);
    $mform->display();
    echo $OUTPUT->footer();
}



