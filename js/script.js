( function() {
	var typesDeMembreNav = function() {
		document.querySelector( '#member-types-toggler' ).addEventListener( 'click', function( event ) {
			event.preventDefault();

			var nav = event.target.closest( 'ul' );

			if ( '#open' === event.target.getAttribute( 'href' ) ) {
				nav.classList.add( 'opened' );
				event.target.setAttribute( 'href', '#close' );
			} else {
				nav.classList.remove( 'opened' );
				event.target.setAttribute( 'href', '#open' );
			}
		} );
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', typesDeMembreNav );
	} else {
		typesDeMembreNav;
	}
} )();
