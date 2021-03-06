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
$search_request_type   = ! empty( $search_request_type ) ? $search_request_type : '';
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo __( self::$plugin_name, 'wsi_user_logs' ); ?></h1>
	<hr class="wp-header-end">
</div>

<div class="wrap">
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
					   placeholder="Login Name"
					   class="wsi_input"
					   value="<?php echo esc_attr( $search_username ); ?>" />
				<input type="text"
					   name="wsi_search_display_name"
					   id="wsi_search_display_name"
					   placeholder="Display Name"
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

				<select	name="wsi_search_request_type"
						id="wsi_search_request_type"
						class="wsi_input">

					<option value="0" <?php selected( $search_request_type, '' ); ?>>Request Type</option>
					<option value="1" <?php selected( $search_request_type, '1' ); ?>>Login</option>
					<option value="2" <?php selected( $search_request_type, '2' ); ?>>Logout</option>
					<option value="3" <?php selected( $search_request_type, '3' ); ?>>Registration</option>
					<option value="4" <?php selected( $search_request_type, '4' ); ?>>Comment</option>
				</select>

				<input type="submit" value="Search" class="button button-primary" id="wsi-submit-button">
				<input type="button" value="Clear" class="button wsi-button" id="wsi-clear-button">

			</form>
		</div>

		<div class="tablenav-pages alignright" id="wsi_pagination">
			<div class="pagination-links">
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
				<span class="displaying-num">Total <?php echo intval( $total_rows ); ?> items</span>
			</div>
		</div>
	</div>
</div>

<div class="wrap">
	<?php
	include_once 'graph.php';
	?>
</div>

<div class="wrap" id="poststuff">
	<?php
	$sort_by    = 'display_name';
	$sort_order = 'desc';
	?>

	<table class="wp-list-table widefat striped table-view-list posts">
		<thead>
		<tr>
			<?php
			self::print_column('User ID', 'log_user_id');
			self::print_column('Username (Login Name)', 'user_login');
			self::print_column('Display Name', 'display_name');
			self::print_column('Email', 'user_email');
			self::print_column('IP Address', 'log_user_ip');
			self::print_column('Request Type', 'log_request_type');
			self::print_column('Login Date', 'log_date');
			?>
			<th class="manage-column column-title">
				Action
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

			$edit_link    = admin_url( 'user-edit.php?user_id=' . $log->log_user_id );
			$delete_link  = admin_url( 'admin.php?page=wsi_user_logs&delete_user_id=' . $log->log_id );

			switch ( $log->log_request_type ) {
				case 1:
					$request_type = 'Login';
					break;
				case 2:
					$request_type = 'Logout';
					break;
				case 3:
					$request_type = 'User Registered';
					break;
				case 4:
					$request_type = 'Comment Added';
					break;
				default :
					$request_type = '--NA--';
			}

			?>
			<tr>
				<td class="title column-title"><?php echo esc_html( $log->log_user_id ); ?></td>
				<td class="title column-title">
					<a href="<?php echo esc_url( $edit_link ); ?>">
						<?php
						if ( empty( $log->log_user_login ) ) {
							echo '<i title="User not found, may be deleted.">--NA--</i>';
						} else {
							echo esc_html( $log->log_user_login );
						}
						?>
					</a>
				</td>
				<td class="title column-title"><?php echo esc_html( $log->log_display_name ); ?></td>
				<td class="title column-title"><?php echo esc_html( $log->log_email ); ?></td>
				<td class="title column-title"><?php echo esc_html( $log->log_user_ip ); ?></td>
				<td class="title column-title"><?php echo esc_html( $request_type ); ?></td>
				<td class="title column-title"><?php echo esc_html( gmdate( 'd M y, h:i a', strtotime( $log->log_date ) ) ); ?></td>
				<td class="title column-title">
					<a href="<?php echo esc_url( $view_link ); ?>">View</a>
					|
					<a href="<?php echo esc_url( $delete_link ); ?>">Delete</a>
				</td>

			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
</div>
<div class="wrap">
	<div class="tablenav top">
		<div class="tablenav-pages alignright" id="wsi_pagination">
			<div class="pagination-links">
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
				<span class="displaying-num">Total <?php echo intval( $total_rows ); ?> items</span>
			</div>
		</div>
	</div>
</div>



