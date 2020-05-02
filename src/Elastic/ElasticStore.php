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
use SMW\Utils\CliMsgFormatter;
use SMW\Elastic\Jobs\FileIngestJob;
use SMW\Elastic\Indexer\DocumentCreator;
use SMW\Elastic\Indexer\Indexer;

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
	const REBUILD_INDEX_RUN_INCOMPLETE = 'smw-elastic-rebuildelasticindex-run-incomplete';

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
		$status = parent::deleteSubject( $title );

		if ( $this->indexer === null ) {
			$this->indexer = $this->elasticFactory->newIndexer( $this, $this->messageReporter );
		}

		$this->indexer->setOrigin( 'ElasticStore::DeleteSubject' );
		$idList = [];

		if ( $status->has( 'delete_list' ) ) {
			$idList = $status->get( 'delete_list' );
		}

		$this->indexer->delete( $idList, $title->getNamespace() === SMW_NS_CONCEPT );

		return $status;
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
		$status = parent::changeTitle( $oldTitle, $newTitle, $pageId, $redirectId );

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

		if ( $status->has( 'delete_list' ) ) {
			$idList = array_merge( $idList, $status->get( 'delete_list' ) );
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

		return $status;
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

		if ( $connection->getConfig()->dotGet( 'query.fallback.no_connection' ) && !$connection->ping() ) {
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
		$status = parent::doDataUpdate( $semanticData );

		$time = -microtime( true );
		$config = $this->getConnection( 'elastic' )->getConfig();

		if ( $this->indexer === null ) {
			$this->indexer = $this->elasticFactory->newIndexer( $this, $this->messageReporter );
		}

		$this->indexer->setOrigin( 'ElasticStore::DoDataUpdate' );
		$subject = $semanticData->getSubject();
		$priorityDeleteList = [];

		if ( $status->has( 'delete_list' ) ) {
			$priorityDeleteList = $status->get( 'delete_list' );
		}

		if ( !$status->has( 'change_diff' ) ) {
			throw new RuntimeException( "Unable to replicate, missing a `change.diff` object!" );
		}

		$text = '';
		$rev_id = $semanticData->getExtensionData( 'revision_id' );

		if ( $config->dotGet( 'indexer.raw.text', false ) && $rev_id !== null ) {
			$text = $this->indexer->fetchNativeData( $rev_id );
		}

		$documentCreator = $this->elasticFactory->newDocumentCreator( $this );
		$document = $documentCreator->newFromSemanticData( $semanticData );

		$document->setPriorityDeleteList( $priorityDeleteList );
		$document->setTextBody( $text );

		$this->indexer->indexDocument( $document, Indexer::REQUIRE_SAFE_REPLICATION );

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

		// We always index (not upsert) since we want to have a complete state of
		// an entity (and ES would delete and insert any document) so trying
		// to filter and diff the data update has no real merit besides that it
		// would require us to read each ID in the update from ES and wire the data
		// back and forth which has shown to be ineffective especially when a
		// subject has many subobjects.
		//
		// The disadvantage is that we loose any auxiliary data that were attached
		// while not being part of the on-wiki information such as attachment
		// information from a file ingest.
		//
		// In order to reapply those information we could read them in the same
		// transaction before the actual update but since we expect the
		// `attachment.content` to contain a large chunk of text, we push that
		// into the job-queue so that the background process can take of it.
		//
		// Of course, this will cause a delay for the file content being searchable
		// but that should be acceptable to avoid blocking any online transaction.
		if ( $config->dotGet( 'indexer.experimental.file.ingest', false ) ) {
			FileIngestJob::pushIngestJob( $subject->getTitle() );
		}

		return $status;
	}

	/**
	 * @see SQLStore::setup
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function setup( $options = true ) {

		$cliMsgFormatter = new CliMsgFormatter();
		$client = $this->getConnection( 'elastic' );

		$installer = $this->elasticFactory->newInstaller( $client );
		$indices = $installer->setup();

		$version = $client->getVersion();

		if ( $options instanceof Options && $options->get( 'verbose' ) ) {

			if (
				$options->has( SMW_EXTENSION_SCHEMA_UPDATER ) &&
				$options->get( SMW_EXTENSION_SCHEMA_UPDATER ) ) {
				$this->messageReporter->reportMessage( $cliMsgFormatter->section( 'Sematic MediaWiki', 3, '=' ) );
				$this->messageReporter->reportMessage( "\n" . $cliMsgFormatter->head() );

				// Only output the head once hence for any succeeding processing
				// remove the marker.
				$options->set( SMW_EXTENSION_SCHEMA_UPDATER, false );
			}

			$setupFile = $installer->newSetupFile();

			// Remove REBUILD_INDEX_RUN_COMPLETE with 3.3+

			if ( $setupFile->get( ElasticStore::REBUILD_INDEX_RUN_COMPLETE ) !== null ) {
				$setupFile->remove( ElasticStore::REBUILD_INDEX_RUN_COMPLETE );
				$setupFile->set( [ 'elasticsearch' => [ 'latest_version' => $version ] ] );
			} elseif ( $setupFile->get( 'elasticsearch' ) === null ) {
				$setupFile->set( [ 'elasticsearch' => [ 'latest_version' => $version ] ] );
				$setupFile->addIncompleteTask( self::REBUILD_INDEX_RUN_INCOMPLETE );
			} else {
				$data = $setupFile->get( 'elasticsearch' );

				if ( $data['latest_version'] !== $version ) {
					$setupFile->set(
						[
							'elasticsearch' => [
								'latest_version'   => $version,
								'previous_version' => $data['latest_version']
							]
						]
					);
				}
			}

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->section( 'Indices setup' )
			);

			$this->messageReporter->reportMessage(
				"\n" . $cliMsgFormatter->twoCols( "Query engine:", 'SMWElasticStore' )
			);

			$this->messageReporter->reportMessage( "\nChecking indices ...\n" );

			foreach ( $indices as $index ) {
				$this->messageReporter->reportMessage(
					$cliMsgFormatter->twoCols( "... $index ...", CliMsgFormatter::OK, 3 )
				);
			}

			$this->messageReporter->reportMessage( "   ... done.\n" );
		}

		parent::setup( $options );
	}

	/**
	 * @see SQLStore::drop
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function drop( $verbose = true ) {

		$cliMsgFormatter = new CliMsgFormatter();
		$client = $this->getConnection( 'elastic' );

		$installer = $this->elasticFactory->newInstaller( $client );
		$indices = $installer->drop();

		$setupFile = $installer->newSetupFile();

		$setupFile->remove(
			ElasticStore::REBUILD_INDEX_RUN_COMPLETE
		);

		$setupFile->removeIncompleteTask(
			ElasticStore::REBUILD_INDEX_RUN_INCOMPLETE
		);

		$setupFile->remove( 'elasticsearch' );

		if ( $verbose ) {

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->section( 'Indices removal' )
			);

			$this->messageReporter->reportMessage(
				"\n" . $cliMsgFormatter->twoCols( "Query engine:", 'SMWElasticStore' )
			);

			$this->messageReporter->reportMessage( "\nDropped index ...\n" );

			foreach ( $indices as $index ) {
				$this->messageReporter->reportMessage(
					$cliMsgFormatter->twoCols( "... $index ...", CliMsgFormatter::OK, 3 )
				);
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

}
