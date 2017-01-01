/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */

/*global jQuery, mediaWiki, mw */
( function ( $, mw ) {

	'use strict';

	/**
	 * @since 2.5
	 * @constructor
	 *
	 * @param {Object} mwApi
	 * @param {Object} util
	 *
	 * @return {this}
	 */
	var admin = function ( mwApi ) {

		this.VERSION = "2.5";
		this.api = mwApi;

		return this;
	};

	/**
	 * @since 2.5
	 * @method
	 *
	 * @param {Object} context
	 */
	admin.prototype.setContext = function( context ) {
		this.context = context;
		this.config = this.context.data( 'config' );

		return this;
	};

	/**
	 * @since 2.5
	 * @method
	 */
	admin.prototype.doApiRequest = function( parameters ) {

		var self = this,
			content = mw.msg( 'smw-no-data-available' );

		self.api.get( parameters ).done( function( data ) {

			if ( data.hasOwnProperty( 'info' ) ) {
				content = data.info.jobcount.length === 0 ? content : '<pre>' + JSON.stringify( data.info.jobcount, null, 2 ) + '</pre>';
			}

			self.appendContent( content );
		} ).fail ( function( xhr, status, error ) {

			var text = 'Unknown API error';

			if ( status.hasOwnProperty( 'error' ) ) {
				text = status.error.code + ': ' + status.error.info;
			}

			self.reportError( text );
		} );
	};

	/**
	 * @since 2.5
	 * @method
	 *
	 * @param {string} error
	 */
	admin.prototype.reportError = function( error ) {
		this.context.find( '.' + this.config.contentClass ).hide();
		this.context.find( '.' + this.config.errorClass ).append( error ).addClass( 'smw-callout smw-callout-error' );
	};

	/**
	 * @since 2.5
	 * @method
	 *
	 * @param {string} content
	 */
	admin.prototype.appendContent = function( content ) {
		this.context.find( '.' + this.config.contentClass ).replaceWith( '<div class="' + this.config.contentClass + '">' + content + '</div>' );
	};

	var instance = new admin(
		new mw.Api()
	);

	$( document ).ready( function() {

		$( '.smw-admin-statistics-job' ).each( function() {

			var parameters = {
				action: 'smwinfo',
				info: 'jobcount'
			};

			instance.setContext( $( this ) ).doApiRequest( parameters );
		} );

	} );

}( jQuery, mediaWiki ) );
