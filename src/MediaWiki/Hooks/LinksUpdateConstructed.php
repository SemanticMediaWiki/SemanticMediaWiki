<?php

namespace SMW\MediaWiki\Hooks;

use LinksUpdate;
use SMW\ApplicationFactory;
use SMW\SemanticData;
use Title;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

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
class LinksUpdateConstructed implements LoggerAwareInterface {

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @var boolean
	 */
	private $enabledDeferredUpdate = true;

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
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
	 * @param LinksUpdate $linksUpdate
	 *
	 * @return true
	 */
	public function process( LinksUpdate $linksUpdate ) {

		$this->applicationFactory = ApplicationFactory::getInstance();
		$title = $linksUpdate->getTitle();

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->applicationFactory->newParserData(
			$title,
			$linksUpdate->getParserOutput()
		);

		if ( $this->isSemanticEnabledNamespace( $title ) && $parserData->getSemanticData()->isEmpty() ) {
			$this->updateEmptySemanticData( $parserData, $title );
		}

		// Push updates on properties directly without delay
		if ( $title->getNamespace() === SMW_NS_PROPERTY ) {
			$this->enabledDeferredUpdate = false;
		}

		// Scan the ParserOutput for a possible externally set option
		if ( $linksUpdate->getParserOutput()->getExtensionData( $parserData::OPT_FORCED_UPDATE ) === true ) {
			$parserData->setOption( $parserData::OPT_FORCED_UPDATE, true );
		}

		// Update incurred by a template change and is signaled through
		// the following condition
		if ( $linksUpdate->mTemplates !== [] && $linksUpdate->mRecursive === false ) {
			$parserData->setOption( $parserData::OPT_FORCED_UPDATE, true );
		}

		$parserData->setOrigin( 'LinksUpdateConstructed' );

		$parserData->updateStore(
			$this->enabledDeferredUpdate
		);

		// Track the update on per revision because MW 1.29 made the LinksUpdate a
		// EnqueueableDataUpdate which creates updates as JobSpecification
		// (refreshLinksPrioritized) and posses a possibility of running an
		// update more than once for the same RevID
		$parserData->markUpdate(
			$title->getLatestRevID( Title::GAID_FOR_UPDATE )
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

		$this->log( __METHOD__ . ' Empty SemanticData : ' . $title->getPrefixedDBkey() . "\n" );

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

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
