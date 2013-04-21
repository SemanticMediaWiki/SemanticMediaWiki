/**
 * SMW Query JavaScript representation
 *
 * @see SMW\Query, SMW\QueryResult
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/* Private object and methods */
	var html = mw.html;

	/**
	 * Query constructor
	 *
	 * @since  1.9
	 *
	 * @param {array}
	 * @param {array}
	 * @param {array|string}
	 *
	 * @return {this}
	 */
	var query = function ( printouts, parameters, conditions ) {
		this.printouts  = printouts !== '' && printouts !== undefined ? printouts : [];
		this.parameters = parameters !== '' && parameters !== undefined ? parameters : {};
		this.conditions = conditions !== '' && conditions !== undefined ? conditions :[];
		return this;
	};

	/**
	 * Constructor
	 *
	 * @var Object
	 */
	smw.Query = function( printouts, parameters, conditions ) {

		// You need to have some conditions otherwise jump the right light
		// because a query one can survive without printouts or parameters
		if ( conditions !== '' || $.type( this.conditions ) === 'array' ) {
			this.constructor( printouts, parameters, conditions  );
		} else {
			throw new Error( 'smw.dataItem.query: conditions are empty' );
		}
	};

	/* Public methods */

	var fn = {

		constructor: query,

		/**
		 * Returns query limit parameter
		 *
		 * @see SMW\Query::getLimit()
		 *
		 * @since  1.9
		 *
		 * @return {number}
		 */
		getLimit: function() {
			return this.parameters.limit;
		},

		/**
		 * Returns query link
		 *
		 * @see SMW\QueryResult::getLink()
		 *
		 * Caption text is set either by using parameters.searchlabel or by
		 * .getLink( 'myCaption' )
		 *
		 * @since  1.9
		 *
		 * @param {string}
		 * @return {string}
		 */
		getLink: function( caption ) {
			var c = caption ? caption : this.parameters.searchlabel !== undefined ? this.parameters.searchlabel : '...' ;
			return html.element( 'a', {
				'class': 'query-link',
				'href' : mw.config.get( 'wgScript' ) + '?' + $.param( {
						title: 'Special:Ask',
						'q':  $.type( this.conditions ) === 'string' ? this.conditions : this.conditions.join( '' ),
						'po': this.printouts.join( '\n' ),
						'p':  this.parameters
					} )
			}, c );
		},

		/**
		 * Returns query string
		 *
		 * @see SMW\Query::getQueryString()
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		toString: function() {

			var printouts = '';
			if ( $.type( this.printouts ) === 'array' ){
				$.each( this.printouts, function( key, value ) {
					printouts += '|' + value;
				} );
			} else {
				// @see ext.smw.query.test why we are failing here and not earlier
				throw new Error( 'smw.Api.query: printouts is not an array, it is a + ' + $.type( this.printouts ) );
			}

			var parameters = '';
			if ( $.type( this.parameters ) === 'object' ){
				$.each( this.parameters, function( key, value ) {
					parameters += '|' + key + '=' + value;
				} );
			} else {
				// @see ext.smw.query.test why we are failing here and not earlier
				throw new Error( 'smw.Api.query: parameters is not an object, it is a + ' + $.type( this.parameters ) );
			}

			var conditions = '';
			if ( $.type( this.conditions ) === 'array' || $.type( this.conditions ) === 'object' ){
				$.each( this.conditions, function( key, value ) {
					conditions += value;
				} );
			} else if ( $.type( this.conditions ) === 'string' ) {
				conditions += this.conditions;
			}

			return  conditions + printouts + parameters;
		}
	};

	// Alias
	fn.getQueryString = fn.toString;

	// Assign methods
	smw.Query.prototype = fn;
	smw.query = smw.Query;

} )( jQuery, mediaWiki, semanticMediaWiki );