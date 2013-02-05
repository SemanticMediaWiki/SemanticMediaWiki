/**
 * SMW wikiPage DataItem JavaScript representation
 *
 * @see SMW\DIWikiPage
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
	 * wikiPage constructor
	 *
	 * @since  1.9
	 *
	 * @param {String} fulltext
	 * @param {String} fullurl
	 * @param {Integer} namespace
	 * @return {Object} this
	 */
	var wikiPage = function ( fulltext, fullurl, namespace, exists ) {
		this.fulltext  = fulltext !== ''&& fulltext !==  undefined ? fulltext : null;
		this.fullurl   = fullurl !== '' && fullurl !==  undefined ? fullurl : null;
		this.namespace = namespace !==  undefined ? namespace : 0;
		this.exists    = exists !==  undefined ? exists : true;

		// Get mw.Title inheritance
		if ( this.fulltext !== null ){
			this.title = new mw.Title( this.fulltext );
		}

		return this;
	};

	/**
	 * Constructor
	 *
	 * @var Object
	 */
	smw.dataItem.wikiPage = function( fulltext, fullurl, namespace, exists ) {
		if ( $.type( fulltext ) === 'string' && $.type( fullurl ) === 'string' ) {
			this.constructor( fulltext, fullurl, namespace, exists );
		} else {
			throw new Error( 'smw.dataItem.wikiPage: fulltext, fullurl must be a string' );
		}
	};

	/**
	 * Creates an object with methods related to the wikiPage dataItem
	 *
	 * @see SMW\DIWikiPage
	 *
	 * @since  1.9
	 *
	 * @type object
	 */
	var fn = {

		constructor: wikiPage,

		/**
		 * Returns type
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return '_wpg';
		},

		/**
		 * Returns wikiPage text title
		 *
		 * Get full name in text form, like "File:Foo bar.jpg" due to fact
		 * that name is serialized in fulltext
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getName: function() {
			return this.fulltext;
		},

		/**
		 * Returns wikiPage uri
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getUri: function() {
			return this.fullurl;
		},

		/**
		 * Returns mw.Title
		 *
		 * @since  1.9
		 *
		 * @return {mw.Title}
		 */
		getTitle: function() {
			return this.title;
		},

		/**
		 * Returns wikiPage is a known entity or not
		 *
		 * @since  1.9
		 *
		 * @return {boolean}
		 */
		isKnown: function(){
			return this.exists;
		},

		/**
		 * Returns namespace
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getNamespace: function() {
			return this.namespace;
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
				return html.element( 'a', { 'href': this.fullurl }, this.fulltext );
			}
			return this.fulltext;
		}
	};

	// Alias
	fn.exists = fn.isKnown;
	fn.getPrefixedText = fn.getName;

	// Assign methods
	smw.dataItem.wikiPage.prototype = fn;

	// Extension
	// If you need to extend methods just use
	// $.extend( smw.dataItem.wikiPage.prototype, { method: function (){ ... } } );

} )( jQuery, mediaWiki, semanticMediaWiki );