<?php

/**
 * @link              https://hesabix.ir/
 * @since             1.0.0
 * @package           ssbhesabix
 *
 * @wordpress-plugin
 * Plugin Name:       hesabix Accounting
 * Plugin URI:        https://www.hesabix.ir/
 * Description:       Connect hesabix Online Accounting to WooCommerce.
 * Version:           0.1.1
 * Author:            Babak Alizadeh
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       ssbhesabix
 * Domain Path:       /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 8.4.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 */
define('SSBHESABIX_VERSION', '0.1.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ssbhesabix-activator.php
 */
function activate_ssbhesabix() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ssbhesabix-activator.php';
    Ssbhesabix_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ssbhesabix-deactivator.php
 */
function deactivate_ssbhesabix() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ssbhesabix-deactivator.php';
    Ssbhesabix_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ssbhesabix' );
register_deactivation_hook( __FILE__, 'deactivate_ssbhesabix' );

/**
 * The core plugin class that is used to define internationalization and
 * admin-specific hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ssbhesabix.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ssbhesabix() {
	$plugin = new Ssbhesabix();
	$plugin->run();
}

run_ssbhesabix();
