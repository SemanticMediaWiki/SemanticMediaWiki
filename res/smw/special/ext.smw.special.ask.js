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
 * @since 1.9
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author: Jeroen De Dauw <jeroendedauw at gmail dot com>
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * @since 3.0
	 * @constructor
	 *
	 * @return {this}
	 */
	var change = function ( name ) {

		this.name = name;
		this.messages = {};

		this.html = mw.html;

		this.hideList = '#ask-embed, #inlinequeryembed, #ask-showhide,' +
		'#ask-debug, #ask-clipboard, #ask-navinfo, #ask-cache, #result,' +
		'#result-error, #ask-pagination, #ask-export-links, #tab-label-smw-askt-compact,' +
		'#tab-label-smw-askt-code, #tab-label-smw-askt-debug, #tab-label-smw-askt-extra,' +
		'#tab-label-smw-askt-result, #tab-label-smw-askt-clipboard, #search';

		return this;
	};

	/**
	 * @since 3.0
	 * @method
	 *
	 * @param {Sting} key
	 * @param {Sting} message
	 */
	change.prototype.add = function( key, message, type ) {
		this.messages[key] = [ message, type ];
	}

	/**
	 * @since 3.0
	 * @method
	 *
	 * @param {Sting} key
	 */
	change.prototype.delete = function( key ) {
		delete this.messages[key];
	}

	/**
	 * @since 3.0
	 * @method
	 *
	 * @param {Sting} key
	 */
	change.prototype.informAbout = function( key ) {

		var informAbout = '';

		// Anything to inform about?
		if ( this.messages.hasOwnProperty( key ) ) {
			informAbout = key;
		} else {
			for( var prop in this.messages ) {
				if ( prop !== key && this.messages.hasOwnProperty( prop ) ) {
					informAbout = prop
				}
			}
		}

		if ( informAbout !== '' ) {
			this.show( informAbout );
		} else {
			this.hide();
		}
	}

	/**
	 * @since 3.0
	 * @method
	 *
	 * @param {Sting} key
	 */
	change.prototype.show = function( key ) {

		var msg = this.messages[key];

		var html = this.html.element(
			'div',
			{
				id: 'status-format-change',
				class: 'smw-callout smw-callout-' + msg[1]
			},
			mw.msg( msg[0], this.name )
		);

		$( this.hideList ).hide();

		$( '#status-format-change' ).remove();
		$( '#ask-change-info' ).append( html );
	}

	/**
	 * @since 3.0
	 * @method
	 */
	change.prototype.hide = function() {

		$( this.hideList ).show();
		$( '#status-format-change' ).remove();

		$( '#inlinequeryembed, #embed_hide' ).hide();
		$( '#embed_show' ).show();
	}

	/**
	 * Support and helper methods
	 * @ignore
	 */
	var tooltip = smw.Factory.newTooltip();

	var _init = {

		// Tooltip
		tooltip: function(){
			$( '.smw-ask-info' ).each( function(){
				tooltip.show( {
					context: $( this ),
					content: $( this ).data( 'info' ),
					title  : mw.msg( 'smw-ui-tooltip-title-parameter' ),
					button : false
				} );
			} );

			$( '#options-list' ).trigger( 'smw.autocomplete.propertysubject', { context: $( '#options-list' ) } );
			$( '#options-list' ).trigger( 'smw.autocomplete.property', { context: $( '#options-list' ) } );
		},

		// Format help link
		formatHelp: function( options ){
			$( '.smw-ask-format-help-link' ).replaceWith(
				'<li class="smw-ask-format-help-link">'+ mw.msg( 'smw-ask-format-selection-help', addFormatHelpLink( options ) )  + '</li>'
			);
		}
	};

	/**
	 * Add format help link
	 *
	 * We do not try to be smart here but using a pragmatic approach to generate
	 * the URL by assuming Help:<format> format
	 *
	 * @return object
	 */
	function addFormatHelpLink ( options ){
		var h = mw.html,
			link = h.element( 'a', {
					href: 'https://semantic-mediawiki.org/wiki/Help:' + options.format + ' format',
					title: options.name
				}, options.name
			);
		return link;
	}

	/**
	 * Multiple sorting
	 */
	var num_elements = $( '#sorting_main > div' ).length;

	function addSortInstance(starter_div_id, main_div_id) {
		num_elements++;

		var starter_div = $( '#' + starter_div_id),
		main_div = $( '#' + main_div_id),
		new_div = starter_div.clone();

		new_div.attr( {
			'class': 'smw-ask-sort-input multipleTemplate',
			'id': 'sort_div_' + num_elements
		} );

		new_div.css( 'display', 'block' );

		//Create 'delete' link
		var button = $( '<a>').attr( {
			'class': 'smw-ask-sort-delete-action',
			'data-target': 'sort_div_' + num_elements
		} ).text( mw.msg( 'smw-ask-delete' ) );

		// Register event on the added instance
		button.click( function( event ) {
			removeInstance( $( this ).data( 'target' ) );
		} );

		new_div.append(
			$( '<span class="smw-ask-sort-delete">' ).html( button )
		);

		// Trigger an event to ensure that the input field has an autocomplete
		// instance attached
		main_div.trigger( 'SMW::Property::Autocomplete' , {
			'context': new_div
		} );

		// Add the new instance
		main_div.append( new_div );
	}

	function removeInstance(div_id) {
		$( '#' + div_id ).remove();
	}

	/**
	 * Implementation of an Special:Ask instance
	 * @since 1.8
	 * @ignore
	 */
	$( document ).ready( function() {

		var condition = $( '#ask-query-condition' ),
			condVal = '',
			isEmpty = '';

		if ( condition.length ) {

			condVal = condition.val().trim(),
			isEmpty = condVal === '';

			var entitySuggester = smw.Factory.newEntitySuggester(
				condition
			);

			// Register autocomplete default tokens
			entitySuggester.registerDefaultTokenList(
				[
					'property',
					'concept',
					'category'
				]
			);
		};

		// Field input is kept disabled until JS is fully loaded to signal
		// "ready for input"
		$( '#ask, #result' ).removeClass( 'is-disabled' );

		// Get initial format and language settings
		var selected = $( '#formatSelector option:selected' ),
			options = {
				selector : '#formatSelector',
				format : selected.val(),
				name : selected.text(),
				isExport: selected.data( 'isexport' ) == 1
			};

		var chg = new change( options['name'] );

		// Init
		_init.tooltip();
		_init.formatHelp( options );

		$( '.smw-ask-sort-delete-action' ).click( function() {
			removeInstance( $( this).data( 'target' ) );
		} );

		$( '.smw-ask-sort-add-action' ).click( function() {
			addSortInstance( 'sorting_starter', 'sorting_main' );
		} );

		// Options toggle icon
		$( '.options-toggle-action label' ).click( function() {
			if ( $( '#options-toggle' ).prop( 'checked' ) ) {
				$( this ).html( '+' );
				$( this ).attr( 'title', mw.msg( 'smw-section-expand' ) );
			} else {
				$( this ).html( '-' );
				$( this ).attr( 'title', mw.msg( 'smw-section-collapse' ) );
			}
		} );

		// `CTRL + q` shortcut support to invoke a query/search with a keystroke
		// Char check for `q` means in:
		// - Chrome: event.charCode == 17
		// - FF: event.charCode == 113
		$( "#mw-content-text" ).keypress( function ( event ) {
			if ( event.ctrlKey && ( event.charCode == 17 || event.charCode == 113 ) ) {
				$( '#search-action' ).click();
				event.preventDefault();
				return false;
			};

			return true;
		} );

		// Changed condition
		$( '#ask-query-condition' ).change( function( event, $opts ) {

			var $this = $( this );

			if ( isEmpty === false && $this.val().trim() !== condVal ) {
				chg.add( 'condition', 'smw-ask-condition-change-info', 'warning' );
			} else {
				chg.delete( 'condition' );
			}

			chg.informAbout( 'condition' );
		} );

		// Change format parameter form via ajax
		$( '#formatSelector' ).change( function( event, $opts ) {

			var $this = $( this ),
				isExport = $this.find( 'option:selected' ).data( 'isexport' ) == 1;

			// Opaque options list for as long as the list is being generated
			// via an ajax request
			$( '#options-list' ).addClass( 'is-disabled' );

			if ( isExport ) {
				chg.add( 'format', 'smw-ask-format-export-info', 'info' );
			} else if ( isEmpty === false && options['format'] !== $this.val() ) {
				chg.add( 'format', 'smw-ask-format-change-info', 'warning' );
			} else {
				chg.delete( 'format' )
			}

			chg.informAbout( 'format' );

			$.ajax( {
				// Evil hack to get more evil Spcial:Ask stuff to work with less evil JS.
				'url': $this.data( 'url' ).replace( 'this.value',  $this.val() ),
				'context': document.body,
				'success': function( data ) {
					$( '#options-list' ).html(
						'<div class="options-parameter-list">' + data + '</div>'
					);

					// Remove disable state that was set at the beginning of the
					// onChange event
					$( '#options-list' ).removeClass( 'is-disabled' );

					// Reinitialize functions after each ajax request
					_init.tooltip();

					// Update format created by the ajax instance
					_init.formatHelp( $.extend( {}, options, {
						format:  $this.val(),
						name: $this.find( 'option:selected' ).text()
					} ) );
				}
			} );
		} );
	} );
} )( jQuery, mediaWiki, semanticMediaWiki );
