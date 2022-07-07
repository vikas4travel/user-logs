(function($) {

	$( '#wsi-clear-button' ).on('click', function (e) {
		$("#wsi_search_user_id").val('');
		$("#wsi_search_username").val('');
		$("#wsi_search_display_name").val('');
		$("#wsi_search_ip_address").val('');
		$("#wsi_search_email").val('');
		$("#wsi_search_from_date").val('');
		$("#wsi_search_to_date").val('');
	})

})( jQuery );
