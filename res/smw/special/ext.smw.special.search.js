/*!
 * This file is part of the Semantic MediaWiki
 *
 * @section LICENSE
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
( function( $, mw ) {
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
			context.on( 'keyup keypres focus', function( e ) {
				var highlighter = context.parent().find( '.oo-ui-widget' ),
					style = '';

				if ( context.val().indexOf( '[' ) > -1 ) {
					style = highlighter.attr( 'style' );
					highlighter.hide();
					isHidden = true;
				} else if( isHidden ) {
					highlighter.attr( 'style', style );
					highlighter.show();
					isHidden = false;
				};
			} );
		}
	};

	function load( callback ) {
		if ( document.readyState == 'complete' ) {
			callback();
		} else {
			window.addEventListener( 'load', callback );
		}
	}

	// Only load when it is Special:Search and the search type supports
	// https://www.semantic-mediawiki.org/wiki/Help:SMWSearch
	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) == 'Search' && mw.config.get( 'wgSearchType' ) == 'SMWSearch' ) {
		load( search );
	};

} )( jQuery, mediaWiki );
