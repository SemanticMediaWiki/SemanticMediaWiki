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
	 * Uri constructor
	 *
	 * @since  1.9
	 *
	 * @param {String}
	 * @return this
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
	 * Inheritance class
	 *
	 * @since 1.9
	 * @type Object
	 */
	smw.dataItem = smw.dataItem || {};

	/**
	 * Constructor
	 *
	 * @var Object
	 */
	smw.dataItem.uri = function( fullurl ) {
		if ( $.type( fullurl ) === 'string' ) {
			this.constructor( fullurl );
		} else {
			throw new Error( 'smw.dataItem.uri: fullurl must be a string' );
		}
	};

	/**
	 * Public methods
	 *
	 * Invoke methods on the constructor
	 *
	 * @since  1.9
	 *
	 * @type object
	 */
	smw.dataItem.uri.prototype = {

		constructor: uri,

		/**
		 * Returns type
		 *
		 * @since  1.9
		 *
		 * @return {String}
		 */
		getDIType: function() {
			return '_uri';
		},

		/**
		 * Returns invoked uri
		 *
		 * @since  1.9
		 *
		 * @return {String}
		 */
		getUri: function() {
			return this.fullurl;
		},

		/**
		 * Returns html representation
		 *
		 * @since  1.9
		 *
		 * @param linker
		 *
		 * @return {String}
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