/**
 * This file is part of the Semantic MediaWiki Extension
 * @see https://semantic-mediawiki.org/
 *
 * @section LICENSE
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @ignore
 *
 * @since 1.9
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/*global md5 */

	/**
	 * Constructor to create an object to interact with the Semantic
	 * MediaWiki Api
	 *
	 * @since 1.9
	 *
	 * @class
	 * @alias smw.api
	 * @constructor
	 */
	smw.Api = function() {};

	/* Public methods */

	smw.Api.prototype = {

		/**
		 * Convenience method to parse and map a JSON string
		 *
		 * Emulates partly $.parseJSON (jquery.js)
		 * (see https://web.archive.org/web/20170101125747/http://www.json.org/js.html)
		 * @since  1.9
		 *
		 * @param {string} data
		 *
		 * @return {object|null}
		 */
		parse: function( data ) {

			// Use smw.Api JSON custom parser to resolve raw data and add
			// type hinting
			var smwData = new smw.Data();

			if ( !data || typeof data !== 'string' ) {
				return null;
			}

			// Remove leading/trailing whitespace
			data = $.trim(data);

			// Attempt to parse using the native JSON parser first
			if ( window.JSON && window.JSON.parse ) {
				return JSON.parse( data, function ( key, value ) { return smwData.factory( key, value ); } );
			}

			// If the above fails, use jquery to do the rest
			return $.parseJSON( data );
		},

		/**
		 * Returns results from the SMWApi
		 *
		 * On the topic of converters (see http://bugs.jquery.com/ticket/9095)
		 *
		 * Example:
		 *         var smwApi = new smw.Api();
		 *         smwApi.fetch( query )
		 *           .done( function ( data ) { } )
		 *           .fail( function ( error ) { } );
		 *
		 * @since 1.9
		 *
		 * @param {string} queryString
		 * @param {boolean|number} useCache
		 *
		 * @return {jQuery.Promise}
		 */
		fetch: function( queryString, useCache ){
			var self = this,
				apiDeferred = $.Deferred();

			if ( !queryString || typeof queryString !== 'string' ) {
				throw new Error( 'Invalid query string: ' + queryString );
			}

			// Look for a cache object otherwise do an Ajax call
			if ( useCache ) {

				// Use a hash key to compare queries and use it as identifier for
				// stored resultObjects, each change in the queryString will result
				// in another hash key which will ensure only objects are stored
				// with this key can be reused
				var hash = md5( queryString );

				var resultObject = $.jStorage.get( hash );
				if ( resultObject !== null ) {
					var results = self.parse( resultObject );
					results.isCached = true;
					apiDeferred.resolve( results );
					return apiDeferred.promise();
				}
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
					// Store only the string as we want to return a typed object
					// If useCache is not a number use 15 min as default ttl
					if ( useCache ){
						$.jStorage.set( hash, data, {
							TTL: $.type( useCache ) === 'number' ? useCache : 900000
						} );
					}

					var results;

					try {
						results = self.parse( data );
					} catch ( e ) {
						console.log( e );
						throw e;
					}

					results.isCached = false;
					return results;
				} }
			} );
		}
	};

	//Alias
	smw.api = smw.Api;

} )( jQuery, mediaWiki, semanticMediaWiki );
