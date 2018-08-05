/*!
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
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.8
 *
 * @file
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 * @author mwjames
 */
var smw = ( function ( $, undefined ) {
	'use strict';

	/*global console:true message:true */

	/**
	 *
	 * Declares methods and properties that are available through the smw namespace
	 *
	 * @since 1.8
	 * @class smw
	 * @alternateClassName semanticMediaWiki
	 * @singleton
	 */
	return {

		/* Public Members */

		/**
		 * Merges two objects
		 *
		 * @since 1.8
		 *
		 * @return {object}
		 */
		merge: function( obj1, obj2 ) {
			// ECMAScript 2015 (ES6) Standard Method
			// return Object.assign(obj1, obj2);

			// ES5 and Earlier
			return jQuery.extend( obj1, obj2 );
		},

		/**
		 * Outputs a debug log
		 *
		 * @since 1.8
		 *
		 * @return {string}
		 */
		log: function( message ) {
			if ( typeof mediaWiki === 'undefined' ) {
				if ( typeof console !== 'undefined' ) {
					console.log( 'SMW: ', message );
				}
			} else {
				return mediaWiki.log.call( mediaWiki.log, 'SMW: ', message );
			}
		},

		/**
		 * @since 3.0
		 */
		load: function load( callback ) {
			if ( document.readyState == 'complete' ) {
				callback();
			} else {
				window.addEventListener( 'load', callback );
			}
		},

		/**
		 * Outputs a message
		 *
		 * @since 1.8
		 *
		 * @return {string}
		 */
		msg: function() {
			if ( typeof mediaWiki === 'undefined' ) {
				message = window.wgSMWMessages[arguments[0]];

				for ( var i = arguments.length - 1; i > 0; i-- ) {
					message = message.replace( '$' + i, arguments[i] );
				}
				return message;
			} else {
				return mediaWiki.msg.apply( mediaWiki.msg, arguments );
			}
		},

		/**
		 * Returns current debug status
		 *
		 * @since 1.9
		 *
		 * @return {boolean}
		 */
		debug: function() {
			return mediaWiki.config.get( 'debug' );
		},

		/**
		 * Returns Semantic MediaWiki version
		 *
		 * @since 1.9
		 *
		 * @return {string}
		 */
		version: function() {
			return mediaWiki.config.get( 'smw-config' ).version;
		},

		/**
		 * Declares methods to access utility functions
		 *
		 * @since  1.9
		 *
		 * @static
		 * @class smw.util
		 * @alias smw.Util
		 */
		util: {

			/**
			 * Strip some illegal chars: control chars, colon, less than, greater than,
			 * brackets, braces, pipe, whitespace and normal spaces. This still leaves some insanity
			 * intact, like unicode bidi chars, but it's a good start..
			 *
			 * Borrowed from mw.Title
			 *
			 * @ignore
			 * @param {string} s
			 * @return {string}
			 */
			clean: function ( s ) {
				if ( s !== undefined ) {
					return s.trim().replace( /[\x00-\x1f\x23\x3c\x3e\x5b\x5d\x7b\x7c\x7d\x7f\s]+/g, '_' );
				}
			},

			/**
			 * Capitalizes the first letter of a string
			 *
			 * @ignore
			 * @param {string} s
			 * @return {string}
			 */
			ucFirst: function( s ) {
				return s.charAt(0).toUpperCase() + s.slice(1);
			},

			/**
			 * Declares methods to access information about namespace settings

			 * Example to find localised name:
			 *         smw.util.namespace.getName( 'property' );
			 *         smw.util.namespace.getName( 'file' );
			 *
			 * @since  1.9
			 *
			 * @static
			 * @class smw.util.namespace
			 */
			namespace: {

				/**
				 * Returns list of available namespaces
				 *
				 * @since 1.9
				 *
				 * @return {Object}
				 */
				getList: function() {
					return smw.settings.get( 'namespace' );
				},

				/**
				 * Returns namespace Id
				 *
				 * @since 1.9
				 *
				 * @param {string} key
				 *
				 * @return {number}
				 */
				getId: function( key ) {
					if( typeof key === 'string' ) {
						return this.getList()[ smw.util.ucFirst( smw.util.clean( key ) ) ];
					}
					return undefined;
				},

				/**
				 * Returns formatted localized name for a selected namespace
				 *
				 * @since 1.9
				 *
				 * @param {string} key
				 *
				 * @return {string}
				 */
				getName: function( key ) {
					if( typeof key === 'string' ) {
						var id = this.getId( key );
						return id && mediaWiki.config.get( 'wgFormattedNamespaces' )[id.toString()];
					}
					return undefined;
				}
			}
		},

		/**
		 * Declares methods to improve browser responsiveness by loading
		 * invoked methods asynchronously using the jQuery.eachAsync plug-in
		 *
		 * Example:
		 *         var fn = function( options ) {};
		 *         smw.async.load( $( this ), fn, {} );
		 *
		 * @since  1.9
		 *
		 * @singleton
		 * @class smw.async
		 */
		async: {

			/**
			 * Returns if eachAsync is available for asynchronous loading
			 *
			 * @return {boolean}
			 */
			isEnabled: function() {
				return $.isFunction( $.fn.eachAsync );
			},

			/**
			 * Negotiates and executes asynchronous loading
			 *
			 * @since  1.9
			 *
			 * @param {object} context
			 * @param {function} method
			 * @param {object|string} args
			 *
			 * @return {boolean}
			 * @throws {Error} Missing callback
			 */
			load: function( context, method ) {
				if ( typeof method !== 'function' ) {
					throw new Error( 'Invoked parameter was not a function' );
				}

				// Filter arguments that are attached to the caller
				var args = Array.prototype.slice.call( arguments, 2 );

				if ( this.isEnabled() ) {
					context.eachAsync( {
						delay: 100,
						bulk: 0,
						loop: function() {
							method.apply( $( this ), args );
						}
					} );
				} else {
					context.each( function() {
						method.apply( $( this ), args );
					} );
				}
			}
		},

		/**
		 * Declares methods to access information about available formats
		 *
		 * @since  1.9
		 *
		 * @class smw.formats
		 * @alias smw.Formats
		 */
		formats: {

			/**
			 * Returns list of available formats
			 *
			 * @since 1.9
			 * @extends smw.formats
			 *
			 * @return {Object}
			 */
			getList: function() {
				return mediaWiki.config.get( 'smw-config' ).formats;
			},

			/**
			 * Returns localized name for  a select format
			 *
			 * @since 1.9
			 *
			 * @param {string} format
			 *
			 * @return {string}
			 */
			getName: function( format ) {
				if( typeof format === 'string' ){
					return this.getList()[ smw.util.clean( format ).toLowerCase() ];
				}
				return undefined;
			}
		},

		/**
		 * Declares methods to access information about invoked settings (see also
		 * SMWHooks::onResourceLoaderGetConfigVars)
		 *
		 * @since 1.9
		 *
		 * @class smw.settings
		 * @singleton
		 */
		settings: {

			/**
			 * Returns list of available settings
			 *
			 * @since 1.9
			 *
			 * @return {Object}
			 */
			getList: function() {
				return mediaWiki.config.get( 'smw-config' ).settings;
			},

			/**
			 * Returns a specific settings value (see SMW\Settings::get)
			 *
			 * @since 1.9
			 *
			 * @param  {string} key to be selected
			 *
			 * @return {mixed}
			 */
			get: function( key ) {
				if( typeof key === 'string' ) {
					return this.getList()[key];
				}
				return undefined;
			}
		}
	};

} )( jQuery );

// Assign namespace
window.smw = window.semanticMediaWiki = smw;