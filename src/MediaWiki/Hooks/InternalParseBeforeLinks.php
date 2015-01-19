<?php

namespace SMW\MediaWiki\Hooks;

use Parser;
use SMW\ApplicationFactory;

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
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
 *
 * @ingroup FunctionHook
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InternalParseBeforeLinks {

	/**
	 * @var Parser
	 */
	private $parser = null;

	/**
	 * @var string
	 */
	private $text;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @since 1.9
	 *
	 * @param Parser $parser
	 * @param string $text
	 */
	public function __construct( Parser &$parser, &$text ) {
		$this->parser = $parser;
		$this->text =& $text;
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return $this->canPerformUpdate() ? $this->performUpdate() : true;
	}

	private function canPerformUpdate() {

		if ( $this->getRedirectTarget() !== null ) {
			return true;
		}

		// ParserOptions::getInterfaceMessage is being used to identify whether a
		// parse was initiated by `Message::parse`
		if ( $this->text === '' || $this->parser->getOptions()->getInterfaceMessage() ) {
			return false;
		}

		if ( !$this->parser->getTitle()->isSpecialPage() ) {
			return true;
		}

		$isEnabledSpecialPage = $this->applicationFactory->getSettings()->get( 'smwgEnabledSpecialPage' );

		foreach ( $isEnabledSpecialPage as $specialPage ) {
			if ( $this->parser->getTitle()->isSpecial( $specialPage ) ) {
				return true;
			}
		}

		return false;
	}

	private function performUpdate() {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->applicationFactory->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		/**
		 * Performs [[link::syntax]] parsing and adding of property annotations
		 * to the ParserOutput
		 *
		 * @var InTextAnnotationParser
		 */
		$inTextAnnotationParser = $this->applicationFactory->newInTextAnnotationParser( $parserData );
		$inTextAnnotationParser->setRedirectTarget( $this->getRedirectTarget() );
		$inTextAnnotationParser->parse( $this->text );

		$parserData->setSemanticDataStateToParserOutputProperty();

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
