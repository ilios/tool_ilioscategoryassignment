<?php

namespace tool_ilioscategoryassignment\output;

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
     * @param \stdClass[] $ilios_schools
     * @param \stdClass[] $ilios_roles
     * @return string HTML to output.
     */
    public function sync_jobs_table(array $sync_jobs, array $course_categories, array $roles, array $ilios_schools,
            array $ilios_roles) {
        global $CFG;
        $table = new \html_table();
        $table->head = array(
                get_string('title', 'tool_ilioscategoryassignment'),
                get_string('coursecategory'),
                get_string('role'),
                get_string('iliosschool', 'tool_ilioscategoryassignment'),
                get_string('iliosroles', 'tool_ilioscategoryassignment'),
                get_string('actions')
        );
        $table->attributes['class'] = 'admintable generaltable';
        $data = array();
        $not_found_label = '[[ ' . get_string('notfound', 'tool_ilioscategoryassignment') . ' ]]';

        foreach ($sync_jobs as $job) {
            $titlecell = new \html_table_cell($job->get_title());
            $titlecell->header = true;

            $course_category_id = $job->get_course_category_id();
            $course_title = $not_found_label;
            if (! empty($course_categories[$course_category_id])) {
                $course_title = $course_categories[$course_category_id]->get_formatted_name();
            }
            $coursecatcell = new \html_table_cell($course_title);

            $role_id = $job->get_role_id();
            $role_title = $not_found_label;
            if (array_key_exists($role_id, $roles)) {
                $role_title = $roles[$role_id]->name;
            }

            $rolecell = new \html_table_cell($role_title);

            $source = $job->get_sources()[0]; // there should be exactly one.
            $iliosschoolcell = new \html_table_cell($source->get_school_id());
            $iliosrolecell = new \html_table_cell(implode(', ', $source->get_role_ids()));

            $actions = array();
            if ($job->is_enabled()) {
                $actions[] = $this->action_icon(
                        new \moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/actions.php",
                            array('job_id' => $job->get_id(), 'action' => 'disable', 'sesskey' => \sesskey())),
                        new \pix_icon('t/hide', new \lang_string('disable'))
                );
            } else {
                $actions[] = $this->action_icon(
                        new \moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/actions.php",
                                array('job_id' => $job->get_id(), 'action' => 'enable', 'sesskey' => \sesskey())),
                        new \pix_icon('t/show', new \lang_string('enable'))
                );
            }

            $actions[] = $this->action_icon(
                    new \moodle_url("$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/actions.php",
                            array('job_id' => $job->get_id(), 'action' => 'delete', 'sesskey' => \sesskey())),
                    new \pix_icon('t/delete', new \lang_string('delete'))
            );


            $actionscell = new \html_table_cell(implode(' ', $actions));

            $row = new \html_table_row(array(
                    $titlecell,
                    $coursecatcell,
                    $rolecell,
                    $iliosschoolcell,
                    $iliosrolecell,
                    $actionscell
            ));
            $data[] = $row;
        }
        $table->data = $data;
        return \html_writer::table($table);
    }
}
