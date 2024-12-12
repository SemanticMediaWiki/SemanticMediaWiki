/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */

/*global jQuery, mediaWiki, mw, smw */
( function ( $, mw ) {

	'use strict';

	/**
	 * @since  2.5.0
	 * @constructor
	 *
	 * @param {Object} mwApi
	 * @param {Object} util
	 *
	 * @return {this}
	 */
	var browse = function ( mwApi ) {

		this.VERSION = "3.0.0";
		this.api = mwApi;

		return this;
	};

	/**
	 * @since 2.5
	 * @method
	 *
	 * @param {Object} context
	 */
	browse.prototype.setContext = function( context ) {
		this.context = context;
		this.options = context.data( 'mw-smw-browse-options' );

		return this;
	}

	/**
	 * @since 2.5
	 * @method
	 */
	browse.prototype.requestHTML = function() {

		var self = this,
			subject = self.context.data( 'mw-smw-browse-subject' );

		self.api.post( {
			action: "smwbrowse",
			browse: "subject",
			params: JSON.stringify( {
				subject: subject.dbkey,
				ns: subject.ns,
				iw: subject.iw,
				subobject: subject.subobject,
				options: self.options,
				type: 'html'
			} )
		} ).done( function( data ) {
			self.context.find( '.smw-browse-content' ).replaceWith( data.query );
			self.triggerEvents();
		} ).fail ( function( xhr, status, error ) {
			self.reportError( xhr, status, error );
		} );
	}

	/**
	 * @since 3.0
	 * @method
	 */
	browse.prototype.doUpdate = function( opts  ) {

		var self = this,
			subject = self.context.data( 'mw-smw-browse-subject' );

		subject = subject.split( "#" );

		self.api.post( {
			action: "smwbrowse",
			browse: "subject",
			params: JSON.stringify( {
				subject: subject[0],
				ns: subject[1],
				iw: subject[2],
				subobject: subject[3],
				options: $.extend( self.options, opts ),
				type: 'html'
			} )
		} ).done( function( data ) {
			self.context.find( '.smw-browse-search' ).remove();
			self.context.find( '.smw-browse-modules' ).remove();
			self.context.find( '.smw-factbox' ).replaceWith( data.query );

			self.triggerEvents();
		} ).fail ( function( xhr, status, error ) {
			self.reportError( xhr, status, error );
		} );
	}

	/**
	 * @since 2.5
	 * @method
	 *
	 * @param {object} xhr
	 * @param {object} status
	 * @param {object} error
	 */
	browse.prototype.reportError = function( xhr, status, error ) {

		var text = 'The API encountered an unknown error';

		if ( status.hasOwnProperty( 'xhr' ) ) {
			var xhr = status.xhr;

			if ( xhr.hasOwnProperty( 'statusText' ) ) {
				text = 'The API returned with: ' + xhr.statusText.replace(/\<br \/\>/g," " );
			};

			if ( xhr.hasOwnProperty( 'responseText' ) ) {
				text = xhr.responseText.replace(/\<br \/\>/g," " ).replace(/#/g, "<br\>#" );
			};
		}

		if ( status.hasOwnProperty( 'error' ) ) {
			text = '<b>' + status.error.code + '</b><br\>' + status.error.info.replace(/#/g, "<br\>#" );
		}

		this.context.find( '.smw-browse-content' ).prepend( text ).addClass( 'error' );
	}

	/**
	 * @since 2.5
	 * @method
	 */
	browse.prototype.triggerEvents = function() {

		var self = this;
		var form = self.context.find( '.smw-browse-search' );

		form.trigger( 'smw.page.autocomplete' , {
			'context': form
		} );

		mw.loader.load(
			self.context.find( '.smw-browse-modules' ).data( 'modules' )
		);

		// Re-apply JS-component instances on new content
		// Trigger an event
		mw.hook( 'smw.browse.apiparsecomplete' ).fire( self.context );

		$( document ).trigger( 'SMW::Browse::ApiParseComplete' , {
			'context': self.context
		} );
	}

	var instance = new browse(
		new mw.Api()
	);

	$( document ).ready( function() {

		/**
		 * Group related actions
		 */
		$( document ).on( 'click', '.smw-browse-hide-group', function( event ) {
			instance.doUpdate( { "group": "hide" } );
			event.preventDefault();
		} );

		$( document ).on( 'click', '.smw-browse-show-group', function( event ) {
			instance.doUpdate( { "group": "show" } );
			event.preventDefault();
		} );

		/**
		 * Incoming, outgoing related actions
		 */
		$( document ).on( 'click', '.smw_browse_hide_incoming', function( event ) {
			instance.doUpdate( { "dir": "out" } );
			event.preventDefault();
		} );

		$( document ).on( 'click', '.smw_browse_show_incoming', function( event ) {
			instance.doUpdate( { "dir": "both" } );
			event.preventDefault();
		} );

		$( '.smw-browse' ).each( function() {
			if ( !$( this )[0].dataset.mwSmwBrowseSubject ) {
				return;
			}
			instance.setContext( $( this ) ).requestHTML();
		} );

		var form = $( this ).find( '.smw-browse-search' );

		mw.loader.using( [ 'ext.smw.browse', 'ext.smw.browse.autocomplete' ] ).done( function () {
			form.trigger( 'smw.page.autocomplete' , {
				'context': form
			} );
		} );

	} );

}( jQuery, mediaWiki ) );
