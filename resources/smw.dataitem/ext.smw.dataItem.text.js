/**
 * SMW Text DataItem JavaScript representation
 *
 * @see  SMW\DIString, SMW\DIBlob
 *
 * A string is a text representation only limited by its length.
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
	 * @param {string}
	 * @param {string}
	 * @return {this}
	 */
	var text = function ( text, type ) {
		this.text = text !== '' ? text : null;

		// If the type is not specified we assume it has to be '_txt'
		this.type = type !== '' && type !== undefined ? type : '_txt';

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
	smw.dataItem.text = function( text, type ) {
		if ( $.type( text ) === 'string' ) {
			this.constructor( text, type );
		} else {
			throw new Error( 'smw.dataItem.text: invoked text must be a string but is of type ' + $.type( text ) );
		}
	};

	/* Public methods */

	var fn = {

		constructor: text,

		/**
		 * Returns type
		 *
		 * Flexible in what to return as type as it could be either '_str' or
		 * '_txt' but the methods are the same therefore there is no need for
		 * an extra dataItem representing a string
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return this.type;
		},

		/**
		 * Returns a plain text representation
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getText: function() {
			return this.text;
		}
	};

	// Alias
	fn.getValue = fn.getText;
	fn.getString = fn.getText;

	// Assign methods
	smw.dataItem.text.prototype = fn;

} )( jQuery, mediaWiki, semanticMediaWiki );