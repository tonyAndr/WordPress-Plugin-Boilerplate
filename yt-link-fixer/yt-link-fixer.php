<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://seocherry.ru/
 * @since             0.1.0
 * @package           Yt_Link_Fixer
 *
 * @wordpress-plugin
 * Plugin Name:       Youtube Fixer
 * Plugin URI:        http://seocherry.ru/
 * Description:       Find and replace broken youtube embed videos on your website.
 * Version:           0.1.1
 * Author:            SeoCherry.Ru
 * Author URI:        http://seocherry.ru/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       yt-link-fixer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 0.1.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'YT_LINK_FIXER_VERSION', '0.1.1' );

define( 'YT_LINK_FIXER_CRON_HOOK_CHECK', 'yt_link_fixer_cron_hook_check');
define( 'YT_LINK_FIXER_CRON_HOOK_REPLACE', 'yt_link_fixer_cron_hook_replace');
define( 'YT_LINK_FIXER_CRON_HOOK_MAILER', 'yt_link_fixer_cron_hook_mailer');


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-yt-link-fixer-activator.php
 */
function activate_yt_link_fixer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-yt-link-fixer-activator.php';
	Yt_Link_Fixer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-yt-link-fixer-deactivator.php
 */
function deactivate_yt_link_fixer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-yt-link-fixer-deactivator.php';
	Yt_Link_Fixer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_yt_link_fixer' );
register_deactivation_hook( __FILE__, 'deactivate_yt_link_fixer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-yt-link-fixer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1.0
 */
function run_yt_link_fixer() {

	$plugin = new Yt_Link_Fixer();
	$plugin->run();

}
run_yt_link_fixer();
