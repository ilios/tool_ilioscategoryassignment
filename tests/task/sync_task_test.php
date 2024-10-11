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
 * Test coverage for the sync task.
 *
 * @category   test
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment\task;

use advanced_testcase;
use coding_exception;
use core\di;
use core\http_client;
use core\task\manager;
use dml_exception;
use Exception;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use moodle_exception;
use Psr\Http\Message\RequestInterface;
use tool_ilioscategoryassignment\tests\helper;

/**
 * Sync task test class.
 *
 * @category   test
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_ilioscategoryassignment\task\sync_task
 */
final class sync_task_test extends advanced_testcase {

    /**
     * Tests task execution, checks that category role assignment/unassignment works as intended.
     * @return void
     * @throws dml_exception
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function test_execute(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('tool_ilioscategoryassignment');

        $schoolid = 1;

        // Create a course category, a user role, and sync job.
        $category = $dg->create_category();
        $roleid = $dg->create_role();
        $syncjob = $lpg->create_sync_job(['coursecatid' => $category->id, 'schoolid' => $schoolid, 'roleid' => $roleid]);

        // Create some users.
        $dg->create_user(['idnumber' => 'xx00001']);
        $user2 = $dg->create_user(['idnumber' => 'xx00002']);
        $user3 = $dg->create_user(['idnumber' => 'xx00003']);
        $user4 = $dg->create_user(['idnumber' => 'xx00004']);
        $user5 = $dg->create_user(['idnumber' => 'xx00005']);
        $user6 = $dg->create_user(['idnumber' => 'xx00006']);

        // Assign some of these users in the given role to the given course category via this plugin.
        role_assign($syncjob->get('roleid'), $user4->id, $category->get_context()->id, 'tool_ilioscategoryassignment');
        role_assign($syncjob->get('roleid'), $user5->id, $category->get_context()->id, 'tool_ilioscategoryassignment');
        role_assign($syncjob->get('roleid'), $user6->id, $category->get_context()->id, 'tool_ilioscategoryassignment');

        // Mock the Ilios response.
        $mockresponsepayload = [
            'users' => [
                // Non-director/non-instructor, exists in Moodle, currently not assigned to category.
                [
                    'campusId' => 'xx00001',
                    'directedCourses' => [],
                    'directedPrograms' => [],
                    'directedSchools' => [],
                    'instructedLearnerGroups' => [],
                    'instructedOfferings' => [],
                    'instructorIlmSessions' => [],
                    'instructorGroups' => [],
                ],
                // Director, exists in Moodle, currently not assigned to category.
                [
                    'campusId' => 'xx00002',
                    'directedCourses' => [1],
                    'directedPrograms' => [],
                    'directedSchools' => [],
                    'instructedLearnerGroups' => [],
                    'instructedOfferings' => [],
                    'instructorIlmSessions' => [],
                    'instructorGroups' => [],
                ],
                // Instructor, exists in Moodle, currently not assigned to category.
                [
                    'campusId' => 'xx00003',
                    'directedCourses' => [],
                    'directedPrograms' => [],
                    'directedSchools' => [],
                    'instructedLearnerGroups' => [],
                    'instructedOfferings' => [],
                    'instructorIlmSessions' => [],
                    'instructorGroups' => [1],
                ],
                // Instructor, exists in Moodle, currently assigned to category.
                [
                    'campusId' => 'xx00004',
                    'directedCourses' => [],
                    'directedPrograms' => [],
                    'directedSchools' => [],
                    'instructedLearnerGroups' => [],
                    'instructedOfferings' => [],
                    'instructorIlmSessions' => [],
                    'instructorGroups' => [1],
                ],
                // Director, exists in Moodle, currently assigned to category.
                [
                    'campusId' => 'xx00005',
                    'directedCourses' => [],
                    'directedPrograms' => [1],
                    'directedSchools' => [],
                    'instructedLearnerGroups' => [],
                    'instructedOfferings' => [],
                    'instructorIlmSessions' => [],
                    'instructorGroups' => [],
                ],
                // Non-instructor/non-director, exists in Moodle, currently assigned to category.
                [
                    'campusId' => 'xx00006',
                    'directedCourses' => [],
                    'directedPrograms' => [],
                    'directedSchools' => [],
                    'instructedLearnerGroups' => [],
                    'instructedOfferings' => [],
                    'instructorIlmSessions' => [],
                    'instructorGroups' => [],
                ],
                // Instructor, does not exist in Moodle.
                [
                    'campusId' => 'xx99998',
                    'directedCourses' => [],
                    'directedPrograms' => [],
                    'directedSchools' => [],
                    'instructedLearnerGroups' => [],
                    'instructedOfferings' => [],
                    'instructorIlmSessions' => [1],
                    'instructorGroups' => [],
                ],
                // Non-director/non-instructor, does not exist in Moodle.
                [
                    'campusId' => 'xx99999',
                    'directedCourses' => [],
                    'directedPrograms' => [],
                    'directedSchools' => [],
                    'instructedLearnerGroups' => [],
                    'instructedOfferings' => [],
                    'instructorIlmSessions' => [],
                    'instructorGroups' => [],
                ],
            ],
        ];
        $accesstoken = helper::create_valid_ilios_api_access_token();
        set_config('apikey', $accesstoken, 'tool_ilioscategoryassignment');
        set_config('host_url', 'http://ilios.demo', 'tool_ilioscategoryassignment');

        // Since the task will be run twice in this test, we'll need to set up two mock responses from Ilios.
        // We'll let the mocked API respond with the same payload twice in order to check that no state change occurs.
        $handlerstack = HandlerStack::create(new MockHandler([
            function(RequestInterface $request) use ($schoolid, $mockresponsepayload) {
                $this->assertEquals('/api/v3/users', $request->getUri()->getPath());
                $this->assertEquals(
                    "filters[enabled]=true&filters[school]={$schoolid}",
                    urldecode($request->getUri()->getQuery())
                );
                return new Response(200, [], json_encode($mockresponsepayload));

            },
            function(RequestInterface $request) use ($schoolid, $mockresponsepayload) {
                $this->assertEquals('/api/v3/users', $request->getUri()->getPath());
                $this->assertEquals(
                    "filters[enabled]=true&filters[school]={$schoolid}",
                    urldecode($request->getUri()->getQuery())
                );
                return new Response(200, [], json_encode($mockresponsepayload));
            },
        ]));
        di::set(http_client::class, new http_client(['handler' => $handlerstack]));

        // Instantiate the task.
        $task = manager::get_scheduled_task(sync_task::class);
        $this->assertInstanceOf(sync_task::class, $task);

        // Pre-sync category role assignment checks.
        $roleassignments = get_users_from_role_on_context((object)['id' => $roleid], $category->get_context());
        $this->assertCount(3, $roleassignments);
        $userids = array_map(fn($assignment) => $assignment->userid, $roleassignments);
        $this->assertContains($user4->id, $userids);
        $this->assertContains($user5->id, $userids);
        $this->assertContains($user6->id, $userids);

        // Execute the task the first time, capture the output.
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();

        // Check the captured task output.
        $this->assertStringContainsString('Retrieved 5 Ilios user(s) to sync.', $output);
        $this->assertStringContainsString(
            'WARNING: Skipping non-matching user accounts with the following Ilios campus IDs: xx99998',
            $output
        );
        $this->assertStringContainsString('Assigned 2 user(s) into category.', $output);
        $this->assertStringContainsString('Un-assigned 1 user(s) from category.', $output);

        // Post-sync category role assignment checks.
        $roleassignments = get_users_from_role_on_context((object)['id' => $roleid], $category->get_context());
        $this->assertCount(4, $roleassignments);
        $userids = array_map(fn($assignment) => $assignment->userid, $roleassignments);
        $this->assertContains($user2->id, $userids, 'User 2 has been assigned to the category role.');
        $this->assertContains($user3->id, $userids, 'User 3 has been assigned to the category role.');
        $this->assertContains($user4->id, $userids, 'User 4 has retained their category role assignment.');
        $this->assertContains($user5->id, $userids, 'User 5 has retained their category role assignment.');
        $this->assertNotContains(
            $user6->id,
            $userids,
            "User 6 has been unassigned since they're neither instructor nor director in Ilios."
        );

        // Execute the task a second time.
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();

        // Check the captured task output.
        $this->assertStringContainsString('Retrieved 5 Ilios user(s) to sync.', $output);
        $this->assertStringContainsString(
            'WARNING: Skipping non-matching user accounts with the following Ilios campus IDs: xx99998',
            $output
        );
        $this->assertStringContainsString('No user assignment/un-assignment necessary.', $output);

        // Post-sync user assignment checks, nothing should have changed this time.
        $roleassignments = array_values(get_users_from_role_on_context((object)['id' => $roleid], $category->get_context()));
        $this->assertCount(4, $roleassignments);
        $userids = array_map(fn($assignment) => $assignment->userid, $roleassignments);
        $components = array_unique(array_map(fn($assignment) => $assignment->component, $roleassignments));
        $this->assertCount(1, $components);
        $this->assertEquals('tool_ilioscategoryassignment', $components[0]);
        $this->assertContains($user2->id, $userids, 'User 2 has retained their category role assignment.');
        $this->assertContains($user3->id, $userids, 'User 3 has retained their category role assignment.');
        $this->assertContains($user4->id, $userids, 'User 4 has retained their category role assignment.');
        $this->assertContains($user5->id, $userids, 'User 5 has retained their category role assignment.');
    }

    /**
     * Check that out-of-band category assignments are not affected by the sync process.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_execute_ignore_out_of_band_category_assignments(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('tool_ilioscategoryassignment');

        $schoolid = 1;

        $category = $dg->create_category();
        $roleid = $dg->create_role();
        $syncjob = $lpg->create_sync_job(['coursecatid' => $category->id, 'schoolid' => $schoolid, 'roleid' => $roleid]);
        $user = $dg->create_user(['idnumber' => 'xx00001']);

        // Manually assign our test user the role into the course category.
        role_assign($syncjob->get('roleid'), $user->id, $category->get_context()->id);

        $accesstoken = helper::create_valid_ilios_api_access_token();
        set_config('apikey', $accesstoken, 'tool_ilioscategoryassignment');
        set_config('host_url', 'http://ilios.demo', 'tool_ilioscategoryassignment');

        $handlerstack = HandlerStack::create(new MockHandler([
            // User is in the payload, as instructor.
            function(RequestInterface $request) use ($schoolid) {
                $this->assertEquals('/api/v3/users', $request->getUri()->getPath());
                $this->assertEquals(
                    "filters[enabled]=true&filters[school]={$schoolid}",
                    urldecode($request->getUri()->getQuery())
                );
                return new Response(200, [], json_encode(['users' => [['campusId' => 'xx00001', 'instructorGroups' => [1]]]]));
            },
            // User is in the payload, as director.
            function(RequestInterface $request) use ($schoolid) {
                $this->assertEquals('/api/v3/users', $request->getUri()->getPath());
                $this->assertEquals(
                    "filters[enabled]=true&filters[school]={$schoolid}",
                    urldecode($request->getUri()->getQuery())
                );
                return new Response(200, [], json_encode(['users' => [['campusId' => 'xx00001', 'directedCourses' => [1]]]]));

            },
            // User is not in payload.
            function(RequestInterface $request) use ($schoolid) {
                $this->assertEquals('/api/v3/users', $request->getUri()->getPath());
                $this->assertEquals(
                    "filters[enabled]=true&filters[school]={$schoolid}",
                    urldecode($request->getUri()->getQuery())
                );
                return new Response(200, [], json_encode(['users' => [['campusId' => 'xx99999']]]));
            },
        ]));
        di::set(http_client::class, new http_client(['handler' => $handlerstack]));

        // Instantiate the task.
        $task = manager::get_scheduled_task(sync_task::class);
        $this->assertInstanceOf(sync_task::class, $task);

        // Pre-sync category role assignment checks.
        $roleassignments = get_users_from_role_on_context((object)['id' => $roleid], $category->get_context());
        $this->assertCount(1, $roleassignments);
        $this->assertEquals($user->id, current($roleassignments)->userid);
        $this->assertEquals('', current($roleassignments)->component);

        // Run the task two times - each time with the user in the payload (see above).
        // The outcome should be the same each time - the out-of-band user assignment does not change,
        // and no new/duplicate role assignment is performed.
        for ($i = 0, $n = 2; $i < $n; $i++) {
            ob_start();
            $task->execute();
            $output = ob_get_contents();
            ob_end_clean();

            // Check the captured task output.
            $this->assertStringContainsString(
                "User with id = {$user->id} had this role assigned out-of-band, skipping.",
                $output
            );

            // Post-sync category role assignment checks.
            // The out-of-band category role assignment is still there.
            $roleassignments = get_users_from_role_on_context((object)['id' => $roleid], $category->get_context());
            $this->assertCount(1, $roleassignments);
            $this->assertEquals($user->id, current($roleassignments)->userid);
            $this->assertEquals('', current($roleassignments)->component);
        }

        // Run the task one more time - this time the user is not in the payload.
        // Confirm that this did not result in the removal of the out-of-band assignment.
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();

        // Check the captured task output.
        $this->assertStringContainsString('No user assignment/un-assignment necessary.', $output);

        // Post-sync category role assignment checks.
        // This still only be this one out-of-band assigned user.
        $roleassignments = get_users_from_role_on_context((object)['id' => $roleid], $category->get_context());
        $this->assertCount(1, $roleassignments);
        $this->assertEquals($user->id, current($roleassignments)->userid);
        $this->assertEquals('', current($roleassignments)->component);
    }

    /**
     * Checks exception handing when Ilios responds with an error.
     * @return void
     * @throws coding_exception
     */
    public function test_execute_with_ilios_api_error(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('tool_ilioscategoryassignment');

        // Minimal setup with one user who has a role assignment in this category via this plugin.
        $category = $dg->create_category();
        $roleid = $dg->create_role();
        $syncjob = $lpg->create_sync_job(['coursecatid' => $category->id, 'schoolid' => 1, 'roleid' => $roleid]);
        $user = $dg->create_user(['idnumber' => 'xx00001']);
        role_assign($syncjob->get('roleid'), $user->id, $category->get_context()->id, 'tool_ilioscategoryassignment');

        $accesstoken = helper::create_valid_ilios_api_access_token();
        set_config('apikey', $accesstoken, 'tool_ilioscategoryassignment');

        $handlerstack = HandlerStack::create(new MockHandler([
            // Respond with a server error.
            new Response(500),
        ]));
        di::set(http_client::class, new http_client(['handler' => $handlerstack]));

        // Instantiate the task.
        $task = manager::get_scheduled_task(sync_task::class);
        $this->assertInstanceOf(sync_task::class, $task);

        // Pre-sync category role assignment checks.
        $roleassignments = get_users_from_role_on_context((object)['id' => $roleid], $category->get_context());
        $this->assertCount(1, $roleassignments);
        $this->assertEquals($user->id, current($roleassignments)->userid);

        // Run the task.
        ob_start();
        try {
            $task->execute();
        } catch (Exception $e) {
            $this->assertEquals('Failed to retrieve users from Ilios with the following error message.', $e->getMessage());
        }
        ob_end_clean();

        // Post-sync category role assignment checks.
        // Confirm that this failure did not result in any assignment changes.
        $roleassignments = get_users_from_role_on_context((object)['id' => $roleid], $category->get_context());
        $this->assertCount(1, $roleassignments);
        $this->assertEquals($user->id, current($roleassignments)->userid);
    }

    /**
     * Checks the task name.
     * @return void
     */
    public function test_get_name(): void {
        $task = manager::get_scheduled_task(sync_task::class);
        $this->assertEquals('Sync category role assignments against Ilios', $task->get_name());
    }
}
