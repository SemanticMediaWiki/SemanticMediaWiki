<?php

namespace SMW\MediaWiki\Jobs;

use Closure;
use MediaWiki\Title\Title;
use SMW\MediaWiki\RevisionGuard;
use SMW\Parser\ContentParser;

/**
 * Constructs `ContentParser` instances bound to a specific `Title`.
 *
 * Extracted to lift `UpdateJob` (and any future callers) off
 * `ServicesFactory::getInstance()->newContentParser(...)`.
 *
 * Parser is resolved through a provider closure rather than captured at
 * construction time. Eagerly calling `MediaWikiServices::getParser()` in
 * the wiring would force Parser singleton construction every time this
 * factory is resolved, which fires `ParserFirstCallInit` against whatever
 * hook listeners happen to be registered at that moment. In test setups
 * that resolve this factory before all extension hook listeners are in
 * place (e.g. via ObjectFactory normalization triggered by
 * `HookContainer::isRegistered`), the resulting Parser singleton ends up
 * missing parser-function registrations from downstream extensions.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ContentParserFactory {

	private readonly Closure $parserProvider;

	/**
	 * @param Closure $parserProvider Returns the MediaWiki Parser; invoked once per
	 *  `newContentParser()` call. Wrapping `MediaWikiServices::getParser()` in a closure
	 *  defers Parser singleton construction until a `ContentParser` is actually built.
	 */
	public function __construct(
		Closure $parserProvider,
		private readonly RevisionGuard $revisionGuard,
	) {
		$this->parserProvider = $parserProvider;
	}

	/**
	 * @since 7.0.0
	 */
	public function newContentParser( Title $title ): ContentParser {
		$contentParser = new ContentParser( $title, ( $this->parserProvider )() );
		$contentParser->setRevisionGuard( $this->revisionGuard );

		return $contentParser;
	}

}
