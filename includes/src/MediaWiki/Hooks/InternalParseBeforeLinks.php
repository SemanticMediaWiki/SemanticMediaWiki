<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Application;

use Parser;
use Title;

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
	protected $parser = null;

	/**
	 * @var string
	 */
	protected $text;

	/**
	 * @since 1.9
	 *
	 * @param Parser $parser
	 * @param string $text
	 */
	public function __construct( Parser &$parser, &$text ) {
		$this->parser = $parser;
		$this->text =& $text;
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return $this->canPerformUpdate() ? $this->performUpdate() : true;
	}

	protected function canPerformUpdate() {

		if ( !$this->parser->getTitle()->isSpecialPage() ) {
			return true;
		}

		$isEnabledSpecialPage = Application::getInstance()->getSettings()->get( 'smwgEnabledSpecialPage' );

		foreach ( $isEnabledSpecialPage as $specialPage ) {
			if ( $this->parser->getTitle()->isSpecial( $specialPage ) ) {
				return true;
			}
		}

		return false;
	}

	protected function performUpdate() {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = Application::getInstance()->newParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		/**
		 * Performs [[link::syntax]] parsing and adding of property annotations
		 * to the ParserOutput
		 *
		 * @var InTextAnnotationParser
		 */
		$inTextAnnotationParser = Application::getInstance()->newInTextAnnotationParser( $parserData );
		$inTextAnnotationParser->parse( $this->text );

		$this->parser->getOutput()->setProperty(
			'smw-semanticdata-status',
			$parserData->getSemanticData()->getProperties() !== array()
		);

		return true;
	}

}
