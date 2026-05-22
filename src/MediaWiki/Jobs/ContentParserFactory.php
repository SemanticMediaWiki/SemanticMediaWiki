<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use SMW\MediaWiki\RevisionGuard;
use SMW\Parser\ContentParser;

/**
 * Constructs `ContentParser` instances bound to a specific `Title`.
 *
 * Extracted to lift `UpdateJob` (and any future callers) off
 * `ServicesFactory::getInstance()->newContentParser(...)`.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ContentParserFactory {

	public function __construct(
		private readonly Parser $parser,
		private readonly RevisionGuard $revisionGuard,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function newContentParser( Title $title ): ContentParser {
		$contentParser = new ContentParser( $title, $this->parser );
		$contentParser->setRevisionGuard( $this->revisionGuard );

		return $contentParser;
	}

}
