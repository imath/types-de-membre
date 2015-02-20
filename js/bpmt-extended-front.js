(function($){

	var selected_type;
	var member_type_front = {
		start: function() {
			this.select( 'members-' + $.cookie( 'bp-members-scope' ) );
		},

		select: function( id ) {
			$( '.member-types li' ).each( function() {

				if ( id == $(this).prop( 'id' ) ) {
					selected_type = $(this);
					$(this).parent().parent().after( $(this) );
					$( '#members-types a' ).first().find('span').html( '&gt;' );
				}
			} );
		},

		list: function() {
			$( '.member-types' ).append( selected_type );
		}
	}

	$( '.item-list-tabs li a' ).on( 'click', function( event ) {
		event.preventDefault();

		if ( $( event.target ).parent().prop( 'id' ) == 'members-types' || $( event.target ).parent().parent().prop( 'id' ) == 'members-types' ) {
			if ( ! $( '.member-types' ).hasClass( 'children' ) ) {
				$( '.member-types' ).addClass( 'children' );
				$( event.target ).parent().find( 'span' ).first().html( '&or;' );

				member_type_front.list();
			} else {
				$( '.member-types' ).removeClass( 'children' );
				$( event.target ).parent().find( 'span' ).first().html( '&gt;' );

				if ( typeof selected_type != 'undefined' && $( selected_type ).hasClass( 'selected' ) ) {
					member_type_front.select( $( selected_type ).prop( 'id' ) );
				}
			}
		} else if ( $( event.target ).parent().parent().parent().prop( 'id' ) == 'members-types' || $( event.target ).parent().parent().parent().parent().prop( 'id' ) == 'members-types' ) {
			target = $( event.target ).parent().prop( 'id' );

			if ( ! target.length ) {
				target = $( event.target ).parent().parent().prop( 'id' );
			}

			$('.member-types').removeClass( 'children' );
			member_type_front.select( target );
		} else if ( typeof selected_type == 'undefined' || ( $( selected_type ).prop( 'id' ) != $( event.target ).parent().prop( 'id' ) && $( selected_type ).prop( 'id' ) != $( event.target ).parent().parent().prop( 'id' ) ) ) {
			member_type_front.list();
			$( '.member-types' ).removeClass( 'children' );
			$( '#members-types a' ).first().find('span').html( '&or;' );
		}
	} );


	member_type_front.start();

})(jQuery);
