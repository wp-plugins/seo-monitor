<?php
/**
 *
 * @link              http://www.tobeontheweb.nl/seo-monitor
 * @since             1.0
 * @package           Seo_Monitor
 *
 * @wordpress-plugin
 * Plugin Name:       Seo Monitor
 * Plugin URI:        http://www.tobeontheweb.nl/seo-monitor
 * Description:       Seo Monitor is a free WordPress plugin to monitor your SEO performance for your website(s).
 * Version:           1.0
 * Author:            To Be On The Web
 * Author URI:        http://www.tobeontheweb.nl/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       seo-monitor
 * Domain Path:       /languages
 */

define( 'SEOMONITOR_DB_VERSION', '1.0' );

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-seo-monitor-activator.php
 */
function activate_Seo_Monitor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-seo-monitor-activator.php';
	Seo_Monitor_Activator::activate();
}
/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-seo-monitor-deactivator.php
 */
function deactivate_Seo_Monitor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-seo-monitor-deactivator.php';
	Seo_Monitor_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'activate_Seo_Monitor' );
register_deactivation_hook( __FILE__, 'deactivate_Seo_Monitor' );
/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-seo-monitor.php';
/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_Seo_Monitor() {
	$plugin = new Seo_Monitor();
	$plugin->run();
}
run_Seo_Monitor();