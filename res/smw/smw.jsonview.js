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

	s.init = function( container, json ) {

		// https://github.com/yesmeck/jquery-jsonview
		container.JSONView( json, { collapsed: true } )
		container.JSONView( 'toggle', 1 );

		container.find( '.jsonview' ).before(
			'<div class="smw-jsonview-button-group">' +
			'<button id="smw-jsonview-copy-btn" title="' + mw.msg( 'smw-copy-clipboard-title' ) + '" class="smw-jsonview-button">' + mw.msg( 'smw-copy' ) + '</button>' +
			'<button id="smw-jsonview-toggle-btn"title="' + mw.msg( 'smw-jsonview-expand-title' ) + '" class="smw-jsonview-button">' + mw.msg( 'smw-expand' ) + '</button>' +
			'</div>'
		);

		$( "#smw-jsonview-copy-btn" ).on('click', function() {
			s.copyToClipboard( json );
		} );

		$( "#smw-jsonview-toggle-btn" ).on('click', function() {
			s.toggle( $( this ), container );
		} );
	}

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
			container.JSONView( 'toggle', 2 );
			context.data( 'type', 'expand' );
			context.text( mw.msg( 'smw-expand' ) );
			context.prop('title', mw.msg( 'smw-jsonview-expand-title' ) );
		} else {
			context.data( 'type', 'collapse' );
			context.text( mw.msg( 'smw-collapse' ) );
			context.prop('title', mw.msg( 'smw-jsonview-collapse-title' ) );
			container.JSONView('expand' );
		}
	}

	return s;
}( mediaWiki ) );

window.smw.jsonview = jsonview;
