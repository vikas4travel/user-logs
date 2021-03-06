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
		add_filter( 'wp_login', [ $this, 'login_action' ], 10, 2 );
		add_filter( 'wp_logout', [ $this, 'logout_action' ], 10, 1 );
		add_filter( 'user_register', [ $this, 'user_registered_action' ], 10, 1 );
		add_filter( 'comment_post', [ $this, 'comment_posted_action' ], 10, 2 );
		//self::insert_test_data();
	}

	/**
	 * Activate the plugin
	 */
	public function plugin_activation() {

		global $wpdb;

		set_transient( 'wsi_user_logs_activation_redirect_transient', true, 30 );
		update_option( 'wsi_user_logs_welcome', 0 );

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}user_logs` (
				`log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`log_user_id` bigint(20) UNSIGNED NOT NULL,
				`log_user_login` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
				`log_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
				`log_display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
				`log_comment_id` int(11) DEFAULT NULL,
				`log_user_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
				`log_request_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=login, 2=logout',
				`log_date` datetime NOT NULL,
				PRIMARY KEY (`log_id`)
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
		$search_request_type = ! empty( $_GET['wsi_search_request_type'] ) ? sanitize_text_field( $_GET['wsi_search_request_type'] ) : '';
		$order_by            = ! empty( $_GET['wsi_order_by'] ) ? sanitize_text_field( $_GET['wsi_order_by'] ) : 'log_id';
		$order               = ! empty( $_GET['wsi_order'] ) ? sanitize_text_field( $_GET['wsi_order'] ) : 'DESC';

		// Setting a default argument for $wpdb->prepare();
		$where = ' WHERE 1=%s ';
		$args  = [1];

		if ( ! empty( $search_user_id ) ) {
			$where .= " AND log_user_id LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_user_id );
		}

		if ( ! empty( $search_username ) ) {
			$where .= " AND log_user_login LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_username );
		}

		if ( ! empty( $search_display_name ) ) {
			$where .= " AND log_display_name LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_display_name );
		}

		if ( ! empty( $search_ip_address ) ) {
			$where .= " AND log_user_ip LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_ip_address );
		}

		if ( ! empty( $search_email ) ) {
			$where .= " AND log_email LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_email );
		}

		if ( ! empty( $search_from_date ) ) {

			$where .= " AND DATE(log_date) >= %s";
			$args[] = sanitize_text_field( gmdate( 'Y-m-d', strtotime( $search_from_date ) ) );
		} else {
			// get first log date.
			$results = $wpdb->get_row( "SELECT DATE(log_date) AS log_date FROM {$wpdb->prefix}user_logs ORDER BY log_id ASC LIMIT 0,1" );
			$placeholder_from_date = ! empty( $results->log_date ) ? $results->log_date : '';
		}

		if ( ! empty( $search_to_date ) ) {
			$where .= " AND DATE(log_date) <= %s";
			$args[] = sanitize_text_field( gmdate( 'Y-m-d', strtotime( $search_to_date ) ) );
		} else {
			// get last log date.
			$results = $wpdb->get_row( "SELECT DATE(log_date) AS log_date FROM {$wpdb->prefix}user_logs ORDER BY log_id DESC LIMIT 0,1" );
			$placeholder_to_date = ! empty( $results->log_date ) ? $results->log_date : '';
		}

		if ( ! empty( $search_request_type ) ) {
			$where .= " AND log_request_type = %d";
			$args[] = sanitize_text_field( $search_request_type );
		}

		// Login Graph Data
		$graph = self::get_graph_data( $where, $args );

		// Get rows.
		$sql = "SELECT COUNT(*) AS total FROM {$wpdb->prefix}user_logs {$where}";

		$results = $wpdb->get_row( $wpdb->prepare( $sql, $args ) );

		$total_rows = ! empty( $results->total ) ? $results->total : 0;

		// Pagination Variables.
		$limit        = 50; // Number of rows to show in page.
		$offset       = ( $current_page - 1 ) * $limit;
		$num_of_pages = ceil( $total_rows / $limit );

		$args[] = $offset;
		$args[] = $limit;

		$sql = "SELECT * FROM {$wpdb->prefix}user_logs
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
	 * @return array
	 */
	public static function get_graph_data( $where, $args ) {
		global $wpdb;

		$graph_data = [];
		for( $request_type = 0; $request_type <= 4; $request_type ++ ) {

			$request_type_sql = '';
			if ( $request_type > 0 ) {
				$request_type_sql = 'AND log_request_type = ' . $request_type;
			}

			// Fetching max 365 data points for performance reasons.
			$sql = "SELECT COUNT(*) AS total_count, DATE(log_date) AS wsi_log_date 
					FROM {$wpdb->prefix}user_logs
					{$where}
					{$request_type_sql}
					GROUP BY wsi_log_date
					ORDER BY log_date ASC
					LIMIT 0, 365";

			$results = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
			if ( empty( $results ) ) {
				continue;
			}

			$graph_data[ $request_type ] = wp_list_pluck( (array) $results, 'total_count', 'wsi_log_date' );
		}

		if ( empty( $graph_data[0] ) ) {
			return [];
		}

		$data_points = array_keys( $graph_data[0] );
		$dataset = [];
		$ticks   = [];

		foreach ( (array) $data_points as $date ) {
			$timestamp  = strtotime( $date );
			$year  = date( 'Y', $timestamp );
			$month = date( 'm', $timestamp ) - 1;
			$day   = date( 'd', $timestamp );

			// First element of a dataset is date
			$ticks[]   = "new Date($year, $month, $day)";

			$login_count    = ! empty( $graph_data[1][ $date ] ) ? $graph_data[1][ $date ] : 0;
			$logout_count   = ! empty( $graph_data[2][ $date ] ) ? $graph_data[2][ $date ] : 0;
			$reg_count      = ! empty( $graph_data[3][ $date ] ) ? $graph_data[3][ $date ] : 0;
			$comments_count = ! empty( $graph_data[4][ $date ] ) ? $graph_data[4][ $date ] : 0;

			$dataset[] = [ "new Date($year, $month, $day)", $login_count, $logout_count, $reg_count, $comments_count ];
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

	/**
	 * User Login
	 * @param $user_login
	 * @param $user
	 */
	public function login_action( $user_login, $user ) {
		$args = [
			'user_id'      => $user->ID,
			'user_login'   => $user_login,
			'user_email'   => $user->user_email,
			'display_name' => $user->display_name,
			'request_type' => 1,
		];

		self::insert_log( $args );
	}

	/**
	 * User logout
	 * @param $user_id
	 */
	public function logout_action( $user_id ) {
		$user = get_userdata( $user_id );

		$args = [
			'user_id'      => $user->ID,
			'user_login'   => $user->user_login,
			'user_email'   => $user->user_email,
			'display_name' => $user->display_name,
			'request_type' => 2,
		];

		self::insert_log( $args );
	}

	/**
	 * User Registration
	 * @param $user_id
	 */
	public function user_registered_action( $user_id ) {
		$user = get_userdata( $user_id );

		$args = [
			'user_id'      => $user->ID,
			'user_login'   => $user->user_login,
			'user_email'   => $user->user_email,
			'display_name' => $user->display_name,
			'request_type' => 3,
		];

		self::insert_log( $args );
	}

	/**
	 * Comment posted
	 * @param $comment_id
	 */
	public function comment_posted_action( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( empty( $comment ) ) {
			return;
		}

		if ( ! empty( $comment->user_id ) ) {

			$user = get_userdata( $comment->user_id );

			$args = [
				'user_id'      => $user->ID,
				'user_login'   => $user->user_login,
				'user_email'   => $user->user_email,
				'display_name' => $user->display_name,
				'comment_id'   => $comment_id,
				'request_type' => 4,
			];
		} else {
			$args = [
				'user_id'      => 0,
				'user_login'   => '',
				'user_email'   => $comment->comment_author_email,
				'display_name' => $comment->comment_author,
				'comment_id'   => $comment_id,
				'request_type' => 4,
			];
		}

		self::insert_log( $args );
	}

	public static function insert_log( $args ) {
		global $wpdb;

		$user_id      = ! empty( $args['user_id'] ) ? $args['user_id'] : 0;
		$user_login   = ! empty( $args['user_login'] ) ? $args['user_login'] : '';
		$user_email   = ! empty( $args['user_email'] ) ? $args['user_email'] : '';
		$display_name = ! empty( $args['display_name'] ) ? $args['display_name'] : '';
		$comment_id   = ! empty( $args['comment_id'] ) ? $args['comment_id'] : 0;
		$request_type = ! empty( $args['request_type'] ) ? $args['request_type'] : 0;
		$ip           = self::get_user_ip();

		$sql = "INSERT INTO {$wpdb->prefix}user_logs 
				SET log_user_id  = %d,
				log_user_login   = %s,
				log_email        = %s,
				log_display_name = %s,
				log_comment_id   = %d,
				log_user_ip      = %s,
				log_request_type = %d,
				log_date         = %s";

		$args = [
			$user_id,
			$user_login,
			$user_email,
			$display_name,
			$comment_id,
			$ip,
			$request_type,
			gmdate( 'Y-m-d H:i:s' )
		];

		$wpdb->query( $wpdb->prepare( $sql, $args ) );
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

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}user_logs WHERE log_id = %d", $log_id ) );

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
				SET log_user_id  = %d,
				log_user_ip      = %s,
				log_request_type = %d,
				log_date         = %s";

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
