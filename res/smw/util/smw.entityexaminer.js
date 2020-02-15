/*!
 * This file is part of the Semantic MediaWiki Reload module
 * @see https://www.semantic-mediawiki.org/wiki/Help:Purge
 *
 * @since 3.0
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/*global jQuery, mediaWiki, smw */
/*jslint white: true */

( function( $, mw ) {

	'use strict';

	/**
	 * @since 3.0
	 */
	mw.loader.using( [ 'mediawiki.api', 'smw.tippy', 'ext.smw.style' ] ).then( function () {

		$( '.smw-entity-examiner.smw-indicator-vertical-bar-loader' ).each( function() {

			var self = $( this );
			var api = new mw.Api();
			var subject = $( this ).data( 'subject' );

			if ( subject !== undefined && subject !== '' ) {

				var params = {
					'subject': subject,
					'is_placeholder': true,
					'dir': $( this ).data( 'dir' ),
					'count': $( this ).data( 'count' )
				};

				var postArgs = {
					'action': 'smwtask',
					'task': 'run-entity-examiner',
					'params': JSON.stringify( params )
				};

				api.postWithToken( 'csrf', postArgs ).then( function ( data ) {

					self.replaceWith( data.task.html['smw-entity-examiner'] );
					self.find( '.is-disabled' ).removeClass( 'is-disabled' );

					// Enable the `mw-indicator-mw-helplink` in case it was disabled
					if (
						data.task.html['smw-entity-examiner'] === undefined &&
						document.getElementById( 'mw-indicator-mw-helplink' ) !== null ) {
						document.getElementById( 'mw-indicator-mw-helplink' ).style.display = 'inline-block';
					};
				} );
			}

		} );

		$( '#mw-indicator-smw-entity-examiner > .smw-highlighter' ).each( function() {

			if ( $( this ).data( 'deferred' ) !== 'yes' ) {
				return;
			};

			var self = $( this );
			var api = new mw.Api();
			var subject = $( this ).data( 'subject' );
			var tooltipWasObserved = false;
			var addedResponse = false;
			var consistencyCheckData = null;

			var tooltipReferenceElement = document.getElementById( 'mw-indicator-smw-entity-examiner' );

			var config = { attributes: true, childList: true, subtree: true };

			var callback = function( mutationsList, observer) {

			    for( let mutation of mutationsList ) {
			        if (
						consistencyCheckData !== null &&
						addedResponse === false &&
						mutation.type === 'attributes' &&
						mutation.attributeName === 'aria-describedby') {

					for ( var key in consistencyCheckData.task['indicators'] ) {
						var el = $( '#' + key );

						if ( consistencyCheckData.task['indicators'][key].content === '' ) {
							$('label[for="' + 'itab' + key + '"]').hide();
						} else if( consistencyCheckData.task['indicators'][key].severity_class !== '' ) {
							$('label[for="' + 'itab' + key + '"]').addClass( consistencyCheckData.task['indicators'][key].severity_class );
						}

						if ( el.length > 0 ) {
							el.replaceWith( consistencyCheckData.task['indicators'][key].content );
							addedResponse = true
						};
					};
				};

				tooltipWasObserved = true;

				if ( addedResponse === true ) {
					observer.disconnect();
				};
			}
		};

		var observer = new MutationObserver(callback);

		observer.observe( tooltipReferenceElement, config);

		if ( subject !== undefined && subject !== '' ) {

			var params = {
				'subject': subject,
				'dir': $( this ).data( 'dir' ),
				'count': $( this ).data( 'count' ),
				'options': $( this ).data( 'options' )
			};

			var postArgs = {
				'action': 'smwtask',
				'task': 'run-entity-examiner',
				'params': JSON.stringify( params )
			};

			api.postWithToken( 'csrf', postArgs ).then( function ( data ) {
				consistencyCheckData = data;

				if ( tooltipWasObserved && addedResponse === false ) {
					for ( var key in consistencyCheckData.task['indicators'] ) {
						var el = $( '#' + key );

						if ( consistencyCheckData.task['indicators'][key].content === '' ) {
							$('label[for="' + 'itab' + key + '"]').hide();
						} else if( consistencyCheckData.task['indicators'][key].severity_class !== '' ) {
							$('label[for="' + 'itab' + key + '"]').addClass( consistencyCheckData.task['indicators'][key].severity_class );
						}

						if ( el.length > 0 ) {
							el.replaceWith( consistencyCheckData.task['indicators'][key].content );
							addedResponse = true
						};
					};
				};

				// Enable the `mw-indicator-mw-helplink` in case it was disabled
				if ( data.task.html === '' && document.getElementById( 'mw-indicator-mw-helplink' ) !== null ) {
					document.getElementById( 'mw-indicator-mw-helplink' ).style.display = 'inline-block';
				};
			} );

			}

		} );

	} );

}( jQuery, mediaWiki ) );
