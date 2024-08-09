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
 * Competency data generator.
 *
 * @package    tool_ilioscategoryassignment
 * @category   test
 * @copyright  2015 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\invalid_persistent_exception;
use core_competency\competency;
use core_competency\competency_framework;
use core_competency\course_competency;
use core_competency\course_module_competency;
use core_competency\evidence;
use core_competency\external;
use core_competency\plan;
use core_competency\plan_competency;
use core_competency\related_competency;
use core_competency\template;
use core_competency\template_cohort;
use core_competency\template_competency;
use core_competency\user_competency;
use core_competency\user_competency_course;
use core_competency\user_competency_plan;
use core_competency\user_evidence;
use core_competency\user_evidence_competency;
use tool_ilioscategoryassignment\sync_job;


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/grade/grade_scale.php');

/**
 * Test data generator class for this plugin.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_ilioscategoryassignment_generator extends component_generator_base {

    /** @var int Number of created sync jobs. */
    protected $syncjobcount = 0;

    /**
     * Resets internal instance counter.
     * @return void
     */
    public function reset(): void {
        $this->syncjobcount = 0;
    }

    /**
     * Create a new user competency plan.
     *
     * @param array|stdClass $record
     * @return sync_job
     * @throws coding_exception
     * @throws invalid_persistent_exception
     */
    public function create_sync_job(array|stdClass $record): sync_job {
        $this->syncjobcount++;
        $i = $this->syncjobcount;
        $record = (object) $record;

        if (!isset($record->title)) {
            $record->title = "Sync job {$i}";
        }

        if (!isset($record->schoolid)) {
            throw new coding_exception('The schoolid value is required.');
        }

        if (!isset($record->coursecatid)) {
            throw new coding_exception('The coursecatid value is required.');
        }

        if (!isset($record->roleid)) {
            throw new coding_exception('The roleid value is required.');
        }

        if (!isset($record->enabled)) {
            $record->enabled = true;
        }

        $syncjob = new sync_job(0, $record);
        $syncjob->create();

        return $syncjob;
    }
}
