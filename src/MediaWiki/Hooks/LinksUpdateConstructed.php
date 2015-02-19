<?php

namespace SMW\MediaWiki\Hooks;

use LinksUpdate;
use SMW\ApplicationFactory;
use SMW\SemanticData;
use SMW\SemanticDataCache;

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
	 * @var CacheFactory
	 */
	private $cacheFactory = null;

	/**
	 * @since  1.9
	 *
	 * @param LinksUpdate $linksUpdate
	 */
	public function __construct( LinksUpdate $linksUpdate ) {
		$this->linksUpdate = $linksUpdate;
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->cacheFactory = $this->applicationFactory->newCacheFactory();

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->applicationFactory->newParserData(
			$this->linksUpdate->getTitle(),
			$this->linksUpdate->getParserOutput() );

		if ( $parserData->getSemanticData()->isEmpty() ) {
			$this->updateEmptySemanticData( $parserData, $this->linksUpdate->getTitle() );
		}

		$this->cacheFactory->getSemanticDataCache()->save( $parserData->getSemanticData() );

		$parserData->updateStore();

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

		if ( $this->cacheFactory->getSemanticDataCache()->has( $title ) ) {
			$semanticData = $this->cacheFactory->getSemanticDataCache()->get( $title );
		} else {
			$semanticData = $this->reparseToFetchSemanticData( $title );
		}

		if ( $semanticData instanceOf SemanticData ) {
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

}
