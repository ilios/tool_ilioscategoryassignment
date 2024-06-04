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
 * Admin settings.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('root', new admin_category(
        'ilioscategoryassignment',
        get_string('pluginname', 'tool_ilioscategoryassignment')));

    // Sync jobs admin page.
    $ADMIN->add('ilioscategoryassignment', new admin_externalpage(
        'ilioscategoryassignment_jobs',
        get_string('syncjobs', 'tool_ilioscategoryassignment'),
        "$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php",
        'moodle/site:config'
    ));

    // New job page.
    $ADMIN->add('ilioscategoryassignment', new admin_externalpage(
        'ilioscategoryassignment_new_jobs',
        get_string('newsyncjob', 'tool_ilioscategoryassignment'),
        "$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/create.php",
        'moodle/site:config'
    ));

    // API client settings page.
    $settings = new admin_settingpage(
        'ilioscategoryassignment_clientconfig',
        get_string('clientconfig', 'tool_ilioscategoryassignment'),
        'moodle/site:config'
    );

    $settings->add(new admin_setting_heading('tool_ilioscategoryassignment_settings', '',
        get_string('clientconfig_desc', 'tool_ilioscategoryassignment')));

    if (!during_initial_install()) {
        $settings->add(new admin_setting_configtext('tool_ilioscategoryassignment/host_url',
            get_string('host_url', 'tool_ilioscategoryassignment'),
            get_string('host_url_desc', 'tool_ilioscategoryassignment'), 'localhost'));
        $settings->add(new admin_setting_configtext('tool_ilioscategoryassignment/apikey',
            get_string('apikey', 'tool_ilioscategoryassignment'),
            get_string('apikey_desc', 'tool_ilioscategoryassignment'), ''));
    }

    $ADMIN->add('ilioscategoryassignment', $settings);
}
