<?php
/**
 * Form for creating a new sync job.
 *
 * @package  tool_ilioscategoryassignment
 * @category form
 */

namespace tool_ilioscategoryassignment\form;

defined('MOODLE_INTERNAL') || die();

use core_course_category;
use moodleform;
use tool_ilioscategoryassignment\manager;
use tool_ilioscategoryassignment\output\renderer;

/* @global $CFG */
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating a new sync job.
 *
 * @package  tool_ilioscategoryassignment
 * @category form
 */
class create_sync_job extends moodleform {

    /**
     * @inheritdoc
     */
    public function definition() {
        /* @var \moodle_page $PAGE */
        global $PAGE;

        /* @var renderer $renderer */
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

        $role_options = array();
        foreach ($roles as $role) {
            $role_options[$role->id] = $role->localname;
        }

        try {
            $ilios_client = manager::instantiate_ilios_client();
            $access_token = manager::get_config('apikey', '');
            $ilios_schools = $ilios_client->get($access_token, 'schools');
            if (!empty($ilios_schools)) {
                $ilios_schools = array_column($ilios_schools, 'title', 'id');
            }
        } catch (\Exception $e) {
            $warning = $renderer->notify_error(get_string('ilioserror', 'tool_ilioscategoryassignment'));
            $mform->addElement('html', $warning);
            return;
        }

        $mform->addElement('text', 'title', get_string('title', 'tool_ilioscategoryassignment')); // Add elements to your form
        $mform->setType('title', PARAM_NOTAGS);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('select', 'categoryid', get_string('selectcategory', 'tool_ilioscategoryassignment'), $categories);
        $mform->addRule('categoryid', null, 'required', null, 'client');

        $mform->addElement('select', 'roleid', get_string('selectrole', 'tool_ilioscategoryassignment'), $role_options);
        $mform->addRule('roleid', null, 'required', null, 'client');

        $mform->addElement('select', 'iliosschoolid', get_string('selectiliosschool', 'tool_ilioscategoryassignment'),
            $ilios_schools);
        $mform->addRule('iliosschoolid', null, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('submit'));
    }
}
