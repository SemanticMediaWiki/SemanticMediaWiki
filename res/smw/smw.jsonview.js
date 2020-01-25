/*!
 * This file is part of the Semantic MediaWiki
 *
 * @since 3.1
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/*global jQuery, mediaWiki, smw */
/*jslint white: true */

var jsonview = ( function( mw ) {

	var s = {};

	var currentClass = "current",
		// top offset for the jump (the search bar)
		offsetTop = 50,
		// the current index of the focused element
		currentIndex = 0;

	s.init = function( container, json ) {

		// https://github.com/yesmeck/jquery-jsonview
		container.JSONView( json, { collapsed: true } )
		container.JSONView( 'toggle', 1 );

		if ( container.data( 'level' ) !== undefined ) {
			container.JSONView( 'expand', container.data( 'level' ) );
		};

		if ( container.prev().hasClass( 'smw-jsonview-menu' ) ) {
			container.prev().css( 'display', 'block' );
			container.css( 'margin-top', '0' );
			container.css( 'opacity', '1' );

			container.prev().append(
				'<div class="smw-jsonview-button-group-left">' +
				'<span class="smw-jsonview-search-label">' + mw.msg( 'smw-jsonview-search-label' ) + '</span><input id="smw-jsonview-search" class="smw-jsonview-search" type="search" placeholder="..." align="middle">' +
				'</div>'
			);

			container.prev().append(
				'<div class="smw-jsonview-button-group-right">' +
				'<button id="smw-jsonview-copy-btn" title="' + mw.msg( 'smw-copy-clipboard-title' ) + '" class="smw-jsonview-button">' + '<span class="smw-jsonview-clipboard"></span>' + '</button>' +
				'<button id="smw-jsonview-toggle-btn"title="' + mw.msg( 'smw-jsonview-expand-title' ) + '" class="smw-jsonview-button"><span class="smw-jsonview-toogle smw-jsonview-expand">' + '+' + '</span></button>' +
				'</div>'
			);
		} else {
			container.find( '.jsonview' ).before(
				'<div class="smw-jsonview-button-group-right">' +
				'<button id="smw-jsonview-copy-btn" title="' + mw.msg( 'smw-copy-clipboard-title' ) + '" class="smw-jsonview-button">' + '<span class="smw-jsonview-clipboard"></span>' + '</button>' +
				'<button id="smw-jsonview-toggle-btn"title="' + mw.msg( 'smw-jsonview-expand-title' ) + '" class="smw-jsonview-button"><span class="smw-jsonview-toogle smw-jsonview-expand">' + '+' + '</span></button>' +
				'</div>'
			);
		}

		$( ".smw-jsonview-search" ).on( 'input', function() {
			s.findAndMarkSearch( this.value, container );
		} );

		$( ".smw-jsonview-clipboard" ).on('click', function() {
			s.copyToClipboard( json );
		} );

		$( ".smw-jsonview-toogle" ).on('click', function() {
			s.toggle( $( this ), container );
		} );
	}

	s.findAndMarkSearch = function( searchVal, container ) {
		container.unmark( {
			done: function() {
				container.mark( searchVal, {
					separateWordSearch: true,
					done: function() {
						var results = container.find("mark");
						currentIndex = 0;

						if ( results.length ) {
							var position,
								current = results.eq( currentIndex );
								results.removeClass( currentClass );
							if ( current.length ) {
								current.addClass( currentClass );
								position = current.offset().top - offsetTop;
								window.scrollTo( 0, position );
							}
						}
					}
				} );
			}
		} );
	};

	s.copyToClipboard = function( json ) {
		var copyElement = document.createElement( 'input' );

		copyElement.setAttribute('type', 'text');
		copyElement.setAttribute('value', JSON.stringify( JSON.parse( json ) ) );
		copyElement = document.body.appendChild( copyElement );
		copyElement.select();

		document.execCommand( 'copy' );
		copyElement.remove();
	}

	s.toggle = function( context, container ) {
		if ( context.data( 'type' ) === 'collapse' ) {
			context.data( 'type', 'expand' );

			if ( container.data( 'level' ) !== undefined ) {
				container.JSONView( 'toggle', container.data( 'level' ) + 1 );
			} else {
				container.JSONView( 'toggle', 2 );
			}

			context.text( '+' );
			context.prop('title', mw.msg( 'smw-jsonview-expand-title' ) );
		} else {
			context.data( 'type', 'collapse' );
			context.text( '−' );
			context.prop('title', mw.msg( 'smw-jsonview-collapse-title' ) );
			container.JSONView('expand' );
		}
	}

	return s;
}( mediaWiki ) );

window.smw.jsonview = jsonview;
