/**
 * JavaScript for SMW autocomplete functionality
 *
 * @see https://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki
 *
 * @since 1.8
 * @release 0.1
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
( function( mw, $ ) {
	"use strict";
	/*global mediaWiki:true*/

	/**
	 * Default options
	 *
	 */
	var defaults = {
			limit: 10,
			separator: null,
			search: 'property',
			namespace: mw.config.get( 'wgNamespaceIds' ).property
	};

	/**
	 * Handle autocomplete function for various instances
	 *
	 * @var options
	 *
	 * @since: 1.8
	 */
	$.fn.smwAutocomplete = function( options ){

		// Merge defaults and options
		var options = $.extend( {}, defaults, options );

		// Specify regular expression
		var regex = new RegExp( options.separator , 'mi' );

		// Helper functions
		function split( val ) {
			return val.split( regex );
		}

		function extractLast( term ) {
			return split( term ).pop();
		}

		function escapeQuestion(term){
			if ( term.substring(0, 1) === "?" ) {
				return term.substring(1);
			} else {
				return term;
			}
		}

		// Extending jQuery functions for custom highligting
		$.ui.autocomplete.prototype._renderItem = function( ul, item ) {
			var term_without_q = escapeQuestion(extractLast( this.term ));
			var re = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term_without_q.replace("/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi", "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi");
			var loc = item.label.search(re),
				t = '';
			if (loc >= 0) {
				t = item.label.substr(0, loc) + '<strong>' + item.label.substr(loc, term_without_q.length) + '</strong>' + item.label.substr(loc + term_without_q.length);
			} else {
				t = item.label;
			}
			$( "<li></li>" )
				.data( "item.autocomplete", item )
				.append( " <a>" + t + "</a>" )
				.appendTo( ul );
		};

		// Extending jquery functions for custom autocomplete matching
		$.extend( $.ui.autocomplete, {
			filter: function(array, term) {
				var matcher = new RegExp( "\\\b" + $.ui.autocomplete.escapeRegex(term), "i" );
				return $.grep( array, function(value) {
					return matcher.test( value.label || value.value || value );
				});
			}
		} );

		// Autocomplete core
		this.autocomplete( {
			minLength: 2,
			source: function(request, response) {
				$.getJSON(
					mw.config.get( 'wgScriptPath' ) + '/api.php',
					{
						'action': 'opensearch',
						'format': 'json',
						'limit': options.limit,
						'namespace': options.namespace ,
						'search': extractLast( request.term )
					},
					function( data ){

						if ( data.error === undefined ) {
							//remove the word 'Property:' from returned data
							if ( options.search === 'property' ){
								for( var i=0; i < data[1].length; i++ ) {
									data[1][i]= data[1][i].substr( data[1][i].indexOf( ':' ) + 1 );
								}
							}
							response( data[1] );
						} else {
							response ( false );
						}
					}
				);
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = this.value;
				terms = split( terms );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				// add placeholder to get the comma-and-space at the end
				terms.push("");
				this.value = terms.join( options.separator !== null ? options.separator : '' );
				return false;
			}
		} );
	};

} )( mediaWiki, jQuery );
