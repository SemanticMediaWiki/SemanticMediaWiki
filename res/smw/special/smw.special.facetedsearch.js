/**
 * This file is part of the Semantic MediaWiki Special:Ask module
 * @see https://semantic-mediawiki.org/
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
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @ignore
 *
 * @since 3.2
 *
 * @licence GNU GPL v2+
 *
 * @author mwjames
 */
( function( $, mw ) {
	'use strict';

	/**
	 * Using Vue only works with !! MW 1.35+ !!
	 *
	 * @see https://www.mediawiki.org/wiki/Vue.js
	 */
	/*
	var Vue = require( 'vue' );

	new new Vue({
	  el: '#app',
	  data: {
	    todos: [
	      { text: 'Learn JavaScript' },
	      { text: 'Learn Vue.js' },
	      { text: 'Build Something Awesome' }
	    ]
	  }
	})
	*/

	// https://codippa.com/how-to-get-url-parameters-in-javascript/
	function getQueryParams() {
		// initialize an empty object
		let result = {};
		// get URL query string
		let params = decodeURIComponent( window.location.search );
		// remove the '?' character
		params = params.substr(1);
		let queryParamArray = params.split('&');
		// iterate over parameter array
		queryParamArray.forEach(function(queryParam) {
		// split the query parameter over '='
		let item = queryParam.split("=");
		result[item[0]] = decodeURIComponent(item[1]);
		});
		// return result object
		return result;
	}

	/**
	 * Implementation of an Special:FacetedSearch instance
	 *
	 * @since 1.8
	 * @ignore
	 */
	$( document ).ready( function() {

		// Field input is kept disabled until JS is fully loaded to signal
		// "ready for input"
		$( '#smw-factedsearch-content-overlay' ).hide();
		$( '.filter-card .smw-overlay-spinner' ).hide();
		$( '.smw-factedsearch-content' ).removeClass( 'is-disabled' );

		/**
		 * Disable the screen when a new filter is apllied or altered
		 */
		$( ".filter-uncheck-all > a, .filter-item-unlink > a, .filter-item-link > a" ).on( 'click', function() {
			$( '.smw-factedsearch' ).addClass( 'is-disabled' );
		} );

		$( ".checkbox, .reset-button, .button-link, .search-button" ).on( 'click', function() {
			$( '.smw-factedsearch' ).addClass( 'is-disabled' );
		} );

		$( ".options-field > select, .filter-items-option * > select" ).on( 'change', function() {
			$( '.smw-factedsearch' ).addClass( 'is-disabled' );
		} );

		// Allow the click on the title to trigger the collapsible/expand event
		$( ".filter-card-title" ).on( 'click', function() {
			$( this ).parent().find( '.mw-collapsible-toggle' ).trigger( 'click' );
		} );

		$( ".mw-collapsible-toggle" ).on( 'click', function() {
			var collapsible = $( this );
			var state = collapsible[0].className.indexOf( 'collapsed' ) > -1 ? 'c' : 'e'
			var name = 'cstate[' + $( this ).parent().prop( 'id' ) + ']';
			var input = $( 'form' ).find( "input[name='" + name + "']" );

			if ( input.length > 0 ) {
				input.attr( 'name', name ).attr( 'value', state );
			} else {
				$('<input>').attr( 'name', name ).attr( 'type', 'hidden' ).attr( 'value', state ).appendTo('form');
			}
		} );

		/**
		 * @see https://www.w3schools.com/howto/howto_js_filter_lists.asp
		 * Filter the input of an individual filter list
		 */
		$( ".filter-item-input > input,.filter-items-input > input" ).on( 'keyup', function() {

			var filter = $( this ).val().toUpperCase();
			var parent = $( this ).closest( '.filter-card-content' );

			// For those displayed as checkboxes
			parent.find( ".filter-items * > .checkbox * > .filter-item-label" ).each( function(){
				var txtValue = $( this )[0].textContent || $( this )[0].innerText;

				if ( txtValue.toUpperCase().indexOf( filter ) > -1 ) {
					$( this ).parent().parent().show();
				} else {
					$( this ).parent().parent().hide();
				}
			} );

			// For those displayed as tree
			parent.find( ".filter-items.tree * > li" ).each( function(){
				var txtValue = '';

				if ( $( this ).find( '.filter-item-label' ).length > 0 ) {
					txtValue = $( this ).find( '.filter-item-label' )[0].textContent;
				} else if ( $( this ).find( '.button-link' ).length > 0 ) {
					txtValue = $( this )[0].textContent;
				} else if ( $( this ).find( 'a' ).length > 0 ) {
					var a = $( this ).find( 'a' )[0];
					txtValue = a.textContent || a.innerText;
				}

				if ( txtValue.toUpperCase().indexOf( filter ) > -1 ) {
					$( this ).find( '.filter-item' ).show();
				} else {
					$( this ).find( '.filter-item' ).hide();
				}
			} );

			// For those displayed as links
			parent.find( ".filter-items:not(.tree) * > li" ).each( function() {
				var txtValue = '';

				if ( $( this ).find( '.filter-item-label' ).length > 0 ) {
					txtValue = $( this ).find( '.filter-item-label' )[0].textContent;
				} else if ( $( this ).find( '.button-link' ).length > 0 ) {
					txtValue = $( this )[0].textContent;
				} else if ( $( this ).find( 'a' ).length > 0 ) {
					var a = $( this ).find( 'a' )[0];
					txtValue = a.textContent || a.innerText;
				}

				if ( txtValue.toUpperCase().indexOf( filter ) > -1 ) {
					$( this ).show();
				} else {
					$( this ).hide();
				}
			} );

		} );

		// https://stackoverflow.com/questions/2977023/how-do-you-detect-the-clearing-of-a-search-html5-input
		$('.filter-item-input > input,.filter-items-input > input' ).on( 'search', function ( event ) {
			$( this ).closest( '.filter-card-content' ).find( '.filter-item,.filter-items * > li' ).show();
		});

		$( '.range-filter' ).each( function() {
			var that = $( this );

			that.ionRangeSlider( {
				type: "double",
				min: 0,
				max: 100,
				step: 1,
				from: 0,
				to: 100,
			//	values: values,
				force_edges: true,
				input_values_separator: "|",
				postfix: '',
				min_interval: 1,
				grid: true,
				grid_num: 2,
				onChange: function ( data ) {
					//
				},
				onFinish: function ( data ) {
					document.getElementById( 'search-input-form' ).submit();
				}
			} );
		} );
	} );

} )( jQuery, mediaWiki );
