<?php

namespace SMW\Parser;

use MediaWiki\HookContainer\HookContainer;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\ParserData;
use SMW\Settings;

/**
 * Produces a configured {@link InTextAnnotationParser} for the
 * `OutputPageParserOutput` hook's `oldid` branch, which re-parses cached
 * parser output through SMW's in-text annotation parser to recover
 * annotations from the historical revision.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class InTextAnnotationParserFactory {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly MwCollaboratorFactory $mwCollaboratorFactory,
		private readonly Settings $settings,
		private readonly HookContainer $hookContainer,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function newFor( ParserData $parserData ): InTextAnnotationParser {
		$linksProcessor = new LinksProcessor();
		$linksProcessor->isStrictMode(
			$this->settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_STRICT )
		);

		$inTextAnnotationParser = new InTextAnnotationParser(
			$parserData,
			$linksProcessor,
			$this->mwCollaboratorFactory->newMagicWordsFinder(),
			$this->mwCollaboratorFactory->newRedirectTargetFinder()
		);

		$inTextAnnotationParser->isLinksInValues(
			$this->settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_LINV )
		);

		$inTextAnnotationParser->showErrors(
			$this->settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_INL_ERROR )
		);

		$inTextAnnotationParser->setHookContainer( $this->hookContainer );

		return $inTextAnnotationParser;
	}

}
