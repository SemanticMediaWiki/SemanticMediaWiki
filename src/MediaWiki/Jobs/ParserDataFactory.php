<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use SMW\ParserData;

/**
 * Constructs `ParserData` instances bound to a specific `Title` and
 * `ParserOutput`.
 *
 * Extracted to lift `UpdateJob` (and any future callers) off
 * `ServicesFactory::getInstance()->newParserData(...)`.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ParserDataFactory {

	public function __construct(
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function newParserData( Title $title, ParserOutput $parserOutput ): ParserData {
		$parserData = new ParserData( $title, $parserOutput );
		$parserData->setLogger( $this->logger );

		return $parserData;
	}

}
