<?php

namespace SMW\Elastic;

use Hooks;
use RuntimeException;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\SQLStore;
use SMWQuery as Query;
use SMW\Options;
use Title;
use SMW\SetupFile;

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
	 * Setup key to verify that the `rebuildElasticIndex.php` has been executed.
	 */
	const REBUILD_INDEX_RUN_COMPLETE = 'elastic.rebuild_index_run_complete';

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
	 * @since 3.1
	 *
	 * @param ElasticFactory $elasticFactory
	 */
	public function setElasticFactory( ElasticFactory $elasticFactory ) {
		$this->elasticFactory = $elasticFactory;
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
			//
			$this->servicesContainer->add( 'IndicatorProvider', function() {
				return $this->elasticFactory->newIndicatorProvider(
					$this
				);
			} );
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
		$changeDiff = $this->extensionData['change.diff'];
		$rev_id = $semanticData->getExtensionData( 'revision_id' );
		$changeDiff->setAssociatedRev( $rev_id );

		if ( $config->dotGet( 'indexer.raw.text', false ) && $rev_id !== null ) {
			$text = $this->indexer->fetchNativeData( $rev_id );
		}

		$this->indexer->safeReplicate(
			$changeDiff,
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

		if ( $config->dotGet( 'indexer.experimental.file.ingest', false ) && $semanticData->getOption( 'is.fileupload' ) ) {
			$this->ingestFile( $subject->getTitle() );
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

		$indices = $this->indexer->setup();

		if ( $verbose instanceof Options && $verbose->get( 'verbose' ) ) {

			$setupFile = new SetupFile();

			if ( $setupFile->get( ElasticStore::REBUILD_INDEX_RUN_COMPLETE ) === null ) {
				$setupFile->set(
					[
						ElasticStore::REBUILD_INDEX_RUN_COMPLETE => false
					]
				);
			}

			$this->messageReporter->reportMessage( "\n" );
			$this->messageReporter->reportMessage( 'Query engine: "SMWElasticStore"' );
			$this->messageReporter->reportMessage( "\n" );
			$this->messageReporter->reportMessage( "\nSetting up indices ...\n" );

			foreach ( $indices as $index ) {
				$this->messageReporter->reportMessage( "   ... $index ...\n" );
			}

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

		$indices = $this->indexer->drop();

		$setupFile = new SetupFile();

		$setupFile->remove(
			ElasticStore::REBUILD_INDEX_RUN_COMPLETE
		);

		if ( $verbose ) {
			$this->messageReporter->reportMessage( "\n" );
			$this->messageReporter->reportMessage( 'Query engine: "SMWElasticStore"' );
			$this->messageReporter->reportMessage( "\n" );
			$this->messageReporter->reportMessage( "\nDropping indices ...\n" );

			foreach ( $indices as $index ) {
				$this->messageReporter->reportMessage( "   ... $index ...\n" );
			}

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

	private function ingestFile( $title, array $params = [] ) {

		if ( $title->getNamespace() !== NS_FILE ) {
			return;
		}

		$this->indexer->getFileIndexer()->pushIngestJob( $title, $params );
	}

}
