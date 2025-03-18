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
 * Utility class.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment;

use core\di;
use core\http_client;
use GuzzleHttp\HandlerStack;
use tool_ilioscategoryassignment\tests\ilios_backend;

/**
 * Provides utility methods for this plugin.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Returns an Ilios API client.
     * @return ilios The Ilios API client.
     */
    public static function get_ilios_client(): ilios {
        // ACHTUNG!
        // If we're running in Behat test mode ONLY, then return a client with a mocked Ilios backend.
        if (defined('BEHAT_SITE_RUNNING') && get_config('tool_ilioscategoryassignment', 'ilios_mock_backend_enabled')) {
            $handlerstack = HandlerStack::create(new ilios_backend());
            $httpclient = new http_client(['handler' => $handlerstack]);
            return new ilios($httpclient);
        }
        // Otherwise, return the container-managed Ilios client instance.
        return di::get(ilios::class);
    }
}
