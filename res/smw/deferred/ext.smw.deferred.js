/**
 * Responsible for executing a deferred request to the MediaWiki back-end to
 * retrieve the representation for a #ask query.
 */

/*global jQuery, mediaWiki, smw */
/*jslint white: true */

( ( $, mw, onoi ) => {

	'use strict';

	class Query {
		/**
		 * @since 3.0
		 * @constructor
		 *
		 * @param container {Object}
		 * @param api {Object}
		 */
		constructor( container, api ) {
			this.VERSION = '6.0.1';
			this.title = mw.config.get('wgPageName' );
			this.step = 5;
			this.postfix = '';
			this.neededParams = [ 'limit', 'offset', 'default' ];
			this.default = mw.msg( 'smw_result_noresults' );

			/**
			 * So that the same RegExp objects are not created for every deferred query on a page.
			 *
			 * @since 6.0.1
			 */
			this.paramMatchers = Object.fromEntries( this.neededParams.map( param =>
				[ param, new RegExp('\\|\\s*' + param + '\\s*=' ) ]
			));

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
			this.query = this.neededParams.reduce(
				( query, param ) =>
					query + ( query.match( this.paramMatchers[param] ) ? '' : ( '|' + param + '=' + this[param] ) ),
			this.query );
		}

		/**
		 * Request and parse a #ask/#show query using the MediaWiki API back-end
		 *
		 * @since 3.0
		 */
		doApiRequest() {
			// Replace limit, offset with altered values
			const query = this.query.replace(
				new RegExp( 'limit\\s*=\\s*' + this.limit ),
				'limit=' + this.rangeLimit
			).replace(
				new RegExp( 'offset\\s*=\\s*' + this.offset ),
				'offset=' + this.rangeOffset
			);

			// In case the query was altered from its original request, signal
			// to the QueryDependencyLinksStore to disable any tracking.
			const noTrace = this.query === query ? '' : '|@notrace';

			// API notes "modules: Gives the ResourceLoader modules used on the page.
			// Either jsconfigvars or encodedjsconfigvars must be requested jointly
			// with modules. 1.24+"
			this.mwApi.post( {
				action: 'parse',
				title: this.title,
				contentmodel: 'wikitext',
				prop: 'text|modules|jsconfigvars',
				text: '{{#' + this.cmd + ':' +  query + noTrace + '}}'
			} ).done( data => {

				if ( this.init ) {
					this.initControls();
					this.replaceOutput( '' );
				}

				// Remove any comments retrieved from the API parse
				let text = data.parse.text[ '*' ].replace( /<!--[\S\s]*?-->/gm, '' );

				// Remove any remaining placeholder loading classes
				if ( this.control !== '' ) {
					text = text.replace( 'smw-loading-image-dots', '' );
				}

				// Remove any <p> element to avoid line breakages
				if ( this.cmd === 'show' ) {
					text = text.replace( /(^<p[^>]*>|<\/p>$)/img, '' );
				}

				this.replaceOutput( text, '', data.parse.modules );

			} ).fail ( ( code, failure ) => {
				let error =  code + ': ' + failure.textStatus;

				if ( failure.hasOwnProperty( 'exception' ) && failure.hasOwnProperty( 'xhr' ) ) {
					error = failure.xhr.responseText;
				} else if ( failure.hasOwnProperty( 'error' ) && failure.error.hasOwnProperty( 'info' ) ) {
					error = failure.error.info;
				}

				this.container.find( '#deferred-control' ).replaceWith( '<div id="deferred-control"></div>' );
				this.container.find( '.irs' ).hide();
				this.replaceOutput( error, 'error' );
			});
		}

		/**
		 * Replace output with generated content
		 *
		 * @since 3.0
		 *
		 * @param text {String}
		 * @param oClass {String}
		 * @param reloadModules {Array}
		 *
		 * @return {this}
		 */
		replaceOutput( text, oClass, reloadModules ) {
			const element = this.cmd === 'ask' ? 'div' : 'span';
			this.container.find( '#deferred-output' ).replaceWith(
				'<' + element + ' id="deferred-output"' +
				( oClass !== undefined ? 'class="' + oClass + '"' : '' ) +
				'>' + text + '</' + element + '>'
			);
			this.reload( reloadModules );
		}

		/**
		 * Reload module objects that rely on JavaScript to be executed after a
		 * fresh parse.
		 *
		 * @since 3.0
		 *
		 * @param modules {Array}
		 */
		reload( modules ) {
			// Trigger an event to re-apply JS instances initialization on new
			// content
			if ( modules !== undefined ) {
				mw.loader.using( modules ).done( () => {
					mw.hook( 'smw.deferred.query' ).fire( this.container );
				} );
			} else {
				mw.hook( 'smw.deferred.query' ).fire( this.container );
			}

			// MW's table sorter isn't listed as page module therefore make an exception
			// and reload it manually
			let table = this.container.find( '#deferred-output table' );
			if ( table.length > 0 && table.hasClass( 'sortable' ) ) {
				mw.loader.using( 'jquery.tablesorter' ).done( () => {
					table.tablesorter();
					mw.hook( 'smw.deferred.query.tablesorter' ).fire( table );
				} );
			}
		}

		/**
		 * Executes and manages the initialization of control elements
		 *
		 * @since 3.0
		 */
		initControls() {
			const loading = '<div class="smw-flex-center smw-absolute">'
				+ '<span class="smw-overlay-spinner medium flex" alt="Loading..."></span></div>';

			if ( this.init && this.control === 'slider' ) {
				this.container.find( '#deferred-control' ).ionRangeSlider( {
					type: 'double',
					min: 0,
					max: this.max,
					step: this.step,
					from: this.offset,
					to: this.limit + this.offset,
					force_edges: true,
					postfix: this.postfix,
					min_interval: 1,
					grid: true,
					grid_num: 2,
					onChange: data => {
						this.container.find( '#deferred-output' ).addClass( 'is-disabled' ).append( loading );
					},
					onFinish: data => {
						this.rangeOffset = data.from - this.offset;
						this.rangeLimit = data.to - data.from;
						this.doApiRequest();
					}
				} );
			}

			// Once called turn off the init flag.
			this.init = false;
		}
	}


	/**
	 * @since 3.0
	 */
	$( document ).ready( () => {
		const api = new mw.Api();
		$( '.smw-deferred-query' ).each( ( index, element ) => {
			let q = new Query( $( element ), api );
			q.doApiRequest();
		} );
	} );

} )( jQuery, mediaWiki );
