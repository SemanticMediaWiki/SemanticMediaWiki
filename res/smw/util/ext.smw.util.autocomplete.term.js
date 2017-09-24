/*!
 * This file is part of the Semantic MediaWiki
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
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Inheritance class for the smw.util constructor
	 *
	 * @since 3.0
	 * @class
	 */
	smw.util = smw.util || {};
	smw.util.autocomplete = smw.util.autocomplete || {};

	/**
	 * Class constructor
	 *
	 * @since 3.0
	 *
	 * @class
	 * @constructor
	 */
	smw.util.autocomplete.term = function ( context ) {

		this.category = mw.config.get( 'smw-config' ).namespaces.localizedName[mw.config.get( 'wgNamespaceIds' ).category];
		this.concept = mw.config.get( 'smw-config' ).namespaces.localizedName[mw.config.get( 'wgNamespaceIds' ).concept];
		this.context = context;

		this.currentRequest = null;
		this.tempCache = [];

		return this;
	};

	/**
	 * @since 3.0
	 * @method
	 *
	 * @param {Sting} key
	 * @param {Sting} cacheKey
	 * @param {Sting} term
	 * @param {Object} callback
	 */
	/* Public methods */

	var fn = {

		query: function( key, cacheKey, term, limit, callback ) {

			var that = this;

			var data = {
				'action': 'smwbrowse',
				'format': 'json',
				'browse': key,
				'params': JSON.stringify( {
					"search": term,
					"limit": limit
				} )
			};

			// Abort any active request handle
			if ( that.currentRequest !== null ) {
				that.currentRequest.abort();
			};

			that.currentRequest = $.ajax( {
				url: mw.util.wikiScript( 'api' ),
				dataType: 'json',
				data: data,
				'success': function( response ) {

					that.tempCache[cacheKey] = $.map( response.query, function( item, key ) {
						return { id: item.label, name: item.label };
					} )

					that.currentRequest = null;
					that.context.removeClass( 'is-disabled' );

					return callback( that.tempCache[cacheKey] );
				}
			} );

			return callback( [] );
		},

		/**
		 * @since 3.0
		 * @method
		 *
		 * @param {Array} keys
		 */
		register: function( keys ) {

			var that = this;

			keys.forEach( function( s ) {
				that.initialize( s );
			} );
		},

		/**
		 * @since 3.0
		 * @method
		 *
		 * @param {Sting} key
		 */
		initialize: function( key ) {

			var that = this,
				limit = 20,
				atToken = '';

			// Identify a token at which the search should be initiated
			if ( key === 'property' ) {
				atToken = 'p:';
			};

			if ( key === 'category' ) {
				atToken = 'c:';
			};

			if ( key === 'concept' ) {
				atToken = 'con:';
			};

			// Default atwho options
			var options = {
				at: atToken,
				spaceSelectsMatch: false,
				startWithSpace: false,
				lookUpOnClick: false,
				acceptSpaceBar: true,
				hideWithoutSuffix: false,
				displayTimeout: 300,
				suffix: '',
				limit: limit,
				callbacks: {
					remoteFilter: function ( term, callback ) {

						var cacheKey = atToken + term;

						if ( term == null || term.length < 2 ) {
							return callback( [] );
						}

						that.context.addClass( 'is-disabled' );

						if( typeof that.tempCache[cacheKey] == "object" ) {
							that.context.removeClass( 'is-disabled' );
							return callback( that.tempCache[cacheKey] );
						}

						return that.query( key, cacheKey, term, limit, callback );
					}
				}
			};

			// After a term is returned from the match process, clean-up the token
			// and add appropriated extensions
			if ( key === 'property' ) {
				options.callbacks.beforeInsert = function ( value, $li ) {
					return value.replace( atToken, '' ) + '::';
				};
			};

			if ( key === 'category' ) {
				options.callbacks.beforeInsert = function ( value, $li ) {
					return that.category + ':' + value.replace( atToken, '' );
				};
			};

			if ( key === 'concept' ) {
				options.callbacks.beforeInsert = function ( value, $li ) {
					return that.concept + ':' + value.replace( atToken, '' );
				};
			};

			// https://github.com/ichord/At.js
			this.context.atwho( options );
		}

	}

	/**
	 * Factory
	 * @since 3.0
	 */
	var Factory = {
		newTermAutocomplete: function( $context ) {
			return new smw.util.autocomplete.term( $context );
		}
	}

	smw.util.autocomplete.term.prototype = fn;

	smw.Factory = smw.Factory || {};
	smw.Factory = $.extend( smw.Factory, Factory );

} )( jQuery, mediaWiki, semanticMediaWiki );
