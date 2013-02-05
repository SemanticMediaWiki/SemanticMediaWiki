/**
 * SMW Api base class
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Constructor to create an object to interact with the API and SMW
	 *
	 * @since 1.9
	 *
	 * @class
	 * @constructor
	 */
	smw.Api = function() {};

	/**
	 * Public methods
	 *
	 * @since  1.9
	 *
	 * @type object
	 */
	smw.Api.prototype = {

		/**
		 * Convenience method to parse and map a JSON string
		 *
		 * Emulates partly $.parseJSON (jquery.js)
		 * @see http://www.json.org/js.html
		 *
		 * @since  1.9
		 *
		 * @param {string} data
		 *
		 * @return {object|null}
		 */
		parse: function( data ) {

			// Use smw.Api JSON custom parser to resolve raw data and add
			// type hinting
			var dataItem = new smw.dataItem();

			if ( !data || typeof data !== 'string' ) {
				return null;
			}

			// Remove leading/trailing whitespace
			data = $.trim(data);

			// Attempt to parse using the native JSON parser first
			if ( window.JSON && window.JSON.parse ) {
				return JSON.parse( data, function ( key, value ) { return dataItem.factory( key, value ); } );
			}

			// If the above fails, use jquery to do the rest
			return $.parseJSON( data );
		},

		/**
		 * Returns results from the SMWAPI
		 *
		 * On the topic of converters
		 * @see http://bugs.jquery.com/ticket/9095
		 *
		 * var smwApi = new smw.Api();
		 * smwApi.fetch( query )
		 *   .done( function ( data ) { } )
		 *   .fail( function ( error ) { } );
		 *
		 *
		 * @since 1.9
		 *
		 * @param {string} queryString
		 *
		 * @return {jQuery.Promise}
		 */
		fetch: function( queryString ){
			var self = this;

			if ( !queryString || typeof queryString !== 'string' ) {
				$.error( 'Invalid query string: ' + queryString );
			}

			return $.ajax( {
				url: mw.util.wikiScript( 'api' ),
				dataType: 'json',
				data: {
					'action': 'ask',
					'format': 'json',
					'query' : queryString
					},
				converters: { 'text json': function ( data ) {
					return self.parse( data );
				} }
			} );
		}
	};

	//Alias
	smw.api = smw.Api;

} )( jQuery, mediaWiki, semanticMediaWiki );