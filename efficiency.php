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


function check_for_plugin_update()
{
    $plugin_data = get_plugin_data(__FILE__);
    $current_version = $plugin_data['Version'];
    
    $github_url = 'https://api.github.com/repos/proenix12/efficiency/releases/latest';
    $response = wp_remote_get($github_url);
    
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        $latest_version = $data->tag_name;
        
        if (version_compare($current_version, $latest_version, '<')) {
            $plugin_slug = plugin_basename(__FILE__);
            $download_url = "https://github.com/yourusername/your-repo/archive/{$latest_version}.zip";
            
            $upgrade = new Plugin_Upgrader();
            $package = $download_url;
            
            // Perform the update
            $upgrade_result = $upgrade->run([
                'package' => $package,
                'destination' => WP_PLUGIN_DIR,
                'clear_destination' => true,
                'clear_working' => true,
                'is_multi' => false,
                'hook_extra' => [
                    'plugin' => $plugin_slug,
                    'type' => 'plugin',
                    'action' => 'update',
                ],
            ]);
            
            if ($upgrade_result === true) {
                // Update was successful
                // You may want to perform additional actions after a successful update
            } else {
                // Update failed
                // You may want to handle errors or log the failure
            }
        }

    }
}
add_action('init', 'check_for_plugin_update');