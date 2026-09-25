/**
 * Security regression tests for the tooltip module's content assembly.
 *
 * The module binds tippy over `.smw-highlighter` spans and, on hover, feeds the
 * span's `data-content` attribute and its decoded `.smwttcontent` text into the
 * tooltip. Both channels are wikitext-authorable, so a planted span can carry an
 * active payload. These tests drive the real `onShow` handler (captured from the
 * options passed to the stubbed `tippy.delegate`) and observe the content handed
 * to `setContent`: a forged payload must be neutralised, while benign formatting
 * the wiki legitimately produces must survive.
 *
 * SMW CI does not run QUnit, so this file is verified locally and in review.
 */

const path = require( 'path' );

// The module reads a global `tippy` and, at load time, calls tippy.delegate()
// to bind the document. Stub it and capture the options object so the tests can
// invoke the real onShow handler.
let capturedOptions = null;
global.tippy = function () {};
global.tippy.delegate = function ( target, opts ) {
	capturedOptions = opts;
};

require( path.resolve( __dirname, '../../res/smw/util/ext.smw.tooltip.tippy.js' ) );

QUnit.module( 'ext.smw.tooltip.tippy', function () {

	// Build a .smw-highlighter reference inside #bodyContent and a minimal tip,
	// then run the real onShow. Returns the content handed to setContent, wrapped
	// so assertions read it as DOM regardless of whether it is a node or a string.
	function contentAppliedFor( span ) {
		document.body.innerHTML = '<div id="bodyContent"></div>';
		document.getElementById( 'bodyContent' ).appendChild( span );

		let applied;
		const tip = {
			reference: span,
			props: { maxWidth: 260 },
			popper: document.createElement( 'div' ),
			setProps: function () {},
			hide: function () {},
			setContent: function ( content ) {
				applied = content;
			}
		};

		capturedOptions.onShow( tip );

		const holder = document.createElement( 'div' );
		if ( typeof applied === 'string' ) {
			holder.innerHTML = applied;
		} else if ( applied ) {
			holder.appendChild( applied.cloneNode ? applied.cloneNode( true ) : applied );
		}
		return holder;
	}

	function highlighterWithDataContent( encodedContent ) {
		const span = document.createElement( 'span' );
		span.className = 'smw-highlighter';
		span.setAttribute( 'data-content', encodedContent );
		return span;
	}

	function highlighterWithTtContent( encodedContent ) {
		const span = document.createElement( 'span' );
		span.className = 'smw-highlighter';
		const tt = document.createElement( 'span' );
		tt.className = 'smwttcontent';
		tt.textContent = encodedContent;
		span.appendChild( tt );
		return span;
	}

	QUnit.test( 'a forged data-content script payload cannot execute on hover', function ( assert ) {
		const span = highlighterWithDataContent( '<img src=x onerror=window.__xss=1>' );

		const applied = contentAppliedFor( span );

		assert.strictEqual( applied.querySelectorAll( '[onerror]' ).length, 0, 'no onerror handler survives' );
		assert.strictEqual( applied.querySelectorAll( 'script' ).length, 0, 'no script element survives' );
	} );

	QUnit.test( 'a forged data-content script element cannot execute on hover', function ( assert ) {
		const span = highlighterWithDataContent( '<script>window.__xss=1</script>' );

		const applied = contentAppliedFor( span );

		assert.strictEqual( applied.querySelectorAll( 'script' ).length, 0, 'no script element survives' );
	} );

	QUnit.test( 'a javascript: URL in forged data-content is neutralised', function ( assert ) {
		const span = highlighterWithDataContent( '<a href="javascript:window.__xss=1">x</a>' );

		const applied = contentAppliedFor( span );

		const link = applied.querySelector( 'a' );
		assert.notOk( link && /javascript:/i.test( link.getAttribute( 'href' ) || '' ), 'javascript: href is stripped' );
	} );

	QUnit.test( 'a forged smwttcontent payload cannot execute on hover', function ( assert ) {
		// The .smwttcontent path (info-tag content) is entity-encoded server-side
		// and decoded client-side before it reaches the tooltip.
		const span = highlighterWithTtContent( '&lt;img src=x onerror=window.__xss=1&gt;' );

		const applied = contentAppliedFor( span );

		assert.strictEqual( applied.querySelectorAll( '[onerror]' ).length, 0, 'no onerror handler survives' );
	} );

	QUnit.test( 'legitimate formatting HTML in the content is preserved', function ( assert ) {
		const span = highlighterWithTtContent( '&lt;b&gt;bold&lt;/b&gt; &lt;a href="/wiki/Main_Page"&gt;link&lt;/a&gt;' );

		const applied = contentAppliedFor( span );

		assert.strictEqual( applied.querySelectorAll( 'b' ).length, 1, 'bold element is preserved' );
		const link = applied.querySelector( 'a' );
		assert.ok( link, 'link element is preserved' );
		assert.strictEqual( link.getAttribute( 'href' ), '/wiki/Main_Page', 'benign href is preserved' );
	} );

} );
