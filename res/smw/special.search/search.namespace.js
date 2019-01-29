/*!
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Namespace form in Special:Search
	 *
	 * @since 3.0
	 */
	var namespace = function() {

		/**
		 * Copied from mediawiki.special.search.js in order to have the NS
		 * button to work without #powersearch
		 */
		var $checkboxes = $( '#search input[id^=mw-search-ns]' );

		// JS loaded enable all fields
		$( ".is-disabled" ).removeClass( 'is-disabled' );

		$( document ).on( "click", "#mw-search-toggleall", function() {
			$checkboxes.prop( 'checked', true );
		} );

		$( document ).on( "click", "#mw-search-togglenone", function() {
			$checkboxes.prop( 'checked', false );
		} );

		// When saving settings, use the proper request method (POST instead of GET).
		$( this ).on( "change", "#mw-search-powersearch-remember", function() {
			this.form.method = this.checked ? 'post' : 'get';
		} ).trigger( 'change' );

		var nsList = '';

		if ( $( '#mw-search-ns' ).css( 'display' ) !== 'none' ) {
			nsList = mw.msg( 'smw-search-hide' );
		} else {
			nsList = mw.msg( 'smw-search-show' );
		};

		$( document ).on( "click", "#smw-togglensview", function( event ) {
			// We carry the hidden `ns-list` on a submit so the status
			// of the previous action is retained to either show or hide
			// the section
			if ( $( '#mw-search-ns' ).css( 'display' ) !== 'none' ) {
				$( 'input[name=ns-list]' ).attr( 'value', 1 );
				event.target.value = mw.msg( 'smw-search-show' );
				$( '#mw-search-ns' ).css( 'display', 'none' );
				$( '#smw-search-togglebox' ).css( 'display', 'none' );
			} else {
				event.target.value = mw.msg( 'smw-search-hide' );
				$( 'input[name=ns-list]' ).attr( 'value', 0 );
				$( '#mw-search-ns' ).css( 'display', 'block' );
				$( '#smw-search-togglebox' ).css( 'display', 'block' );
			}
		} );
	};

	// Only load when it is Special:Search and the search type supports
	// https://www.semantic-mediawiki.org/wiki/Help:SMWSearch
	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) == 'Search' && mw.config.get( 'wgSearchType' ) == 'SMWSearch' ) {
		smw.load( namespace );
	};

} )( jQuery, mediaWiki, semanticMediaWiki );
