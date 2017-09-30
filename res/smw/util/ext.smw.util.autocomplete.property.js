/**
 * JavaScript for property autocomplete function
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */

( function( $, mw ) {
	'use strict';

	var autocomplete = function( context ) {

		var limit = 20;

		// https://github.com/devbridge/jQuery-Autocomplete
		context.autocomplete( {
			serviceUrl: mw.util.wikiScript( 'api' ),
			dataType: 'json',
			minChars: 3,
			maxHeight: 150,
			paramName: 'search',
			delimiter: "\n",
			noCache: false,
			triggerSelectOnValidInput: false,
			params: {
				'action': 'smwbrowse',
				'format': 'json',
				'browse': 'property',
				'params': {
					"search": '',
					"limit": limit
				}
			},
			onSelect: function( suggestion ) {
				// #611
				$(this).off("focus");
			},
			onSearchStart: function( query ) {

				// Avoid a search request on options or invalid characters
				if ( query.search.indexOf( '#' ) > 0 || query.search.indexOf( '|' ) > 0 ) {
					return false;
				};

				context.addClass( 'is-disabled' );

				query.params = JSON.stringify( {
					'search': query.search.replace( "?", '' ),
					'limit': limit
				} );

				// Avoids {"warnings":{"main":{"*":"Unrecognized parameter: search."}
				delete query.search;
			},
			onSearchComplete: function( query ) {
				context.removeClass( 'is-disabled' );
			},
			transformResult: function( response ) {
				return {
					suggestions: $.map( response.query, function( dataItem, key ) {
						return { value: dataItem.label, data: key };
					} )
				};
			}
		} );
	}

	// Listen to an event (see Special:Ask)
	$ ( document ).on( 'SMW::Property::Autocomplete', function( event, opts ) {
		autocomplete( opts.context.find( '.smw-property-input' ) );
	} );

	$( document ).ready( function() {

		$( '#smw-property-input, .smw-property-input' ).each( function() {
			autocomplete( $( this ) );
		} );

	} );

} )( jQuery, mediaWiki );
