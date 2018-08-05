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

		$( this ).on( "click", "#mw-search-toggleall", function(){
			$checkboxes.prop( 'checked', true );
		} );

		$( this ).on( "click", "#mw-search-toggleall", function(){
			$checkboxes.prop( 'checked', true );
		} );

		$( this ).on( "click", "#mw-search-toggleall", function(){
			$checkboxes.prop( 'checked', true );
		} );

		$( this ).on( "click", "#mw-search-togglenone", function(){
			$checkboxes.prop( 'checked', false );
		} );

		// When saving settings, use the proper request method (POST instead of GET).
		$( this ).on( "change", "#mw-search-powersearch-remember", function() {
			this.form.method = this.checked ? 'post' : 'get';
		} ).trigger( 'change' );

		var nsList = $( '#ns-list' ).css( 'display' ) !== 'none' ? 'Hide': 'Show';

		/**
		 * Append hide/show button to the NS section
		 */
		$( '#smw-search-togglensview' ).append(
			$( '<input>' ).attr( 'type', 'button' )
				.attr( 'id', 'smw-togglensview' )
				.prop( 'value', nsList )
				.click( function ( event ) {

					// We carry the hidden `ns-list` on a submit so the status
					// of the prevsious acion is retained to either show or hide
					// the section
					if ( $( '#ns-list' ).css( 'display' ) !== 'none' ) {
						$( 'input[name=ns-list]' ).attr( 'value', 1 );
						event.target.value = 'Show';
						$( '#ns-list' ).css( 'display', 'none' );
					} else {
						event.target.value = 'Hide';
						$( 'input[name=ns-list]' ).attr( 'value', 0 );
						$( '#ns-list' ).css( 'display', 'block' );
					}
				} )
		)

	};

	// Only load when it is Special:Search and the search type supports
	// https://www.semantic-mediawiki.org/wiki/Help:SMWSearch
	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) == 'Search' && mw.config.get( 'wgSearchType' ) == 'SMWSearch' ) {
		smw.load( namespace );
	};

} )( jQuery, mediaWiki, semanticMediaWiki );
