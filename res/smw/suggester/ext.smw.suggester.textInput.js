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
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Simple semantic links autocompleter.
	 *
	 * Only when the registered marker are used will the search be activated
	 * to avoid arbitrary matches for non-semantic content.
	 */
	mw.loader.using( [ 'ext.smw.suggester' ], function() {

		/**
		 * Support text input on Special:Search
		 *
		 * @since 3.0
		 */
		var search = function() {

			var context = $( '#searchText > input' );

			if ( context.length ) {
				smw.log( 'autocomplete.Input: search field registration' );

				// This features is only enabled for SMWSearch hence when the input
				// field contains [[ ... ]] we assume a search via the QueryEngine
				// therefore disable the highlighlter as no meaningfull input help
				// will be shown
				context.on( 'keyup keypres focus', function( e ) {
					var highlighter = context.parent().find( '.oo-ui-widget' );

					if ( context.val().indexOf( '[' ) > -1 ) {
						highlighter.hide();
					} else {
						highlighter.show();
					};
				} );

				var searchSuggester = smw.Factory.newSearchSuggester(
					context
				);

				// Register autocomplete default tokens
				searchSuggester.registerDefaultTokenList(
					[
						'property',
						'concept',
						'category'
					]
				);
			};
		};

		/**
		 * Support text input on the wikiEditor textbox
		 *
		 * @since 3.0
		 */
		var wikiEditor = function() {

			var wpTextbox1 = $( '#wpTextbox1' );

			// Attach to the textarea
			if ( wpTextbox1.length ) {
				smw.log( 'autocomplete.Input: textbox1 field registration' );

				var searchSuggester = smw.Factory.newSearchSuggester(
					wpTextbox1
				);

				// Register autocomplete default tokens
				searchSuggester.registerDefaultTokenList(
					[
						'property',
						'concept',
						'category'
					]
				);

				// Register additional definition since the editing can involve
				// different patterns

				// Used in combination with printouts as in:
				//
				// {{#ask: ..
				//  |?p: ...
				// }}
				searchSuggester.registerTokenDefinition(
					'property',
					{
						token: '?p:',
						beforeInsert: function( token, value ) {
							return value.replace( 'p:', '' );
						}
					}
				);

				// Used in combination with #set and #subobject as in:
				//
				// {{#set:
				//  |p: ...
				// }}
				searchSuggester.registerTokenDefinition(
					'property',
					{
						token: '|p:',
						beforeInsert: function( token, value ) {
							return value.replace( 'p:', '' ) + '=';
						}
					}
				);
			};
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

		var wgAction = mw.config.get( 'wgAction' );

		if ( ( wgAction == 'edit' || wgAction == 'submit' ) && mw.config.get( 'wgPageContentModel' ) == 'wikitext'  ) {
			load( wikiEditor );
		};
	} );

} )( jQuery, mediaWiki, semanticMediaWiki );
