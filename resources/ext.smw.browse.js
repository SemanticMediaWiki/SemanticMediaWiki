/**
 * JavaScript for Special:Browse related functions
 *
 * @see https://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki
 * @licence: GNU GPL v2 or later
 *
 * @since: 1.7
 * @release: 0.2
 *
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 * @author Devayon Das
 * @author mwjames
 */
( function( $, mw ) {

	$( document ).ready( function() {

		// Used in Special:Browse
		// Function is specified in ext.smw.autocomplete
		$( '#page_input_box' ).smwAutocomplete( { search: 'page', namespace: 0 } );

	} );

} )( jQuery, mediaWiki );