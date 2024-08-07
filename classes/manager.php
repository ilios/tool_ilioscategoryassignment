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
use local_iliosapiclient\ilios_client;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');

/**
 * Handler class event callbacks and Ilios API client management.
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
        $url = get_config('tool_ilioscategoryassignment', 'host_url') ?: '';
        return new ilios_client($url, new curl());
    }
}
