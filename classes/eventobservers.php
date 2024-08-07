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
 * Event observer callbacks.
 *
 * @package tool_ilioscategoryassignment
 * @copyright The Regents of the University of California
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment;

use coding_exception;
use core\event\course_category_deleted;
use moodle_exception;

/**
 * Event observer callbacks.
 *
 * @package tool_ilioscategoryassignment
 * @copyright The Regents of the University of California
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eventobservers {
    /**
     * Event observer for the "course category deleted" event.
     * Removes any sync jobs and role assignments associated with a given deleted category.
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


