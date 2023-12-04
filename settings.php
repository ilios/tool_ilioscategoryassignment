<?php
/**
 * Adds this plugin to the admin menu.
 *
 * @package tool_ilioscategoryassignment
 */

defined('MOODLE_INTERNAL') || die();

/* @var admin_root $ADMIN */
/* @var $CFG */

if ($hassiteconfig) {

    $ADMIN->add('root', new admin_category(
        'ilioscategoryassignment',
        get_string('pluginname', 'tool_ilioscategoryassignment')));

    // Sync jobs admin page
    $ADMIN->add('ilioscategoryassignment', new admin_externalpage(
        'ilioscategoryassignment_jobs',
        get_string('syncjobs', 'tool_ilioscategoryassignment'),
        "$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/index.php",
        'moodle/site:config'
    ));

    // New job page
    $ADMIN->add('ilioscategoryassignment', new admin_externalpage(
        'ilioscategoryassignment_new_jobs',
        get_string('newsyncjob', 'tool_ilioscategoryassignment'),
        "$CFG->wwwroot/$CFG->admin/tool/ilioscategoryassignment/create.php",
        'moodle/site:config'
    ));

    // API client settings page
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
