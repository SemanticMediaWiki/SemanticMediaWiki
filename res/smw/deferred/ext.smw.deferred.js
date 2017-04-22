/**
 * Responsible for executing a deferred request to the MediaWiki back-end to
 * retrieve the representation for a #ask query.
 */

/*global jQuery, mediaWiki, smw */
/*jslint white: true */

( function( $, mw, onoi ) {

	'use strict';

	/**
	 * @since 3.0
	 * @constructor
	 *
	 * @param container {Object}
	 * @param api {Object}
	 */
	var Query = function ( container, api ) {

		this.VERSION = "3.0";

		this.container = container;
		this.mwApi = api;

		this.title = mw.config.get( 'wgPageName' );
		this.query = container.data( 'query' );

		this.cmd = container.data( 'cmd' );
		this.control = container.find( '#deferred-control' ).data( 'control' );

		this.limit = container.data( 'limit' );
		this.offset = container.data( 'offset' );

		this.rangeLimit = this.limit;
		this.init = true;

		this.max = container.data( 'max' );
		this.step = 1;
		this.postfix = '';

		// Ensure to have a limit parameter for queries that use
		// the default setting
		if ( this.query.indexOf( "|limit=" ) == -1 ) {
			this.query = this.query + '|limit=' + this.limit;
		}
	};

	/**
	 * Request and parse a #ask/#show query using the MediaWiki API back-end
	 *
	 * @since 3.0
	 */
	Query.prototype.doApiRequest = function() {

		var self = this,
			noTrace = '';

		// Replace limit with that of the range
		var query = self.query.replace(
			'limit=' + self.limit,
			'limit=' + self.rangeLimit
		);

		// In case the query was altered from its original request, signal
		// to the QueryDependencyLinksStore to disable any tracking
		if ( self.query !== query ) {
			noTrace = '|@notrace';
		};

		// API notes "modules: Gives the ResourceLoader modules used on the page.
		// Either jsconfigvars or encodedjsconfigvars must be requested jointly
		// with modules. 1.24+"
		self.mwApi.post( {
			action: "parse",
			title: self.title,
			contentmodel: 'wikitext',
			prop: 'text|modules|jsconfigvars',
			text: '{{#' + self.cmd + ':' +  query + noTrace + '}}'
		} ).done( function( data ) {

			if ( self.init === true ) {
				self.replaceOutput( '' );
			};

			// Remove any comments retrieved from the API parse
			// Remove any remaining placeholder loading classes
			var text = data.parse.text['*']
				.replace(/<!--[\S\s]*?-->/gm, '' )
				.replace( 'smw-loading-image-dots', '' );

			// Remove any <p> element to avoid line breakages
			if ( self.cmd === 'show' ) {
				text = text.replace( /(?:^<p[^>]*>)|(?:<\/p>$)/img, '' );
			}

			self.replaceOutput( text, '', data.parse.modules );

		} ).fail ( function( code, details ) {
			var error =  code + ': ' + details.textStatus;

			if ( details.error.hasOwnProperty( 'info' ) ) {
				error = details.error.info;
			}

			self.container.find( '#deferred-control' ).replaceWith( "<div id='deferred-control'></div>" );
			self.container.find( '.irs' ).hide();
			self.replaceOutput( error, "smw-callout smw-callout-error" );
		} );
	};

	/**
	 * Replace output with generated content
	 *
	 * @since 3.0
	 *
	 * @param text {String}
	 * @param oClass {String}
	 * @param modules {Array}
	 *
	 * @return {this}
	 */
	Query.prototype.replaceOutput = function( text, oClass, modules ) {

		var self = this,
			element = this.cmd === 'ask' ? 'div' : 'span';

		oClass = oClass !== undefined ? "class='" + oClass + "'" : '';

		self.container.find( '#deferred-output' ).replaceWith(
			"<" + element + " id='deferred-output'" + oClass + ">" + text + "</" + element + ">"
		);

		self.reload( modules );
	};

	/**
	 * Reload module objects that rely on JavaScript to be executed after a
	 * fresh parse.
	 *
	 * @since 3.0
	 *
	 * @param modules {Array}
	 */
	Query.prototype.reload = function( modules ) {

		var self = this;

		self.initControls();

		// Trigger an event to re-apply JS instances initialization on new
		// content
		if ( modules !== undefined ) {
			mw.loader.using( modules ).done( function () {
				mw.hook( 'smw.deferred.query' ).fire( self.container );
			} );
		} else {
			mw.hook( 'smw.deferred.query' ).fire( self.container );
		}

		var table = self.container.find( '#deferred-output table' );

		// MW's table sorter isn't listed as page module therefore make an exception
		// and reload it manually
		if ( table.length > 0 && table.hasClass( 'sortable' ) ) {
			mw.loader.using( 'jquery.tablesorter' ).done( function () {
				table.tablesorter();
				mw.hook( 'smw.deferred.query.tablesorter' ).fire( table );
			} );
		}
	};

	/**
	 * Executes and manages the initialization of control elements
	 *
	 * @since 3.0
	 */
	Query.prototype.initControls = function() {

		var self = this;
		var loading = '<span class="smw-overlay-spinner large inline" alt="Loading..."></span>';

		if ( self.init === true && self.control === 'slider' ) {
			self.container.find( '#deferred-control' ).ionRangeSlider( {
				min: self.limit + self.offset,
				max: self.max,
				step: self.step,
				from: self.limit,
				force_edges: true,
				postfix: self.postfix,
				onChange: function ( data ) {
					self.container.find( '#deferred-output' ).addClass( 'is-disabled' ).append( loading );
				},
				onFinish: function ( data ) {
					self.rangeLimit = data.from - self.offset;
					self.doApiRequest();
				}
			} );
		};

		// Once called turn off the init flag
		self.init = self.init === true ? false : self.init;
	};

	/**
	 * @since 3.0
	 */
	$( document ).ready( function() {
		$( '.smw-deferred-query' ).each( function() {
			var q = new Query(
				$( this ),
				new mw.Api()
			);

			q.doApiRequest();
		} );
	} );

}( jQuery, mediaWiki ) );
