document.addEventListener( 'DOMContentLoaded', function() {

	const dialog = document.getElementById( 'modal-details' );

	const shows = document.querySelectorAll( '.email-show' );
	shows.forEach( function( show ) {
		show.addEventListener( 'click', function( e ) {
			e.preventDefault();
			let emailTR = show.closest( 'tr' );

			document.getElementById( 'modal-1-title' ).innerHTML = emailTR.querySelector( '.subject' ).innerHTML;
			document.getElementById( 'modal-1-content' ).innerHTML = emailTR.querySelector( '.message .hidden' ).innerText;
			//document.getElementById('modal-1-headers').innerHTML = 'Additional Headers: <pre>' + emailTR.headers + '</pre>';

			dialog.showModal();
		} );
	} );

	const closeButtons = dialog.querySelectorAll( 'button' );
	closeButtons.forEach( function( button ) {
		button.addEventListener( 'click', function() {
			dialog.close();
			document.getElementById( 'modal-1-title' ).innerHTML = '';
			document.getElementById( 'modal-1-content' ).innerHTML = '';
			//document.getElementById( 'modal-1-headers' ).innerHTML = '';
		} );
	} );
	
	// Get first and last tabbable elements
	const firstTabbable = document.getElementById( 'modal-close' );
	const lastTabbable = document.getElementById( 'modal-btn' );

	// Constrain tabbing within the modal dialog
	dialog.addEventListener( 'keydown', function( event ) {
		if ( 'Tab' !== event.key ) {
			return;
		}

		if ( lastTabbable === event.target && ! event.shiftKey ) {
			event.preventDefault();
			firstTabbable.focus();
		} else if ( firstTabbable === event.target && event.shiftKey ) {
			event.preventDefault();
			lastTabbable.focus();
		}
	} );
} );
