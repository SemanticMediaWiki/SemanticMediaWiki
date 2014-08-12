<?php

namespace SMW\MediaWiki\Hooks;

use OutputPage;
use ParserOutput;
use SMW\Application;
use SMW\NamespaceExaminer;
use SMWOutputs;
use Title;

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
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class OutputPageParserOutput {

	/**
	 * @var OutputPage
	 */
	protected $outputPage = null;

	/**
	 * @var ParserOutput
	 */
	protected $parserOutput = null;

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
			!$this->isEnabledNamespace( $title ) ) {
			return false;
		}

		if ( isset( $this->outputPage->mSMWFactboxText ) && $this->outputPage->getContext()->getRequest()->getCheck( 'wpPreview' ) ) {
			return false;
		}

		return true;
	}

	protected function performUpdate() {

		$factboxCache = Application::getInstance()->newFactboxBuilder()->newFactboxCache( $this->outputPage );
		$factboxCache->process( $this->getParserOutput() );

		return true;
	}

	protected function getParserOutput() {

		if ( $this->outputPage->getContext()->getRequest()->getInt( 'oldid' ) ) {

			$text = $this->parserOutput->getText();

			$parserData = Application::getInstance()->newParserData(
				$this->outputPage->getTitle(),
				$this->parserOutput
			);

			$inTextAnnotationParser = Application::getInstance()->newInTextAnnotationParser( $parserData );
			$inTextAnnotationParser->parse( $text );

			return $parserData->getOutput();
		}

		return $this->parserOutput;
	}

	private function isEnabledNamespace( Title $title ) {
		return NamespaceExaminer::newFromArray(
			Application::getInstance()->getSettings()->get( 'smwgNamespacesWithSemanticLinks' ) )->isSemanticEnabled( $title->getNamespace()
		);
	}

}
