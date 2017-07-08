<?php

namespace SMW\MediaWiki\Jobs;

use LinkCache;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\EventHandler;
use SMW\Enum;
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
class UpdateJob extends JobBase {

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
	function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\UpdateJob', $title, $params );
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

		if ( $this->matchWikiPageLastModifiedToRevisionLastModified( $this->getTitle() ) ) {
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

	private function matchWikiPageLastModifiedToRevisionLastModified( $title ) {

		if ( $this->getParameter( 'pm' ) !== ( $this->getParameter( 'pm' ) | SMW_UJ_PM_CLASTMDATE ) ) {
			return false;
		}

		$lastModified = $this->getWikiPageLastModifiedTimestamp(
			DIWikiPage::newFromTitle( $title )
		);

		if ( $lastModified === \WikiPage::factory( $title )->getTimestamp() ) {
			$pageUpdater = $this->applicationFactory->newPageUpdater();
			$pageUpdater->addPage( $title );
			$pageUpdater->waitOnTransactionIdle();
			$pageUpdater->doPurgeParserCache();
			return true;
		}

		return false;
	}

	private function doUpdate() {

		// ChangePropagationJob
		if ( $this->hasParameter( self::CHANGE_PROP ) ) {
			return $this->doUpdateFromChangePropagation( $this->getParameter( self::CHANGE_PROP ) );
		}

		if ( $this->hasParameter( self::SEMANTIC_DATA ) ) {
			return $this->doUpdateFromSemanticData( $this->getParameter( self::SEMANTIC_DATA ) );
		}

		return $this->doUpdateFromFreshContentParse();
	}

	private function doUpdateFromChangePropagation( $dataItem ) {

		$subject = DIWikiPage::doUnserialize( $dataItem );

		// Read the _CHGPRO property and fetch the serialized
		// SemanticData object
		$pv = $this->applicationFactory->getStore()->getPropertyValues(
			$subject,
			new DIProperty( DIProperty::TYPE_CHANGE_PROP )
		);

		if ( $pv === array() ) {
			return;
		}

		// PropertySpecificationChangeNotifier encodes the serialized content
		// using the JSON format
		$semanticData = json_decode( end( $pv )->getString(), true );

		$this->doUpdateFromSemanticData(
			$semanticData
		);
	}

	private function doUpdateFromSemanticData( $semanticData ) {

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

	/**
	 * SMW_UJ_PM_NP = new Parser to avoid "Parser state cleared" exception
	 */
	private function doUpdateFromFreshContentParse() {

		$contentParser = $this->applicationFactory->newContentParser( $this->getTitle() );

		if ( $this->getParameter( 'pm' ) === ( $this->getParameter( 'pm' ) | SMW_UJ_PM_NP ) ) {
			$contentParser->setParser(
				new \Parser( $GLOBALS['wgParserConf'] )
			);
		}

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

		$eventHandler = EventHandler::getInstance();

		$dispatchContext = $eventHandler->newDispatchContext();
		$dispatchContext->set( 'title', $this->getTitle() );

		$eventHandler->getEventDispatcher()->dispatch(
			'factbox.cache.delete',
			$dispatchContext
		);

		$eventHandler->getEventDispatcher()->dispatch(
			'cached.propertyvalues.prefetcher.reset',
			$dispatchContext
		);

		// TODO
		// Rebuild the factbox

		$parserData->setOrigin( 'UpdateJob' );

		$parserData->setOption(
			$parserData::OPT_FORCED_UPDATE,
			$this->getParameter( self::FORCED_UPDATE )
		);

		$parserData->setOption(
			$parserData::OPT_CHANGE_PROP_UPDATE,
			$this->getParameter( self::CHANGE_PROP )
		);

		$parserData->getSemanticData()->setOption(
			\SMW\SemanticData::OPT_LAST_MODIFIED,
			wfTimestamp( TS_UNIX )
		);

		$parserData->disableBackgroundUpdateJobs();
		$parserData->updateStore();

		return true;
	}

	/**
	 * Convenience method to find last modified MW timestamp for a subject that
	 * has been added using the storage-engine.
	 */
	private function getWikiPageLastModifiedTimestamp( DIWikiPage $wikiPage ) {

		$dataItems = $this->applicationFactory->getStore()->getPropertyValues(
			$wikiPage,
			new DIProperty( '_MDAT' )
		);

		if ( $dataItems !== array() ) {
			return end( $dataItems )->getMwTimestamp( TS_MW );
		}

		return 0;
	}

}
