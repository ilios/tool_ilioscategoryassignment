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
 * Test fixtures generator testcase.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_ilioscategoryassignment;

use advanced_testcase;
use coding_exception;
use Firebase\JWT\JWT;
use moodle_exception;

/**
 * Tests the fixture generator for tests.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \tool_ilioscategoryassignment_generator
 */
final class generator_test extends advanced_testcase {

    /**
     * Tests sync job generator.
     * @return void
     * @throws coding_exception
     */
    public function test_create_sync_job(): void {
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $lpg = $dg->get_plugin_generator('tool_ilioscategoryassignment');
        $roleid = $dg->create_role();
        $category = $dg->create_category();
        $schoolid = 1;
        $this->assertEquals(0, sync_job::count_records());
        $props = [
            'roleid' => $roleid,
            'coursecatid' => $category->id,
            'schoolid' => $schoolid,
        ];

        $syncjob = $lpg->create_sync_job($props);
        $this->assertEquals(1, sync_job::count_records());
        $this->assertInstanceOf(sync_job::class, $syncjob);
        $this->assertEquals('Sync job 1', $syncjob->get('title'));
        $this->assertEquals($roleid, $syncjob->get('roleid'));
        $this->assertEquals($category->id, $syncjob->get('coursecatid'));
        $this->assertEquals($schoolid, $syncjob->get('schoolid'));
        $this->assertTrue($syncjob->get('enabled'));

        $syncjob = $lpg->create_sync_job($props);
        $this->assertEquals(2, sync_job::count_records());
        $this->assertEquals('Sync job 2', $syncjob->get('title'));

        $syncjob = $lpg->create_sync_job(array_merge(
            $props,
            [
                'title' => 'foo bar',
                'enabled' => false,
            ]
        ));
        $this->assertEquals(3, sync_job::count_records());
        $this->assertEquals('foo bar', $syncjob->get('title'));
        $this->assertFalse($syncjob->get('enabled'));

        $syncjob = $lpg->create_sync_job($props);
        $this->assertEquals(4, sync_job::count_records());
        $this->assertEquals('Sync job 4', $syncjob->get('title'));
    }

    /**
     * Checks that the generator function creates a valid access token.
     * @return void
     * @throws moodle_exception
     */
    public function test_create_valid_ilios_api_access_token(): void {
        $lpg = $this->getDataGenerator()->get_plugin_generator('tool_ilioscategoryassignment');
        $accesstoken = $lpg->create_valid_ilios_api_access_token();
        $tokenpayload = ilios::get_access_token_payload($accesstoken);
        $this->assertLessThan($tokenpayload['exp'], time(), 'Token expiration date is in the future.');
    }

    /**
     * Checks that the generator function creates an invalid access token.
     * @return void
     * @throws moodle_exception
     */
    public function test_create_invalid_ilios_api_access_token(): void {
        $lpg = $this->getDataGenerator()->get_plugin_generator('tool_ilioscategoryassignment');
        $accesstoken = $lpg->create_invalid_ilios_api_access_token();
        $tokenpayload = ilios::get_access_token_payload($accesstoken);
        $this->assertLessThan(time(), $tokenpayload['exp'], 'Token expiration date is in the past.');
    }
}

