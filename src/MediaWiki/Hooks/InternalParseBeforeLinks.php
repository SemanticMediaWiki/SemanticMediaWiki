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
	private $parser;

	/**
	 * @var array
	 */
	private $enabledSpecialPage = array();

	/**
	 * @since 1.9
	 *
	 * @param Parser $parser
	 */
	public function __construct( Parser &$parser ) {
		$this->parser = $parser;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $enabledSpecialPage
	 */
	public function setEnabledSpecialPage( array $enabledSpecialPage ) {
		$this->enabledSpecialPage = $enabledSpecialPage;
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

		// ParserOptions::getInterfaceMessage is being used to identify whether a
		// parse was initiated by `Message::parse`
		//
		// #2209 If the text was a `InterfaceMessage` send from a SpecialPage such as
		// Special:Booksources we allow to proceed
		if ( $text === '' || ( $this->parser->getOptions()->getInterfaceMessage() && !$title->isSpecialPage() ) ) {
			return false;
		}

		if ( !$title->isSpecialPage() ) {
			return true;
		}

		foreach ( $this->enabledSpecialPage as $specialPage ) {
			if ( $title->isSpecial( $specialPage ) ) {
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

		$inTextAnnotationParser->setRedirectTarget( $this->getRedirectTarget() );
		$inTextAnnotationParser->parse( $text );

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
