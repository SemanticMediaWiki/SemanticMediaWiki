/*!
 * @license GNU GPL v2+
 * @since  3.2
 *
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * @since 3.2
	 * @class
	 */
	smw.entityexaminer = {};

	/**
	 * @since 3.2
	 *
	 * @class
	 * @constructor
	 */
	smw.entityexaminer = function () {

		this.userLanguage = mw.config.get( 'wgUserLanguage' );
		this.api = new mw.Api();
		this.tooltipWasObserved = false;
		this.addedResponse = false;
		this.examinationData = null;

		return this;
	};

	/* Public methods */

	smw.entityexaminer.prototype = {

		/**
		 * Enable the `mw-indicator-mw-helplink` in case it was disabled
		 *
		 * @since 3.2
		 * @method
		 */
		showHelpLink: function() {
			if ( document.getElementById( 'mw-indicator-mw-helplink' ) !== null ) {
				document.getElementById( 'mw-indicator-mw-helplink' ).style.display = 'inline-block';
			};
		},

		/**
		 * @since 3.2
		 * @method
		 *
		 * @param {Object} mutations
		 */
		subscriber: function ( mutations, observer ) {

			var self = this;

			mutations.forEach( ( mutation ) => {

				if (
					self.examinationData !== null &&
					self.addedResponse === false &&
					mutation.type === 'attributes' &&
					mutation.attributeName === 'aria-describedby') {

					for ( var key in self.examinationData.task['indicators'] ) {
						var el = $( '#' + key );

						if ( self.examinationData.task['indicators'][key].content === '' ) {
							$('label[for="' + 'itab' + key + '"]').hide();
							el.hide();
						} else if( self.examinationData.task['indicators'][key].severity_class !== '' ) {
							$('label[for="' + 'itab' + key + '"]').addClass( self.examinationData.task['indicators'][key].severity_class );
						}

						if ( el.length > 0 && self.examinationData.task['indicators'][key].content !== '' ) {
							el.replaceWith( self.examinationData.task['indicators'][key].content );
							self.addedResponse = true
						};
					};

					self.tooltipWasObserved = true;

					if ( self.addedResponse === true ) {
						observer.disconnect();
					};
				}
			} );
		},

		/**
		 * @since 3.2
		 * @method
		 *
		 * @param {Object} subject
		 * @param {Object} params
		 */
		update: function ( subject, params ) {

			var self = this;

			if (
				subject === undefined ||
				subject === '' ) {
				return;
			}

			var postArgs = {
				'action': 'smwtask',
				'task': 'run-entity-examiner',
				'params': JSON.stringify( params )
			};

			this.api.postWithToken( 'csrf', postArgs ).then( function ( data ) {
				self.examinationData = data;

				for ( var key in self.examinationData.task['indicators'] ) {
					var el = $( '#' + key );

					if ( self.examinationData.task['indicators'][key].content === '' ) {
						var parent = $('label[for="' + 'itab' + key + '"]').parent();

						if ( parent.length > 0 ) {
							$( parent.find( 'input')[0] ).prop( 'checked', true );
						};

						$('label[for="' + 'itab' + key + '"]').hide();
						el.hide();
					} else if( self.examinationData.task['indicators'][key].severity_class !== '' ) {
						$('label[for="' + 'itab' + key + '"]').addClass( self.examinationData.task['indicators'][key].severity_class );
					}

					if ( el.length > 0 && self.examinationData.task['indicators'][key].content !== '' ) {
						el.replaceWith( self.examinationData.task['indicators'][key].content );
						self.addedResponse = true
					};
				};

				if ( data.task.html === '' ) {
					self.showHelpLink();
				};
			} );
		},

		/**
		 * @since 3.2
		 * @method
		 *
		 * @param {Object} subject
		 * @param {Object} params
		 */
		runOnPlaceholder: function ( context ) {

			var self = this;

			context.each( function() {

				var that = $( this );
				var subject = that.data( 'subject' );

				if (
					subject === undefined ||
					subject === '' ) {
					return;
				}

				var params = {
					'subject': subject,
					'is_placeholder': true,
					'dir': that.data( 'dir' ),
					'uselang': that.data( 'uselang' ),
					'count': that.data( 'count' )
				};

				var postArgs = {
					'action': 'smwtask',
					'task': 'run-entity-examiner',
					'params': JSON.stringify( params )
				};

				self.api.postWithToken( 'csrf', postArgs ).then( function ( data ) {

					// When run as placholder replacement, we expect the entire HTML
					// the be replaced therefore using the `html` accessor.
					that.replaceWith( data.task.html['smw-entity-examiner'] );
					that.find( '.is-disabled' ).removeClass( 'is-disabled' );

					if ( data.task.html['smw-entity-examiner'] === undefined ) {
						self.showHelpLink();
					}
				} );
			} );
		}
	};

	mw.loader.using( [ 'mediawiki.api', 'smw.tippy', 'ext.smw.style' ] ).then( function () {

		var entityexaminer = new smw.entityexaminer();

		var config = {
			attributes: true,
			childList: true,
			subtree: true
		};

		var tooltipReferenceElement = document.getElementById( 'mw-indicator-smw-entity-examiner' );

		// Run a replacement for the entire placeholder
		entityexaminer.runOnPlaceholder(
			$( '.smw-entity-examiner.smw-indicator-vertical-bar-loader' )
		);

		// Run on those examiners that have been marked as deferred and require
		// an update via the API
		$( '#mw-indicator-smw-entity-examiner > .smw-highlighter' ).each( function() {

			var that = $( this );

			if ( that.data( 'deferred' ) !== 'yes' ) {
				return;
			};

			var subject = that.data( 'subject' );

			// Attach an observer to update the specific section once the information
			// is returned from the API
			var observer = new MutationObserver( function( mutations, observer ) {
				return entityexaminer.subscriber( mutations, observer );
			} );

			observer.observe( tooltipReferenceElement, config );

			var params = {
				'subject': subject,
				'dir': that.data( 'dir' ),
				'uselang': that.data( 'uselang' ),
				'count': that.data( 'count' ),
				'options': that.data( 'options' )
			};

			entityexaminer.update( subject, params )
		} );
	} );

} )( jQuery, mediaWiki, semanticMediaWiki );
