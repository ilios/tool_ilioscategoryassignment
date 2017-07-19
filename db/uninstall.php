<?php
/**
 * Plugin un-installation callback.
 *
 * @package tool_ilioscategoryassignment
 * @link https://docs.moodle.org/dev/Plugin_files#db.2Funinstall.php
 */

defined('MOODLE_INTERNAL') || die();

/* @global $CFG */
require_once($CFG->libdir . '/accesslib.php');

/**
 * @return bool
 */
function xmldb_tool_ilioscategoryassignment_uninstall() {
    role_unassign_all(array('component' => 'tool_ilioscategoryassignment'));
}
