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
	 * @param {Object} mwapi
	 * @param {Object} util
	 *
	 * @return {this}
	 */
	var admin = function ( mwapi ) {
		this.VERSION = "2.5";
		this.mwapi = mwapi;

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

		this.contentClass = '';
		this.errorClass = '';

		if ( this.config && this.config.hasOwnProperty( 'contentClass' ) ) {
			this.contentClass = this.config.contentClass;
		};

		if ( this.config && this.config.hasOwnProperty( 'errorClass' ) ) {
			this.errorClass = this.config.errorClass;
		};

		return this;
	};

	/**
	 * @since 2.5
	 * @method
	 */
	admin.prototype.info = function( data ) {

		if ( data.info.jobcount.length === 0 ) {
			return this.replace(  mw.msg( 'smw-no-data-available' ) );
		}

		this.replace(
			'<pre>' + JSON.stringify( data.info.jobcount, null, 2 ) + '</pre>'
		);
	}

	/**
	 * @since 2.5
	 * @method
	 */
	admin.prototype.task = function( data, parameters, pre_content ) {

		var msg = data.task.count > 1 ? 'smw-list-count-plural' : 'smw-list-count';
		var content = '';

		if ( data.task.hasOwnProperty( 'isFromCache' ) ) {
			var time = new Date( data.task.time * 1000 );
			content = '<p>' + mw.msg( msg + '-from-cache', data.task.count, time.toUTCString() ) + '</p>';
		} else {
			content = '<p>' + mw.msg( msg, data.task.count ) + '</p>';
		}

		if ( data.task.list.length === 0 ) {
			this.replace( mw.msg( 'smw-no-data-available' ) );
		} else if ( data.task.hasOwnProperty( 'query-continue-offset' ) && data.task['query-continue-offset'] > 0 ) {

			if ( data.task.hasOwnProperty( 'from' ) ) {
				pre_content.push( smw.merge(
				{
					'from': data.task.from,
					'to': data.task.to
				}, data.task.list ) );

				$( '#smw-request-update' ).replaceWith(
					'<div id="smw-request-update" style="clear:both;width:100%;">' +
					mw.msg( 'smw-processing' ) +
					'&nbsp;' +
					mw.msg( 'smw-api-data-collection-processing', data.task.from, data.task.to ) +
					'</div>'
				)
			} else {
				pre_content.push( data.task.list );
			};

			var params = JSON.parse( parameters.params );

			parameters.params = JSON.stringify( {
				'limit': params.limit,
				'offset': data.task['query-continue-offset']
			} )

			this.api( parameters, pre_content );
		} else {
			if ( pre_content.length > 0 ) {
				content = '';
			};

			if ( data.task.hasOwnProperty( 'from' ) ) {
				pre_content.push(
					smw.merge(
						{
							'from': data.task.from,
							'to': data.task.to
						},
						data.task.list
					)
				);
			} else if ( data.task.hasOwnProperty( 'isFromCache' ) ) {
				pre_content = smw.merge(
					data.task.list,
					{
						'isFromCache': data.task.isFromCache,
						'timestamp': data.task.time
					}
				);
			} else {
				pre_content = data.task.list;
			};

			this.jsonview( JSON.stringify( pre_content, null, 2 ) );
		}
	}

	/**
	 * @since 2.5
	 * @method
	 */
	admin.prototype.api = function( parameters, pre_content = [] ) {

		var self = this,
			content = mw.msg( 'smw-no-data-available' );

		self.mwapi.postWithToken( 'csrf', parameters ).done( function( data ) {
			if ( data.hasOwnProperty( 'info' ) ) {
				self.info( data );
			} else if ( data.hasOwnProperty( 'task' ) ) {
				self.task( data, parameters, pre_content );
			}
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

		if ( this.contentClass === '' || this.errorClass === '' ) {
			return;
		};

		this.context.find( '.' + this.contentClass ).hide();
		this.context.find( '.' + this.errorClass ).append( error ).addClass( 'smw-callout smw-callout-error' );
	};

	/**
	 * @since 2.5
	 * @method
	 *
	 * @param {string} content
	 */
	admin.prototype.replace = function( content ) {

		if ( this.contentClass === '' ) {
			return;
		};

		this.context.css( 'opacity', 1 );
		this.context.find( '.' + this.contentClass ).replaceWith( '<div class="' + this.contentClass + '">' + content + '</div>' );
	};

	admin.prototype.jsonview = function( json ) {

		if ( this.contentClass === '' ) {
			return;
		};

		var self = this;
		this.context.css( 'opacity', 1 );

		mw.loader.using( [ 'smw.jsonview' ] ).then( function () {
			smw.jsonview.init( self.context.find( '.' + self.contentClass ), json )
		} );
	};

	var instance = new admin(
		new mw.Api()
	);

	$( document ).ready( function() {

		// JS is loaded, now remove the "soft" disabled functionality
		$( "#smw-json" ).removeClass( 'smw-schema-placeholder' );

		var container = $( "#smw-json" ),
			json = container.find( '.smw-data' ).text();

		if ( json !== '' ) {
			smw.jsonview.init( container, json );
		};

		/**
		 * Find job count via the API
		 */
		$( '.smw-admin-statistics-job' ).each( function() {

			var parameters = {
				action: 'smwinfo',
				info: 'jobcount'
			};

			instance.setContext( $( this ) ).api( parameters );
		} );

		/**
		 * Run selected jobs via the API
		 */
		$( '.smw-admin-api-job-task' ).on( 'click', function( event ) {

			var params = $.extend(
				{ 'waitOnCommandLine': true },
				$( this ).data( 'parameters' )
			);

			instance.setContext( $( this ) )

			instance.api( {
				action: 'smwtask',
				task: 'insert-job',
				params: JSON.stringify( {
					'subject': $( this ).data( 'subject' ),
					'job': $( this ).data( 'job' ),
					'parameters': params
				} )
			} );
		} );

		/**
		 * Reload the page to ensure that the page on the first visit is not
		 * blocked by the request.
		 *
		 * @see https://stackoverflow.com/questions/5997450/append-to-url-and-refresh-page
		 */
		$( '.smw-admin-db-preparation' ).each( function() {
			window.location.search += '&prep=done';
		} );

		/**
		 * Find duplicate entities via the API
		 */
		$( '.smw-admin-supplementary-duplicate-lookup' ).each( function() {

			instance.setContext( $( this ) )

			instance.api( {
				action: 'smwtask',
				task: 'duplicate-lookup',
				formatversion:2,
				params: JSON.stringify( [] )
			} );
		} );

		/**
		 * Generate replication report via the API
		 */
		$( '.smw-admin-supplementary-es-replication-report' ).each( function() {

			instance.setContext( $( this ) );

			instance.api( {
				action: 'smwtask',
				task: 'es-replication-report',
				params: JSON.stringify( {
					'waitOnCommandLine': true,
					'limit': $( this ).data( 'limit' ),
					'offset': 0
				} )
			} );
		} );

	} );

}( jQuery, mediaWiki ) );
