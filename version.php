<?php
/**
 * Ilios category assignment tool - version file.
 *
 * @package tool_ilioscategoryassignment
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2024052300;       // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2023100400;       // Requires this Moodle version
$plugin->component = 'tool_ilioscategoryassignment';     // Full name of the plugin (used for diagnostics)
$plugin->release = 'v4.3';
$plugin->supported = [403, 403];
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = [
    'local_iliosapiclient' => 2024032200,
];
