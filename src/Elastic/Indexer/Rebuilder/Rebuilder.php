<?php

namespace SMW\Elastic\Indexer\Rebuilder;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\Indexer\Document;
use SMW\Elastic\Indexer\DocumentCreator;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Installer;
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

	private const BATCH_SIZE = 100;

	/**
	 * Kept well below Elasticsearch's request size limit
	 */
	private const BATCH_BYTES = 10 * 1024 * 1024;

	private array $settings = [];

	/**
	 * @var Document[]
	 */
	private array $pendingDocuments = [];

	private int $pendingBytes = 0;

	private array $versions = [];

	private array $options = [];

	/**
	 * @since 3.0
	 */
	public function __construct(
		private ElasticClient $client,
		private Indexer $indexer,
		private FileIndexer $fileIndexer,
		private DocumentCreator $documentCreator,
		private Installer $installer,
	) {
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
	public function set( $key, $value ): void {
		$this->options[$key] = $value;
	}

	/**
	 * @since 3.0
	 */
	public function select( Store $store, array $conditions ): array {
		$connection = $store->getConnection( 'mw.db' );

		$res = $connection->newSelectQueryBuilder()
			->select( [ 'smw_id', 'smw_iw', 'smw_rev' ] )
			->from( SQLStore::ID_TABLE )
			->where( $conditions )
			->orderBy( 'smw_id' )
			->caller( __METHOD__ )
			->fetchResultSet();

		$last = $connection->newSelectQueryBuilder()
			->select( 'MAX(smw_id)' )
			->from( SQLStore::ID_TABLE )
			->caller( __METHOD__ )
			->fetchField();

		return [ $res, $last ];
	}

	/**
	 * @since 3.0
	 */
	public function rollover(): bool {
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
	public function prepare(): void {
		$this->client->setMaintenanceLock();

		$this->prepareIndexByType( ElasticClient::TYPE_DATA );
		$this->prepareIndexByType( ElasticClient::TYPE_LOOKUP );
	}

	/**
	 * @since 3.0
	 */
	public function deleteAndSetupIndices(): void {
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
	 */
	public function hasIndices(): bool {
		return $this->client->hasIndex( ElasticClient::TYPE_DATA ) &&
			$this->client->hasIndex( ElasticClient::TYPE_LOOKUP );
	}

	/**
	 * @since 3.0
	 */
	public function createIndices(): void {
		$this->createIndexByType( ElasticClient::TYPE_DATA );
		$this->createIndexByType( ElasticClient::TYPE_LOOKUP );
	}

	/**
	 * @since 3.0
	 */
	public function setDefaults(): bool {
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

		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param int $id
	 */
	public function delete( $id ): void {
		// In the same batch as the documents before it, which can hold an upsert for this ID
		$this->add( new Document( (int)$id, [], Document::TYPE_DELETE ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param int $id
	 * @param SemanticData $semanticData
	 */
	public function rebuild( $id, SemanticData $semanticData ): void {
		$dataItem = $semanticData->getSubject();
		$dataItem->setId( $id );

		$document = $this->documentCreator->newFromSemanticData( $semanticData );
		$document->setTextBody( $this->fetchRawText( $dataItem ) );

		$this->add( $document );

		if ( $dataItem->getNamespace() === NS_FILE && $this->indexesFiles() ) {
			// The file indexer updates the page's document, so that has to be indexed first
			$this->flush();

			$this->fileIndexer->setVersions( $this->versions );
			$this->fileIndexer->noSha1Check();
			$this->fileIndexer->index( $dataItem );
		}
	}

	private function indexesFiles(): bool {
		if ( isset( $this->options['skip-fileindex'] ) && $this->options['skip-fileindex'] ) {
			return false;
		}

		return (bool)$this->client->getConfig()->dotGet( 'indexer.experimental.file.ingest', false );
	}

	/**
	 * Sends the documents and deletions that rebuild() and delete() have not sent yet
	 *
	 * @since 7.4.1
	 */
	public function flush(): void {
		if ( $this->pendingDocuments === [] ) {
			return;
		}

		$this->indexer->setVersions( $this->versions );
		$this->indexer->indexDocuments( $this->pendingDocuments );

		$this->pendingDocuments = [];
		$this->pendingBytes = 0;
	}

	/**
	 * @since 7.4.1
	 */
	public function hasPendingDocuments(): bool {
		return $this->pendingDocuments !== [];
	}

	private function add( Document $document ): void {
		$bytes = strlen( $document->jsonSerialize() );

		if ( $this->pendingBytes + $bytes > self::BATCH_BYTES ) {
			$this->flush();
		}

		$this->pendingDocuments[] = $document;
		$this->pendingBytes += $bytes;

		if ( count( $this->pendingDocuments ) >= self::BATCH_SIZE ) {
			$this->flush();
		}
	}

	/**
	 * @since 3.0
	 */
	public function refresh(): bool {
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

		return true;
	}

	private function fetchRawText( WikiPage $dataItem ) {
		$config = $this->client->getConfig();

		if (
			!$config->dotGet( 'indexer.raw.text', false ) ||
			$dataItem->getSubobjectName() !== '' ) {
			return '';
		}

		$title = $dataItem->getTitle();
		if ( $title !== null ) {
			return $this->indexer->fetchNativeData( $title );
		}

		return '';
	}

	private function prepareIndexByType( string $type ): void {
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

	private function refreshIndexByType( string $type ): void {
		$this->client->refresh( [ 'index' => $this->client->getIndexName( $type ) ] );
	}

	private function setDefaultByType( string $type ): void {
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
		if ( !$this->client->isOpenSearch() &&
			version_compare( (string)$this->client->getVersion(), '6.4.0', '<' )
		) {
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

	private function createIndexByType( string $type ): void {
		// If for some reason a recent rebuild didn't finish, use
		// the locked version as master
		$version = $this->client->getLock( $type );
		if ( $version === false ) {
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

			$params = [
				'body' => [ 'actions' => $actions ]
			];

			$this->client->updateAliases( $params );
		}

		$this->versions[$type] = $version;
		$this->client->setLock( $type, $version );
	}

	private function rolloverByTypeAndVersion( string $type, $version ): void {
		$cliMsgFormatter = new CliMsgFormatter();

		$old = $this->installer->rollover(
			$type,
			$version
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols(
				sprintf( "... rollover from %s to %s ...", $old, $version ),
				CliMsgFormatter::OK,
				7
			)
		);
	}

}
