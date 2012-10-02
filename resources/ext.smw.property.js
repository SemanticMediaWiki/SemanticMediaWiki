/**
 * JavaScript for property related functions
 *
 * @see https://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki
 * @licence: GNU GPL v2 or later
 *
 * @since: 1.8
 * @release: 0.1
 *
 * @author:  mwjames
 */

( function( $, mw ) {

	$( document ).ready( function() {

		// Used in SMW_SpecialSearchByProperty.php
		// Function is specified in ext.smw.autocomplete
		$( '#property_box' ).smwAutocomplete();

	} );
} )( jQuery, mediaWiki );