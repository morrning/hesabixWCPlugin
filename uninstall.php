<?php

/**
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 * @since      1.0.0
 *
 * @package    ssbhesabix
 */

// If uninstall not called from WordPress, then exit.
if (!defined( 'WP_UNINSTALL_PLUGIN')) {
	exit;
}

include_once(plugin_dir_path(__DIR__) . 'admin/services/hesabixLogService.php');
require 'includes/class-ssbhesabix-api.php';

// delete tags in hesabix
$hesabixApi = new Ssbhesabix_Api();
$result = $hesabixApi->fixClearTags();
if (!$result->Success) {
    hesabixLogService::log(array("ssbhesabix - Cannot clear tags. Error Message: " . (string)$result->ErrorMessage . ". Error Code: " . (string)$result->ErrorCode));
}

global $wpdb;
$options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%ssbhesabix%'");
foreach ($options as $option) {
    delete_option($option->option_name);
}

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ssbhesabix");
