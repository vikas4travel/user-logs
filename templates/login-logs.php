<?php
$logs         = ! empty( $logs ) ? $logs : [];
$total_rows   = ! empty( $total_rows ) ? $total_rows : [];
$num_of_pages = ! empty( $num_of_pages ) ? $num_of_pages : [];
$current_page = ! empty( $current_page ) ? $current_page : [];

$search_user_id  = ! empty( $search_user_id ) ? $search_user_id : '';
$search_username  = ! empty( $search_username ) ? $search_username : '';
$search_display_name  = ! empty( $search_user_id ) ? $search_display_name : '';
$search_ip_address  = ! empty( $search_user_id ) ? $search_ip_address : '';


?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo __( self::$plugin_name, 'wsi_user_logs' ); ?></h1>
	<hr class="wp-header-end">
</div>

<div class="wrap" id="poststuff">
	<div class="tablenav top">

		<div class="alignleft actions bulkactions">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="wsi_user_logs">
				<p>
					<label>Search Logs</label>
					<input type="text"
						   name="search_user_id"
						   placeholder="User ID"
						   value="<?php echo esc_attr( $search_user_id ); ?>" />
					<input type="text"
						   name="search_username"
						   placeholder="User Login Name"
						   value="<?php echo esc_attr( $search_username ); ?>" />
					<input type="text"
						   name="search_display_name"
						   placeholder="User Display Name"
						   value="<?php echo esc_attr( $search_display_name ); ?>" />
					<input type="text"
						   name="search_ip"
						   placeholder="IP Address"
						   value="<?php echo esc_attr( $search_ip_address ); ?>" />

					<input type="submit" value="Search" class="button button-primary">
				</p>
			</form>
		</div>
		<h2></h2>
		<div class="tablenav-pages" >
			<div class="pagination-links">
				<span class="displaying-num">Total <?php echo intval( $total_rows ); ?> items</span>
				<?php
				$page_links = paginate_links(
					array(
						'base'               => add_query_arg( 'current_page', '%#%' ),
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


	<table class="wp-list-table widefat striped table-view-list posts">
		<thead>
		<tr>
			<td class="manage-column column-title">User ID</td>
			<td class="manage-column column-title">Username</td>
			<td class="manage-column column-title">Display Name</td>
			<td class="manage-column column-title">IP Address</td>
			<td class="manage-column column-title">Request Type</td>
			<td class="manage-column column-title">Login Date</td>
			<td class="manage-column column-title">Action</td>
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
						<?php echo esc_html( $log->user_login ); ?>
					</a>
				</td>
				<td class="title column-title"><?php echo esc_html( $log->display_name ); ?></td>
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



