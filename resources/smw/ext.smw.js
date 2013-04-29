/**
 * JavaScript for Semantic MediaWiki.
 * @see http://semantic-mediawiki.org/
 *
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 */
/*global console:true message:true */

// Declare instance
var instance = ( function () {
	'use strict';

	var instance = {};

	instance.log = function( message ) {
		if ( typeof mediaWiki === 'undefined' ) {
			if ( typeof console !== 'undefined' ) {
				console.log( 'SMW: ' + message );
			}
		}
		else {
			return mediaWiki.log.call( mediaWiki.log, 'SMW: ' + message );
		}
	};

	instance.msg = function() {
		if ( typeof mediaWiki === 'undefined' ) {
			message = window.wgSMWMessages[arguments[0]];

			for ( var i = arguments.length - 1; i > 0; i-- ) {
				message = message.replace( '$' + i, arguments[i] );
			}

			return message;
		}
		else {
			return mediaWiki.msg.apply( mediaWiki.msg, arguments );
		}

	};

	/**
	 * Declares methods to access information about available formats
	 */
	instance.formats = {

		/**
		 * Returns "real" name in the current page content language of a
		 * select format
		 *
		 * @since 1.9
		 *
		 * @param {String} format
		 *
		 * @return {String}
		 */
		getName: function( format ) {
			if( typeof format === 'string' ){
				return mediaWiki.config.get( 'smw-config' ).formats[format];
			}
			return undefined;
		},

		/**
		 * Returns list of available formats
		 *
		 * @since 1.9
		 *
		 * @return {Object}
		 */
		getList: function() {
			return mediaWiki.config.get( 'smw-config' ).formats;
		}
	};

	/**
	 * Returns SMW settings array
	 *
	 * @see SMW\Settings::newFromGlobals
	 *
	 * @since 1.9
	 *
	 * @return {Mixed}
	 */
	instance.settings = function() {
		return mediaWiki.config.get( 'smw-config' ).settings;
	};

	/**
	 * Returns a specific settings value
	 *
	 * @see SMW\Settings::get
	 *
	 * @since 1.9
	 *
	 * @param  {String} key options to be selected
	 *
	 * @return {Mixed}
	 */
	instance.settings.get = function( key ) {
		if( typeof key === 'string' ){
			return mediaWiki.config.get( 'smw-config' ).settings[key];
		}
		return undefined;
	};

	/**
	 * Returns SMW version
	 *
	 * @since 1.9
	 *
	 * @return {String}
	 */
	instance.version = function() {
		return mediaWiki.config.get( 'smw-config' ).version;
	};

	return instance;
} )();

// Assign namespace
window.smw = window.semanticMediaWiki = instance;

( function( $ ) { 'use strict'; $( document ).ready( function() {
} ); } )( jQuery );