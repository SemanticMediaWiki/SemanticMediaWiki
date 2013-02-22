/**
 * SMW Number DataItem JavaScript representation
 *
 * @see  SMW\DINumber
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
	 * Inheritance class for the smw.dataItem constructor
	 *
	 * @since 1.9
	 *
	 * @class
	 * @abstract
	 */
	smw.dataItem = smw.dataItem || {};

	/**
	 * Number constructor
	 *
	 * @since  1.9
	 *
	 * @param {number}
	 * @return {this}
	 */
	var number = function ( number ) {
		this.number = number !== '' ? number : null;

		return this;
	};

	/**
	 * Class constructor
	 *
	 * @since 1.9
	 *
	 * @class
	 * @constructor
	 * @extends smw.dataItem
	 */
	smw.dataItem.number = function( value ) {
		if ( $.type( value ) === 'number' ) {
			this.constructor( value );
		} else {
			throw new Error( 'smw.dataItem.number: invoked value must be a number but is of type ' + $.type( value ) );
		}
	};

	/* Public methods */

	smw.dataItem.number.prototype = {

		constructor: number,

		/**
		 * Returns type
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return '_num';
		},

		/**
		 * Returns a number together with the number constructor functions
		 *
		 * @since  1.9
		 *
		 * @return {number}
		 */
		getNumber: function() {
			return Number( this.number );
		},

		/**
		 * Returns a plain value representation
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getValue: function() {
			return this.number;
		}
	};

	// Alias

} )( jQuery, mediaWiki, semanticMediaWiki );