<?php

namespace SMW\MediaWiki\Hooks;

use OutputPage;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\QueryRefFinder;
use SMW\MediaWiki\IndicatorRegistry;
use SMW\NamespaceExaminer;
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
class OutputPageParserOutput extends HookHandler {

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var IndicatorRegistry
	 */
	private $indicatorRegistry;

	/**
	 * @since 1.9
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer ) {
		$this->namespaceExaminer = $namespaceExaminer;
	}

	/**
	 * @since 3.1
	 *
	 * @param IndicatorRegistry $indicatorRegistry
	 */
	public function setIndicatorRegistry( IndicatorRegistry $indicatorRegistry ) {
		$this->indicatorRegistry = $indicatorRegistry;
	}

	/**
	 * @since 1.9
	 *
	 * @param OutputPage $outputPage
	 * @param ParserOutput $parserOutput
	 */
	public function process( OutputPage &$outputPage, ParserOutput $parserOutput ) {

		$title = $outputPage->getTitle();

		if ( $title->isSpecialPage() || $title->isRedirect() ) {
			return true;
		}

		if ( !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return true;
		}

		$context = $outputPage->getContext();
		$request = $context->getRequest();

		$options = [
			'action' => $request->getVal( 'action' ),
			'diff' => $request->getVal( 'diff' ),
			'isRTL' => $context->getLanguage()->isRTL()
		];

		if ( $this->indicatorRegistry !== null && $this->indicatorRegistry->hasIndicator( $title, $options ) ) {
			$this->indicatorRegistry->attachIndicators( $outputPage );
		}

		$this->addFactbox( $outputPage, $parserOutput );
		$this->addPostProc( $title, $outputPage, $parserOutput );
	}

	private function addPostProc( $title, $outputPage, $parserOutput ) {

		$request = $outputPage->getContext()->getRequest();

		if ( in_array( $request->getVal( 'action' ), [ 'delete', 'purge', 'protect', 'unprotect', 'history', 'edit' ] ) ) {
			return '';
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$postProcHandler = $applicationFactory->create( 'PostProcHandler', $parserOutput );

		$html = $postProcHandler->getHtml(
			$title,
			$request
		);

		if ( $html !== '' ) {
			$outputPage->addModules( $postProcHandler->getModules() );
			$outputPage->addHtml( $html );
		}
	}

	protected function addFactbox( $outputPage, $parserOutput ) {

		$request = $outputPage->getContext()->getRequest();

		if ( isset( $outputPage->mSMWFactboxText ) && $request->getCheck( 'wpPreview' ) ) {
			return '';
		}

		$applicationFactory = ApplicationFactory::getInstance();

		$cachedFactbox = $applicationFactory->singleton( 'FactboxFactory' )->newCachedFactbox();

		$cachedFactbox->prepare(
			$outputPage,
			$this->getParserOutput( $outputPage, $parserOutput )
		);

		return true;
	}

	protected function getParserOutput( $outputPage, $parserOutput ) {

		if ( $outputPage->getContext()->getRequest()->getInt( 'oldid' ) ) {

			$text = $parserOutput->getText();

			$parserData = ApplicationFactory::getInstance()->newParserData(
				$outputPage->getTitle(),
				$parserOutput
			);

			$inTextAnnotationParser = ApplicationFactory::getInstance()->newInTextAnnotationParser( $parserData );
			$inTextAnnotationParser->parse( $text );

			return $parserData->getOutput();
		}

		return $parserOutput;
	}

}
