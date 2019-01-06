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
				'browse': 'page',
				'params': {
					search: '',
					limit: limit,
					fullText: true
				}
			},
			onSearchStart: function( query ) {

				// Avoid a search request on options or invalid characters
				if ( query.search.indexOf( '#' ) > 0 || query.search.indexOf( '|' ) > 0 ) {
					return false;
				};

				context.removeClass( 'autocomplete-arrow' );
				context.addClass( 'is-disabled' );

				if ( indicator ) {
					context.addClass( 'autocomplete-loading' );
				};

				query.params = JSON.stringify( {
					search: query.search.replace( "?", '' ),
					limit: limit,
					fullText: true
				} );

				// Avoids {"warnings":{"main":{"*":"Unrecognized parameter: search."}
				delete query.search;
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
					suggestions: $.map( response.query, function( val, key ) {
						return { value: val.fullText, data: key };
					} )
				};
			}
		} );

		// https://github.com/devbridge/jQuery-Autocomplete/issues/498
		context.off( 'focus.autocomplete' );
	}

	mw.hook( 'smw.page.autocomplete' ).add( function( context ) {
		context.find( '.smw-page-input' ).each( function() {
			autocomplete( $( this ) );
		} );
	} );

	$ ( document ).on( 'smw.page.autocomplete', function( event, opts ) {
		opts.context.find( '.smw-page-input' ).each( function() {
			autocomplete( $( this ) );
		} );
	} );

	$( document ).ready( function() {
		$( '#smw-page-input, .smw-page-input' ).each( function() {
			autocomplete( $( this ) );
		} );
	} );

} )( jQuery, mediaWiki );
