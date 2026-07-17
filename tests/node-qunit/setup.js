const path = require( 'path' );
const sinon = require( 'sinon' );

/**
 * provide a clean jsdom + jQuery environment for each test
 *
 * @return {function(): void} a function to reset the DOM between tests
 */
function createDom() {
	const { JSDOM } = require( 'jsdom' );
	const dom = new JSDOM();
	global.window = dom.window;
	global.document = window.document;
	global.navigator = window.navigator;
	global.Node = window.Node;
	global.HTMLElement = window.HTMLElement;
	global.$ = global.jQuery = require( 'jquery' );

	// In a real browser, window.setTimeout IS the global setTimeout; in
	// node+jsdom they're two distinct functions, and jsdom's window.setTimeout
	// itself calls Node's real (bare) setTimeout internally to schedule the
	// underlying timer -- aliasing global.setTimeout to window.setTimeout
	// *before* capturing that real one would make it recurse into itself.
	// ext.smw.util.purge.js calls the bare global, while a test stubs
	// window.setTimeout to observe the scheduled delay (see #7044's backoff
	// test); define global.setTimeout as a live accessor so either name
	// reflects the other, without touching Node's own setTimeout.
	const nodeSetTimeout = global.setTimeout;
	window.setTimeout = nodeSetTimeout;
	Object.defineProperty( global, 'setTimeout', {
		configurable: true,
		get: () => window.setTimeout,
		set: ( fn ) => {
			window.setTimeout = fn;
		}
	} );

	return () => {
		global.document.body.innerHTML = '';
	};
}

/**
 * Minimal mw.Title stand-in: SMW's dataItem.wikiPage only stores the title
 * it's constructed with (see res/smw/data/ext.smw.dataItem.wikiPage.js:67)
 * and the test suite only asserts instanceof/deepEqual, not any parsing
 * behaviour of the real mediawiki.Title module.
 *
 * @param {string} name
 */
function Title( name ) {
	this.name = name;
}

/**
 * Minimal mw.hook: SMW resources register a handful of custom hooks
 * (smw.tooltip, smw.deferred.query, wikipage.content, ...) at module load
 * time; nothing under test here depends on a handler actually firing.
 *
 * @return {Object}
 */
function createHookStore() {
	const handlers = {};

	return function ( name ) {
		handlers[ name ] = handlers[ name ] || [];

		return {
			add: function () {
				handlers[ name ].push.apply( handlers[ name ], arguments );
				return this;
			},
			remove: function () {
				return this;
			},
			fire: function () {
				var args = arguments;
				handlers[ name ].forEach( function ( handler ) {
					handler.apply( null, args );
				} );
				return this;
			}
		};
	};
}

/**
 * minimal mw.html.element/Raw implementation, sufficient for the resources
 * under test (building small trusted-attribute snippets, no real
 * MediaWiki Sanitizer)
 *
 * @param value
 */
function escapeAttribute( value ) {
	return String( value )
		.replace( /&/g, '&amp;' )
		.replace( /"/g, '&quot;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' );
}

function Raw( value ) {
	this.value = value;
}

function htmlElement( tagName, attrs, contents ) {
	attrs = attrs || {};
	const attrString = Object.keys( attrs )
		.filter( ( key ) => attrs[ key ] !== false )
		.map( ( key ) => ` ${ key }="${ escapeAttribute( attrs[ key ] === true ? key : attrs[ key ] ) }"` )
		.join( '' );
	const inner = contents instanceof Raw ? contents.value : escapeAttribute( contents === null || contents === undefined ? '' : contents );
	return `<${ tagName }${ attrString }>${ inner }</${ tagName }>`;
}

/**
 * Build the default (per-test) mw.config/storage values. Split out from
 * prepareMediaWiki() so resetMediaWiki() can restore fresh values into the
 * *same* mw object across tests, see the comment on prepareMediaWiki().
 *
 * @return {Object}
 */
function defaultConfigValues() {
	return {
		'smw-config': {
			version: '0.1',
			settings: {
				smwgQMaxLimit: 500,
				// smw.util.namespace.getId/getName look up namespace
				// names/ids via this setting (see res/smw/ext.smw.js).
				namespace: { Property: 102, Concept: 100 }
			},
			formats: { table: 'Table' }
		},
		// 24h-locale so ext.smw.localtime's Intl.DateTimeFormat output is
		// directly comparable against fixed "HH:MM" expectations without
		// pulling AM/PM formatting into the assertions.
		wgUserLanguage: 'de',
		wgFormattedNamespaces: { 102: 'Property', 100: 'Concept' },
		wgPageName: 'Foo',
		wgScriptPath: '/w',
		wgArticlePath: '/wiki/$1',
		wgServer: 'http://localhost',
		debug: false
	};
}

/**
 * setup minimal MediaWiki globals (mw.loader, mw.message, mw.html, mw.storage,
 * mw.config, mw.Api, mw.Title, mw.hook, ...) used by SMW's res/smw/*.js
 *
 * res/smw/*.js files are require()'d exactly once (see loadSmwResources())
 * and close over the mw reference they're called with at that time. So the
 * mw object itself must stay the same object for the lifetime of the run --
 * resetting between tests mutates its config/storage values in place rather
 * than reassigning global.mw, or resources like ext.smw.util.purge.js would
 * keep pointing at a stale, pre-reset copy.
 *
 * @return {function(): void} a function to reset mw's state between tests
 */
function prepareMediaWiki() {
	let configValues = defaultConfigValues();
	let storageValues = {};
	let sessionStorageValues = {};

	global.mediaWiki = global.mw = {
		smw: {},
		loader: {
			using: ( _dep, callback ) => {
				if ( callback ) {
					callback();
				}
				return Promise.resolve();
			}
		},
		log: function () {},
		message: ( key ) => ( {
			text: () => key,
			escaped: () => key,
			parse: () => key
		} ),
		msg: ( key ) => key,
		notify: function () {},
		html: {
			element: htmlElement,
			Raw: Raw
		},
		hook: createHookStore(),
		Title: Title,
		util: {
			wikiScript: ( str ) => `${ configValues.wgScriptPath }/${ str || 'index' }.php`,
			// modern replacement for the removed mw.util.wikiGetlink,
			// see res/smw/data/ext.smw.dataItem.property.js. Real mw.util.getUrl
			// normalizes spaces to underscores via mw.Title; mirror that here.
			getUrl: ( title ) => configValues.wgArticlePath.replace( '$1', String( title ).replace( / /g, '_' ) )
		},
		storage: {
			get: ( key ) => ( Object.prototype.hasOwnProperty.call( storageValues, key ) ? storageValues[ key ] : null ),
			set: ( key, value ) => {
				storageValues[ key ] = value;
			},
			session: {
				get: ( key ) => ( Object.prototype.hasOwnProperty.call( sessionStorageValues, key ) ? sessionStorageValues[ key ] : null ),
				set: ( key, value ) => {
					sessionStorageValues[ key ] = value;
				},
				remove: ( key ) => {
					delete sessionStorageValues[ key ];
				}
			}
		},
		config: {
			get: ( key ) => configValues[ key ],
			set: ( key, value ) => {
				configValues[ key ] = value;
			}
		},
		user: {
			options: {
				get: () => null
			}
		},
		language: {
			months: {
				names: [ 'January', 'February', 'March', 'April', 'May', 'June',
					'July', 'August', 'September', 'October', 'November', 'December' ]
			}
		},
		// real API calls go through here in ext.smw.util.purge.js; tests
		// stub mw.Api directly, this is just a safe default.
		Api: function () {}
	};

	return () => {
		configValues = defaultConfigValues();
		storageValues = {};
		sessionStorageValues = {};
	};
}

/**
 * load the resources under test once per run, in their dependency order
 * (mirrors extension.json's QUnitTestModule "dependencies" list).
 *
 * @return {void}
 */
function loadSmwResources() {
	const base = path.resolve( __dirname, '../../res/smw' );

	require( path.join( base, 'ext.smw.js' ) );
	global.smw = global.semanticMediaWiki = window.smw;

	[
		'data/ext.smw.dataItem.wikiPage.js',
		'data/ext.smw.dataItem.uri.js',
		'data/ext.smw.dataItem.time.js',
		'data/ext.smw.dataItem.property.js',
		'data/ext.smw.dataItem.unknown.js',
		'data/ext.smw.dataItem.number.js',
		'data/ext.smw.dataItem.text.js',
		'data/ext.smw.dataValue.quantity.js',
		'data/ext.smw.data.js',
		'api/ext.smw.api.js',
		'query/ext.smw.query.js',
		'util/ext.smw.localtime.js',
		'util/ext.smw.util.purge.js'
	].forEach( ( relPath ) => require( path.join( base, relPath ) ) );
}

/**
 * Minimal stand-in for MediaWiki core's QUnit.newMwEnvironment: the real
 * helper snapshots/restores mw.config and wires beforeEach/afterEach into a
 * live MediaWiki page. Here mw.config is already reset per-test by
 * resetMediaWiki(), so this only needs to apply the config overrides and
 * chain any caller-supplied hooks.
 *
 * @param {Object} [options]
 * @param {Object} [options.config]
 * @param {Function} [options.beforeEach]
 * @param {Function} [options.afterEach]
 * @return {Object}
 */
function newMwEnvironment( options ) {
	options = options || {};

	return {
		beforeEach: function ( assert ) {
			if ( options.config ) {
				Object.keys( options.config ).forEach( function ( key ) {
					mw.config.set( key, options.config[ key ] );
				} );
			}
			if ( options.beforeEach ) {
				options.beforeEach.call( this, assert );
			}
		},
		afterEach: function ( assert ) {
			if ( options.afterEach ) {
				options.afterEach.call( this, assert );
			}
		}
	};
}

global.sinon = sinon;

const resetDom = createDom();
const resetMediaWiki = prepareMediaWiki();
loadSmwResources();

global.QUnit.newMwEnvironment = newMwEnvironment;

QUnit.hooks.afterEach( () => {
	resetDom();
	resetMediaWiki();
	sinon.restore();
} );

QUnit.module( 'setup' );

QUnit.test( 'smw resources loaded onto the global namespace', ( assert ) => {
	assert.ok( global.smw instanceof Object, 'smw is accessible' );
	assert.equal( typeof global.smw.dataItem.wikiPage, 'function', 'smw.dataItem.wikiPage is accessible' );
	assert.equal( typeof global.mw.smw.purge, 'object', 'mw.smw.purge is accessible' );
} );
