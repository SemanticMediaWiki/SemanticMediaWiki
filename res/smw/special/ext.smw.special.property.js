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

		context.autocomplete( {
			serviceUrl: mw.util.wikiScript( 'api' ),
			dataType: 'json',
			minChars: 3,
			maxHeight: 150,
			paramName: 'property',
			delimiter: "\n",
			noCache: false,
			triggerSelectOnValidInput: false,
			params: {
				'action': 'browsebyproperty',
				'format': 'json',
				'listonly': true,
				'limit': 100
			},
			onSelect: function( suggestion ) {
				// #611
				$(this).off("focus");
			},
			onSearchStart: function( query ) {

				// Avoid a search request on options or invalid characters
				if ( query.property.indexOf( '#' ) > 0 || query.property.indexOf( '|' ) > 0 ) {
					return false;
				};

				context.addClass( 'is-disabled' );
				query.property = query.property.replace( "?", '' );
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
