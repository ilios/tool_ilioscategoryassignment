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
 * Provides test coverage for the sync job model.
 *
 * @category   test
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment;

use advanced_testcase;
use coding_exception;
use moodle_exception;

/**
 * Test case for the sync_job class.
 *
 * @category   test
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_ilioscategoryassignment\sync_job
 */
final class sync_job_test extends advanced_testcase {

    /**
     * Checks that sync_job::get_course_category() works as intended.
     *
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function test_get_course_category(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('tool_ilioscategoryassignment');

        $category = $dg->create_category();
        $roleid = $dg->create_role();
        $syncjob = $lpg->create_sync_job(['coursecatid' => $category->id, 'schoolid' => 1, 'roleid' => $roleid]);

        $this->assertEquals($category->id, $syncjob->get_course_category()->id);

        $category->delete_full();
        $this->assertNull($syncjob->get_course_category());
    }

    /**
     * Checks that all course category role assignments for users managed by this plugin are removed when their
     * corresponding sync job is deleted.
     * See <code>\tool_ilioscategoryassignment\sync_job::before_delete()</code>.
     *
     * @return void
     * @throws coding_exception
     */
    public function test_before_delete(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('tool_ilioscategoryassignment');

        $category = $dg->create_category();
        $roleid = $dg->create_role();
        $user1 = $dg->create_user();
        $user2 = $dg->create_user();
        $user3 = $dg->create_user();
        $syncjob = $lpg->create_sync_job(['coursecatid' => $category->id, 'schoolid' => 1, 'roleid' => $roleid]);

        $context = $category->get_context();

        // These users have role assignments made by this plugin.
        role_assign($roleid, $user1->id, $context->id, 'tool_ilioscategoryassignment');
        role_assign($roleid, $user2->id, $context->id, 'tool_ilioscategoryassignment');
        // This user is "manually" assigned.
        role_assign($roleid, $user3->id, $context->id);

        $this->assertTrue(user_has_role_assignment($user1->id, $roleid, $context->id));
        $this->assertTrue(user_has_role_assignment($user2->id, $roleid, $context->id));
        $this->assertTrue(user_has_role_assignment($user3->id, $roleid, $context->id));

        $syncjob->delete();

        $this->assertFalse(
            user_has_role_assignment($user1->id, $roleid, $context->id),
            'The plugin-managed role assignment for user1 has been removed'
        );
        $this->assertFalse(
            user_has_role_assignment($user2->id, $roleid, $context->id),
            'The plugin-managed role assignment for user2 has been removed'
        );
        $this->assertTrue(
            user_has_role_assignment($user3->id, $roleid, $context->id),
            'The out-of-band managed role assignment for user3 persists.'
        );
    }
}
