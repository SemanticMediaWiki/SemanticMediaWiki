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

	$( document ).ready( function() {

		$( '#smw-property-input, .smw-property-input' ).autocomplete( {
			serviceUrl: mw.util.wikiScript( 'api' ),
			dataType: 'json',
			minChars: 3,
			maxHeight: 150,
			paramName: 'property',
			delimiter: "\n",
			params: {
				'action': 'browsebyproperty',
				'format': 'json',
				'listonly': true,
				'limit': 100
			},
			onSearchStart: function( query ) {
				query.property = query.property.replace( "?", '' );
			},
			transformResult: function( response ) {
				return {
					suggestions: $.map( response.query, function( dataItem, key ) {
						return { value: dataItem.label, data: key };
					} )
				};
			}
		} );

	} );
} )( jQuery, mediaWiki );