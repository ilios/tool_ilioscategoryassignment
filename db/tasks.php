<?php
/**
 * Definition of Ilios category assignment tasks
 *
 * @package   tool_ilioscategoryassignment
 * @category  task
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'tool_ilioscategoryassignment\task\sync_task',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    )
);
