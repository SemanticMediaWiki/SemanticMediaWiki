/**
 * This file is part of the Semantic MediaWiki JavaScript Query module
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
	 * Private object and methods
	 * @ignore
	 */
	var html = mw.html;

	/**
	 * Query constructor
	 *
	 * @since  1.9
	 *
	 * @param {object} printouts
	 * @param {object} parameters
	 * @param {object|string} conditions
	 *
	 * @return {this}
	 */
	var query = function ( printouts, parameters, conditions ) {
		this.printouts  = printouts !== '' && printouts !== undefined ? printouts : [];
		this.parameters = parameters !== '' && parameters !== undefined ? parameters : {};
		this.conditions = conditions !== '' && conditions !== undefined ? conditions :[];
		return this;
	};

	/**
	 * Constructor to create an object to interact with the Query
	 *
	 * @since 1.9
	 *
	 * @class
	 * @alias smw.Query
	 * @constructor
	 */
	smw.Query = function( printouts, parameters, conditions ) {

		// You need to have some conditions otherwise jump the right light
		// because a query one can survive without printouts or parameters
		if ( conditions !== '' || $.type( this.conditions ) === 'array' ) {
			this.constructor( printouts, parameters, conditions  );
		} else {
			throw new Error( 'smw.dataItem.query: conditions are empty' );
		}
	};

	/* Public methods */

	var fn = {

		constructor: query,

		/**
		 * Returns query limit parameter (see SMW\Query::getLimit())
		 *
		 * @since  1.9
		 *
		 * @return {number}
		 */
		getLimit: function() {
			return this.parameters.limit;
		},

		/**
		 * Returns query link (see SMW\QueryResult::getLink())
		 *
		 * Caption text is set either by using parameters.searchlabel or by
		 * .getLink( 'myCaption' )
		 *
		 * @since  1.9
		 *
		 * @param {string}
		 * @return {string}
		 */
		getLink: function( caption ) {
			var c = caption ? caption : this.parameters.searchlabel !== undefined ? this.parameters.searchlabel : '...' ;
			return html.element( 'a', {
				'class': 'query-link',
				'href' : mw.config.get( 'wgScript' ) + '?' + $.param( {
						title: 'Special:Ask',
						'q':  $.type( this.conditions ) === 'string' ? this.conditions : this.conditions.join( '' ),
						'po': this.printouts.join( '\n' ),
						'p':  this.parameters
					} )
			}, c );
		},

		/**
		 * Returns query string (see SMW\Query::getQueryString())
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		toString: function() {

			var printouts = '';
			if ( $.type( this.printouts ) === 'array' ){
				$.each( this.printouts, function( key, value ) {
					printouts += '|' + value;
				} );
			} else {
				// @see ext.smw.query.test why we are failing here and not earlier
				throw new Error( 'smw.Api.query: printouts is not an array, it is a + ' + $.type( this.printouts ) );
			}

			var parameters = '';
			if ( $.type( this.parameters ) === 'object' ){
				$.each( this.parameters, function( key, value ) {
					parameters += '|' + key + '=' + value;
				} );
			} else {
				// @see ext.smw.query.test why we are failing here and not earlier
				throw new Error( 'smw.Api.query: parameters is not an object, it is a + ' + $.type( this.parameters ) );
			}

			var conditions = '';
			if ( $.type( this.conditions ) === 'array' || $.type( this.conditions ) === 'object' ){
				$.each( this.conditions, function( key, value ) {
					conditions += value;
				} );
			} else if ( $.type( this.conditions ) === 'string' ) {
				conditions += this.conditions;
			}

			return  conditions + printouts + parameters;
		}
	};

	// Alias
	fn.getQueryString = fn.toString;

	// Assign methods
	smw.Query.prototype = fn;
	smw.query = smw.Query;

} )( jQuery, mediaWiki, semanticMediaWiki );