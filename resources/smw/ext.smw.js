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
	return instance;
} )();

// Assign namespace
window.smw = window.semanticMediaWiki = instance;

( function( $ ) { 'use strict'; $( document ).ready( function() {
} ); } )( jQuery );