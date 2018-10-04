<?php

namespace SMW\MediaWiki\Hooks;

use OutputPage;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\Query\QueryRefFinder;
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

		$title = $this->outputPage->getTitle();

		if ( $title->isSpecialPage() ||
			$title->isRedirect() ||
			!$this->isSemanticEnabledNamespace( $title ) ) {
			return true;
		}

		$request = $this->outputPage->getContext()->getRequest();

		$this->factbox( $request );
		$this->postProc( $title, $request );
	}

	private function postProc( $title, $request) {

		if ( in_array( $request->getVal( 'action' ), [ 'delete', 'purge', 'protect', 'unprotect', 'history', 'edit' ] ) ) {
			return '';
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$postProcHandler = $applicationFactory->create( 'PostProcHandler', $this->parserOutput );

		$html = $postProcHandler->getHtml(
			$title,
			$request
		);

		if ( $html !== '' ) {
			$this->outputPage->addModules( $postProcHandler->getModules() );
			$this->outputPage->addHtml( $html );
		}
	}

	protected function factbox( $request ) {

		if ( isset( $this->outputPage->mSMWFactboxText ) && $request->getCheck( 'wpPreview' ) ) {
			return '';
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$cachedFactbox = $applicationFactory->singleton( 'FactboxFactory' )->newCachedFactbox();

		$cachedFactbox->prepareFactboxContent(
			$this->outputPage,
			$this->outputPage->getLanguage(),
			$this->getParserOutput()
		);

		return true;
	}

	protected function getParserOutput() {

		if ( $this->outputPage->getContext()->getRequest()->getInt( 'oldid' ) ) {

			$text = $this->parserOutput->getText();

			$parserData = ApplicationFactory::getInstance()->newParserData(
				$this->outputPage->getTitle(),
				$this->parserOutput
			);

			$inTextAnnotationParser = ApplicationFactory::getInstance()->newInTextAnnotationParser( $parserData );
			$inTextAnnotationParser->parse( $text );

			return $parserData->getOutput();
		}

		return $this->parserOutput;
	}

	private function isSemanticEnabledNamespace( Title $title ) {
		return ApplicationFactory::getInstance()->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
	}

}
