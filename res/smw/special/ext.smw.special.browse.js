/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */

/*global jQuery, mediaWiki, mw, smw */

// (ES6), see https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Classes
class Browse {

	/**
	 * @since 2.5
	 * @constructor
	 *
	 * @param {Object} api
	 */
	constructor ( api ) {
		this.VERSION = "2.5";
		this.api = api;
	}

	/**
	 * @since 2.5
	 * @method
	 *
	 * @param {Object} context
	 */
	setContext ( context ) {
		this.context = context;
	}

	/**
	 * @since 2.5
	 * @method
	 */
	doApiRequest () {

		var self = this,
			subject = self.context.data( 'subject' ),
			options = JSON.stringify( self.context.data( 'options' ) );

		// Expect format generated from DIWikiPage::getHash
		if ( subject.indexOf( "#" ) == -1 ) {
			return self.reportError( mw.msg( 'smw-browse-api-subject-serialization-invalid' ) );
		}

		subject = subject.split( "#" );

		self.api.get( {
			action: "browsebysubject",
			subject: subject[0],
			ns: subject[1],
			iw: subject[2],
			subobject: subject[3],
			options: options,
			type: 'html'
		} ).done( function( data ) {
			self.appendContent( data.query );
		} ).fail ( function( xhr, status, error ) {

			var text = 'Unknown API error';

			if ( status.hasOwnProperty( 'error' ) ) {
				text = status.error.code + ': ' + status.error.info;
			}

			self.reportError( text );
		} );
	}

	/**
	 * @since 2.5
	 * @method
	 *
	 * @param {string} error
	 */
	reportError ( error ) {
		this.context.find( '.smwb-status' ).append( error ).addClass( 'smw-callout smw-callout-error' );
	}

	/**
	 * @since 2.5
	 * @method
	 *
	 * @param {string} content
	 */
	appendContent ( content ) {

		var self = this;

		self.context.find( '.smwb-content' ).replaceWith( content );

		// Re-apply JS-component instances on new content
		mw.loader.using( 'ext.smw.tooltips' ).done( function () {
			smw.Factory.newTooltip().initFromContext( self.context );
		} );

		mw.loader.using( 'ext.smw.browse' ).done( function () {
			self.context.find( '#smwb-page-search' ).smwAutocomplete( { search: 'page', namespace: 0 } );
		} );

		mw.loader.load(
			self.context.find( '.smwb-modules' ).data( 'modules' )
		);

		// Trigger an event
		$( document ).trigger( 'SMW::Browse::ApiParseComplete' , {
			'context': self.context
		} );
	}
}

( function ( $, mw ) {

	'use strict';

	var browse = new Browse(
		new mw.Api()
	);

	$( document ).ready( function() {

		$( '.smwb-container' ).each( function() {
			browse.setContext( $( this ) );
			browse.doApiRequest();
		} );

		$( '#smwb-page-search' ).smwAutocomplete( { search: 'page', namespace: 0 } );
	} );

}( jQuery, mediaWiki ) );
