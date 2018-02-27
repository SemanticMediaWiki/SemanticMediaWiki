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

		self.api.postWithToken( 'csrf', parameters ).done( function( data ) {

			if ( data.hasOwnProperty( 'info' ) ) {
				content = data.info.jobcount.length === 0 ? content : '<pre>' + JSON.stringify( data.info.jobcount, null, 2 ) + '</pre>';
			} else if ( data.hasOwnProperty( 'task' ) ) {

				if ( data.task.hasOwnProperty( 'isFromCache' ) ) {
					var time = new Date( data.task.time * 1000 );
					content = '<p>' + mw.msg( 'smw-list-count-from-cache', data.task.count, time.toUTCString() ) + '</p>';;
				} else {
					content = '<p>' + mw.msg( 'smw-list-count', data.task.count ) + '</p>';
				}

				if ( data.task.list.length === 0 ) {
					content = mw.msg( 'smw-no-data-available' );
				} else {
					content = content + '<pre>' + JSON.stringify( data.task.list, null, 2 ) + '</pre>';
				}
			}

			self.replace( content );
		} ).fail ( function( xhr, status, error ) {

			var text = 'The API encountered an unknown error';

			if ( status.hasOwnProperty( 'xhr' ) ) {
				var xhr = status.xhr;

				if ( xhr.hasOwnProperty( 'responseText' ) ) {
					text = xhr.responseText.replace(/\<br \/\>/g," " );
				} else if ( xhr.hasOwnProperty( 'statusText' ) ) {
					text = 'The API returned with: ' + xhr.statusText.replace(/\<br \/\>/g," " );
				};
			}

			if ( status.hasOwnProperty( 'error' ) ) {
				text = status.error.code + ': ' + status.error.info + status.error['*'];
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
	admin.prototype.replace = function( content ) {
		this.context
			.css( 'opacity', 1 )
			.find( '.' + this.config.contentClass )
			.replaceWith( '<div class="' + this.config.contentClass + '">' + content + '</div>'	);
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

		$( '.smw-admin-db-preparation' ).each( function() {
			// https://stackoverflow.com/questions/5997450/append-to-url-and-refresh-page
			window.location.search += '&prep=done';
		} );

		$( '.smw-admin-supplementary-duplookup' ).each( function() {

			var parameters = {
				action: 'smwtask',
				task: 'duplookup',
				params: []
			};

			instance.setContext( $( this ) ).doApiRequest( parameters );
		} );

	} );

}( jQuery, mediaWiki ) );
