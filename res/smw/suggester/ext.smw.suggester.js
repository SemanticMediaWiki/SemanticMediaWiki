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
/* global jQuery, mediaWiki, semanticMediaWiki */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * @since 3.0
	 * @class
	 */
	smw.suggester = smw.suggester || {};

	/**
	 * Class constructor
	 *
	 * @since 3.0
	 *
	 * @class
	 * @constructor
	 */
	smw.suggester = function ( context ) {

		var localizedName = mw.config.get( 'smw-config' ).namespaces.localizedName;
		this.context = context;

		this.category = localizedName[mw.config.get( 'wgNamespaceIds' ).category];
		this.concept = localizedName[mw.config.get( 'wgNamespaceIds' ).concept];

		this.currentRequest = null;
		this.tempCache = [];

		return this;
	};

	/* Public methods */

	var fn = {

		/**
		 * @since 3.0
		 * @method
		 *
		 * @return {Object}
		 */
		getDefaultTokenDefinitions: function() {

			var that = this;

			return {
				property: {
					token: '[[p:',
					beforeInsert: function( token, value ) {
						return value.replace( 'p:', '' ) + '::';
					}
				},
				category: {
					token: '[[c:',
					beforeInsert: function( token, value ) {
						return '[[' + that.category + ':' + value.replace( token, '' ) + ']]';
					}
				},
				concept: {
					token: '[[con:',
					beforeInsert: function( token, value ) {
						return '[[' + that.concept + ':' + value.replace( token, '' ) + ']]';
					}
				}
			};
		},

		/**
		 * @since 3.0
		 * @method
		 *
		 * @param {Sting} key
		 * @param {Sting} cacheKey
		 * @param {Sting} term
		 * @param {Object} callback
		 *
		 * @return {Object}
		 */
		search: function( key, cacheKey, term, limit, callback ) {

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
			}

			that.currentRequest = $.ajax( {
				url: mw.util.wikiScript( 'api' ),
				dataType: 'json',
				data: data,
				'success': function( response ) {

					that.tempCache[cacheKey] = $.map( response.query, function( item, key ) {
						return { id: item.label, name: item.label };
					} );

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
		 * @param {Array} list
		 */
		registerDefaultTokenList: function( list ) {

			var that = this,
				defaultTokenDefinitions = that.getDefaultTokenDefinitions();

			list.forEach( function( key ) {
				if ( defaultTokenDefinitions.hasOwnProperty( key ) ) {
					that.registerTokenDefinition( key, defaultTokenDefinitions[key] );
				}
			} );
		},

		/**
		 * @since 3.0
		 * @method
		 *
		 * @param {String} key
		 * @param {Object} definition
		 */
		registerTokenDefinition: function( key, definition ) {
			this.register( key, definition.token, definition.beforeInsert );
		},

		/**
		 * @since 3.0
		 * @method
		 *
		 * @param {Sting} key
		 * @param {Sting} token
		 * @param {Object} beforeInsert
		 */
		register: function( key, token, beforeInsert ) {

			var that = this,
				limit = 20;

			// Default atwho options
			var options = {
				at: token,
				spaceSelectsMatch: false,
				startWithSpace: false,
				lookUpOnClick: false,
				acceptSpaceBar: true,
				hideWithoutSuffix: false,
				displayTimeout: 300,
				suffix: '',
				limit: limit,
				callbacks: {}
			};

			// https://github.com/ichord/At.js/wiki/How-to-use-remoteFilter
			options.callbacks.remoteFilter = function ( term, callback ) {

				var cacheKey = token + term;

				if ( term == null || term.length < 2 ) {
					return callback( [] );
				}

				that.context.addClass( 'is-disabled' );

				if( typeof that.tempCache[cacheKey] == "object" ) {
					that.context.removeClass( 'is-disabled' );
					return callback( that.tempCache[cacheKey] );
				}

				return that.search( key, cacheKey, term, limit, callback );
			};

			/**
			 * Sort data
			 *
			 * @param query [String] matched string
			 * @param items [Array] data that was refactored
			 * @param searchKey [String] at char to search
			 *
			 * @return [Array] sorted data
			 */
			options.callbacks.sorter = function ( query, items, searchKey ) {
				// https://stackoverflow.com/questions/1129216/sort-array-of-objects-by-string-property-value-in-javascript
				return items.sort( function( a, b ) {
					return ( a.id > b.id ) ? 1 : ( ( b.id > a.id ) ? -1 : 0 );
				} );
			};

			// https://github.com/ichord/At.js/wiki/Callbacks
			options.callbacks.beforeInsert = function ( value, $li ) {
				return beforeInsert( token, value );
			};

			// https://github.com/ichord/At.js
			this.context.atwho( options );
		}
	};

	/**
	 * Factory
	 * @since 3.0
	 */
	var Factory = {
		newEntitySuggester: function( $context ) {
			return new smw.suggester( $context );
		}
	};

	smw.suggester.prototype = fn;

	smw.Factory = smw.Factory || {};
	smw.Factory = $.extend( smw.Factory, Factory );

} )( jQuery, mediaWiki, semanticMediaWiki );
