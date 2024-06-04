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

/**
 * Sync job model class.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_job {
    /**
     * @var int $id The sync job ID.
     */
    protected $id;

    /**
     * @var string $title The sync job title.
     */
    protected $title;

    /**
     * @var int $role_id The Moodle user role ID to assign to users during sync.
     */
    protected $roleid;

    /**
     * @var int $coursecategoryid The Moodle course category ID to sync users into.
     */
    protected $coursecategoryid;

    /**
     * @var bool $enabled TRUE if this job is enabled, otherwise FALSE.
     */
    protected $enabled;

    /**
     * @var int $schoolid The Ilios school ID to sync users from.
     */
    protected $schoolid;

    /**
     * Constructor.
     *
     * @param int $id The sync job Id.
     * @param string $title The sync job title.
     * @param int $roleid The Moodle user role ID to assign to users during sync.
     * @param int $coursecategoryid The Moodle course category ID to sync users into.
     * @param bool $enabled TRUE if this job is enabled, otherwise FALSE.
     * @param int $schoolid The Ilios school ID to sync users from.
     */
    public function __construct($id, $title, $roleid, $coursecategoryid, $enabled, $schoolid) {
        $this->id = $id;
        $this->title = $title;
        $this->roleid = $roleid;
        $this->coursecategoryid = $coursecategoryid;
        $this->enabled = $enabled;
        $this->schoolid = $schoolid;
    }

    /**
     * Returns the sync job ID.
     *
     * @return int The sync job ID.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns the sync job title.
     *
     * @return string The sync job title.
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Returns the Moodle user role ID.
     *
     * @return int The user role ID.
     */
    public function get_role_id() {
        return $this->roleid;
    }

    /**
     * Returns the Ilios school ID.
     *
     * @return int The school ID.
     */
    public function get_school_id() {
        return $this->schoolid;
    }

    /**
     * Returns the Moodle course category ID.
     *
     * @return int The course category ID.
     */
    public function get_course_category_id() {
        return $this->coursecategoryid;
    }

    /**
     * Returns whether this sync job is enabled or not.
     *
     * @return bool TRUE if this job is enabled, otherwise FALSE.
     */
    public function is_enabled() {
        return $this->enabled;
    }

    /**
     * Magic getter function.
     *
     * @param string $prop The property name.
     * @return mixed The property value.
     */
    public function __get($prop) {
        return $this->$prop;
    }

    /**
     * Determine if a variable is declared and is different from NULL.
     *
     * @param string $prop The property name.
     * @return bool TRUE if the property is declared/set, FALSE otherwise.
     */
    public function __isset($prop) {
        return isset($this->$prop);
    }
}
