<?php

namespace SMW\Tests\Integration\Services;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use SMW\MediaWiki\Jobs\ContentParserFactory;

/**
 * Pins the property that resolving `SMW.ContentParserFactory` does not
 * eagerly construct the MediaWiki Parser singleton.
 *
 * Background: `SemanticMediaWiki.LinksUpdateComplete`'s HookHandler declares
 * `SMW.ContentParserFactory` in its `services:` block. `HookContainer`
 * normalises declarative handlers lazily via `ObjectFactory::createObject()`
 * the first time something calls `getHandlers()` or `isRegistered()` on the
 * hook. Any service the handler depends on is therefore resolved at that
 * moment, well before the test body or even the test class's own `setUp()`
 * runs.
 *
 * If `SMW.ContentParserFactory`'s wiring eagerly calls
 * `MediaWikiServices::getParser()`, it constructs (and caches) the Parser
 * singleton at that moment. Any extension that registers a
 * `ParserFirstCallInit` listener later in the boot sequence (e.g.
 * SemanticCite, SemanticForms, SemanticGlossary, and several other downstream
 * extensions that use the `HookContainer::register()` pattern from
 * `wgExtensionFunctions`) never gets a chance to install their parser
 * functions against this Parser singleton: their `setFunctionHook()` calls
 * never happen, and `{{#scite:...}}` style invocations parse as literal
 * wikitext.
 *
 * The regression manifested in SemanticCite's CI run 26573787660 on
 * 2026-05-28 as 12 JSONScript test failures all showing "Counted properties
 * include: [_MDAT, _SKEY]", i.e. SCI subobjects never reached the store
 * because `{{#scite:...}}` never fired. Root cause was the conjunction of
 * #6886 (added `SMW.ContentParserFactory` to `LinksUpdateComplete`'s
 * `services:` array) and #6888 (the new `SMWDeclarativeHookReseater` calls
 * `HookContainer::isRegistered()` for every SMW-declared hook in test setUp,
 * which triggers the eager-normalisation path).
 *
 * The fix is to defer Parser resolution to a closure stored on
 * `ContentParserFactory` and invoke it inside `newContentParser()`. This
 * test characterises that contract: any future change that re-introduces
 * eager `getParser()` at wiring time will fail here, before it ships and
 * breaks downstream CI.
 *
 * @covers \SMW\MediaWiki\Jobs\ContentParserFactory
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ContentParserFactoryLazyParserTest extends MediaWikiIntegrationTestCase {

	public function testResolvingContentParserFactoryDoesNotConstructParser(): void {
		$services = MediaWikiServices::getInstance();

		// Force re-resolution: a cached ContentParserFactory or Parser from a
		// prior test would make this a no-op assertion.
		$services->resetServiceForTesting( 'SMW.ContentParserFactory' );
		$services->resetServiceForTesting( 'Parser' );

		$this->assertNull(
			$services->peekService( 'Parser' ),
			'Test precondition: Parser must not be cached before we resolve SMW.ContentParserFactory.'
		);

		$contentParserFactory = $services->getService( 'SMW.ContentParserFactory' );

		$this->assertInstanceOf( ContentParserFactory::class, $contentParserFactory );

		$this->assertNull(
			$services->peekService( 'Parser' ),
			'Resolving SMW.ContentParserFactory must not eagerly construct the Parser singleton. '
				. 'Eager construction at this point misses ParserFirstCallInit listeners that downstream '
				. 'extensions register later in the boot sequence (the SemanticCite CI failure on 2026-05-28). '
				. 'Keep Parser resolution behind a closure invoked inside ContentParserFactory::newContentParser().'
		);
	}

}
