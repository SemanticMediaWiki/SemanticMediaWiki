/**
 * This file is part of the Semantic MediaWiki JavaScript DataItem module
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

	/**
	 * Constructor to create an object to interact with data objects and Api
	 *
	 * @since 1.9
	 *
	 * @class
	 * @alias smw.data
	 * @constructor
	 */
	smw.Data = function() {};

	/* Public methods */

	smw.Data.prototype = {

		/**
		 * List of properties used
		 *
		 * @property
		 * @static
		 */
		properties: null,

		/**
		 * Factory methods that maps an JSON.parse key/value to an dataItem object
		 * This function is normally only called during smw.Api.parse/fetch()
		 *
		 * Structure will be similar to
		 *
		 * Subject (if exists is of type smw.dataItem.wikiPage otherwise a simple object)
		 * |--> property -> smw.dataItem.property
		 *         |--> smw.dataItem.wikiPage
		 *         |--> ...
		 * |--> property -> smw.dataItem.property
		 *         |--> smw.dataItem.uri
		 *         |--> ...
		 * |--> property -> smw.dataItem.property
		 *         |--> smw.dataItem.time
		 *         |--> ...
		 *
		 * @since  1.9
		 *
		 * @param {string} key
		 * @param {mixed} value
		 *
		 * @return {object}
		 */
		factory: function( key, value ) {
			var self = this;

			// Map printrequests in order to be used as key accessible reference object
			// which enables type hinting for all items that exists within in this list
			if ( key === 'printrequests' && value !== undefined ){
				var list = {};
				$.map( value, function( key, index ) {
					list[key.label] = { typeid: key.typeid, position: index };
				} );
				self.properties = list;
			}

			// Map the entire result object, for objects that have a subject as
			// full fledged head item and rebuild the entire object to ensure
			// that wikiPage is invoked at the top as well
			if ( key === 'results' ){
				var nResults = {};

				$.each( value, function( subjectName, subject ) {
					if( subject.hasOwnProperty( 'fulltext' ) ){
						var nSubject = new smw.dataItem.wikiPage( subject.fulltext, subject.fullurl, subject.namespace, subject.exists );
						nSubject.printouts = subject.printouts;
						nResults[subjectName] = nSubject;
					} else {
						// Headless entry without a subject
						nResults = value;
					}
				} );

				return nResults;
			}

			// Map individual properties according to its type
			if ( typeof value === 'object' && self.properties !== null ){
				if ( key !== '' && value.length > 0 && self.properties.hasOwnProperty( key ) ){
					var property = new smw.dataItem.property( key ),
						typeid = self.properties[key].typeid,
						factoredValue = [];

					// Assignment of individual classes
					switch ( typeid ) {
						case '_wpg':
							$.map( value, function( w ) {
								factoredValue.push( new smw.dataItem.wikiPage( w.fulltext, w.fullurl, w.namespace, w.exists ) );
							} );
							break;
						case '_uri':
							$.map( value, function( u ) {
								factoredValue.push( new smw.dataItem.uri( u ) );
							} );
							break;
						case '_dat':
							$.map( value, function( t ) {
								factoredValue.push( new smw.dataItem.time( t ) );
							} );
							break;
						case '_num':
							$.map( value, function( n ) {
								factoredValue.push( new smw.dataItem.number( n ) );
							} );
							break;
						case '_qty':
							$.map( value, function( q ) {
								factoredValue.push( new smw.dataValue.quantity( q.value, q.unit ) );
							} );
							break;
						case '_str':
						case '_txt':
							$.map( value, function( s ) {
								factoredValue.push( new smw.dataItem.text( s, typeid ) );
							} );
							break;
						default:
							// Register all non identifiable types as unknown
							$.map( value, function( v ) {
								factoredValue.push( new smw.dataItem.unknown( v, typeid ) );
							} );
					}

					return $.extend( property, factoredValue );
				}
			}

			// Return all other values
			return value;
		}
	};

	// Alias
	smw.data = smw.Data;

} )( jQuery, mediaWiki, semanticMediaWiki );