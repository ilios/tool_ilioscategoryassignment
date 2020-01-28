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
 * @package tool_ilioscategoryassignment
 * @link https://docs.moodle.org/dev/Plugin_files#db.2Funinstall.php
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_tool_ilioscategoryassignment_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018041100) {
        $table = new xmldb_table('tool_ilioscatassignment');
        $field = new xmldb_field('schoolid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'title');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $DB->execute('UPDATE {tool_ilioscatassignment} t SET t.schoolid = (SELECT schoolid FROM {tool_ilioscatassignment_src} WHERE jobid = t.id ORDER BY id DESC LIMIT 1)');
        $table = new xmldb_table('tool_ilioscatassignment_src');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        upgrade_plugin_savepoint(true, 2018041100, 'tool', 'ilioscategoryassignment');
    }
    
    return true;
}
