<?php

namespace SMW;

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
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InternalParseBeforeLinks extends FunctionHook {

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
	 * @see FunctionHook::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return !$this->parser->getTitle()->isSpecialPage() ? $this->performUpdate( $this->parser->getTitle() ) : true;
	}

	/**
	 * Performs [[link::syntax]] parsing and adding of property annotations
	 * to the ParserOutput
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	protected function performUpdate( Title $title ) {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->withContext()->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $title,
			'ParserOutput' => $this->parser->getOutput()
		) );

		/**
		 * @var ParserTextProcessor $contentProcessor
		 */
		$contentProcessor = $this->withContext()->getDependencyBuilder()->newObject( 'ContentProcessor', array(
			'ParserData' => $parserData
		) );

		$contentProcessor->parse( $this->text );

		return true;
	}

}
