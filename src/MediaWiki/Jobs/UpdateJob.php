<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use LinkCache;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Enum;
use SMW\EventHandler;
use Title;

/**
 * UpdateJob is responsible for the asynchronous update of semantic data
 * using MediaWiki's JobQueue infrastructure.
 *
 * Update jobs are created if, when saving an article,
 * it is detected that the content of other pages must be re-parsed as well (e.g.
 * due to some type change).
 *
 * @note This job does not update the page display or parser cache, so in general
 * it might happen that part of the wiki page still displays old data (e.g.
 * formatting in-page values based on a datatype thathas since been changed), whereas
 * the Factbox and query/browsing interfaces might already show the updated records.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Daniel M. Herzig
 * @author Markus Krötzsch
 * @author mwjames
 */
class UpdateJob extends Job {

	/**
	 * Enforces an update independent of the update marker status
	 */
	const FORCED_UPDATE = 'forcedUpdate';

	/**
	 * Indicates the use of the _CHGPRO property as base for the SemanticData
	 */
	const CHANGE_PROP = 'changeProp';

	/**
	 * Indicates the use of the semanticData parameter
	 */
	const SEMANTIC_DATA = 'semanticData';

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @since  1.9
	 *
	 * @param Title $title
	 * @param array $params
	 */
	function __construct( Title $title, $params = [] ) {
		parent::__construct( 'smw.update', $title, $params );
		$this->removeDuplicates = true;

		$this->isEnabledJobQueue(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgEnableUpdateJobs' )
		);
	}

	/**
	 * @see Job::run
	 *
	 * @return boolean
	 */
	public function run() {

		// #2199 ("Invalid or virtual namespace -1 given")
		if ( $this->getTitle()->isSpecialPage() ) {
			return true;
		}

		LinkCache::singleton()->clear();

		$this->applicationFactory = ApplicationFactory::getInstance();

		if ( !$this->hasParameter( self::FORCED_UPDATE ) && $this->matchesLastModified( $this->getTitle() ) ) {
			return true;
		}

		if ( $this->getTitle()->exists() ) {
			return $this->doUpdate();
		}

		$this->applicationFactory->getStore()->clearData(
			DIWikiPage::newFromTitle( $this->getTitle() )
		);

		return true;
	}

	private function matchesLastModified( $title ) {

		if ( !$this->getParameter( 'shallowUpdate' ) ) {
			return false;
		}

		$lastModified = $this->getLastModifiedTimestamp(
			DIWikiPage::newFromTitle( $title )
		);

		if ( $lastModified !== \WikiPage::factory( $title )->getTimestamp() ) {
			return false;
		}

		$pageUpdater = $this->applicationFactory->newPageUpdater();
		$pageUpdater->addPage( $title );
		$pageUpdater->waitOnTransactionIdle();
		$pageUpdater->doPurgeParserCache();

		return true;
	}

	private function doUpdate() {

		// ChangePropagationJob
		if ( $this->hasParameter( self::CHANGE_PROP ) ) {
			return $this->change_propagation( $this->getParameter( self::CHANGE_PROP ) );
		}

		if ( $this->hasParameter( self::SEMANTIC_DATA ) ) {
			return $this->set_data( $this->getParameter( self::SEMANTIC_DATA ) );
		}

		return $this->parse_content();
	}

	private function change_propagation( $dataItem ) {

		$this->setParameter( 'updateType', 'ChangePropagation' );
		$subject = DIWikiPage::doUnserialize( $dataItem );

		// Read the _CHGPRO property and fetch the serialized
		// SemanticData object
		$pv = $this->applicationFactory->getStore()->getPropertyValues(
			$subject,
			new DIProperty( DIProperty::TYPE_CHANGE_PROP )
		);

		if ( $pv === [] ) {
			return;
		}

		// PropertySpecificationChangeNotifier encodes the serialized content
		// using the JSON format
		$semanticData = json_decode( end( $pv )->getString(), true );

		$this->set_data(
			$semanticData
		);
	}

	private function set_data( $semanticData ) {

		$this->setParameter( 'updateType', 'SemanticData' );

		$semanticData = $this->applicationFactory->newSerializerFactory()->newSemanticDataDeserializer()->deserialize(
			$semanticData
		);

		$semanticData->removeProperty(
			new DIProperty( DIProperty::TYPE_CHANGE_PROP )
		);

		$parserData = $this->applicationFactory->newParserData(
			$this->getTitle(),
			new ParserOutput()
		);

		$parserData->setSemanticData( $semanticData );

		$parserData->setOption(
			Enum::OPT_SUSPEND_PURGE,
			false
		);

		return $this->updateStore( $parserData );
	}

	private function parse_content() {

		$this->setParameter( 'updateType', 'ContentParse' );

		$contentParser = $this->applicationFactory->newContentParser( $this->getTitle() );
		$contentParser->parse();

		if ( !( $contentParser->getOutput() instanceof ParserOutput ) ) {
			$this->setLastError( $contentParser->getErrors() );
			return false;
		}

		$parserData = $this->applicationFactory->newParserData(
			$this->getTitle(),
			$contentParser->getOutput()
		);

		// Suspend the purge as any preceding parse process most likely has
		// invalidated the cache for a selected subject
		$parserData->setOption(
			Enum::OPT_SUSPEND_PURGE,
			true
		);

		return $this->updateStore( $parserData );
	}

	private function updateStore( $parserData ) {

		$this->applicationFactory->getMediaWikiLogger()->info(
			[
				'Job',
				'UpdateJob',
				'{title}',
				'Type: {updateType}',
				'Origin: {origin}',
				'isForcedUpdate: {forcedUpdate}'
			],
			[
				'method' => __METHOD__,
				'role' => 'user',
				'title' => $this->getTitle()->getPrefixedDBKey(),
				'origin' => $this->getParameter( 'origin', 'N/A' ),
				'updateType' => $this->getParameter( 'updateType' ),
				'forcedUpdate' => $this->getParameter( self::FORCED_UPDATE )
			]
		);

		$eventHandler = EventHandler::getInstance();
		$eventDispatcher = $eventHandler->getEventDispatcher();

		$eventDispatcher->dispatch(
			'InvalidateEntityCache',
			[
				'context' => 'UpdateJob',
				'title' => $this->getTitle()
			]
		);

		// TODO
		// Rebuild the factbox

		$origin[] = 'UpdateJob';

		if ( $this->hasParameter( 'origin' ) ) {
			$origin[] = $this->getParameter( 'origin' );
		}

		if ( $this->hasParameter( 'ref' ) ) {
			$origin[] = $this->getParameter( 'ref' );
		}

		$parserData->setOrigin( $origin );

		$parserData->setOption(
			Enum::OPT_SUSPEND_PURGE,
			$this->getParameter( Enum::OPT_SUSPEND_PURGE )
		);

		$parserData->setOption(
			$parserData::OPT_FORCED_UPDATE,
			true
		);

		$parserData->setOption(
			$parserData::OPT_CHANGE_PROP_UPDATE,
			$this->getParameter( self::CHANGE_PROP )
		);

		$parserData->getSemanticData()->setOption(
			\SMW\SemanticData::OPT_LAST_MODIFIED,
			wfTimestamp( TS_UNIX )
		);

		$parserData->setOption(
			$parserData::OPT_CREATE_UPDATE_JOB,
			false
		);

		$parserData->getSemanticData()->setOption(
			Enum::PURGE_ASSOC_PARSERCACHE,
			(bool)$this->getParameter( Enum::PURGE_ASSOC_PARSERCACHE )
		);

		$parserData->updateStore();

		return true;
	}

	/**
	 * Convenience method to find last modified MW timestamp for a subject that
	 * has been added using the storage-engine.
	 */
	private function getLastModifiedTimestamp( DIWikiPage $wikiPage ) {

		$dataItems = $this->applicationFactory->getStore()->getPropertyValues(
			$wikiPage,
			new DIProperty( '_MDAT' )
		);

		if ( $dataItems !== [] ) {
			return end( $dataItems )->getMwTimestamp( TS_MW );
		}

		return 0;
	}

}
