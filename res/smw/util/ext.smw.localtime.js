/**
 * Converts server-rendered, wiki-local <time class="smw-localtime"> elements to
 * the viewing user's local time on the client, so parser-cached #ask output can
 * stay user-independent (#6820).
 *
 * @licence GNU GPL v2 or later
 */
( function ( mw ) {
	'use strict';

	mw.smw = mw.smw || {};

	var localtime = {
		/**
		 * Parse a MediaWiki `timecorrection` preference value.
		 *
		 * Note: an invalid ZoneInfo zone name is passed through here and later
		 * throws in Intl (caught in init, leaving the wiki-local fallback),
		 * whereas PHP falls back to the stored offset. MediaWiki validates the
		 * timezone on save, so this divergence is not reachable in practice.
		 *
		 * @param {string} tc
		 * @return {Object|null} { zone } or { minutes }, or null to fall back
		 *  to the browser zone.
		 */
		parseTimeCorrection: function ( tc ) {
			var parts = String( tc || '' ).split( '|' );

			if ( parts[ 0 ] === 'ZoneInfo' && parts[ 2 ] ) {
				return { zone: parts[ 2 ] };
			}

			if ( parts[ 0 ] === 'Offset' ) {
				return { minutes: parseInt( parts[ 1 ], 10 ) || 0 };
			}

			return null;
		},

		/**
		 * @param {HTMLElement} el a <time> element with an ISO datetime anchor
		 * @param {Object|null} opt result of parseTimeCorrection, or null for
		 *  the browser zone
		 */
		convert: function ( el, opt ) {
			var iso = el.getAttribute( 'datetime' );

			if ( !iso ) {
				return;
			}

			var date = new Date( iso );

			if ( isNaN( date.getTime() ) ) {
				return;
			}

			var locale = mw.config.get( 'wgUserLanguage' ) || 'en';
			var text;

			if ( opt && typeof opt.minutes === 'number' ) {
				// Fixed offset: shift the UTC instant and format its UTC parts.
				var shifted = new Date( date.getTime() + opt.minutes * 60000 );
				text = new Intl.DateTimeFormat( locale, {
					dateStyle: 'long',
					timeStyle: 'short',
					timeZone: 'UTC'
				} ).format( shifted );
			} else {
				// Named zone, or browser zone when opt is null.
				var options = { dateStyle: 'long', timeStyle: 'short' };
				if ( opt && opt.zone ) {
					options.timeZone = opt.zone;
				}
				text = new Intl.DateTimeFormat( locale, options ).format( date );
			}

			el.textContent = text;
		},

		init: function ( $content ) {
			var opt = localtime.parseTimeCorrection(
				mw.user.options.get( 'timecorrection' )
			);

			$content.find( 'time.smw-localtime' ).each( function () {
				try {
					localtime.convert( this, opt );
				} catch ( e ) {
					// Leave the wiki-local fallback in place on any failure.
				}
			} );
		}
	};

	mw.smw.localtime = localtime;

	mw.hook( 'wikipage.content' ).add( function ( $content ) {
		localtime.init( $content );
	} );
}( mediaWiki ) );
