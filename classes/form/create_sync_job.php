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
 * Form for creating a new sync job.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment\form;

defined('MOODLE_INTERNAL') || die();

use core\di;
use core_course_category;
use Exception;
use moodle_page;
use moodleform;
use tool_ilioscategoryassignment\ilios;
use tool_ilioscategoryassignment\output\renderer;

require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * New sync job form class.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_sync_job extends moodleform {
    /**
     * Form definition.
     */
    public function definition(): void {
        /* @var moodle_page $PAGE The current page. */
        global $PAGE;

        /* @var renderer $renderer The plugin renderer. */
        $renderer = $PAGE->get_renderer('tool_ilioscategoryassignment');

        $mform = $this->_form;

        $categories = core_course_category::make_categories_list();
        if (empty($categories)) {
            $warning = $renderer->notify_error(get_string('noassignablecategories', 'tool_ilioscategoryassignment'));
            $mform->addElement('html', $warning);
            return;
        }

        $roles = \role_get_names();
        if (empty($roles)) {
            $warning = $renderer->notify_error(get_string('noassignableroles', 'tool_ilioscategoryassignment'));
            $mform->addElement('html', $warning);
            return;
        }

        $roleoptions = [];
        foreach ($roles as $role) {
            $roleoptions[$role->id] = $role->localname;
        }

        try {
            $iliosclient = di::get(ilios::class);
            $iliosschools = $iliosclient->get_schools();
            if (!empty($iliosschools)) {
                $iliosschools = array_column($iliosschools, 'title', 'id');
            }
        } catch (Exception $e) {
            $warning = $renderer->notify_error(get_string('ilioserror', 'tool_ilioscategoryassignment'));
            $mform->addElement('html', $warning);
            return;
        }

        // Add elements to your form.
        $mform->addElement('text', 'title', get_string('title', 'tool_ilioscategoryassignment'));
        $mform->setType('title', PARAM_NOTAGS);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('select', 'categoryid', get_string('selectcategory', 'tool_ilioscategoryassignment'), $categories);
        $mform->addRule('categoryid', null, 'required', null, 'client');

        $mform->addElement('select', 'roleid', get_string('selectrole', 'tool_ilioscategoryassignment'), $roleoptions);
        $mform->addRule('roleid', null, 'required', null, 'client');

        $mform->addElement('select', 'iliosschoolid', get_string('selectiliosschool', 'tool_ilioscategoryassignment'),
            $iliosschools);
        $mform->addRule('iliosschoolid', null, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('submit'));
    }
}
