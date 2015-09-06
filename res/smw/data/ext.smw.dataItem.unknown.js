/**
 * SMW Unlisted/Unknown DataItem JavaScript representation
 *
 * A representation for types we do not know or are unlisted
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
	 * Unknown constructor
	 *
	 * @since  1.9
	 *
	 * @param {string}
	 * @param {mixed}
	 * @return {this}
	 */
	var unknown = function ( value, type ) {
		this.value = value !== '' ? value : null;
		this.type = type !== '' ? type : null;

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
	smw.dataItem.unknown = function( value, type ) {
		this.constructor( value, type );
	};

	/* Public methods */

	var fn = {

		constructor: unknown,

		/**
		 * Returns type
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return this.type;
		},

		/**
		 * Returns value
		 *
		 * @since  1.9
		 *
		 * @return {mixed}
		 */
		getValue: function() {
			return this.value;
		}
	};

	// Alias
	fn.getHtml = fn.getValue;

	// Assign methods
	smw.dataItem.unknown.prototype = fn;

} )( jQuery, mediaWiki, semanticMediaWiki );