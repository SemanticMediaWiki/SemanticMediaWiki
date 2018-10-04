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
		var currentValue = context.val();

		var indicator = context.hasClass( 'autocomplete-arrow' );
		context.removeClass( 'is-disabled' );

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
			onSearchStart: function( query ) {

				// Avoid a search request on options or invalid characters
				if (
					query.search.indexOf( '#' ) > -1 ||
					query.search.indexOf( '|' ) > -1 ) {
					return false;
				};

				// Avoid a request for when the search term on the current
				// selected value are the same
				if ( currentValue !== '' && currentValue === query.search ) {
					return false;
				};

				context.removeClass( 'autocomplete-arrow' );
				context.addClass( 'is-disabled' );

				if ( indicator ) {
					context.addClass( 'autocomplete-loading' );
				};

				query.params = JSON.stringify( {
					'search': query.search.replace( "?", '' ),
					'limit': limit
				} );

				// Avoids {"warnings":{"main":{"*":"Unrecognized parameter: search."}
				// from the API request
				delete query.search;
			},
			onSelect: function( suggestion ) {

				if ( suggestion ) {
					currentValue = suggestion.value;
				};

				context.trigger( 'smw.autocomplete.property.select.complete', {
					suggestion: suggestion,
					context: context
				} );
			},
			onSearchComplete: function( query ) {
				context.removeClass( 'is-disabled' );

				if ( indicator ) {
					context.removeClass( 'autocomplete-loading' );
					context.addClass( 'autocomplete-arrow' );
				};
			},
			transformResult: function( response ) {

				if ( !response.hasOwnProperty( 'query' ) ) {
					return { suggestions: [] };
				};

				return {
					suggestions: $.map( response.query, function( dataItem, key ) {
						return { value: dataItem.label, data: key };
					} )
				};
			}
		} );

		// https://github.com/devbridge/jQuery-Autocomplete/issues/498
		context.off( 'focus.autocomplete' );
	}

	// Listen to an event (see Special:Ask)
	$ ( document ).on( 'SMW::Property::Autocomplete', function( event, opts ) {
		autocomplete( opts.context.find( '.smw-property-input' ) );
	} );

	// Listen to any event that requires a value autocomplete
	// The trigger needs to set { context: ... } so we isolate the processing
	// to a specific instance
	$ ( document ).on( 'smw.autocomplete.property', function( event, opts ) {
		autocomplete( opts.context.find( '.smw-property-input' ) );
	} );

	$( document ).ready( function() {
		$( '#smw-property-input, .smw-property-input' ).each( function() {
			autocomplete( $( this ) );
		} );

	} );

} )( jQuery, mediaWiki );
