/**
 * JavaScript for property value autocomplete function
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
( function( $, mw ) {
	'use strict';

	var autocomplete = function( context ) {

		// Keep the list small to minimize straining the DB
		var limit = 10;
		var currentValue = context.val();

		// There is no reason for the field to be enabled as long as their is no
		// property
		if ( context.data( 'property' ) === '' || context.data( 'property' ) === undefined ) {
			return context.addClass( 'is-disabled' );;
		};

		context.removeClass( 'is-disabled' );

		var params = {
			'action': 'smwbrowse',
			'format': 'json',
			'browse': 'pvalue',
			'params': {
				"search": '',
				'property': context.data( 'property' ),
				"limit": limit
			}
		};

		// https://github.com/devbridge/jQuery-Autocomplete
		context.autocomplete( {
			serviceUrl: mw.util.wikiScript( 'api' ),
			dataType: 'json',
			minChars: 0,
			maxHeight: 150,
			paramName: 'search',
			delimiter: "\n",
			noCache: false,
			triggerSelectOnValidInput: false,
			params: params,
			onSearchStart: function( query ) {

				// Avoid a search request on options or invalid characters
				if (
					query.search.indexOf( '#' ) > -1 ||
					query.search.indexOf( '|' ) > -1 ||
					query.search.indexOf( '*' ) > -1 ||
					query.search.indexOf( '+' ) > -1 ||
					query.search.indexOf( '!' ) > -1 ||
					query.search.indexOf( '>' ) > -1 ||
					query.search.indexOf( '<' ) > -1 ) {
					return false;
				};

				// Avoid a request for when the search term on the current
				// selected value are the same
				if ( currentValue !== '' && currentValue === query.search ) {
					return false;
				};

				context.removeClass( 'autocomplete-arrow' );
				context.addClass( 'is-disabled' );
				context.addClass( 'autocomplete-loading' );

				query.params = JSON.stringify( {
					'search': query.search.replace( "?", '' ),
					'property': context.data( 'property' ),
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

				context.trigger( 'smw.autocomplete.propertyvalue.select.complete', {
					suggestion: suggestion,
					context : context
				} );
			},
			onSearchComplete: function( query ) {
				context.removeClass( 'is-disabled' );
				context.removeClass( 'autocomplete-loading' );
				context.addClass( 'autocomplete-arrow' );
			},
			transformResult: function( response ) {

				if ( !response.hasOwnProperty( 'query' ) ) {
					return { suggestions: [] };
				};

				return {
					suggestions: $.map( response.query, function( key ) {
						
						if ( key === null ) {
							return [];
						};

						return { value: key, data: key };
					} )
				};
			}
		} );

		// https://github.com/devbridge/jQuery-Autocomplete/issues/498
		// context.off( 'focus.autocomplete' );
	}

	// Listen to any event that requires a value autocomplete
	// The trigger needs to set { context: ... } so we isolate the processing
	// to a specific instance
	$ ( document ).on( 'smw.autocomplete.propertyvalue', function( event, opts ) {
		autocomplete( opts.context.find( '.smw-propertyvalue-input' ) );
	} );

	$( document ).ready( function() {
		$( '.smw-propertyvalue-input' ).each( function() {
			autocomplete( $( this ) );
		} );
	} );

} )( jQuery, mediaWiki );
