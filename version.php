<?php
/**
 * Ilios category assignment tool.
 *
 * @package tool_ilioscategoryassignment
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2017072800;
$plugin->requires = 2016112900;
$plugin->component = 'tool_ilioscategoryassignment';
$plugin->maturity = MATURITY_RC;
$plugin->release = '1.0.0-RC2';
$plugin->dependencies = array(
    'local_iliosapiclient' => 2017071700,
);
