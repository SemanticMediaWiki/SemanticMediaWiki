<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DIC\ObjectFactory;

use Parser;
use Title;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InternalParseBeforeLinks
 *
 * Hook: InternalParseBeforeLinks is used to process the expanded wiki
 * code after <nowiki>, HTML-comments, and templates have been treated.
 *
 * This method is called before an article is displayed or previewed.
 * For display and preview semantic properties are stripped from the text
 * and stored internally.
 *
 * @note MW 1.20+ see InternalParseBeforeSanitize
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InternalParseBeforeLinks {

	/** @var Parser */
	protected $parser = null;

	/** @var string */
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
		return $this->canPerformTextParse() ? $this->performTextParse() : true;
	}

	protected function canPerformTextParse() {

		if ( !$this->parser->getTitle()->isSpecialPage() ) {
			return true;
		}

		$isEnabledSpecialPage = ObjectFactory::getInstance()->getSettings()->get( 'smwgEnabledSpecialPage' );

		foreach ( $isEnabledSpecialPage as $specialPage ) {
			if ( $this->parser->getTitle()->isSpecial( $specialPage ) ) {
				return true;
			}
		}

		return false;
	}

	protected function performTextParse() {

		$redirectTarget = $this->getRedirectTarget();

		$parserData = ObjectFactory::getInstance()->newByParserData(
			$this->parser->getTitle(),
			$this->parser->getOutput()
		);

		$inTextAnnotationParser = ObjectFactory::getInstance()->newInTextAnnotationParser( $parserData );

		$inTextAnnotationParser
			->setRedirectTarget( $redirectTarget )
			->parse( $this->text );

		$this->setStatusPropertyForSemanticData(
			$parserData->getSemanticData()->getProperties() !== array()
		);

		return true;
	}

	protected function setStatusPropertyForSemanticData( $status ) {
		$this->parser->getOutput()->setProperty( 'smw-semanticdata-status', $status );
	}

	protected function getRedirectTarget() {
		return Title::newFromRedirect( $this->text );
	}

}
