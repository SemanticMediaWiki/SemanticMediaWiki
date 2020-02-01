<?php

namespace SMW\Elastic\Indexer\Attachment;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Indexer\Bulk;
use SMW\Elastic\QueryEngine\FieldMapper;
use SMW\Store;
use SMWContainerSemanticData as ContainerSemanticData;
use Title;
use File;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FileAttachment {

	use MessageReporterAwareTrait;
	use LoggerAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Indexer
	 */
	private $indexer;

	/**
	 * @var Bulk
	 */
	private $bulk;

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param Indexer $indexer
	 * @param Bulk $bulk
	 */
	public function __construct( Store $store, Indexer $indexer, Bulk $bulk ) {
		$this->store = $store;
		$this->indexer = $indexer;
		$this->bulk = $bulk;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 3.2
	 *
	 * @param DIWikiPage $dataItem
	 */
	public function createAttachment( DIWikiPage $dataItem ) {

		$time = -microtime( true );

		if ( $dataItem->getId() == 0 ) {
			$dataItem->setId( $this->indexer->getId( $dataItem ) );
		}

		if ( $dataItem->getId() == 0 ) {
			throw new RuntimeException( "Missing ID: " . $dataItem );
		}

		$context = [
			'method' => __METHOD__,
			'role' => 'production',
			'origin' => $this->origin,
			'subject' => $dataItem->getHash()
		];

		$semanticData = $this->store->getSemanticData( $dataItem );
		$connection = $this->indexer->getConnection();

		$index = $this->indexer->getIndexName( ElasticClient::TYPE_DATA );
		$doc = [ '_source' => [] ];

		$params = [
			'index' => $index,
			'type'  => ElasticClient::TYPE_DATA,
			'id'    => $dataItem->getId(),
		];

		if ( !$connection->exists( $params ) ) {

			$msg = [
				'File indexer',
				'Abort annotation update',
				'Missing {id} document!'
			];

			return $this->logger->info( $msg, $context + [ 'id' => $dataItem->getId() ] );
		}

		// Available properties
		// @see https://www.elastic.co/guide/en/elasticsearch/plugins/master/using-ingest-attachment.html
		$params = $params + [
			'_source_include' => [
				'file_sha1',
				'attachment.date',
				'attachment.content_type',
				'attachment.author',
				'attachment.language',
				'attachment.title',
				'attachment.content_length',
				'attachment.keywords',
				'attachment.name'
			]
		];

		$doc = $connection->get( $params );

		if ( !isset( $doc['_source']['file_sha1'] ) ) {

			$msg = [
				'File indexer',
				'No annotation update',
				'Missing file_sha1!'
			];

			return $this->logger->info( $msg, $context );
		}

		$containerSemanticData = $this->newContainerSemanticData(
			$dataItem,
			$doc
		);

		$attachmentAnnotator = new AttachmentAnnotator(
			$containerSemanticData,
			$doc
		);

		$attachmentAnnotator->addAnnotation();
		$property = $attachmentAnnotator->getProperty();

		// Remove any existing `_FILE_ATTCH` in case it was a reupload with a different
		// content sha1
		foreach ( $semanticData->getPropertyValues( $property ) as $pv ) {
			$semanticData->removePropertyObjectValue( $property, $pv );
		}

		$semanticData->addPropertyObjectValue(
			$property,
			$attachmentAnnotator->getContainer()
		);

		$callableUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate( function() use( $semanticData, $attachmentAnnotator ) {

			// Update the SQLStore with the annotated information which will NOT
			// trigger another ES index update BUT ...
			$this->store->updateData( $semanticData );

			// ... we need to replicate the container data (subobject) in order to
			// make them usable via query engine therefore ...
			$this->indexAttachmentInfo( $attachmentAnnotator );
		} );

		$callableUpdate->setOrigin( __METHOD__ );
		$callableUpdate->waitOnTransactionIdle();
		$callableUpdate->pushUpdate();

		$context['procTime'] = microtime( true ) + $time;

		$msg = [
			'File indexer',
			'Attachment annotation update completed ({subject})',
			'procTime (in sec): {procTime}'
		];

		$this->logger->info( $msg, $context );
	}

	/**
	 * Meta assignments from a file ingest need to be republished in a SMW conform
	 * manner so that property path `[[File attachment.Content title::..]]` work
	 * as expected.
	 *
	 * @since 3.2
	 *
	 * @param AttachmentAnnotator $attachmentAnnotator
	 */
	public function indexAttachmentInfo( AttachmentAnnotator $attachmentAnnotator ) {

		$data = [];
		$time = -microtime( true );

		$semanticData = $attachmentAnnotator->getSemanticData();
		$subject = $semanticData->getSubject();

		// Find base document ID
		$baseDocId = $this->indexer->getId( $subject->asBase() );

		if ( $baseDocId == 0 ) {
			throw new RuntimeException( "Missing ID: " . $subject );
		}

		$subject->setId( $this->indexer->getId( $subject ) );

		if ( $subject->getId() == 0 ) {
			throw new RuntimeException( "Missing ID: " . $subject );
		}

		$context = [
			'method' => __METHOD__,
			'role' => 'production',
			'origin' => $this->origin,
			'subject' => $subject->getHash()
		];

		foreach ( $semanticData->getProperties() as $property ) {

			$pid = $this->indexer->getId(
				$property->getCanonicalDiWikiPage()
			);

			$pid = FieldMapper::getPID( $pid );
			$data[$pid] = [];
			$field = FieldMapper::getField( $property );

			$data[$pid][$field] = [];

			foreach ( $semanticData->getPropertyValues( $property ) as $dataItem ) {
				$data[$pid][$field][] = $dataItem->getSortKey();
			}
		}

		$this->indexer->create( $subject, $data );

		// Attach the subobject to the base subject
		$response = $this->upsertDoc(
			$baseDocId,
			$subject,
			$attachmentAnnotator->getProperty()
		);

		$context['time'] = microtime( true ) + $time;
		$context['response'] = $response;

		$msg = [
			'File indexer',
			'Pushed attachment information to ES ({subject})',
			'procTime (in sec): {procTime}',
			'Response: {response}'
		];

		$this->logger->info( $msg, $context );
	}

	private function upsertDoc( $baseDocId, $subject, $property ) {

		$params = [
			'_index' => $this->indexer->getIndexName( ElasticClient::TYPE_DATA ),
			'_type'  => ElasticClient::TYPE_DATA
		];

		$this->bulk->clear();
		$this->bulk->head( $params );
		$data = [];

		$pid = $this->indexer->getId(
			$property->getCanonicalDiWikiPage()
		);

		$pid = FieldMapper::getPID( $pid );
		$data[$pid] = [];

		// It is the ID field we want not any type related field!
		$field = 'wpgID';

		$data[$pid][$field] = [];
		$data[$pid][$field][] = $subject->getId();

		// Upsert of the base document to link subject -> subobject otherwise
		// a property path like `File attachment.Content length`) is not going
		// to work
		$this->bulk->upsert( [ '_id' => $baseDocId ], $data );

		return $this->bulk->execute();
	}

	private function newContainerSemanticData( $dataItem, $doc ) {

		$subobjectName = '_FILE' . $doc['_source']['file_sha1'];

		$subject = new DIWikiPage(
			$dataItem->getDBkey(),
			$dataItem->getNamespace(),
			$dataItem->getInterwiki(),
			$subobjectName
		);

		return new ContainerSemanticData( $subject );
	}

}
