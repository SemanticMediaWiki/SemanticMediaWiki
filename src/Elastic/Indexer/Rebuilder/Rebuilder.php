<?php

namespace SMW\Elastic\Indexer\Rebuilder;

use Exception;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\Indexer\DocumentCreator;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Installer;
use SMW\SemanticData;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\CliMsgFormatter;

/**
 * @private
 *
 * @license GPL-2.0-or-later
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
	 * @var FileIndexer
	 */
	private $fileIndexer;

	/**
	 * @var DocumentCreator
	 */
	private $documentCreator;

	/**
	 * @var Installer
	 */
	private $installer;

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
	 * @param FileIndexer $fileIndexer
	 * @param DocumentCreator $documentCreator
	 * @param Installer $installer
	 */
	public function __construct( ElasticClient $client, Indexer $indexer, FileIndexer $fileIndexer, DocumentCreator $documentCreator, Installer $installer ) {
		$this->client = $client;
		$this->indexer = $indexer;
		$this->fileIndexer = $fileIndexer;
		$this->documentCreator = $documentCreator;
		$this->installer = $installer;
	}

	/**
	 * @since 3.0
	 *
	 * @return bool
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
	 * @return bool
	 */
	public function rollover() {
		if ( $this->versions === [] ) {
			return false;
		}

		$this->rolloverByTypeAndVersion(
			ElasticClient::TYPE_DATA,
			$this->versions[ElasticClient::TYPE_DATA]
		);

		$this->rolloverByTypeAndVersion(
			ElasticClient::TYPE_LOOKUP,
			$this->versions[ElasticClient::TYPE_LOOKUP]
		);
	}

	/**
	 * @since 3.0
	 */
	public function prepare() {
		$this->client->setMaintenanceLock();

		$this->prepareIndexByType( ElasticClient::TYPE_DATA );
		$this->prepareIndexByType( ElasticClient::TYPE_LOOKUP );
	}

	/**
	 * @since 3.0
	 */
	public function deleteAndSetupIndices() {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage( "\n" );

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( '   ... deleting indices and aliases ...' )
		);

		$this->installer->drop();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( '   ... setting up indices and aliases ...' )
		);

		$this->installer->setup();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);
	}

	/**
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function hasIndices() {
		return $this->client->hasIndex( ElasticClient::TYPE_DATA ) &&
			$this->client->hasIndex( ElasticClient::TYPE_LOOKUP );
	}

	/**
	 * @since 3.0
	 */
	public function createIndices() {
		$this->createIndexByType( ElasticClient::TYPE_DATA );
		$this->createIndexByType( ElasticClient::TYPE_LOOKUP );
	}

	/**
	 * @since 3.0
	 */
	public function setDefaults() {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage( "\n" );

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( '   ... finding default settings ... ' )
		);

		if ( !$this->client->hasIndex( ElasticClient::TYPE_DATA ) ) {

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->secondCol( CliMsgFormatter::FAILED )
			);

			return false;
		}

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->setDefaultByType( ElasticClient::TYPE_DATA );
		$this->setDefaultByType( ElasticClient::TYPE_LOOKUP );
	}

	/**
	 * @since 3.0
	 *
	 * @param int $id
	 */
	public function delete( $id ) {
		$index = $this->client->getIndexName( ElasticClient::TYPE_DATA );

		if ( isset( $this->versions[ElasticClient::TYPE_DATA] ) ) {
			$index = $index . '-' . $this->versions[ElasticClient::TYPE_DATA];
		}

		$params = [
			'index' => $index,
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
	 * @param int $id
	 * @param SemanticData $semanticData
	 */
	public function rebuild( $id, SemanticData $semanticData ) {
		$dataItem = $semanticData->getSubject();
		$dataItem->setId( $id );

		$fileIndexer = null;
		$skip = false;

		if ( isset( $this->options['skip-fileindex'] ) ) {
			$skip = (bool)$this->options['skip-fileindex'];
		}

		$config = $this->client->getConfig();

		if ( !$skip && $config->dotGet( 'indexer.experimental.file.ingest', false ) ) {
			$fileIndexer = $this->fileIndexer;
		}

		$this->indexer->setVersions( $this->versions );
		$this->indexer->isRebuild();
	// $this->indexer->setState( Indexer::REBUILD_STATE );

		$dataItem = $semanticData->getSubject();
		$dataItem->setId( $id );

		$document = $this->documentCreator->newFromSemanticData( $semanticData );
		$document->setTextBody( $this->fetchRawText( $dataItem ) );

		$this->indexer->indexDocument( $document, false );

		if ( $fileIndexer !== null ) {
			$fileIndexer->setVersions( $this->versions );
			$fileIndexer->noSha1Check();
			$fileIndexer->index( $dataItem );
		}
	}

	/**
	 * @since 3.0
	 */
	public function refresh() {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( '   ... refreshing indices ...' )
		);

		if ( !$this->client->hasIndex( ElasticClient::TYPE_DATA ) ) {

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->secondCol( CliMsgFormatter::FAILED )
			);

			return false;
		}

		$this->refreshIndexByType( ElasticClient::TYPE_DATA );
		$this->refreshIndexByType( ElasticClient::TYPE_LOOKUP );

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);
	}

	private function fetchRawText( $dataItem ) {
		$config = $this->client->getConfig();

		if (
			!$config->dotGet( 'indexer.raw.text', false ) ||
			$dataItem->getSubobjectName() !== '' ) {
			return '';
		}

		if ( ( $title = $dataItem->getTitle() ) !== null ) {
			return $this->indexer->fetchNativeData( $title );
		}

		return '';
	}

	private function prepareIndexByType( $type ) {
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

	private function refreshIndexByType( $type ) {
		$this->client->refresh( [ 'index' => $this->client->getIndexName( $type ) ] );
	}

	private function setDefaultByType( $type ) {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->oneCol( "... $type index ...", 3 )
		);

		$index = $this->client->getIndexName(
			$type
		);

		if ( $this->client->hasLock( $type ) ) {
			$this->rolloverByTypeAndVersion( $type, $this->client->getLock( $type ) );
		}

		$this->messageReporter->reportMessage(
			str_replace( "\n", '', $cliMsgFormatter->oneCol( "... closing", 7 ) )
		);

		// Certain changes ( ... to define new analyzers ...) requires to close
		// and reopen an index
		$this->client->closeIndex( $index );

		$indexDef = $this->client->getIndexDefinition( $type );
		$indexDef = json_decode( $indexDef, true );

		// Cannot be altered by a simple settings update and requires a complete
		// rebuild
		unset( $indexDef['settings']['number_of_shards'] );

		// #4341
		// ES 5.6 may cause a "Can't update [index.number_of_replicas] on closed
		// indices" see elastic/elasticsearch#22993 and should be fixed with ES 6.4.
		if ( !$this->client->isOpenSearch() && version_compare( $this->client->getVersion(), '6.4.0', '<' ) ) {
			unset( $indexDef['settings']['number_of_replicas'] );
		}

		$params = [
			'index' => $index,
			'body' => [
				'settings' => $indexDef['settings'] ?? []
			]
		];

		$this->client->putSettings( $params );

		$params = [
			'index' => $index,
			'body'  => $indexDef['mappings'] ?? []
		];

		$this->client->putMapping( $params );

		$this->messageReporter->reportMessage( ', reopening ...' );
		$this->client->openIndex( $index );

		$this->client->releaseLock( $type );

		$cliMsgFormatter->setFirstColLen(
			$cliMsgFormatter->getLen( '... closing, reopening ...', 7 )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);
	}

	private function createIndexByType( $type ) {
		// If for some reason a recent rebuild didn't finish, use
		// the locked version as master
		if ( ( $version = $this->client->getLock( $type ) ) === false ) {
			$version = $this->client->createIndex( $type );
		}

		if ( !$this->client->hasIndex( $type ) ) {
			$version = $this->client->createIndex( $type );
		}

		$index = $this->client->getIndexName( $type );

		// No Alias available, create one before the rollover
		if ( !$this->client->indexExists( "$index" ) ) {
			$actions = [
				[ 'add' => [ 'index' => "$index-$version", 'alias' => $index ] ]
			];

			$params['body'] = [ 'actions' => $actions ];

			$this->client->updateAliases( $params );
		}

		$this->versions[$type] = $version;
		$this->client->setLock( $type, $version );
	}

	private function rolloverByTypeAndVersion( $type, $version ) {
		$cliMsgFormatter = new CliMsgFormatter();

		$old = $this->installer->rollover(
			$type,
			$version
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( sprintf( "... rollover from %s to %s ...", $old, $version ), CliMsgFormatter::OK, 7 )
		);
	}

}
