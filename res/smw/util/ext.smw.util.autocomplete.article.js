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
				'browse': 'article',
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

				context.addClass( 'is-disabled' );

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
			},
			transformResult: function( response ) {
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

	mw.hook( 'smw.article.autocomplete' ).add( function( context ) {
		context.find( '.smw-article-input' ).each( function() {
			autocomplete( $( this ) );
		} );
	} );

	$ ( document ).on( 'smw.article.autocomplete', function( event, opts ) {
		opts.context.find( '.smw-article-input' ).each( function() {
			autocomplete( $( this ) );
		} );
	} );

	$( document ).ready( function() {
		$( '#smw-article-input, .smw-article-input' ).each( function() {
			autocomplete( $( this ) );
		} );
	} );

} )( jQuery, mediaWiki );
