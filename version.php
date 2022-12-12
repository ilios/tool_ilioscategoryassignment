<?php
/**
 * Ilios category assignment tool.
 *
 * @package tool_ilioscategoryassignment
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2022121200;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2022111800;        // Requires this Moodle version
$plugin->component = 'tool_ilioscategoryassignment';
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = array(
    'local_iliosapiclient' => 2022121200,
);