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
 * Defines the sync job model.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment;

use coding_exception;
use core\persistent;
use core_course_category;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');

/**
 * Sync job active record.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_job extends persistent {

    /**
     * @var string The sync job database table name.
     */
    const TABLE = 'tool_ilioscategoryassignment';

    /**
     * Retrieves the model definition.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'title' => [
                'type' => PARAM_RAW,
            ],
            'schoolid' => [
                'type' => PARAM_INT,
            ],
            'coursecatid' => [
                'type' => PARAM_INT,
            ],
            'roleid' => [
                'type' => PARAM_INT,
            ],
            'enabled' => [
                'type' => PARAM_BOOL,
                'default' => true,
            ],
        ];
    }

    /**
     * Returns the course category that this sync job applies to.
     *
     * @return ?core_course_category
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_course_category(): ?core_course_category {
        return core_course_category::get(
            $this->get('coursecatid'),
            IGNORE_MISSING,
            true
        );
    }

    /**
     * Remove any course category role assignments that were managed by this job before deleting it.
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function before_delete(): void {
        $category = $this->get_course_category();
        if (!$category) {
            return;
        }
        $context = $category->get_context();
        role_unassign_all([
            'component' => 'tool_ilioscategoryassignment',
            'roleid' => $this->get('roleid'),
            'contextid' => $context->id,
        ]);
    }
}
