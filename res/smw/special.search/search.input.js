/*!
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Support text input on Special:Search
	 *
	 * @since 3.0
	 */
	var search = function() {

		var context = $( '#searchText > input' ),
			isHidden = false;

		if ( context.length ) {

			// Disable the standard autocompleter as no meaningfull help can be
			// expected on a [[ ... ]] input
			context.on( 'keyup keypres mouseenter', function( e ) {

				// MW 1.27 - MW 1.31
				var highlighter = context.parent().find( '.oo-ui-widget' );

				// MW 1.32+
				if ( highlighter.length == 0 ) {
					highlighter = $( '.oo-ui-defaultOverlay > .oo-ui-widget' );
				};

				// Disable (hide) the MW's search input highlighter
				if ( context.val().search( /\[|\[\[|in:|not:|has:|phrase:|::/gi ) > -1 ) {
					highlighter.hide();
					isHidden = true;
				} else if( isHidden ) {
					isHidden = false;
					highlighter.show();
				};
			} );
		}
	};

	// Only load when it is Special:Search and the search type supports
	// https://www.semantic-mediawiki.org/wiki/Help:SMWSearch
	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) == 'Search' && mw.config.get( 'wgSearchType' ) == 'SMWSearch' ) {
		smw.load( search );
	};

} )( jQuery, mediaWiki, semanticMediaWiki );
