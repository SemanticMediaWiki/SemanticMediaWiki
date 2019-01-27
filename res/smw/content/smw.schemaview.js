/*!
 * This file is part of the Semantic MediaWiki
 *
 * @since 3.1
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/*global jQuery, mediaWiki, smw */
/*jslint white: true */

( function( $, mw, smw ) {

	'use strict';

	// JS is loaded, now remove the "soft" disabled functionality
	$( "#smw-schema" ).removeClass( 'smw-schema-placeholder' );

	var container = $( "#smw-schema-container" ),
		json = container.find( '.smw-schema-data' ).text();

	if ( json !== '' ) {
		smw.jsonview.init( container, json );
	};

}( jQuery, mediaWiki, semanticMediaWiki ) );
