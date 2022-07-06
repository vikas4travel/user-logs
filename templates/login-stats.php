<?php

$search_logs = ! empty( $search_logs ) ? $search_logs : '';

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
				<span class="displaying-num"><?php echo intval( $total_logs ); ?> items</span>
				<?php
				$page_links = paginate_links(
					array(
						'base'               => add_query_arg( 'pagenum', '%#%' ),
						'format'             => '',
						'prev_text'          => '&laquo;',
						'next_text'          => '&raquo;',
						'total'              => $num_of_pages,
						'current'            => $page_num,
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
			<td class="manage-column column-title column-primary sortable desc">Quiz Title</td>
			<td class="manage-column column-title column-primary">Order</td>
			<td class="manage-column column-title column-primary">Status</td>
			<td class="manage-column column-title column-primary">Total Questions</td>
			<td class="manage-column column-title column-primary">Total Players</td>
			<td class="manage-column column-title column-primary">Updated On</td>
		</tr>
		</thead>
		<tbody>

		<?php
		if ( empty( $quizzes ) ) {
		?>
		<thead>
		<tr>
			<td colspan="5">No Records Found!</td>
		</tr>
		</thead>
		<?php
		}

		foreach ( $quizzes as $quiz ) {

			$edit_quiz_link = admin_url( 'admin.php?page=quizzes-v2-admin&current-page=edit-quiz&quiz_id=' . $quiz['quiz_id'] );
			?>
			<tr>
				<td class="title column-title has-row-actions column-primary page-title" style="width: 40%;">
					<a href="<?php echo esc_url( $edit_quiz_link ); ?>">
						<?php echo esc_html( $quiz['quiz_title'] ); ?>
					</a>
				</td>
				<td class="manage-column column-title column-primary"><?php echo intval( $quiz['order'] ); ?></td>
				<td class="manage-column column-title column-primary">
					<?php
					if ( 1 === intval( $quiz['quiz_status'] ) ) {
						echo 'Active';
					} else {
						echo 'Inactive';
					}
					?>
				</td>

				<td class="manage-column column-title column-primary"><?php echo esc_html( $quiz['questions'] ); ?></td>
				<td class="manage-column column-title column-primary"><?php echo esc_html( $quiz['players'] ); ?></td>
				<td class="manage-column column-title column-primary"><?php echo esc_html( gmdate( 'd M y, h:i a', strtotime( \GD\Helpers::convert_timezone( $quiz['quiz_updated'] ) ) ) ); ?></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
</div>





