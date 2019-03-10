<?php

namespace SMW\Elastic\Indexer;

use Exception;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\SemanticData;
use SMW\SQLStore\PropertyTableRowMapper;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Rebuilder {

	use MessageReporterAwareTrait;

	/**
	 * @var ElasticClient
	 */
	private $client;

	/**
	 * @var Indexer
	 */
	private $indexer;

	/**
	 * @var PropertyTableRowMapper
	 */
	private $propertyTableRowMapper;

	/**
	 * @var Rollover
	 */
	private $rollover;

	/**
	 * @var FileIndexer
	 */
	private $fileIndexer;

	/**
	 * @var array
	 */
	private $settings = [];

	/**
	 * @var array
	 */
	private $versions = [];

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @since 3.0
	 *
	 * @param ElasticClient $client
	 * @param Indexer $indexer
	 * @param PropertyTableRowMapper $propertyTableRowMapper
	 * @param Rollover $rollover
	 */
	public function __construct( ElasticClient $client, Indexer $indexer, PropertyTableRowMapper $propertyTableRowMapper, Rollover $rollover ) {
		$this->client = $client;
		$this->indexer = $indexer;
		$this->propertyTableRowMapper = $propertyTableRowMapper;
		$this->rollover = $rollover;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function ping() {
		return $this->client->ping();
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param array $conditions
	 *
	 * @return array
	 */
	public function select( Store $store, array $conditions ) {

		$connection = $store->getConnection( 'mw.db' );

		$res = $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_iw',
				'smw_rev'
			],
			$conditions,
			__METHOD__,
			[ 'ORDER BY' => 'smw_id' ]
		);

		$last = $connection->selectField(
			SQLStore::ID_TABLE,
			'MAX(smw_id)',
			'',
			__METHOD__
		);

		return [ $res, $last ];
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function rollover() {

		if ( $this->versions === [] ) {
			return false;
		}

		$this->rollover_version(
			ElasticClient::TYPE_DATA,
			$this->versions[ElasticClient::TYPE_DATA]
		);

		$this->rollover_version(
			ElasticClient::TYPE_LOOKUP,
			$this->versions[ElasticClient::TYPE_LOOKUP]
		);
	}

	/**
	 * @since 3.0
	 */
	public function prepare() {
		$this->prepare_index( ElasticClient::TYPE_DATA );
		$this->prepare_index( ElasticClient::TYPE_LOOKUP );
	}

	/**
	 * @since 3.0
	 */
	public function deleteAndSetupIndices() {

		$this->messageReporter->reportMessage( "\n   ... deleting indices and aliases ..." );
		$this->indexer->drop();

		$this->messageReporter->reportMessage( "\n   ... setting up indices and aliases ..." );
		$this->indexer->setup();
	}

	/**
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function hasIndices() {

		if ( !$this->client->hasIndex( ElasticClient::TYPE_DATA ) ) {
			return false;
		}

		if ( !$this->client->hasIndex( ElasticClient::TYPE_LOOKUP ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 3.0
	 */
	public function createIndices() {
		$this->create_index( ElasticClient::TYPE_DATA );
		$this->create_index( ElasticClient::TYPE_LOOKUP );
	}

	/**
	 * @since 3.0
	 */
	public function setDefaults() {

		if ( !$this->client->hasIndex( ElasticClient::TYPE_DATA ) ) {
			return false;
		}

		$this->messageReporter->reportMessage( "\n" . '   ... updating settings and mappings ...' );

		$this->set_default( ElasticClient::TYPE_DATA );
		$this->set_default( ElasticClient::TYPE_LOOKUP );

		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 */
	public function delete( $id ) {

		$index = $this->client->getIndexName( ElasticClient::TYPE_DATA );

		if ( isset( $this->versions[ElasticClient::TYPE_DATA] ) ) {
			$index = $index . '-' . $this->versions[ElasticClient::TYPE_DATA];
		}

		$params = [
			'index' => $index,
			'type' => ElasticClient::TYPE_DATA,
			'id' => $id
		];

		try {
			$this->client->delete( $params );
		} catch ( Exception $e ) {
			// Do nothing
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 * @param SemanticData $semanticData
	 */
	public function rebuild( $id, SemanticData $semanticData ) {

		if ( $this->fileIndexer === null ) {
			$skip = false;

			if ( isset( $this->options['skip-fileindex'] ) ) {
				$skip = (bool)$this->options['skip-fileindex'];
			}

			if ( !$skip && $this->client->getConfig()->dotGet( 'indexer.experimental.file.ingest', false ) ) {
				$this->fileIndexer = $this->indexer->getFileIndexer();
			} else {
				$this->fileIndexer = false;
			}
		}

		$changeOp = $this->propertyTableRowMapper->newChangeOp(
			$id,
			$semanticData
		);

		$dataItem = $semanticData->getSubject();
		$dataItem->setId( $id );

		$this->indexer->setVersions( $this->versions );
		$this->indexer->isRebuild();

		$changeDiff = $changeOp->newChangeDiff();
		$changeDiff->setAssociatedRev( $semanticData->getExtensionData( 'revision_id', 0 ) );

		$this->indexer->index(
			$changeDiff,
			$this->raw_text( $dataItem )
		);

		if ( $this->fileIndexer && $dataItem->getNamespace() === NS_FILE ) {
			$this->fileIndexer->noSha1Check();
			$this->fileIndexer->index( $dataItem, null );
		}
	}

	/**
	 * @since 3.0
	 */
	public function refresh() {

		if ( !$this->client->hasIndex( ElasticClient::TYPE_DATA ) ) {
			return false;
		}

		$this->messageReporter->reportMessage( "\n" . '   ... refreshing indices ...' );

		$this->refresh_index( ElasticClient::TYPE_DATA );
		$this->refresh_index( ElasticClient::TYPE_LOOKUP );

		return true;
	}

	private function raw_text( $dataItem ) {

		if ( !$this->client->getConfig()->dotGet( 'indexer.raw.text', false ) || $dataItem->getSubobjectName() !== ''  ) {
			return '';
		}

		if ( ( $title = $dataItem->getTitle() ) !== null ) {
			return $this->indexer->fetchNativeData( $title );
		}

		return '';
	}

	private function prepare_index( $type ) {

		$index = $this->client->getIndexName( $type );

		if ( isset( $this->versions[$type] ) ) {
			$index = "$index-" . $this->versions[$type];
		}

	 	// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/tune-for-indexing-speed.html
		$params = [
			'index' => $index,
			'body' => [
				'settings' => [
					'number_of_replicas' => 0,
					'refresh_interval' => -1
				]
			]
		];

		$this->client->putSettings( $params );
	}

	private function refresh_index( $type ) {
		$this->client->refresh( [ 'index' => $this->client->getIndexName( $type ) ] );
	}

	private function set_default( $type ) {

		$indices = $this->client->indices();

		$index = $this->client->getIndexName(
			$type
		);

		$this->messageReporter->reportMessage( "\n   ... '$type' index ... " );

		if ( $this->client->hasLock( $type ) ) {
			$this->rollover_version( $type, $this->client->getLock( $type ) );
		}

		$this->messageReporter->reportMessage( "\n      ... closing" );

		// Certain changes ( ... to define new analyzers ...) requires to close
		// and reopen an index
		$indices->close( [ 'index' => $index ] );

		$indexDef = $this->client->getIndexDefByType(
			$type
		);

		$indexDef = json_decode( $indexDef, true );

		// Cannot be altered by a simple settings update and requires a complete
		// rebuild
		unset( $indexDef['settings']['number_of_shards'] );

		$params = [
			'index' => $index,
			'body' => [
				'settings' => $indexDef['settings']
			]
		];

		$this->client->putSettings( $params );

		$params = [
			'index' => $index,
			'type'  => $type,
			'body'  => $indexDef['mappings']
		];

		$this->client->putMapping( $params );

		$this->messageReporter->reportMessage( ", reopening the index ... " );
		$indices->open( [ 'index' => $index ] );


		$this->client->releaseLock( $type );
	}

	private function create_index( $type ) {

		// If for some reason a recent rebuild didn't finish, use
		// the locked version as master
		if ( ( $version = $this->client->getLock( $type ) ) === false ) {
			$version = $this->client->createIndex( $type );
		}

		if ( !$this->client->hasIndex( $type ) ) {
			$version = $this->client->createIndex( $type );
		}

		$index = $this->client->getIndexName( $type );
		$indices = $this->client->indices();

		// No Alias available, create one before the rollover
		if ( !$indices->exists( [ 'index' => "$index" ] ) ) {
			$actions = [
				[ 'add' => [ 'index' => "$index-$version", 'alias' => $index ] ]
			];

			$params['body'] = [ 'actions' => $actions ];

			$indices->updateAliases( $params );
		}

		$this->versions[$type] = $version;
		$this->client->setLock( $type, $version );
	}

	private function rollover_version( $type, $version ) {

		$old = $this->rollover->rollover(
			$type,
			$version
		);

		$this->messageReporter->reportMessage(
			"\n" . sprintf( "      ... switching index version from %s to %s (rollover) ...", $old, $version )
		);
	}

}
