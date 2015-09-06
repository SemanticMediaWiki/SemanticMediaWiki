/**
 * SMW Quantity DataValue JavaScript representation
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
	 * Inheritance class for the smw.dataValue constructor
	 *
	 * @since 1.9
	 *
	 * @class
	 * @abstract
	 */
	smw.dataValue = smw.dataValue || {};

	/**
	 * Number constructor
	 *
	 * @since  1.9
	 *
	 * @param {number}
	 * @param {string}
	 * @param {number}
	 * @return {this}
	 */
	var quantity = function ( value, unit, accuracy ) {
		this.value = value !== '' ? value : null;
		this.unit = unit !== '' ? unit : null;
		this.accuracy = accuracy !== '' ? accuracy : null;

		return this;
	};

	/**
	 * Class constructor
	 *
	 * @since 1.9
	 *
	 * @class
	 * @constructor
	 * @extends smw.dataValue
	 */
	smw.dataValue.quantity = function( value, unit, accuracy ) {
		if ( $.type( value ) === 'number' ) {
			this.constructor( value, unit, accuracy );
		} else {
			throw new Error( 'smw.dataValue.quantity: invoked value must be a number but is of type ' + $.type( value ) );
		}
	};

	/* Public methods */

	smw.dataValue.quantity.prototype = {

		constructor: quantity,

		/**
		 * Returns type
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return '_qty';
		},

		/**
		 * Returns value
		 *
		 * @since  1.9
		 *
		 * @return {number}
		 */
		getValue: function() {
			return this.value;
		},

		/**
		 * Returns unit
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getUnit: function() {
			return this.unit;
		},

		/**
		 * Returns accuracy
		 *
		 * @since  1.9
		 *
		 * @return {number}
		 */
		getAccuracy: function() {
			return this.accuracy;
		}

	};

	// Alias

} )( jQuery, mediaWiki, semanticMediaWiki );