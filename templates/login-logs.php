<?php
$logs                  = ! empty( $logs ) ? $logs : [];
$total_rows            = ! empty( $total_rows ) ? $total_rows : [];
$num_of_pages          = ! empty( $num_of_pages ) ? $num_of_pages : [];
$current_page          = ! empty( $current_page ) ? $current_page : [];
$search_user_id        = ! empty( $search_user_id ) ? $search_user_id : '';
$search_username       = ! empty( $search_username ) ? $search_username : '';
$search_display_name   = ! empty( $search_display_name ) ? $search_display_name : '';
$search_ip_address     = ! empty( $search_ip_address ) ? $search_ip_address : '';
$search_email          = ! empty( $search_email ) ? $search_email : '';
$search_from_date      = ! empty( $search_from_date ) ? $search_from_date : '';
$search_to_date        = ! empty( $search_to_date ) ? $search_to_date : '';
$placeholder_from_date = ! empty( $placeholder_from_date ) ? $placeholder_from_date : '';
$placeholder_to_date   = ! empty( $placeholder_to_date ) ? $placeholder_to_date : '';

?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo __( self::$plugin_name, 'wsi_user_logs' ); ?></h1>
	<hr class="wp-header-end">
</div>

<div class="wrap" id="poststuff">
	<div class="tablenav top">

		<div class="alignleft bulkactions" id="wsi_search_form">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="wsi_user_logs">
				<input type="hidden" name="wsi_order_by" value="" id="wsi_order_by">
				<input type="hidden" name="wsi_order" value="" id="wsi_order">

				<input type="text"
					   name="wsi_search_user_id"
					   id="wsi_search_user_id"
					   placeholder="Search User ID"
					   class="wsi_input"
					   value="<?php echo esc_attr( $search_user_id ); ?>" />
				<input type="text"
					   name="wsi_search_username"
					   id="wsi_search_username"
					   placeholder="User Login Name"
					   class="wsi_input"
					   value="<?php echo esc_attr( $search_username ); ?>" />
				<input type="text"
					   name="wsi_search_display_name"
					   id="wsi_search_display_name"
					   placeholder="User Display Name"
					   class="wsi_input"
					   value="<?php echo esc_attr( $search_display_name ); ?>" />
				<input type="text"
					   name="wsi_search_ip_address"
					   id="wsi_search_ip_address"
					   placeholder="IP Address"
					   class="wsi_input"
					   value="<?php echo esc_attr( $search_ip_address ); ?>" />
				<input type="text"
					   name="wsi_search_email"
					   id="wsi_search_email"
					   placeholder="Email Address"
					   class="wsi_input"
					   value="<?php echo esc_attr( $search_email ); ?>" />

				<input type="text"
					   name="wsi_search_from_date"
					   id="wsi_search_from_date"
					   placeholder="From <?php echo esc_attr( $placeholder_from_date ); ?>"
					   class="wsi_input"
					   value="<?php echo esc_attr( $search_from_date ); ?>" />
				<input type="text"
					   name="wsi_search_to_date"
					   id="wsi_search_to_date"
					   placeholder="To <?php echo esc_attr( $placeholder_to_date ); ?>"
					   class="wsi_input"
					   value="<?php echo esc_attr( $search_to_date ); ?>" />
				<input type="submit" value="Search" class="button button-primary" id="wsi-submit-button">
				<input type="button" value="Clear" class="button wsi-button" id="wsi-clear-button">

			</form>
		</div>
	</div>

	<?php
	include_once 'login-graph.php';
	?>

	<div class="tablenav top">
		<div class="tablenav-pages" id="wsi_pagination">
			<div class="pagination-links">
				<span class="displaying-num">Total <?php echo intval( $total_rows ); ?> items</span>
				<?php
				$page_links = paginate_links(
					array(
						'base'               => add_query_arg( 'wsi_current_page', '%#%' ),
						'format'             => '',
						'prev_text'          => '&laquo;',
						'next_text'          => '&raquo;',
						'total'              => $num_of_pages,
						'current'            => $current_page,
						'before_page_number' => '<span class="tablenav-pages-navspan button" aria-hidden="true">',
						'after_page_number'  => '</span>',
					)
				);
				echo wp_kses_post( $page_links );
				?>
			</div>
		</div>
	</div>

	<?php
	$sort_by    = 'display_name';
	$sort_order = 'desc';
	?>

	<table class="wp-list-table widefat striped table-view-list posts">
		<thead>
		<tr>
			<?php
			self::print_column('User ID', 'login_user_id');
			self::print_column('Username (Login Name)', 'user_login');
			self::print_column('Display Name', 'display_name');
			self::print_column('Email', 'user_email');
			self::print_column('IP Address', 'login_user_ip');
			self::print_column('Request Type', 'login_request_type');
			self::print_column('Login Date', 'login_date');
			?>
			<th class="manage-column column-title">
				Delete Logs
			</th>
		</tr>
		</thead>
		<tbody>

		<?php
		if ( empty( $logs ) ) {
		?>
		<thead>
		<tr>
			<td colspan="5">No Records Found!</td>
		</tr>
		</thead>
		<?php
		}

		foreach ( $logs as $log ) {

			$request_type = 1 === intval( $log->login_request_type ) ? 'Login' : 'Logout';
			$edit_link    = admin_url( 'user-edit.php?user_id=' . $log->login_user_id );
			$delete_link  = admin_url( 'admin.php?page=wsi_user_logs&delete_user_id=' . $log->login_log_id );

			?>
			<tr>
				<td class="title column-title"><?php echo esc_html( $log->login_user_id ); ?></td>
				<td class="title column-title">
					<a href="<?php echo esc_url( $edit_link ); ?>">
						<?php
						if ( empty( $log->user_login ) ) {
							echo '<i title="User not found, may be deleted.">--NA--</i>';
						} else {
							echo esc_html( $log->user_login );
						}
						?>
					</a>
				</td>
				<td class="title column-title"><?php echo esc_html( $log->display_name ); ?></td>
				<td class="title column-title"><?php echo esc_html( $log->user_email ); ?></td>
				<td class="title column-title"><?php echo esc_html( $log->login_user_ip ); ?></td>
				<td class="title column-title"><?php echo esc_html( $request_type ); ?></td>
				<td class="title column-title"><?php echo esc_html( gmdate( 'd M y, h:i a', strtotime( $log->login_date ) ) ); ?></td>
				<td class="title column-title">
					<a href="<?php echo esc_url( $delete_link ); ?>">
						Delete
					</a>
				</td>

			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
</div>



