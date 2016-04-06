<?php

namespace SMW\MediaWiki\Hooks;

use LinksUpdate;
use SMW\ApplicationFactory;
use SMW\SemanticData;
use Title;

/**
 * LinksUpdateConstructed hook is called at the end of LinksUpdate()
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class LinksUpdateConstructed {

	/**
	 * @var LinksUpdate
	 */
	protected $linksUpdate = null;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @var boolean
	 */
	private $enabledDeferredUpdate = true;

	/**
	 * @since  1.9
	 *
	 * @param LinksUpdate $linksUpdate
	 */
	public function __construct( LinksUpdate $linksUpdate ) {
		$this->linksUpdate = $linksUpdate;
	}

	/**
	 * @since 2.4
	 */
	public function disableDeferredUpdate() {
		$this->enabledDeferredUpdate = false;
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$this->applicationFactory = ApplicationFactory::getInstance();
		$title = $this->linksUpdate->getTitle();

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->applicationFactory->newParserData(
			$title,
			$this->linksUpdate->getParserOutput() );

		if ( $this->isSemanticEnabledNamespace( $title ) && $parserData->getSemanticData()->isEmpty() ) {
			$this->updateEmptySemanticData( $parserData, $title );
		}

		$parserData->updateStore(
			$this->enabledDeferredUpdate
		);

		return true;
	}

	/**
	 * #347 showed that an external process (e.g. RefreshLinksJob) can inject a
	 * ParserOutput without/cleared SemanticData which forces the Store updater
	 * to create an empty container that will clear all existing data.
	 *
	 * To ensure that for a Title and its current revision an empty ParserOutput
	 * object is really meant to be "empty" (e.g. delete action initiated by a
	 * human) the content is re-parsed in order to fetch the newest available data
	 *
	 * @note Parsing is expensive but it is more expensive to loose data or to
	 * expect that an external process adheres the object contract
	 */
	private function updateEmptySemanticData( &$parserData, $title ) {

		wfDebug( __METHOD__ . ' Empty SemanticData : ' . $title->getPrefixedDBkey() . "\n" );

		$semanticData = $this->reparseToFetchSemanticData( $title );

		if ( $semanticData instanceof SemanticData ) {
			$parserData->setSemanticData( $semanticData );
		}
	}

	private function reparseToFetchSemanticData( $title ) {

		$contentParser = $this->applicationFactory->newContentParser( $title );
		$parserOutput = $contentParser->parse()->getOutput();

		if ( $parserOutput === null ) {
			return null;
		}

		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			return $parserOutput->getExtensionData( 'smwdata' );
		}

		return $parserOutput->mSMWData;
	}

	private function isSemanticEnabledNamespace( Title $title ) {
		return $this->applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
	}

}
