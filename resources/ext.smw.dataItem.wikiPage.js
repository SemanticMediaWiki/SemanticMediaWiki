/**
 * SMW WikiPage DataItem JavaScript representation
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
	 * wikiPage constructor
	 *
	 * @since  1.9
	 *
	 * @param {String} fulltext
	 * @param {String} fullurl
	 * @param {Integer} namespace
	 * @return {Object} this
	 */
	var wikiPage = function ( fulltext, fullurl, namespace ) {
		this.fulltext  = fulltext !== ''&& fulltext !==  undefined ? fulltext : null;
		this.fullurl   = fullurl !== '' && fullurl !==  undefined ? fullurl : null;

		// Namespace will replaced by this.title.getNamespace() in a follow-up
		// and SMW\DISerializer will instead generate ( fulltext, fullurl, exists )
		this.namespace = namespace !==  undefined ? namespace : 0;

		// Get mw.Title inheritance
		if ( this.fulltext !== null ){
			this.title = new mw.Title( this.fulltext );
		}

		// For now we assume true, as long as SMW\DISerializer doesn't tells us otherwise
		this.exists = true;

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
	smw.dataItem.wikiPage = function( fulltext, fullurl, namespace ) {
		if ( $.type( fulltext ) === 'string' && $.type( fullurl ) === 'string' ) {
			this.constructor( fulltext, fullurl, namespace );
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
	smw.dataItem.wikiPage.prototype = {

		constructor: wikiPage,

		/**
		 * Returns type
		 *
		 * @since  1.9
		 *
		 * @return {String}
		 */
		getDIType: function() {
			return '_wpg';
		},

		/**
		 * Returns wikiPage invoked name
		 *
		 * @since  1.9
		 *
		 * @return {String}
		 */
		getName: function() {
			return this.fulltext;
		},

		/**
		 * Returns wikiPage invoked uri
		 *
		 * @since  1.9
		 *
		 * @return {String}
		 */
		getUri: function() {
			return this.fullurl;
		},

		/**
		 * Returns wikiPage invoked uri
		 *
		 * @since  1.9
		 *
		 * @return {String}
		 */
		getTitle: function() {
			return this.title;
		},

		/**
		 * Returns wikiPage is a known entity or not
		 *
		 * @since  1.9
		 *
		 * @return {Boolean}
		 */
		isKnown: function(){
			return this.exists;
		},

		/**
		 * Returns wikiPage namespace
		 *
		 * @since  1.9
		 *
		 * @return {String}
		 */
		getNamespace: function() {
			return this.namespace;
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
				return html.element( 'a', { 'href': this.fullurl }, this.fulltext );
			}
			return this.fulltext;
		}
	};

	// Extension
	// If you need to extend methods just use
	// $.extend( smw.dataItem.wikiPage.prototype, { method: function (){ ... } } );

	// Alias

} )( jQuery, mediaWiki, semanticMediaWiki );