<?php
/**
 * Plugin Name:       User Logs
 * Plugin URI:        https://websolutionideas.com/
 * Description:       View user login and registration logs
 * Version:           1.0.1
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            Vikas Sharma
 * Author URI:        https://websolutionideas.com/vikas/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wsi_user_logs
 *
 * User Logs
 * Copyright (C) 2021, Vikas Sharma <vikas@websolutionideas.com>
 *
 * 'User Logs' is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * 'User Logs' is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with 'User Logs'. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 *
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

class WSI_User_Logs {

	static $plugin_name     = 'User Logs';
	static $plugin_slug     = 'wsi_user_logs';
	public $error_message   = '';
	public $success_message = '';

	public function __construct() {

		if ( is_admin() ) {
			// Activation and Deactivation hooks
			register_activation_hook( __FILE__, [ $this, 'plugin_activation' ] );
			register_deactivation_hook( __FILE__, [ $this, 'plugin_deactivation' ] );
			add_action( 'admin_init', [ $this, 'do_activation_redirect' ] );
			add_action( 'admin_menu', [ $this, 'create_admin_menu' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts_and_styles' ] );
			add_action( 'admin_notices', [ $this, 'notice_welcome' ] );

			$plugin = plugin_basename(__FILE__);
			add_filter( "plugin_action_links_$plugin", [ $this, 'plugin_page_link' ] );
		}

		// Add Hooks
		add_filter( 'wp_login', [ $this, 'login_filter' ], 10, 2 );
		add_filter( 'wp_logout', [ $this, 'logout_filter' ], 10, 1 );
		add_filter( 'user_register', [ $this, 'user_register_filter' ], 10, 1 );

		self::insert_test_data();
	}

	/**
	 * Activate the plugin
	 */
	public function plugin_activation() {

		global $wpdb;

		set_transient( 'wsi_user_logs_activation_redirect_transient', true, 30 );
		update_option( 'wsi_user_logs_welcome', 0 );

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}user_logs` (
				`login_log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`login_user_id` bigint(20) UNSIGNED NOT NULL,
				`login_user_ip` varchar(255) DEFAULT NULL,
				`login_date` datetime NOT NULL,
				PRIMARY KEY (`login_log_id`)
				) ENGINE=InnoDB {$wpdb->get_charset_collate()};";

		$wpdb->query( $wpdb->prepare( $sql ) );
	}

	/**
	 * Deactivate the plugin
	 */
	public function plugin_deactivation() {
		global $wpdb;

		delete_option( 'wsi_user_logs_welcome' );
		$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}user_logs`" );
	}

	public function do_activation_redirect() {
		// Bail if no activation redirect
		if ( ! get_transient( 'wsi_user_logs_activation_redirect_transient' ) ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( 'wsi_user_logs_activation_redirect_transient' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Redirect to plugin page
		wp_safe_redirect( add_query_arg( array( 'page' => self::$plugin_slug ), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Add menu item in the admin area.
	 */
	public function create_admin_menu() {
		add_menu_page( 'User Logs', 'User Logs', 'edit_posts', 'wsi-user-logs', '', 'dashicons-groups', 100 );
		add_submenu_page( 'wsi-user-logs', 'User Login Logs', 'User Login Logs', 'manage_options', self::$plugin_slug, [ $this, 'login_logs' ] );
		add_submenu_page( 'wsi-user-logs', 'Settings', 'Settings', 'manage_options', self::$plugin_slug, [ $this, 'settings' ] );
		remove_submenu_page( 'wsi-user-logs', 'wsi-user-logs' );
	}

	/**
	 * Plugin settings link.
	 * @param $links
	 * @return mixed
	 */
	public function plugin_page_link( $links ) {
		$settings_link = sprintf( '<a href="admin.php?page=%s">View</a>', self::$plugin_slug );

		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Enqueue CSS for ou plugin in admin area.
	 */
	public function enqueue_admin_scripts_and_styles(){

		// Enqueue these scripts only if we are on the plugin settings page.
		if ( self::is_plugin_page() ) {
			wp_enqueue_style('wsi_user_logs_admin_style', plugin_dir_url(__FILE__) . '/assets/css/admin-styles.css');
			wp_enqueue_script( 'wsi_user_logs_admin_script', plugin_dir_url(__FILE__) . '/assets/js/admin-scripts.js', ['jquery', 'jquery-ui-datepicker'], '1.0.0', true );
			wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
			wp_enqueue_style( 'jquery-ui' );
		}
	}

	/**
	 * Display welcome messages
	 */
	public function notice_welcome() {
		global $pagenow;

		if ( self::is_plugin_page() ) {
			if ( ! get_option( 'wsi_user_logs_welcome' ) ) {
				?>
				<div class="notice notice-success is-dismissible">
						<p><?php echo __( 'Thank you for installing User Logs.', 'wsi_user_logs' ) ?></p>
				</div>
				<?php
				update_option( 'wsi_user_logs_welcome', 1 );
			}
		}
	}

	/**
	 * Plugin page in the admin area.
	 */
	public function login_logs(){
		global $wpdb;

		if ( ! empty( $_GET['delete_user_id'] ) ) {
			$this->delete_login_logs( $_GET['delete_user_id'] );
		}

		$current_page        = ! empty( $_GET['wsi_current_page'] ) ? intval( $_GET['wsi_current_page'] ) : 1;
		$search_user_id      = ! empty( $_GET['wsi_search_user_id'] ) ? sanitize_text_field( $_GET['wsi_search_user_id'] ) : '';
		$search_username     = ! empty( $_GET['wsi_search_username'] ) ? sanitize_text_field( $_GET['wsi_search_username'] ) : '';
		$search_display_name = ! empty( $_GET['wsi_search_display_name'] ) ? sanitize_text_field( $_GET['wsi_search_display_name'] ) : '';
		$search_ip_address   = ! empty( $_GET['wsi_search_ip_address'] ) ? sanitize_text_field( $_GET['wsi_search_ip_address'] ) : '';
		$search_email        = ! empty( $_GET['wsi_search_email'] ) ? sanitize_text_field( $_GET['wsi_search_email'] ) : '';
		$search_from_date    = ! empty( $_GET['wsi_search_from_date'] ) ? sanitize_text_field( $_GET['wsi_search_from_date'] ) : '';
		$search_to_date      = ! empty( $_GET['wsi_search_to_date'] ) ? sanitize_text_field( $_GET['wsi_search_to_date'] ) : '';
		$order_by            = ! empty( $_GET['wsi_order_by'] ) ? sanitize_text_field( $_GET['wsi_order_by'] ) : 'login_log_id';
		$order               = ! empty( $_GET['wsi_order'] ) ? sanitize_text_field( $_GET['wsi_order'] ) : 'DESC';

		// Setting a default argument for $wpdb->prepare();
		$where = ' WHERE 1=%s ';
		$args  = [1];

		if ( ! empty( $search_user_id ) ) {
			$where .= " AND login_user_id LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_user_id );
		}

		if ( ! empty( $search_username ) ) {
			$where .= " AND user_login LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_username );
		}

		if ( ! empty( $search_display_name ) ) {
			$where .= " AND display_name LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_display_name );
		}

		if ( ! empty( $search_ip_address ) ) {
			$where .= " AND login_user_ip LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_ip_address );
		}

		if ( ! empty( $search_email ) ) {
			$where .= " AND user_email LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_email );
		}

		if ( ! empty( $search_from_date ) ) {

			$where .= " AND DATE(login_date) >= %s";
			$args[] = sanitize_text_field( gmdate( 'Y-m-d', strtotime( $search_from_date ) ) );
		} else {
			// get first log date.
			$results = $wpdb->get_row( "SELECT DATE(login_date) AS login_date FROM {$wpdb->prefix}user_logs ORDER BY login_log_id ASC LIMIT 0,1" );
			$placeholder_from_date = ! empty( $results->login_date ) ? $results->login_date : '';
		}

		if ( ! empty( $search_to_date ) ) {
			$where .= " AND DATE(login_date) <= %s";
			$args[] = sanitize_text_field( gmdate( 'Y-m-d', strtotime( $search_to_date ) ) );
		} else {
			// get last log date.
			$results = $wpdb->get_row( "SELECT DATE(login_date) AS login_date FROM {$wpdb->prefix}user_logs ORDER BY login_log_id DESC LIMIT 0,1" );
			$placeholder_to_date = ! empty( $results->login_date ) ? $results->login_date : '';
		}

		// Login Graph Data
		$graph = self::get_login_graph_data( $where, $args );

		// Get rows.
		$sql = "SELECT COUNT(*) AS total 
				FROM {$wpdb->prefix}user_logs AS logs
				LEFT JOIN {$wpdb->prefix}users AS users 
				ON ( logs.login_user_id = users.ID )
				{$where}";

		$results = $wpdb->get_row( $wpdb->prepare( $sql, $args ) );

		$total_rows = ! empty( $results->total ) ? $results->total : 0;

		// Pagination Variables.
		$limit        = 50; // Number of rows to show in page.
		$offset       = ( $current_page - 1 ) * $limit;
		$num_of_pages = ceil( $total_rows / $limit );

		$args[] = $offset;
		$args[] = $limit;

		$sql = "SELECT logs.*, users.ID, users.user_login, users.display_name, users.user_email 
				FROM {$wpdb->prefix}user_logs AS logs
				LEFT JOIN {$wpdb->prefix}users AS users 
				ON ( logs.login_user_id = users.ID )
				{$where}
				ORDER BY {$order_by} {$order} 
				LIMIT %d, %d";

		$logs  = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );

		include_once( __DIR__ . '/templates/logs.php' );
	}

	/**
	 * Get data for login graph.
	 * @param $where
	 * @param $args
	 * @return
	 */
	public static function get_login_graph_data( $where, $args ) {
		global $wpdb;

		// Get data points (Settings a hard limit for max 365 days to avoid query performance issues)
		$sql = "SELECT DATE(logs.login_date) AS wsi_login_date 
				FROM {$wpdb->prefix}user_logs AS logs
				LEFT JOIN {$wpdb->prefix}users AS users 
				ON ( logs.login_user_id = users.ID )
				{$where}
				GROUP BY wsi_login_date
				ORDER BY login_date ASC
				LIMIT 0, 365";

		$data_points = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		if ( empty( $data_points ) ) {
			return [];
		}

		// Get Login Requests
		$sql = "SELECT COUNT(*) AS login_count, DATE(logs.login_date) AS wsi_login_date 
				FROM {$wpdb->prefix}user_logs AS logs
				LEFT JOIN {$wpdb->prefix}users AS users 
				ON ( logs.login_user_id = users.ID )
				{$where}
				AND login_request_type = 1
				GROUP BY wsi_login_date
				ORDER BY login_date ASC
				LIMIT 0, 365";

		$login_requests = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		$login_requests = wp_list_pluck( (array) $login_requests, 'login_count', 'wsi_login_date' );

		// Registration Requests
		$sql = "SELECT COUNT(*) AS registration_count, DATE(logs.login_date) AS wsi_login_date 
				FROM {$wpdb->prefix}user_logs AS logs
				LEFT JOIN {$wpdb->prefix}users AS users 
				ON ( logs.login_user_id = users.ID )
				{$where}
				AND login_request_type = 3
				GROUP BY wsi_login_date
				ORDER BY login_date ASC
				LIMIT 0, 365";

		$registration_requests = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		$registration_requests = wp_list_pluck( (array) $registration_requests, 'registration_count', 'wsi_login_date' );

		$dataset = [];
		$ticks   = [];

		foreach ( (array) $data_points as $data ) {
			$date  = strtotime( $data->wsi_login_date );
			$year  = date( 'Y', $date );
			$month = date( 'm', $date ) - 1;
			$day   = date( 'd', $date );

			// First element of a dataset is date
			$ticks[]   = "new Date($year, $month, $day)";

			$login_count = ! empty( $login_requests[ $data->wsi_login_date ] ) ? $login_requests[ $data->wsi_login_date ] : 0;
			$reg_count   = ! empty( $registration_requests[ $data->wsi_login_date ] ) ? $registration_requests[ $data->wsi_login_date ] : 0;

			$dataset[] = [ "new Date($year, $month, $day)", $login_count, $reg_count ];
		}

		$ticks_json  = str_replace( '"', '', wp_json_encode( $ticks ) );
		$data_json  = str_replace( '"', '', wp_json_encode( $dataset ) );

		return [
			'ticks_json' => $ticks_json,
			'data_json'  => $data_json,
		];
	}

	/**
	 * Plugin page in the admin area.
	 */
	public function settings(){

		$current_page = ! empty( $POST['current-page'] ) ? intval( $POST['current-page'] ) : 1;

		// Display the plugin page
		include_once( __DIR__ . '/templates/settings.php' );
	}

	/**
	 * Are we on our plugin page?
	 * @return bool
	 */
	public static function is_plugin_page() {
		global $pagenow;

		if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && self::$plugin_slug === $_GET['page'] ) {
			return true;
		}
		return false;
	}

	public function login_filter( $user_login, $user ) {
		global $wpdb;

		$table_name = $wpdb->prefix . "user_logs";

		$ip = self::get_user_ip();

		$sql = "INSERT INTO $table_name 
				SET login_user_id  = %d,
				login_user_ip      = %s,
				login_request_type = 1,
				login_date         = %s";

		$wpdb->query( $wpdb->prepare( $sql, [ $user->ID , $ip, gmdate( 'Y-m-d H:i:s' ) ] ) );
	}

	public function logout_filter( $user_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . "user_logs";

		$ip = self::get_user_ip();

		$sql = "INSERT INTO $table_name 
				SET login_user_id  = %d,
				login_user_ip      = %s,
				login_request_type = 2,
				login_date         = %s";

		$wpdb->query( $wpdb->prepare( $sql, [ $user_id , $ip, gmdate( 'Y-m-d H:i:s' ) ] ) );
	}

	public function user_register_filter( $user_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . "user_logs";

		$ip = self::get_user_ip();

		$sql = "INSERT INTO $table_name 
				SET login_user_id  = %d,
				login_user_ip      = %s,
				login_request_type = 3,
				login_date         = %s";

		$wpdb->query( $wpdb->prepare( $sql, [ $user_id , $ip, gmdate( 'Y-m-d H:i:s' ) ] ) );
	}

	/**
	 * Get user' IP
	 */
	public static function get_user_ip() {
		$user_ip = ! empty( $_SERVER["REMOTE_ADDR"] ) ? $_SERVER["REMOTE_ADDR"] : '';

		if ( empty( $user_ip ) ) {
			$user_ip = ! empty( $_SERVER["HTTP_X_FORWARDED_FOR"] ) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : '';
		}

		if ( empty( $user_ip ) ) {
			$user_ip = ! empty( $_SERVER["HTTP_CLIENT_IP"] ) ? $_SERVER["HTTP_CLIENT_IP"] : '';
		}

		return $user_ip;
	}

	/**
	 * Delete login logs
	 * @param $log_id
	 */
	public function delete_login_logs( $log_id ) {
		global $wpdb;

		if ( empty( $log_id ) ) {
			return;
		}

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}user_logs WHERE login_log_id = %d", $log_id ) );

		echo '<div class="notice notice-success is-dismissible"><p>Record deleted successfully!</p></div>';
	}

	/**
	 * Generate random data for stress testing.
	 *
	 * Note: This function is not to be used on production.
	 */
	public static function insert_test_data() {
		global $wpdb;

		$users       = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}users" );
		$user_ids    = wp_list_pluck( $users, 'ID' );
		$total_users = count( $users );

		$no_of_days  = 90;

		for( $i = $no_of_days; $i > 0; $i-- ) {

			$user_count_for_the_day = rand( 50, 200 );

			for( $j=0; $j < $user_count_for_the_day; $j++ ) {

				$random_id     = $user_ids[ rand(0, $total_users) ];
				$random_ip     = rand(10, 255) . '.' . rand(10, 255) . '.' . rand(10, 255) . '.' . rand(10, 255);
				$random_date   = gmdate( 'Y-m-d H:i:s', strtotime( $i . ' days ago' ) );
				$login_request = 0 === $j %2 ? rand( 1, 3 ) : rand( 1, 2 ); // making registrations less frequent then logins.

				$sql = "INSERT INTO {$wpdb->prefix}user_logs 
				SET login_user_id  = %d,
				login_user_ip      = %s,
				login_request_type = %d,
				login_date         = %s";

				$wpdb->query( $wpdb->prepare( $sql, [ $random_id , $random_ip, $login_request, $random_date ] ) );
			}
		}
	}

	public static function print_column( $label, $column ) {
		$order    = ( ! empty( $_GET['wsi_order'] ) && 'asc' === $_GET['wsi_order'] ) ? 'desc' : 'asc';

		$html = "<th class='manage-column column-title sortable %s'>
					<a href='#' class='wsi-sort-column' data-order='%s' data-orderby='%s'>
						<span>%s</span>
						<span class='sorting-indicator'></span>
					</a>
				</th>";

		printf( $html, $order, $order, $column, $label );
	}
}

new WSI_User_Logs();
