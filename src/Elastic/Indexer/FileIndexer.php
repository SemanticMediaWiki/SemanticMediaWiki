<?php

namespace SMW\Elastic\Indexer;

use File;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\EntityCache;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\QueryEngine\FieldMapper;
use SMW\Store;
use SMWContainerSemanticData as ContainerSemanticData;
use SMW\MediaWiki\RevisionGuardAwareTrait;
use SMW\Elastic\Indexer\Attachment\FileHandler;
use SMW\Elastic\Indexer\Attachment\FileAttachment;
use Title;

/**
 * File indexer to use the Elasticsearch ingest pipeline to index and retrieve
 * data from an file (aka. attachment) and make the file content searchable
 * outside of a normal wiki content (i.e. the indexed data is only stored in
 * Elasticsearch).
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FileIndexer {

	use MessageReporterAwareTrait;
	use RevisionGuardAwareTrait;
	use LoggerAwareTrait;

	const INGEST_RESPONSE = 'es.ingest.response';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var FileHandler
	 */
	private $fileHandler;

	/**
	 * @var FileAttachment
	 */
	private $fileAttachment;

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @var boolean
	 */
	private $sha1Check = true;

	/**
	 * @var []
	 */
	private $versions = [];

	/**
	 * @since 3.0
	 *
	 * @param Indexer $indexer
	 * @param EntityCache $entityCache
	 * @param FileHandler $fileHandler
	 * @param FileAttachment $fileAttachment
	 */
	public function __construct( Store $store, EntityCache $entityCache, FileHandler $fileHandler, FileAttachment $fileAttachment ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
		$this->fileHandler = $fileHandler;
		$this->fileAttachment = $fileAttachment;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 3.0
	 *
	 * @param [] $versions
	 */
	public function setVersions( array $versions ) {
		$this->versions = $versions;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getIndexName( $type ) {

		$index = $this->store->getConnection( 'elastic' )->getIndexNameByType(
			$type
		);

		// If the rebuilder has set a specific version, use it to avoid writing to
		// the alias of the index when running a rebuild.
		if ( isset( $this->versions[$type] ) ) {
			$index = "$index-" . $this->versions[$type];
		}

		return $index;
	}

	/**
	 * @since 3.0
	 */
	public function noSha1Check() {
		$this->sha1Check = false;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return File
	 */
	public function findFile( Title $title ) {
		return $this->fileHandler->findFileByTitle( $title );
	}

	/**
	 * The ES ingest pipeline only does create (not update) index content which
	 * means any other content is deleted after the ingest process has finished
	 * therefore:
	 *
	 * - Read the document before, and retrieve any annotations that exists for
	 * that entity
	 * - Let ES ingest the file content and attach the earlier retrieved
	 * annotations
	 * - SMW doesn't know anything about the file attachment details ES has gather
	 * from the file hence update the SQLStore (!important not the ElasticStore)
	 * with the data
	 * - After the SQLStore update make sure that those attachment details (which
	 * are represented as subobject) are added to ES manually (means not through
	 * the standard Store::updateData to avoid an update circle) otherwise there
	 * will be invisible the any SMW user
	 *
	 * @since 3.0
	 *
	 * @param DIWikiPage $dataItem
	 * @param File|null $file
	 */
	public function index( DIWikiPage $dataItem, File $file = null ) {

		$title = $dataItem->getTitle();

		// Allow any third-party extension to modify the file used as base for
		// the index process
		$file = $this->revisionGuard->getFile( $title, $file );

		if ( $file !== null && isset( $file->file_sha1 ) ) {
			$this->logger->info(
				[ 'File indexer', 'Forced file_sha1 change: {file_sha1}' ],
				[ 'file_sha1' => $file->file_sha1 ]
			);
		}

		if ( $dataItem->getId() == 0 ) {
			$dataItem->setId( $this->store->getObjectIds()->getId( $dataItem ) );
		}

		if (
			$dataItem->getId() == 0 ||
			$dataItem->getNamespace() !== NS_FILE ||
			$dataItem->getSubobjectName() !== '' ) {
			return;
		}

		$time = -microtime( true );

		$params = [
			'id' => 'attachment',
			'body' => [
				'description' => 'Extract attachment information',
				'processors' => [
					[
						'attachment' => [
							'field' => 'file_content',
							'indexed_chars' => -1
						]
					],
					[
						'remove' => [
							"field" => "file_content"
						]
					]
				]
			],
		];

		$connection = $this->store->getConnection( 'elastic' );
		$connection->ingest()->putPipeline( $params );

		if ( $file === null ) {
			$file = $this->findFile( $title );
		}

		if ( $file === false || $file === null ) {
			return;
		}

		$url = $file->getFullURL();
		$id = $dataItem->getId();

		$sha1 = $file->getSha1();
		$ingest = true;

		$index = $this->getIndexName( ElasticClient::TYPE_DATA );
		$doc = [ '_source' => [] ];

		$params = [
			'index' => $index,
			'type'  => ElasticClient::TYPE_DATA,
			'id'    => $id,
		];

		// Do we have any existing data? The ingest pipeline will override the
		// entire document, so rescue any data before starting the ingest.
		if ( $connection->exists( $params ) ) {
			$doc = $connection->get( $params + [ '_source_include' => [ 'file_sha1', 'subject', 'text_raw', 'text_copy', 'P*' ] ] );
		}

		// Is the sha1 the same? Don't do anything since the content is expected
		// to be the same!
		if ( $this->sha1Check && isset( $doc['_source']['file_sha1'] ) && $doc['_source']['file_sha1'] === $sha1 ) {
			$ingest = false;
		}

		$context = [
			'method' => __METHOD__,
			'role' => 'production',
			'origin' => $this->origin,
			'subject' => $dataItem->getHash()
		];

		if ( $ingest === false ) {
			$this->logger->info(
				[ 'File indexer', 'Skipping the ingest process', 'Found identical file_sha1 ({subject})' ],
				$context
			);

			return;
		}

		// https://www.elastic.co/guide/en/elasticsearch/plugins/master/ingest-attachment.html
		// "... The source field must be a base64 encoded binary or ... the
		// CBOR format ..."
		$content = $this->fileHandler->format(
			$this->fileHandler->fetchContentFromURL( $url ),
			FileHandler::FORMAT_BASE64
		);

		$params += [
			'pipeline' => 'attachment',
			'body' => [
				'file_content' => $content,
				'file_path' => $url,
				'file_sha1' => $sha1,
			] + $doc['_source']
		];

		$context['response'] = $connection->index( $params );
		$context['procTime'] = microtime( true ) + $time;
		$context['file_sha1'] = $sha1;

		$msg = [
			'File indexer',
			'Ingest process completed ({subject})',
			'procTime (in sec): {procTime}',
			'Response: {response}',
			'file_sha1:{file_sha1}'
		];

		$this->logger->info( $msg, $context );

		// Store the response temporarily to allow the `replication status` board
		// to show whether some files had issues during the indexing and need
		// intervention from a user
		$key = $this->entityCache->makeCacheKey( $title, self::INGEST_RESPONSE );

		$this->entityCache->save( $key, $context['response'] );
		$this->entityCache->associate( $title, $key );

		$this->fileAttachment->createAttachment( $dataItem );
	}

}
