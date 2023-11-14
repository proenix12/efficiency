<?php

namespace Includes;

class Activator {
	public static function activate() {
		// Install database tables.
		self::create_tables();
	}

	private static function create_tables() {
		global $wpdb;

		$table_name_templates = $wpdb->prefix . efficiency_form_plugin_table_name;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = '';

		$query = "CREATE TABLE {$table_name_templates} (
				  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
				  `title` VARCHAR(255) NULL DEFAULT NULL, 
				  `user_name` VARCHAR(255) NULL DEFAULT NULL, 
				  `email` VARCHAR(255) NULL DEFAULT NULL, 
				  `phone` VARCHAR(255) NULL DEFAULT NULL, 
				  `income` VARCHAR(255) NULL DEFAULT NULL, 
				  `income_payable` VARCHAR(255) NULL DEFAULT NULL, 
				  `payable` VARCHAR(255) NULL DEFAULT NULL, 
		        PRIMARY KEY (id)
		        ) $collate;";

		dbDelta( $query );
	}
}