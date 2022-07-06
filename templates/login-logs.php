<?php
$logs         = ! empty( $logs ) ? $logs : [];
$total_rows   = ! empty( $total_rows ) ? $total_rows : [];
$num_of_pages = ! empty( $num_of_pages ) ? $num_of_pages : [];
$current_page = ! empty( $current_page ) ? $current_page : [];
$search_logs  = ! empty( $search_logs ) ? $search_logs : '';
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
						   name="search_logs"
						   value="<?php echo esc_attr( $search_logs ); ?>" />

					<input type="submit" value="Search" class="button button-primary">
				</p>
			</form>
		</div>
		<h2></h2>
		<div class="tablenav-pages" >
			<div class="pagination-links">
				<span class="displaying-num"><?php echo intval( $total_rows ); ?> items</span>
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
			<td class="manage-column column-title column-primary sortable desc">User ID</td>
			<td class="manage-column column-title column-primary">IP Address</td>
			<td class="manage-column column-title column-primary">Login Date</td>
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
			?>
			<tr>
				<td class="title column-title has-row-actions column-primary page-title">
					<?php echo esc_html( $log->login_user_id ); ?>
				</td>
				<td class="manage-column column-title column-primary"><?php echo esc_html( $log->login_user_ip ); ?></td>
				<td class="manage-column column-title column-primary"><?php echo esc_html( gmdate( 'd M y, h:i a', strtotime( $log->login_date ) ) ); ?></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
</div>





