/**
 * SMW Api Query JavaScript representation
 *
 * @see SMW\Query
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

	/**
	 * Inheritance class
	 *
	 * @type Object
	 */
	smw.Api = smw.Api || {};

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
		this.printouts  = printouts !== '' && printouts !== undefined ? printouts : null;
		this.parameters = parameters !== '' && parameters !== undefined ? parameters : null;
		this.conditions = conditions !== '' && conditions !== undefined ? conditions : null;
		return this;
	};

	/**
	 * Constructor
	 *
	 * @var Object
	 */
	smw.Api.query = function( printouts, parameters, conditions ) {
			this.constructor( printouts, parameters, conditions );
	};

	/**
	 * Public methods
	 *
	 * @type {Object}
	 */
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
			if ( this.printouts !== null ){
				$.each( this.printouts, function( key, value ) {
					printouts += '|' + value;
				} );
			}

			var parameters = '';
			if ( this.parameters !== null ){
				$.each( this.parameters, function( key, value ) {
					parameters += '|' + key + '=' + value;
				} );
			}

			var conditions = '';
			if ( this.conditions !== null && typeof this.conditions === 'object' ){
				$.each( this.conditions, function( key, value ) {
					conditions += value;
				} );
			} else if ( this.conditions !== null ) {
				conditions += this.conditions;
			}

			return  conditions + printouts + parameters;
		}
	};

	// Alias
	fn.getQueryString = fn.toString;

	// Assign methods
	smw.Api.query.prototype = fn;

} )( jQuery, mediaWiki, semanticMediaWiki );