<?php

namespace Includes;

class Deactivator
{
	public static function activate() {
		// Install database tables.
		self::deactivate();
	}

	public static function deactivate() {
		global $wpdb;

		$table_name_templates = $wpdb->prefix . efficiency_form_plugin_table_name;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = '';

		$sql = "DROP TABLE IF EXISTS $table_name_templates";
		$wpdb->query($sql);
	}
}