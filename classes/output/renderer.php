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
 * Output renderer for the plugin.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment\output;

use coding_exception;
use core\output\notification;
use html_table;
use html_table_cell;
use html_table_row;
use html_writer;
use lang_string;
use moodle_exception;
use moodle_url;
use pix_icon;
use plugin_renderer_base;
use stdClass;
use tool_ilioscategoryassignment\sync_job;
use function sesskey;

/**
 * Output renderer class.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Renders a table displaying all configured sync jobs.
     *
     * @param sync_job[] $syncjobs
     * @param stdClass[] $roles
     * @param string[] $iliosschools
     * @return string HTML to output.
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function sync_jobs_table(array $syncjobs, array $roles, array $iliosschools): string {
        global $CFG;
        $table = new html_table();
        $table->head = [
            get_string('title', 'tool_ilioscategoryassignment'),
            get_string('coursecategory'),
            get_string('role'),
            get_string('iliosschool', 'tool_ilioscategoryassignment'),
            get_string('actions'),
        ];
        $table->attributes['class'] = 'admintable generaltable';
        $data = [];

        foreach ($syncjobs as $job) {
            $titlecell = new html_table_cell($job->get('title'));
            $titlecell->header = true;

            $coursecategory = $job->get_course_category();
            $coursetitle = get_string('notfound', 'tool_ilioscategoryassignment', $job->get('coursecatid'));
            if ($coursecategory) {
                $coursetitle = $coursecategory->get_nested_name();
            }
            $coursecatcell = new html_table_cell($coursetitle);

            $roleid = $job->get('roleid');
            $roletitle = get_string('notfound', 'tool_ilioscategoryassignment',  $job->get('roleid'));
            if (array_key_exists($roleid, $roles)) {
                $roletitle = $roles[$roleid]->localname;
            }
            $rolecell = new html_table_cell($roletitle);

            $iliosschoolid = $job->get('schoolid');
            $iliosschooltitle = get_string('notfound', 'tool_ilioscategoryassignment', $iliosschoolid);
            if (array_key_exists($iliosschoolid, $iliosschools)) {
                $iliosschooltitle = $iliosschools[$iliosschoolid];
            }

            $iliosschoolcell = new html_table_cell($iliosschooltitle);

            $actions = [];
            if ($job->get('enabled')) {
                $actions[] = $this->action_icon(
                    new moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php",
                        ['job_id' => $job->get('id'), 'action' => 'disable', 'sesskey' => sesskey()]),
                    new pix_icon('t/hide', new lang_string('disable'))
                );
            } else {
                $actions[] = $this->action_icon(
                    new moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php",
                        ['job_id' => $job->get('id'), 'action' => 'enable', 'sesskey' => sesskey()]),
                    new pix_icon('t/show', new lang_string('enable'))
                );
            }

            $actions[] = $this->action_icon(
                new moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php",
                    ['job_id' => $job->get('id'), 'action' => 'delete', 'sesskey' => sesskey()]),
                new pix_icon('t/delete', new lang_string('delete'))
            );

            $actionscell = new html_table_cell(implode(' ', $actions));

            $row = new html_table_row([
                $titlecell,
                $coursecatcell,
                $rolecell,
                $iliosschoolcell,
                $actionscell,
            ]);
            $data[] = $row;
        }
        $table->data = $data;
        return html_writer::table($table);
    }

    /**
     * Renders and returns a notification.
     *
     * @param string $message The message.
     * @return string The formatted message.
     */
    public function notify_info($message) {
        $n = new notification($message, notification::NOTIFY_INFO);
        return $this->render($n);
    }

    /**
     * Renders and returns an error notification.
     *
     * @param string $message The message.
     * @return string The formatted message.
     */
    public function notify_error($message) {
        $n = new notification($message, notification::NOTIFY_ERROR);
        return $this->render($n);
    }

    /**
     * Renders and returns a success notification.
     *
     * @param string $message The message.
     * @return string The formatted message.
     */
    public function notify_success($message) {
        $n = new notification($message, notification::NOTIFY_SUCCESS);
        return $this->render($n);
    }
}
