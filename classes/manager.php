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
 * Handles configuration and Ilios client management.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment;

use coding_exception;
use core\event\course_category_deleted;
use curl;
use dml_exception;
use local_iliosapiclient\ilios_client;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');

/**
 * Handler class for sync job, configuration and Ilios client management.
 *
 * In other words, this is the kitchen sink.
 * This is obviously less than ideal, but still better than polluting the global namespace with functions in locallib.php.
 * [ST 2017/07/24]
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * Instantiates and returns an Ilios API client.
     *
     * @return ilios_client
     * @throws moodle_exception
     */
    public static function instantiate_ilios_client(): ilios_client {
        return new ilios_client(self::get_config('host_url', ''), new curl());
    }

    /**
     * Loads, caches and returns the configuration for this plugin.
     *
     * @return stdClass The plugin configuration object.
     * @see get_config()
     * @throws dml_exception
     */
    public static function get_plugin_config() {
        static $config = null;
        if (!isset($config)) {
            $config = get_config('tool_ilioscategoryassignment');
        }
        return $config;
    }

    /**
     * Returns a configuration item by its given name or a given default value.
     *
     * @param string $name The config item name.
     * @param string $default A default value if the config item does not exist.
     * @return mixed The config value or the given default value.
     * @throws dml_exception
     */
    public static function get_config($name, $default = null) {
        $config = self::get_plugin_config();
        return isset($config->$name) ? $config->$name : $default;
    }

    /**
     * Sets and stores a given config value.
     *
     * @param string $name The config item name.
     * @param string $value string The config item's value, NULL means unset the config item.
     * @return void
     * @throws dml_exception
     */
    public static function set_config($name, $value): void {
        $config = self::get_plugin_config();
        if ($value === null) {
            unset($config->$name);
        } else {
            $config->$name = $value;
        }
        set_config($name, $value, 'tool_ilioscategoryassignment');
    }

    /**
     * Event observer for the "course category deleted" event.
     * Removes any sync jobs and role assignments associated with that category.
     *
     * @param course_category_deleted $event
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function course_category_deleted(course_category_deleted $event): void {
        $category = $event->get_coursecat();
        $jobs = sync_job::get_records(['coursecatid' => $category->id]);
        foreach ($jobs as $job) {
            $job->delete();
        }
    }
}
