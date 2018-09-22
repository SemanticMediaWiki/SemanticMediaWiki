<?php

namespace SMW\Elastic;

use Hooks;
use RuntimeException;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\SQLStore;
use SMWQuery as Query;
use Title;

/**
 * @private
 *
 * The `ElasticStore` is the interface to an `Elasticsearch` cluster both in
 * regards for replicating data to a cluster as well as retrieving search results
 * from it.
 *
 * `Elasticsearch` is expected:
 * - to be used as search (aka query) engine with all other data management tasks
 *   to be carried out using the default `SQLStore`.
 * - to inherit most of the `SQLStore` methods
 *
 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/README.md
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticStore extends SQLStore {

	/**
	 * @var ElasticFactory
	 */
	private $elasticFactory;

	/**
	 * @var Indexer
	 */
	private $indexer;

	/**
	 * @var QueryEngine
	 */
	private $queryEngine;

	/**
	 * @since 3.0
	 */
	public function __construct() {
		parent::__construct();
		$this->elasticFactory = new ElasticFactory();
	}

	/**
	 * @see Store::service
	 *
	 * {@inheritDoc}
	 */
	public function service( $service, ...$args ) {

		if ( $this->servicesContainer === null ) {
			$this->servicesContainer = parent::newServicesContainer();

			// Replace an existing (or add) SQLStore service with a ES specific
			// optimized service

			// $this->servicesContainer->add( 'ProximityPropertyValueLookup', function() {
			//	return $this->elasticFactory->newProximityPropertyValueLookup( $this );
			// } );
		}

		return $this->servicesContainer->get( $service, ...$args );
	}

	/**
	 * @see SQLStore::deleteSubject
	 * @since 3.0
	 *
	 * @param Title $title
	 */
	public function deleteSubject( Title $title ) {
		parent::deleteSubject( $title );

		if ( $this->indexer === null ) {
			$this->indexer = $this->elasticFactory->newIndexer( $this, $this->messageReporter );
		}

		$this->indexer->setOrigin( 'ElasticStore::DeleteSubject' );
		$idList = [];

		if ( isset( $this->extensionData['delete.list'] ) ) {
			$idList = $this->extensionData['delete.list'];
		}

		$this->indexer->delete( $idList, $title->getNamespace() === SMW_NS_CONCEPT );

		unset( $this->extensionData['delete.list'] );
	}

	/**
	 * @see SQLStore::changeTitle
	 * @since 3.0
	 *
	 * @param Title $oldtitle
	 * @param Title $newtitle
	 * @param integer $pageid
	 * @param integer $redirid
	 */
	public function changeTitle( Title $oldTitle, Title $newTitle, $pageId, $redirectId = 0 ) {
		parent::changeTitle( $oldTitle, $newTitle, $pageId, $redirectId );

		$id = $this->getObjectIds()->getSMWPageID(
			$oldTitle->getDBkey(),
			$oldTitle->getNamespace(),
			'',
			'',
			false
		);

		if ( $this->indexer === null ) {
			$this->indexer = $this->elasticFactory->newIndexer( $this, $this->messageReporter );
		}

		$this->indexer->setOrigin( 'ElasticStore::ChangeTitle' );
		$idList = [ $id ];

		if ( isset( $this->extensionData['delete.list'] ) ) {
			$idList = array_merge( $idList, $this->extensionData['delete.list'] );
		}

		$this->indexer->delete( $idList );

		// Use case [[Foo]] redirects to #REDIRECT [[Bar]] with Bar not yet being
		// materialized and with the update not having created any reference,
		// fulfill T:Q0604 by allowing to create a minimized document body
		if ( $newTitle->exists() === false ) {
			$id = $this->getObjectIds()->getSMWPageID(
				$newTitle->getDBkey(),
				$newTitle->getNamespace(),
				'',
				'',
				false
			);

			$dataItem = DIWikiPage::newFromTitle( $newTitle );
			$dataItem->setId( $id );

			$this->indexer->create( $dataItem );
		}

		unset( $this->extensionData['delete.list'] );
	}

	/**
	 * @see SQLStore::fetchQueryResult
	 * @since 3.0
	 *
	 * @param Query $query
	 *
	 * @return QueryResult
	 */
	public function getQueryResult( Query $query ) {

		$result = null;
		$time = -microtime( true );

		$connection = $this->getConnection( 'elastic' );

		if ( $this->queryEngine === null ) {
			$this->queryEngine = $this->elasticFactory->newQueryEngine( $this );
		}

		if ( $connection->getConfig()->dotGet( 'query.fallback.no.connection' ) && !$connection->ping() ) {
			return parent::getQueryResult( $query );
		}

		$params = [
			$this,
			$query,
			&$result,
			$this->queryEngine
		];

		if ( Hooks::run( 'SMW::Store::BeforeQueryResultLookupComplete', $params ) ) {
			$result = $this->queryEngine->getQueryResult( $query );
		}

		$params = [
			$this,
			&$result
		];

		Hooks::run( 'SMW::ElasticStore::AfterQueryResultLookupComplete', $params );
		Hooks::run( 'SMW::Store::AfterQueryResultLookupComplete', $params );

		$query->setOption( Query::PROC_QUERY_TIME, microtime( true ) + $time );

		return $result;
	}

	/**
	 * @see SQLStore::doDataUpdate
	 * @since 3.0
	 *
	 * @param SemanticData $semanticData
	 */
	protected function doDataUpdate( SemanticData $semanticData ) {
		parent::doDataUpdate( $semanticData );

		$time = -microtime( true );
		$config = $this->getConnection( 'elastic' )->getConfig();

		if ( $this->indexer === null ) {
			$this->indexer = $this->elasticFactory->newIndexer( $this, $this->messageReporter );
		}

		$this->indexer->setOrigin( 'ElasticStore::DoDataUpdate' );
		$subject = $semanticData->getSubject();

		if ( isset( $this->extensionData['delete.list'] ) ) {
			$this->indexer->delete( $this->extensionData['delete.list'] );
		}

		if ( !isset( $this->extensionData['change.diff'] ) ) {
			throw new RuntimeException( "Unable to replicate, missing a `change.diff` object!" );
		}

		$text = '';

		if ( $config->dotGet( 'indexer.raw.text', false ) && ( $revID = $semanticData->getExtensionData( 'revision_id' ) ) !== null ) {
			$text = $this->indexer->fetchNativeData( $revID );
		}

		$this->indexer->safeReplicate(
			$this->extensionData['change.diff'],
			$text
		);

		unset( $this->extensionData['delete.list'] );
		unset( $this->extensionData['change.diff'] );

		$this->logger->info(
			[
				'ElasticStore',
				'Data update completed',
				'procTime in sec: {procTime}',
			],
			[
				'method' => __METHOD__,
				'role' => 'production',
				'procTime' => microtime( true ) + $time,
			]
		);

		if ( $subject->getNamespace() === NS_FILE && $config->dotGet( 'indexer.experimental.file.ingest', false ) && $semanticData->getOption( 'is.fileupload' ) ) {
			$this->indexer->getFileIndexer()->planIngestJob( $subject->getTitle() );
 		}
	}

	/**
	 * @see SQLStore::setup
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function setup( $verbose = true ) {

		if ( $this->indexer === null ) {
			$this->indexer = $this->elasticFactory->newIndexer( $this, $this->messageReporter );
		}

		$this->indexer->setup();

		if ( $verbose ) {
			$this->messageReporter->reportMessage( "\n" );
			$this->messageReporter->reportMessage( 'Selected query engine: "SMWElasticStore"' );
			$this->messageReporter->reportMessage( "\n" );
			$this->messageReporter->reportMessage( "\nSetting up indices ...\n" );
			$this->messageReporter->reportMessage( "   ... done.\n" );
		}

		parent::setup( $verbose );
	}

	/**
	 * @see SQLStore::drop
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function drop( $verbose = true ) {

		if ( $this->indexer === null ) {
			$this->indexer = $this->elasticFactory->newIndexer( $this, $this->messageReporter );
		}

		$this->indexer->drop();

		if ( $verbose ) {
			$this->messageReporter->reportMessage( "\n" );
			$this->messageReporter->reportMessage( 'Selected query engine: "SMWElasticStore"' );
			$this->messageReporter->reportMessage( "\n" );
			$this->messageReporter->reportMessage( "\nDropping indices ...\n" );
			$this->messageReporter->reportMessage( "   ... done.\n" );
		}

		parent::drop( $verbose );
	}

	/**
	 * @see SQLStore::clear
	 * @since 3.0
	 */
	public function clear() {
		parent::clear();
		$this->indexer = null;
		$this->queryEngine = null;
	}

	/**
	 * @see Store::getInfo
	 * @since 3.0
	 *
	 * @param string|null $type
	 *
	 * @return array
	 */
	public function getInfo( $type = null ) {

		if ( $type === 'store' ) {
			return 'SMWElasticStore';
		}

		$database = $this->getConnection( 'mw.db' );
		$client = $this->getConnection( 'elastic' );

		if ( $type === 'db' ) {
			return $database->getInfo();
		}

		if ( $type === 'es' ) {
			return $client->getVersion();
		}

		return [
			'SMWElasticStore' => $database->getInfo() + [ 'es' => $client->getVersion() ]
		];
	}

}
