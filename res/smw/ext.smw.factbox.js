/**
 * Initalize sortable table using jquery.tablesorter for factbox attachments
 * For some reason just adding the sortable class is not enough to trigger the script
 *
 * @returns void
 */
function init() {
	const attachments = document.getElementById( 'smw-factbox-attachments' );
	if ( !attachments ) {
		return;
	}
	mw.loader.using( 'jquery.tablesorter' ).then( () => {
		$( attachments ).tablesorter();
	} );
}

init();
