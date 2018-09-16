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
		this.options = context.data( 'options' );

		return this;
	}

	/**
	 * @since 2.5
	 * @method
	 */
	browse.prototype.requestHTML = function() {

		var self = this,
			subject = self.context.data( 'subject' );

		// Expect a serialization format (see DIWikiPage::getHash)
		if ( subject.indexOf( "#" ) == -1 ) {
			return this.context.find( '.smwb-status' )
				.append(
					mw.msg( 'smw-browse-api-subject-serialization-invalid' )
				)
				.addClass( 'smw-callout smw-callout-error' );
		}

		subject = subject.split( "#" );

		self.api.post( {
			action: "smwbrowse",
			browse: "subject",
			params: JSON.stringify( {
				subject: subject[0],
				ns: subject[1],
				iw: subject[2],
				subobject: subject[3],
				options: self.options,
				type: 'html'
			} )
		} ).done( function( data ) {
			self.context.find( '.smwb-emptysheet' ).replaceWith( data.query );
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
			subject = self.context.data( 'subject' );

		subject = subject.split( "#" );

		self.context.addClass( 'is-disabled' );
		self.context.append( '<span id="smw-wait" class="smw-overlay-spinner large inline"></span>' );

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
			self.context.removeClass( 'is-disabled' );
			self.context.find( '#smw-wait' ).remove();

			self.context.find( '.smwb-form' ).remove();
			self.context.find( '.smwb-modules' ).remove();
			self.context.find( '.smwb-datasheet' ).replaceWith( data.query );

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

		this.context.find( '.smwb-status' ).append( text ).addClass( 'smw-callout smw-callout-error' );
	}

	/**
	 * @since 2.5
	 * @method
	 */
	browse.prototype.triggerEvents = function() {

		var self = this;
		var form = self.context.find( '.smwb-form' );

		form.trigger( 'smw.page.autocomplete' , {
			'context': form
		} );

		mw.loader.load(
			self.context.find( '.smwb-modules' ).data( 'modules' )
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

		$( '.smwb-container' ).each( function() {
			instance.setContext( $( this ) ).requestHTML();
		} );

		var form = $( this ).find( '.smwb-form' );

		mw.loader.using( [ 'ext.smw.browse', 'ext.smw.browse.autocomplete' ] ).done( function () {
			form.trigger( 'smw.page.autocomplete' , {
				'context': form
			} );
		} );

	} );

}( jQuery, mediaWiki ) );
