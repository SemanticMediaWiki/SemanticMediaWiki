<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Enum;
use SMW\Listener\EventListener\EventHandler;
use SMW\MediaWiki\Job;
use SMW\MediaWiki\PageCreator;
use SMW\SerializerFactory;
use SMW\Settings;
use SMW\Store;

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
 * @license GPL-2.0-or-later
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
	 * Bypasses a full content parse and only purges the parser cache if the
	 * stored last-modified timestamp still matches the page's current revision
	 * timestamp.
	 */
	const SHALLOW_UPDATE = 'shallowUpdate';

	/**
	 * Indicates the use of the _CHGPRO property as base for the SemanticData
	 */
	const CHANGE_PROP = 'changeProp';

	/**
	 * Indicates the use of the semanticData parameter
	 */
	const SEMANTIC_DATA = 'semanticData';

	private readonly Settings $settings;

	private readonly PageCreator $pageCreator;

	private readonly PageUpdaterFactory $pageUpdaterFactory;

	private readonly SerializerFactory $serializerFactory;

	private readonly ContentParserFactory $contentParserFactory;

	private readonly ParserDataFactory $parserDataFactory;

	/**
	 * @since  1.9
	 */
	public function __construct(
		Title $title,
		array $params,
		Store $store,
		Settings $settings,
		PageCreator $pageCreator,
		PageUpdaterFactory $pageUpdaterFactory,
		SerializerFactory $serializerFactory,
		ContentParserFactory $contentParserFactory,
		ParserDataFactory $parserDataFactory,
		LoggerInterface $logger
	) {
		parent::__construct( 'smw.update', $title, $params );
		$this->setStore( $store );
		$this->title = $title;
		$this->removeDuplicates = true;
		$this->settings = $settings;
		$this->pageCreator = $pageCreator;
		$this->pageUpdaterFactory = $pageUpdaterFactory;
		$this->serializerFactory = $serializerFactory;
		$this->contentParserFactory = $contentParserFactory;
		$this->parserDataFactory = $parserDataFactory;
		$this->setLogger( $logger );

		$this->isEnabledJobQueue( $settings->get( 'smwgEnableUpdateJobs' ) );
	}

	/**
	 * @see Job::run
	 */
	public function run() {
		// #2199 ("Invalid or virtual namespace -1 given")
		if ( $this->getTitle()->isSpecialPage() ) {
			return true;
		}

		MediaWikiServices::getInstance()->getLinkCache()->clear();

		if ( !$this->hasParameter( self::FORCED_UPDATE ) && $this->matchesLastModified( $this->getTitle() ) ) {
			return true;
		}

		if ( $this->getTitle()->exists() ) {
			return $this->doUpdate();
		}

		$this->store->clearData(
			WikiPage::newFromTitle( $this->getTitle() )
		);

		return true;
	}

	private function matchesLastModified( ?Title $title ): bool {
		if ( !$this->getParameter( self::SHALLOW_UPDATE ) ) {
			return false;
		}

		$lastModified = $this->getLastModifiedTimestamp(
			WikiPage::newFromTitle( $title )
		);

		$wikiPage = $this->pageCreator->createPage( $title );

		if ( $lastModified !== $wikiPage->getTimestamp() ) {
			return false;
		}

		$pageUpdater = $this->pageUpdaterFactory->newPageUpdater();
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

	private function change_propagation( $dataItem ): void {
		$this->setParameter( 'updateType', 'ChangePropagation' );
		$subject = WikiPage::doUnserialize( $dataItem );

		// Read the _CHGPRO property and fetch the serialized
		// SemanticData object
		$pv = $this->store->getPropertyValues(
			$subject,
			new Property( Property::TYPE_CHANGE_PROP )
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

	private function set_data( $semanticData ): bool {
		$this->setParameter( 'updateType', 'SemanticData' );

		$semanticData = $this->serializerFactory->newSemanticDataDeserializer()->deserialize(
			$semanticData
		);

		$semanticData->removeProperty(
			new Property( Property::TYPE_CHANGE_PROP )
		);

		$parserData = $this->parserDataFactory->newParserData(
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

	private function parse_content(): bool {
		$this->setParameter( 'updateType', 'ContentParse' );

		$contentParser = $this->contentParserFactory->newContentParser( $this->getTitle() );
		$contentParser->parse();

		if ( !( $contentParser->getOutput() instanceof ParserOutput ) ) {
			$this->setLastError( implode( ' ', $contentParser->getErrors() ) );
			return false;
		}

		$parserData = $this->parserDataFactory->newParserData(
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

	private function updateStore( $parserData ): bool {
		$this->logger->info(
			'Job UpdateJob {title} Type: {updateType} Origin: {origin} '
				. 'isForcedUpdate: {forcedUpdate}',
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

		$origin = [];
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

		if ( $this->hasParameter( self::FORCED_UPDATE ) ) {
			$parserData->getSemanticData()->setOption(
				Enum::FORCED_UPDATE,
				wfTimestamp( TS_UNIX )
			);
		}

		$parserData->setOption(
			$parserData::OPT_CHANGE_PROP_UPDATE,
			$this->getParameter( self::CHANGE_PROP )
		);

		$parserData->getSemanticData()->setOption(
			SemanticData::OPT_LAST_MODIFIED,
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
	private function getLastModifiedTimestamp( WikiPage $wikiPage ) {
		$dataItems = $this->store->getPropertyValues(
			$wikiPage,
			new Property( '_MDAT' )
		);

		if ( $dataItems !== [] ) {
			return end( $dataItems )->getMwTimestamp( TS_MW );
		}

		return 0;
	}

}
