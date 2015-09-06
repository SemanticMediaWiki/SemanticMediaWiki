/**
 * SMW Uri DataItem JavaScript representation
 *
 * @see SMW\DIUri
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

	var html = mw.html;

	/**
	 * Inheritance class
	 *
	 * @type object
	 */
	smw.dataItem = smw.dataItem || {};

	/**
	 * Uri constructor
	 *
	 * @since  1.9
	 *
	 * @param {string}
	 *
	 * @return {this}
	 */
	var uri = function ( fullurl ) {
		this.fullurl = fullurl !== '' && fullurl !==  undefined ? fullurl : null;

		// Get mw.Uri inheritance
		if ( this.fullurl !== null ) {
			this.uri = new mw.Uri( this.fullurl );
		}

		return this;
	};

	/**
	 * Constructor
	 *
	 * @var object
	 */
	smw.dataItem.uri = function( fullurl ) {
		if ( $.type( fullurl ) === 'string' ) {
			this.constructor( fullurl );
		} else {
			throw new Error( 'smw.dataItem.uri: invoked fullurl must be a string but is of type ' + $.type( fullurl ) );
		}
	};

	/* Public methods */

	smw.dataItem.uri.prototype = {

		constructor: uri,

		/**
		 * Returns type
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return '_uri';
		},

		/**
		 * Returns uri
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getUri: function() {
			return this.fullurl;
		},

		/**
		 * Returns html representation
		 *
		 * @since  1.9
		 *
		 * @param {boolean}
		 *
		 * @return {string}
		 */
		getHtml: function( linker ) {
			if ( linker && this.fullurl !== null ){
				return html.element( 'a', { 'href': this.fullurl }, this.fullurl );
			}
			return this.fullurl;
		}
	};

	// Alias

} )( jQuery, mediaWiki, semanticMediaWiki );