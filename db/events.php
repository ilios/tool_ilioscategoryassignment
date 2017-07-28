<?php

/**
 * Event observer definitions.
 *
 * @package tool_ilioscategoryassignment
 */

// List of observers.
$observers = array(
    array(
        'eventname' => '\core\event\course_category_deleted',
        'priority' => 1,
        'callback' => '\tool_ilioscategoryassignment\manager::course_category_deleted',
    ),
);
