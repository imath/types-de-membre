(function($){

	var member_type_users_admin = {
		feedback: function( n ) {
			var message = member_types_users_admin_vars.message_plural,
				feedback = '<div id="message" class="updated"><p>%s</p></div>';

			if ( n <= 1 ) {
				message = member_types_users_admin_vars.message_singular;
			}

			message = message.replace( '%s', n );
			feedback = feedback.replace( '%s', message );

			$( '#wpbody-content .wrap' ).prepend( feedback );
		},
	}

	updated = bp_get_querystring( 'updated' );
	if ( updated && ! isNaN( parseInt( updated ) ) ) {
		member_type_users_admin.feedback( Number( updated ) );
	}

	if ( ! $('#bpmt-set-type').length && $( '.tablenav.top' ).length && 'undefined' != typeof member_types_users_admin_vars.bulk_output ) {
		$( '.tablenav.top .bulkactions' ).after( '<div class="alignleft actions">' + member_types_users_admin_vars.bulk_output + '</div>' );
	}

})(jQuery);
