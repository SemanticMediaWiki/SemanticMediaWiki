<?php

namespace SMW\MediaWiki\Hooks;

use Parser;
use SMW\ApplicationFactory;
use SMW\Parser\InTextAnnotationParser;
use StripState;

/**
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
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InternalParseBeforeLinks extends HookHandler {

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var StripState
	 */
	private $stripState;

	/**
	 * @since 1.9
	 *
	 * @param Parser $parser
	 * @param StripState $stripState
	 */
	public function __construct( Parser &$parser, $stripState ) {
		$this->parser = $parser;
		$this->stripState = $stripState;
	}

	/**
	 * @since 1.9
	 *
	 * @param string $text
	 *
	 * @return true
	 */
	public function process( &$text ) {

		if ( !$this->canPerformUpdate( $text, $this->parser->getTitle() ) ) {
			return true;
		}

		return $this->performUpdate( $text );
	}

	private function canPerformUpdate( $text, $title ) {

		if ( $this->getRedirectTarget() !== null ) {
			return true;
		}

		// #2209, #2370 Allow content to be parsed that contain [[SMW::off]]/[[SMW::on]]
		// even in case of MediaWiki messages
		if ( InTextAnnotationParser::hasMarker( $text ) || InTextAnnotationParser::hasPropertyLink( $text ) ) {
			return true;
		}

		// ParserOptions::getInterfaceMessage is being used to identify whether a
		// parse was initiated by `Message::parse`
		if ( $text === '' || $this->parser->getOptions()->getInterfaceMessage() ) {
			return false;
		}

		if ( !$title->isSpecialPage() ) {
			return true;
		}

		// #2529
		foreach ( $this->getOption( 'smwgEnabledSpecialPage', [] ) as $specialPage ) {
			if ( is_string( $specialPage ) && $title->isSpecial( $specialPage ) ) {
				return true;
			}
		}

		return false;
	}

	private function performUpdate( &$text ) {

		$applicationFactory = ApplicationFactory::getInstance();

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $applicationFactory->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		/**
		 * Performs [[link::syntax]] parsing and adding of property annotations
		 * to the ParserOutput
		 *
		 * @var InTextAnnotationParser
		 */
		$inTextAnnotationParser = $applicationFactory->newInTextAnnotationParser(
			$parserData
		);

		$stripMarkerDecoder = $applicationFactory->newMwCollaboratorFactory()->newStripMarkerDecoder(
			$this->stripState
		);

		$inTextAnnotationParser->setStripMarkerDecoder(
			$stripMarkerDecoder
		);

		$inTextAnnotationParser->setRedirectTarget(
			$this->getRedirectTarget()
		);

		$inTextAnnotationParser->parse( $text );

		$parserData->markParserOutput();

		return true;
	}

	/**
	 * #656 / MW 1.24+
	 */
	private function getRedirectTarget() {

		if ( method_exists( $this->parser->getOptions(), 'getRedirectTarget' ) ) {
			return $this->parser->getOptions()->getRedirectTarget();
		}

		return null;
	}

}
