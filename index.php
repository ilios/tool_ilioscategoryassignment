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
 * Sync jobs admin page.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use tool_ilioscategoryassignment\manager;

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$action = optional_param('action', 'list', PARAM_ALPHA);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);

$returnurl = new moodle_url('/admin/tool/ilioscategoryassignment/index.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/admin/tool/ilioscategoryassignment/index.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('syncjobs', 'tool_ilioscategoryassignment');
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

$renderer = $PAGE->get_renderer('tool_ilioscategoryassignment');

if (in_array($action, ['enable', 'disable', 'delete'])) {
    require_sesskey();
    $jobid = required_param('job_id', PARAM_INT);
    $job = manager::get_sync_job($jobid);
    if (!empty($job)) {
        if ('enable' === $action && confirm_sesskey()) {
            manager::enable_job($jobid);
            $returnmsg = get_string('jobenabled', 'tool_ilioscategoryassignment', $job->get_title());
            redirect($returnurl, $returnmsg, null, notification::NOTIFY_SUCCESS);
        } else if ('disable' === $action && confirm_sesskey()) {
            manager::disable_job($jobid);
            $returnmsg = get_string('jobdisabled', 'tool_ilioscategoryassignment', $job->get_title());
            redirect($returnurl, $returnmsg, null, notification::NOTIFY_SUCCESS);
        } else if ('delete' === $action && confirm_sesskey()) {
            if ($confirm !== md5($action)) {
                echo $OUTPUT->header();
                echo $OUTPUT->heading(get_string('deletejob', 'tool_ilioscategoryassignment'));
                $deleteurl = new moodle_url(
                    $returnurl,
                    ['action' => $action, 'job_id' => $job->get_id(), 'confirm' => md5($action), 'sesskey' => sesskey()]
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
                    get_string('deletejobconfirm', 'tool_ilioscategoryassignment', $a),
                    $deletebutton,
                    $returnurl
                );
                echo $OUTPUT->footer();
                die;
            } else if (data_submitted()) {
                manager::delete_job($jobid);
                $returnmsg = get_string('jobdeleted', 'tool_ilioscategoryassignment', $job->get_title());
                redirect($returnurl, $returnmsg, null, notification::NOTIFY_SUCCESS);

            }

        }
    }
}

$jobs = manager::get_sync_jobs();

$coursecategories = [];
$roles = [];
$iliosschools = [];

if (!empty($jobs)) {
    $coursecategoryids = array_column($jobs, 'coursecategoryid');
    $coursecategories = core_course_category::get_many($coursecategoryids);
    $roles = role_get_names();
}

try {
    $accesstoken = manager::get_config('apikey', '');
    $iliosclient = manager::instantiate_ilios_client();
    $iliosschools = $iliosclient->get($accesstoken, 'schools');
    $iliosschools = array_column($iliosschools, 'title', 'id');
} catch (Exception $e) {
    echo $renderer->notify_error(get_string('ilioserror', 'tool_ilioscategoryassignment'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('syncjobscount', 'tool_ilioscategoryassignment', count($jobs)));
echo $renderer->sync_jobs_table($jobs, $coursecategories, $roles, $iliosschools);
echo $OUTPUT->footer();
