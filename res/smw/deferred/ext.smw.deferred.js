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
		this.container = container;
		this.mwApi = api;

		this.data = container.data( 'query' );
		this.query = this.data.query;

		this.cmd = this.data.cmd;
		this.control = container.find( '#deferred-control' ).data( 'control' );

		this.limit = this.data.limit;
		this.offset = this.data.offset;

		this.rangeLimit = this.limit;
		this.rangeOffset = this.offset;
		this.init = true;

		this.max = this.data.max;

		// Add some parameters required by a deferred SMW query.
		var self = this; // no let until JS6; this will be reset and need to be remembered.
		this.query = this.neededParams.reduce( function ( query, param) {
			// no lambdas until JS6.
			return query + ( query.match( self.paramMatchers[param] ) ? '' : '|' + param + '=' + self[param] );
		}, this.query );
	};


	/**
	 * Do not initialise class constants every time that a class instance is created for a deferred query on a page.
	 *
	 * @since 6.0.1
	 */
	Query.prototype.VERSION = '6.0.1';
	Query.prototype.title = mw.config.get( 'wgPageName' );
	Query.prototype.step = 5;
	Query.prototype.postfix = '';
	Query.prototype.neededParams = [ 'limit', 'offset', 'default' ];
	Query.prototype.default = mw.msg( 'smw_result_noresults' );

	/**
	 * So that the same RegExp objects are not created for every deferred query on a page.
	 *
	 * @since 6.0.1
	 */
	Query.prototype.paramMatchers = Object.fromEntries( Query.prototype.neededParams.map( function( param) {
		// no lambdas until JS6.
		return [ param, new RegExp( '\\|\\s*' + param + '\\s*=' ) ];
	} ) );

	/**
	 * Request and parse a #ask/#show query using the MediaWiki API back-end
	 *
	 * @since 3.0
	 */
	Query.prototype.doApiRequest = function() {

		var self = this,
		noTrace = '';

		// Replace limit, offset with altered values
		var query = self.query.replace(
			new RegExp( 'limit\\s*=\\s*' + self.limit ),
			'limit=' + self.rangeLimit
		).replace(
			new RegExp( 'offset\\s*=\\s*' + self.offset ),
			'offset=' + self.rangeOffset
		);

		// In case the query was altered from its original request, signal
		// to the QueryDependencyLinksStore to disable any tracking
		if ( self.query !== query ) {
			noTrace = '|@notrace';
		}

		// API notes "modules: Gives the ResourceLoader modules used on the page.
		// Either jsconfigvars or encodedjsconfigvars must be requested jointly
		// with modules. 1.24+"
		self.mwApi.post( {
			action: 'parse',
			title: self.title,
			contentmodel: 'wikitext',
			prop: 'text|modules|jsconfigvars',
			text: '{{#' + self.cmd + ':' +  query + noTrace + '}}'
		} ).done( function( data ) {

			if ( self.init === true ) {
				self.initControls();
				self.replaceOutput( '' );
			}

			// Remove any comments retrieved from the API parse
			var text = data.parse.text['*'].replace(/<!--[\S\s]*?-->/gm, '' );

			// Remove any remaining placeholder loading classes
			if ( self.control !== '' ) {
				text = text.replace( 'smw-loading-image-dots', '' );
			}

			// Remove any <p> element to avoid line breakages
			if ( self.cmd === 'show' ) {
				text = text.replace( /(^<p[^>]*>|<\/p>$)/img, '' );
			}

			self.replaceOutput( text, '', data.parse.modules );

		} ).fail ( function( code, failure ) {
			var error =  code + ': ' + failure.textStatus;

			if ( failure.hasOwnProperty( 'exception' ) && failure.hasOwnProperty( 'xhr' ) ) {
				error = failure.xhr.responseText;
			} else if ( failure.hasOwnProperty( 'error' ) && failure.error.hasOwnProperty( 'info' ) ) {
				error = failure.error.info;
			}

			self.container.find( '#deferred-control' ).replaceWith( '<div id="deferred-control"></div>' );
			self.container.find( '.irs' ).hide();
			self.replaceOutput( error, 'error' );
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

		oClass = oClass !== undefined ? 'class="' + oClass + '"' : '';

		self.container.find( '#deferred-output' ).replaceWith(
			'<' + element + ' id="deferred-output"' + oClass + '>' + text + '</' + element + '>'
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
		var loading = '<div class="smw-flex-center smw-absolute"><span class="smw-overlay-spinner medium flex" alt="Loading..."></span></div>';

		if ( self.init === true && self.control === 'slider' ) {
			self.container.find( '#deferred-control' ).ionRangeSlider( {
				type: 'double',
				min: 0,
				max: self.max,
				step: self.step,
				from: self.offset,
				to: self.limit + self.offset,
				force_edges: true,
				postfix: self.postfix,
				min_interval: 1,
				grid: true,
				grid_num: 2,
				onChange: function ( data ) {
					self.container.find( '#deferred-output' ).addClass( 'is-disabled' ).append( loading );
				},
				onFinish: function ( data ) {
					self.rangeOffset = data.from - self.offset;
					self.rangeLimit = data.to - data.from;
					self.doApiRequest();
				}
			} );
		}

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
