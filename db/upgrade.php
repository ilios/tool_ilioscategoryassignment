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
 * Upgrade script.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Plugin upgrade callback.
 *
 * @param int $oldversion The old plugin version.
 * @return bool Always TRUE.
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws moodle_exception
 * @throws upgrade_exception
 */
function xmldb_tool_ilioscategoryassignment_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018041100) {
        $table = new xmldb_table('tool_ilioscatassignment');
        $field = new xmldb_field('schoolid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'title');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $DB->execute(
            'UPDATE {tool_ilioscatassignment} t SET t.schoolid = ' .
            ' (SELECT schoolid FROM {tool_ilioscatassignment_src} WHERE jobid = t.id ORDER BY id DESC LIMIT 1)'
        );
        $table = new xmldb_table('tool_ilioscatassignment_src');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        upgrade_plugin_savepoint(true, 2018041100, 'tool', 'ilioscategoryassignment');
    }

    if ($oldversion < 2024060400) {
        $table = new xmldb_table('tool_ilioscatassignment');
        $dbman->rename_table($table, 'tool_ilioscategoryassignment');
        upgrade_plugin_savepoint(true, 2024060400, 'tool', 'ilioscategoryassignment');
    }

    if ($oldversion < 2024080600) {
        $tablename = 'tool_ilioscategoryassignment';
        $table = new xmldb_table($tablename);
        foreach (['timecreated', 'timemodified', 'usermodified'] as $field) {
            $dbman->add_field(
                $table,
                new xmldb_field($field, XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0)
            );
        }
        $admin = get_admin();
        $adminid = $admin ? $admin->id : 0;
        $time = time();

        $jobs = $DB->get_records($tablename);
        foreach ($jobs as $job) {
            $job->timecreated = $time;
            $job->timemodified = $time;
            $job->usermodified = $adminid;
            $DB->update_record($tablename, $job);
        }

        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        upgrade_plugin_savepoint(true, 2024080600, 'tool', 'ilioscategoryassignment');
    }

    return true;
}
