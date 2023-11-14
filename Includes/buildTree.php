<?php

namespace Includes;

class buildTree {

	public function __construct() {
		add_action( 'wp_loaded', [ $this, 'load_css_and_js' ] );

		add_action( 'wp_ajax_get_tree', [ $this, 'getTree' ] );
		add_action( 'wp_ajax_nopriv_get_tree', [ $this, 'getTree' ] );

		add_action( 'wp_ajax_get_tree_math', [ $this, 'getTreeMath' ] );
		add_action( 'wp_ajax_nopriv_get_tree_math', [ $this, 'getTreeMath' ] );

		add_action( 'wp_ajax_build_tree', [ $this, 'buildTree' ] );
		add_action( 'wp_ajax_nopriv_build_tree', [ $this, 'buildTree' ] );

		add_action( 'wp_ajax_build_tree_math', [ $this, 'buildTreeMath' ] );
		add_action( 'wp_ajax_nopriv_build_tree_math', [ $this, 'buildTreeMath' ] );

		add_action( 'wp_ajax_upload_file', [ $this, 'uploadFile' ] );
		add_action( 'wp_ajax_nopriv_upload_file', [ $this, 'uploadFile' ] );

		add_action( 'wp_ajax_write_result', [ $this, 'writeResult' ] );
		add_action( 'wp_ajax_nopriv_write_result', [ $this, 'writeResult' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'load_css_and_js_admin' ] );

		add_shortcode( 'decision_tree_load_form', [ $this, 'decision_html' ] );
	}

	function load_css_and_js_admin() {
		$timestamp = time();
		wp_register_style( 'richtexteditor_css', plugins_url( 'assets/richtexteditor/rte_theme_default.css', plugin_dir ), false, $timestamp, 'all' );
		wp_enqueue_style( 'richtexteditor_css' );

		wp_register_script( 'richtexteditor_js', plugins_url( 'assets/richtexteditor/rte.js', plugin_dir ), false, $timestamp, true, true );
		wp_enqueue_script( 'richtexteditor_js' );

		wp_register_script( 'richtexteditor_plugin_js', plugins_url( 'assets/richtexteditor/plugins/all_plugins.js', plugin_dir ), false, $timestamp, true, true );
		wp_enqueue_script( 'richtexteditor_plugin_js' );
	}

	public function load_css_and_js() {
		$timestamp = time();

		wp_register_style( 'build_tree_css', plugins_url( 'assets/css/build_tree.css', plugin_dir ), false, $timestamp, 'all' );
		wp_enqueue_style( 'build_tree_css' );

		wp_register_script( 'button', plugins_url( 'assets/js/button.js', plugin_dir ), false, $timestamp, true, true );
		wp_enqueue_script( 'button' );

		wp_register_script( 'three-ajaxHandle',
			plugins_url( 'assets/js/tree.js', plugin_dir ),
			array( 'jquery' ),
			$timestamp,
			true
		);
		wp_enqueue_script( 'three-ajaxHandle' );
		wp_localize_script(
			'three-ajaxHandle',
			'build_tree',
			array(
				'build_tree_url' => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'     => wp_create_nonce( 'my_ajax_nonce' ),
			)
		);
	}


	public function buildTree() {
		if ( isset( $_POST['jsonData'] ) ) {
			$jsonData = stripslashes( $_POST['jsonData'] );

			$plugin_directory = plugin_dir_path( __FILE__ );

			// The file to write to
			$file = $plugin_directory . 'tree.json';

			// Write the contents back to the file
			file_put_contents( $file, $jsonData );

			wp_die();
		}
	}

	public function buildTreeMath() {
		if ( isset( $_POST['jsonData'] ) ) {
			$jsonData = stripslashes( $_POST['jsonData'] );

			$plugin_directory = plugin_dir_path( __FILE__ );

			// The file to write to
			$file = $plugin_directory . 'math.json';

			// Write the contents back to the file
			file_put_contents( $file, $jsonData );

			wp_send_json($jsonData);
			wp_die();
		}
	}

	public function getTree() {
		$plugin_directory = plugin_dir_path( __FILE__ );

		// The file to read
		$file = $plugin_directory . 'tree.json';

		// Read the file contents
		$jsonData = file_get_contents( $file );

		// Send the JSON data back as a response
		wp_send_json($jsonData);
		wp_die();
	}

	public function getTreeMath() {
		$plugin_directory = plugin_dir_path( __FILE__ );

		// The file to read
		$file = $plugin_directory . 'math.json';

		// Read the file contents
		$jsonData = file_get_contents( $file );

		// Send the JSON data back as a response
		wp_send_json($jsonData);
		wp_die();
	}

	public function uploadFile() {
		if ( isset( $_FILES['file']['tmp_name'] ) ) {
			// Check if it's a JSON file
			if ( $_FILES['file']['type'] == "application/json" ) {
				// Define the path to save the file
				$target_dir  = plugin_dir_path( __FILE__ );
				$target_file = $target_dir . 'tree.json'; // specify the fixed file name

				// Move the uploaded file to the target directory
				if ( move_uploaded_file( $_FILES["file"]["tmp_name"], $target_file ) ) {

					$jsonData = file_get_contents( $target_file );

					echo $jsonData;

					wp_die();
				}
			}
		}
	}


	function writeResult() {
		check_ajax_referer( 'my_ajax_nonce', 'security' );
		//if user is logged in
		if ( is_user_logged_in() ) {
			//get user id
			$user_id      = get_current_user_id();
			$current_user = wp_get_current_user();
			//get user meta
			$user_meta  = get_user_meta( $user_id );
			$user_email = $current_user->user_email;
			$user_name  = $user_meta['first_name'][0] ? $user_meta['first_name'][0] : $user_meta['user_login'][0];
			$user_phone = $user_meta['phone'][0];

			$title          = $_POST['title'];
			$income         = $_POST['income'];
			$income_payable = $_POST['income_payable'];
			$payable        = $_POST['payable'];

			global $wpdb;
			$table = $wpdb->prefix . efficiency_form_plugin_table_name;


			$wpdb->insert( $table, [
				"title"          => $title,
				"user_name"      => $user_name,
				"email"          => $user_email,
				"phone"          => $user_phone,
				"income"         => $income,
				"income_payable" => $income_payable,
				"payable"        => $payable,
			] );

		}
	}


	public function decision_html() {

		ob_start();
		require_once( efficiency_plugin_dir . "/pages/form.php" );

		return ob_get_clean();

	}

}