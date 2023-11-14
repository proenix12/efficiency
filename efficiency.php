<?php

/*
Plugin Name: Efficiency
Plugin URI: https://grind.studio
Description: Plugin helps make decision three json
Version: 1.0
Author: Grind
Author URI: https://grind.studio
Text Domain: Efficiency
License: A "Slug" license name e.g. GPL2
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/vendor/autoload.php';

use Includes\Activator;
use Includes\Deactivator;
use Includes\Menu;
use Includes\buildTree;

define( 'efficiency_form_plugin_table_name', 'efficiency_form' );
define( 'efficiency_plugin_basename', plugin_basename( __FILE__ ) );
define( 'efficiency_plugin_dir', plugin_dir_path( __FILE__ ) );
define( 'efficiency_plugin_dir_url', plugin_dir_url( __FILE__ ) );
const plugin_url = 'efficiency';
const plugin_dir = __FILE__;


register_activation_hook( __FILE__, function() {
	Activator::activate();
} );

register_deactivation_hook( __FILE__, function() {
	Deactivator::deactivate();
} );


new Menu;
new buildTree;