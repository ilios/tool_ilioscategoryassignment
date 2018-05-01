<?php

namespace tool_ilioscategoryassignment\output;

use core\output\notification;
use plugin_renderer_base;
use tool_ilioscategoryassignment\sync_job;

/**
 * Output renderer for the plugin.
 *
 * @package tool_ilioscategoryassignment
 * @category output
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Output renderer for the plugin.
 *
 * @package tool_ilioscategoryassignment
 * @category output
 */
class renderer extends plugin_renderer_base {
    /**
     * Renders a table displaying all configured sync jobs.
     *
     * @param sync_job[] $sync_jobs
     * @param \coursecat[] $course_categories
     * @param \stdClass[] $roles
     * @param string[] $ilios_schools
     *
     * @return string HTML to output.
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function sync_jobs_table(array $sync_jobs, array $course_categories, array $roles, array $ilios_schools) {
        global $CFG;
        $table = new \html_table();
        $table->head = array(
            get_string('title', 'tool_ilioscategoryassignment'),
            get_string('coursecategory'),
            get_string('role'),
            get_string('iliosschool', 'tool_ilioscategoryassignment'),
            get_string('actions')
        );
        $table->attributes['class'] = 'admintable generaltable';
        $data = array();

        foreach ($sync_jobs as $job) {
            $titlecell = new \html_table_cell($job->get_title());
            $titlecell->header = true;

            $course_category_id = $job->get_course_category_id();
            $course_title = $this->get_not_found_message($course_category_id);
            if (!empty($course_categories[$course_category_id])) {
                $course_title = $course_categories[$course_category_id]->get_formatted_name();
            }
            $coursecatcell = new \html_table_cell($course_title);

            $role_id = $job->get_role_id();
            $role_title = $this->get_not_found_message($role_id);
            if (array_key_exists($role_id, $roles)) {
                $role_title = $roles[$role_id]->localname;
            }
            $rolecell = new \html_table_cell($role_title);

            $ilios_school_id = $job->get_school_id();
            $ilios_school_title = $this->get_not_found_message($ilios_school_id);
            if (array_key_exists($ilios_school_id, $ilios_schools)) {
                $ilios_school_title = $ilios_schools[$ilios_school_id];
            }

            $iliosschoolcell = new \html_table_cell($ilios_school_title);

            $actions = array();
            if ($job->is_enabled()) {
                $actions[] = $this->action_icon(
                    new \moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php",
                        array('job_id' => $job->get_id(), 'action' => 'disable', 'sesskey' => \sesskey())),
                    new \pix_icon('t/hide', new \lang_string('disable'))
                );
            } else {
                $actions[] = $this->action_icon(
                    new \moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php",
                        array('job_id' => $job->get_id(), 'action' => 'enable', 'sesskey' => \sesskey())),
                    new \pix_icon('t/show', new \lang_string('enable'))
                );
            }

            $actions[] = $this->action_icon(
                new \moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php",
                    array('job_id' => $job->get_id(), 'action' => 'delete', 'sesskey' => \sesskey())),
                new \pix_icon('t/delete', new \lang_string('delete'))
            );

            $actionscell = new \html_table_cell(implode(' ', $actions));

            $row = new \html_table_row(array(
                $titlecell,
                $coursecatcell,
                $rolecell,
                $iliosschoolcell,
                $actionscell
            ));
            $data[] = $row;
        }
        $table->data = $data;
        return \html_writer::table($table);
    }

    /**
     * @param int $id
     * @return string
     */
    protected function get_not_found_message($id) {
        static $not_found = null;
        if (!isset($not_found)) {
            $not_found = get_string('notfound', 'tool_ilioscategoryassignment');
        }
        return "[[ ${not_found} (id = ${id}) ]]";
    }

    /**
     * Renders and returns a notification.
     *
     * @param string $message the message
     * @return string The formatted message.
     */
    public function notify_info($message) {
        $n = new notification($message, notification::NOTIFY_INFO);
        return $this->render($n);
    }

    /**
     * Renders and returns an error notification.
     *
     * @param string $message the message
     * @return string The formatted message.
     */
    public function notify_error($message) {
        $n = new notification($message, notification::NOTIFY_ERROR);
        return $this->render($n);
    }

    /**
     * Renders and returns a success notification.
     *
     * @param string $message the message
     * @return string The formatted message.
     */
    public function notify_success($message) {
        $n = new notification($message, notification::NOTIFY_SUCCESS);
        return $this->render($n);
    }
}
