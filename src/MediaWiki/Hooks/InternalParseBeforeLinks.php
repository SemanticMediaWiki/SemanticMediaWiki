<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\InternalParseBeforeLinksHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use SMW\MediaWiki\Jobs\ParserDataFactory;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\Parser\InTextAnnotationParser;
use SMW\Parser\InTextAnnotationParserFactory;
use SMW\Settings;

/**
 * The main task for this hook is to parse and replace the Semantic MediaWiki
 * specific annotation syntax (e.g. `[[PropertyA::ValueB]]`) with a corresponding
 * text representation (e.g. wikitext link, reference, URL etc.) of the value
 * and the defined property. Structured information is gathered from a parsed
 * text and attached to the `ParserOutput` for further processing after the
 * parsing has been completed.
 *
 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
 * code after <nowiki>, HTML-comments, and templates have been treated.
 *
 * This method will be called before an article is displayed or previewed.
 * For display and preview we strip out the semantic properties and append them
 * at the end of the article.
 *
 * @note MW 1.20+ see InternalParseBeforeSanitize
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
 *
 * @ingroup FunctionHook
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class InternalParseBeforeLinks implements InternalParseBeforeLinksHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Settings $settings,
		private readonly ParserDataFactory $parserDataFactory,
		private readonly InTextAnnotationParserFactory $inTextAnnotationParserFactory,
		private readonly MwCollaboratorFactory $mwCollaboratorFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onInternalParseBeforeLinks( $parser, &$text, $stripState ) {
		if ( !$this->canPerformUpdate( $text, $parser, $parser->getTitle() ) ) {
			return true;
		}

		return $this->performUpdate( $text, $parser, $stripState );
	}

	private function canPerformUpdate( $text, Parser $parser, Title $title ): bool {
		if ( $parser->getOptions()->getRedirectTarget() !== null ) {
			return true;
		}

		// #2209, #2370 Allow content to be parsed that contain [[SMW::off]]/[[SMW::on]]
		// even in case of MediaWiki messages
		if ( InTextAnnotationParser::hasMarker( $text ) || InTextAnnotationParser::hasPropertyLink( $text ) ) {
			return true;
		}

		// ParserOptions::getInterfaceMessage is being used to identify whether a
		// parse was initiated by `Message::parse`
		if ( $text === '' || $parser->getOptions()->getInterfaceMessage() ) {
			return false;
		}

		if ( !$title->isSpecialPage() ) {
			return true;
		}

		// #2529
		foreach ( $this->settings->get( 'smwgEnabledSpecialPage' ) ?: [] as $specialPage ) {
			if ( is_string( $specialPage ) && $title->isSpecial( $specialPage ) ) {
				return true;
			}
		}

		return false;
	}

	private function performUpdate( &$text, Parser $parser, $stripState ): bool {
		$parserData = $this->parserDataFactory->newParserData(
			$parser->getTitle(),
			$parser->getOutput()
		);

		$inTextAnnotationParser = $this->inTextAnnotationParserFactory->newFor( $parserData );

		$stripMarkerDecoder = $this->mwCollaboratorFactory->newStripMarkerDecoder(
			$stripState
		);

		$inTextAnnotationParser->setStripMarkerDecoder(
			$stripMarkerDecoder
		);

		$inTextAnnotationParser->setRedirectTarget(
			$parser->getOptions()->getRedirectTarget()
		);

		$inTextAnnotationParser->parse( $text );

		$parserData->markParserOutput();

		return true;
	}

}
