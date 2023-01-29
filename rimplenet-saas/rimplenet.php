<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://rimplenet.com
 * @since             1.0.0
 * @package           Rimplenet
 *
 * @wordpress-plugin
 * Plugin Name:       Rimplenet
 * Plugin URI:        https://rimplenet.com
 * Description:       Rimplenet FinTech | E-Banking | E-Wallets  | Investments Plugin | MLM | Matrix Tree | Referral Manager 
 * Version:           1.1.31
 * Author:            Nellalink
 * Author URI:        https://rimplenet.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rimplenet
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Using SemVer - https://semver.org
 */
define( 'RIMPLENET_VERSION', '1.1.31' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rimplenet-activator.php
 */
function activate_rimplenet() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rimplenet-activator.php';
	Rimplenet_Mlm_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rimplenet-deactivator.php
 */
function deactivate_rimplenet() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rimplenet-deactivator.php';
	Rimplenet_Mlm_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rimplenet' );
register_deactivation_hook( __FILE__, 'deactivate_rimplenet' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rimplenet.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */


//  Class Rimplenet_Launcher{
// 	public function __construct()
// 	{
// 		add_action('init', array($this, 'run_rimplenet'), 10);
// 		add_action('admin-init', array($this, 'run_rimplenet'), 10);
// 		$this->run_rimplenet();
		
// 	}

// 	public function run_rimplenet() {

// 		$plugin = new Rimplenet();
// 		$plugin->run();
	
// 	}
//  }

//  new Rimplenet_Launcher();




function run_rimplenet(){
	$plugin = new Rimplenet();
	$plugin->run();
}

run_rimplenet();