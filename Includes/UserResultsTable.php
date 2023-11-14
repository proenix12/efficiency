<?php

namespace Includes;

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class UserResultsTable extends \WP_List_Table {

	function __construct(){
		global $status, $page;

		parent::__construct(array(
			'singular'  => 'user result',
			'plural'    => 'user results',
			'ajax'      => false
		));
	}

	function get_columns(){
		$columns = array(
			'title'           => 'Result',
			'user_name'       => 'Username',
			'email'           => 'Email',
			'phone'           => 'Phone',
			'income'          => 'Income',
			'income_payable'  => 'Income - payable',
			'payable'         => 'Payable'
		);
		return $columns;
	}

	function prepare_items(){
		global $wpdb;
		$table_name_templates = $wpdb->prefix . efficiency_form_plugin_table_name;
		$per_page = 15; // Specify the number of results per page.

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$current_page = $this->get_pagenum();
		$start = ($current_page-1)*$per_page;

		$query = "SELECT * FROM {$table_name_templates} ORDER BY id DESC LIMIT {$start}, {$per_page}";
		$data = $wpdb->get_results($query);

		$total_items = $wpdb->get_var("SELECT COUNT(id) FROM {$table_name_templates}");

		$this->items = $data;

		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		));
	}

	function column_default($item, $column_name){
		switch($column_name){
			case 'title':
			case 'user_name':
			case 'email':
			case 'phone':
			case 'income':
			case 'income_payable':
			case 'payable':
				return $item->$column_name;
			default:
				return print_r($item,true); // In case of unexpected column name
		}
	}
}
