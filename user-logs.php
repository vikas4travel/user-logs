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
	}

	/**
	 * Activate the plugin
	 */
	public function plugin_activation() {

		global $wpdb;

		set_transient( 'wsi_user_logs_activation_redirect_transient', true, 30 );
		update_option( 'wsi_user_logs_welcome', 0 );

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}user_login_logs` (
				`login_log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`login_user_id` bigint(20) UNSIGNED NOT NULL,
				`login_user_ip` varchar(255) DEFAULT NULL,
				`login_date` datetime NOT NULL,
				PRIMARY KEY (`login_log_id`)
				) ENGINE=InnoDB {$wpdb->get_charset_collate()};";

		$wpdb->query( $wpdb->prepare( $sql ) );

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}user_registration_logs` (
				`registration_log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`registration_user_id` bigint(20) UNSIGNED NOT NULL,
				`registration_user_ip` varchar(255) DEFAULT NULL,
				`registration_date` datetime NOT NULL,
				PRIMARY KEY (`registration_log_id`)
				) ENGINE=InnoDB {$wpdb->get_charset_collate()};";

		$wpdb->query( $wpdb->prepare( $sql ) );
	}

	/**
	 * Deactivate the plugin
	 */
	public function plugin_deactivation() {
		global $wpdb;

		delete_option( 'wsi_user_logs_welcome' );
		$wpdb->query( "DROP TABLE `{$wpdb->prefix}user_login_logs`" );
		$wpdb->query( "DROP TABLE `{$wpdb->prefix}user_registration_logs`" );
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
		add_submenu_page( 'wsi-user-logs', 'Registration Logs', 'Registration Logs', 'manage_options', self::$plugin_slug, [ $this, 'registration_logs' ] );
		add_submenu_page( 'wsi-user-logs', 'Activity Logs', 'Activity Logs', 'manage_options', self::$plugin_slug, [ $this, 'login_logs' ] );
		add_submenu_page( 'wsi-user-logs', 'Settings', 'Settings', 'manage_options', self::$plugin_slug, [ $this, 'registration_logs' ] );
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
			wp_enqueue_script( 'wsi_user_logs_admin_script', plugin_dir_url(__FILE__) . '/assets/js/admin-scripts.js', ['jquery'], '1.0.0', true );
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

		$current_page = ! empty( $_GET['current_page'] ) ? intval( $_GET['current_page'] ) : 1;
		$search_logs  = ! empty( $_GET['search_logs'] ) ? sanitize_text_field( $_GET['search_logs'] ) : '';

		$where = ' WHERE 1=1 ';
		$args  = [];
		if ( $search_logs ) {
			$where .= " AND login_user_id LIKE '%%%s%%'";
			$args[] = sanitize_text_field( $search_logs );
		}

		// Get rows.
		if ( empty( $args ) ) {
			$results = $wpdb->get_row( "SELECT COUNT(*) AS total FROM {$wpdb->prefix}user_login_logs" );
		} else {
			$results = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS total FROM {$wpdb->prefix}user_login_logs {$where}", $args ) );
		}

		$total_rows = ! empty( $results->total ) ? $results->total : 0;

		// Pagination Variables.
		$limit        = 50; // Number of rows to show in page.
		$offset       = ( $current_page - 1 ) * $limit;
		$num_of_pages = ceil( $total_rows / $limit );

		$args[] = $offset;
		$args[] = $limit;

		if ( empty( $args ) ) {
			$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}user_login_logs ORDER BY login_log_id DESC LIMIT %d, %d" );
		} else {
			$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}user_login_logs {$where} ORDER BY login_log_id DESC LIMIT %d, %d", $args ) );
		}

		$args = [
			'logs'         => $logs,
			'total_rows'   => $total_rows,
			'num_of_pages' => $num_of_pages,
			'current_page' => $current_page,
			'search_logs'  => $search_logs,
		];


		// Display the plugin page
		include_once( __DIR__ . '/templates/login-logs.php' );
	}

	/**
	 * Plugin page in the admin area.
	 */
	public function registration_logs(){

		$current_page = ! empty( $POST['current-page'] ) ? intval( $POST['current-page'] ) : 1;

		// Display the plugin page
		include_once( __DIR__ . '/templates/registration-logs.php' );
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

		$table_name = $wpdb->prefix . "user_login_logs";

		$ip = self::get_user_ip();

		$sql = "INSERT INTO $table_name 
				SET login_user_id = %d,
				login_user_ip     = %s,
				login_date        = %s";

		$wpdb->query( $wpdb->prepare( $sql, [ $user->ID , $ip, gmdate( 'Y-m-d H:i:s' ) ] ) );
	}

	public function logout_filter( $user_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . "user_login_logs";

		$ip = self::get_user_ip();

		$sql = "INSERT INTO $table_name 
				SET login_user_id = %d,
				login_user_ip     = %s,
				login_date        = %s";

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

}

new WSI_User_Logs();
