<?php
/**
 * Ilios category assignment tool.
 *
 * @package tool_ilioscategoryassignment
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2017071700;
$plugin->requires  = 2016112900;
$plugin->component = 'tool_ilioscategoryassignment';
$plugin->cron      = 24 * 3600;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->dependencies = array(
    'local_iliosapiclient' => 2017071700,
);
