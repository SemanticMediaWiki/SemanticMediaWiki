<?php

namespace SMW;

use ParserOutput;
use OutputPage;
use Title;

use SMWOutputs;

/**
 * OutputPageParserOutput hook is called after parse, before the HTML is
 * added to the output
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
 *
 * @note This hook copies SMW's custom data from the given ParserOutput object to
 * the given OutputPage object, since otherwise it is not possible to access
 * it later on to build a Factbox.
 *
 * @ingroup FunctionHook
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class OutputPageParserOutput extends FunctionHook {

	/** @var OutputPage */
	protected $outputPage = null;

	/** @var ParserOutput */
	protected $skin = null;

	/**
	 * @since  1.9
	 *
	 * @param OutputPage $outputPage
	 * @param ParserOutput $parserOutput
	 */
	public function __construct( OutputPage &$outputPage, ParserOutput $parserOutput ) {
		$this->outputPage = $outputPage;
		$this->parserOutput = $parserOutput;
	}

	/**
	 * @see FunctionHook::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return $this->canPerformUpdate() ? $this->performUpdate() : true;
	}

	protected function canPerformUpdate() {

		$title = $this->outputPage->getTitle();

		if ( $title->isSpecialPage() ||
			$title->isRedirect() ||
			!$this->withContext()->getDependencyBuilder()->newObject( 'NamespaceExaminer' )->isSemanticEnabled( $title->getNamespace() ) ) {
			return false;
		}

		if ( isset( $this->outputPage->mSMWFactboxText ) && $this->outputPage->getContext()->getRequest()->getCheck( 'wpPreview' ) ) {
			return false;
		}

		return true;
	}

	protected function performUpdate() {

		/**
		 * @var FactboxCache $factboxCache
		 */
		$factboxCache = $this->withContext()->getDependencyBuilder()->newObject( 'FactboxCache', array(
			'OutputPage' => $this->outputPage
		) );

		$factboxCache->process( $this->makeParserOutput() );

		// @Legacy code
		// Not sure why this was ever needed but to monitor any
		// deviations we'll keep this as note in case it
		// is needed but tests didn't show it is needed
		// SMWOutputs::commitToOutputPage( $this->outputPage );

		return true;
	}

	protected function makeParserOutput() {

		if ( $this->outputPage->getContext()->getRequest()->getInt( 'oldid' ) ) {

			$parserData = $this->withContext()->getDependencyBuilder()->newObject( 'ParserData', array(
				'Title'        => $this->outputPage->getTitle(),
				'ParserOutput' => $this->parserOutput
			) );

			$contentProcessor = $this->withContext()->getDependencyBuilder()->newObject( 'ContentProcessor', array(
				'ParserData' => $parserData
			) );

			$text = $this->parserOutput->getText();
			$contentProcessor->parse( $text );

			return $parserData->getOutput();
		}

		return $this->parserOutput;
	}
}
