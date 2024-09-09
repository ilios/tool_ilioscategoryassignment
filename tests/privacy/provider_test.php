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
 * Privacy provider tests for tool_ilioscategoryassignment.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_ilioscategoryassignment\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\tests\provider_testcase;


/**
 * Privacy provider test case.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_ilioscategoryassignment\privacy\provider
 */
final class provider_test extends provider_testcase {
    /**
     * @var string This component's name.
     */
    public const COMPONENT_NAME = 'tool_ilioscategoryassignment';

    /**
     * Test fetching information about user data stored.
     */
    public function test_get_metadata(): void {
        $collection = new collection(self::COMPONENT_NAME);
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(1, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('tool_ilioscategoryassignment', $table->get_name());
        $privacyfields = $table->get_privacy_fields();
        $this->assertCount(1, $privacyfields);
        $this->assertArrayHasKey('usermodified', $privacyfields);
        $this->assertEquals('privacy:metadata:tool_ilioscategoryassignment', $table->get_summary());
    }
}
