/**
 * JavasSript for Semantic MediaWiki.
 * @see http://semantic-mediawiki.org/
 * 
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 */

window.semanticMediaWiki = new( function() {
	
	this.log = function( message ) {
		if ( typeof mediaWiki === 'undefined' ) {
			if ( typeof console !== 'undefined' ) {
				console.log( 'SMW: ' + message );
			}
		}
		else {
			return mediaWiki.log.call( mediaWiki.log, 'SMW: ' + message );
		}
	}
	
	this.msg = function() {
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
	}
	
} )();

window.smw = window.semanticMediaWiki;

(function( $ ) { $( document ).ready( function() {

	
	
} ); })( jQuery );
