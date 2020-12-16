( function() {
	var typesDeMembreNav = function() {
		document.querySelector( '#member-types-toggler' ).addEventListener( 'click', function( event ) {
			event.preventDefault();

			var item = event.target, nav;

			if ( 'SPAN' === item.nodeName ) {
				item = event.target.parentElement;
			}

			nav = item.closest( 'ul' );

			if ( '#open' === item.getAttribute( 'href' ) ) {
				nav.classList.add( 'opened' );
				item.setAttribute( 'href', '#close' );
			} else {
				nav.classList.remove( 'opened' );
				item.setAttribute( 'href', '#open' );
			}
		} );
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', typesDeMembreNav );
	} else {
		typesDeMembreNav;
	}
} )();
