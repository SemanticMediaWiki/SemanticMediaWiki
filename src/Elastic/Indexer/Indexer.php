<?php

namespace SMW\Elastic\Indexer;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use Psr\Log\LoggerAwareTrait;
use SMW\Services\ServicesContainer;
use RuntimeException;
use SMW\DIWikiPage;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\Elastic\Jobs\FileIngestJob;
use SMW\Elastic\Jobs\IndexerRecoveryJob;
use SMW\Store;
use SMW\Utils\CharArmor;
use SMW\MediaWiki\RevisionGuardAwareTrait;
use SMW\MediaWiki\Collator;
use SMW\Utils\Timer;
use SMWDIBlob as DIBlob;
use Title;
use Revision;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Indexer {

	use MessageReporterAwareTrait;
	use LoggerAwareTrait;
	use RevisionGuardAwareTrait;

	/**
	 * Whether safe replication is required during the indexing process or not.
	 */
	const REQUIRE_SAFE_REPLICATION = 'replication/safe';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Bulk
	 */
	private $bulk;

	/**
	 * @var FileIndexer
	 */
	private $fileIndexer;

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @var boolean
	 */
	private $isRebuild = false;

	/**
	 * @var []
	 */
	private $versions = [];

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param Bulk $bulk
	 */
	public function __construct( Store $store, Bulk $bulk ) {
		$this->store = $store;
		$this->bulk = $bulk;
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
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function getId( DIWikiPage $dataItem ) {
		return $this->store->getObjectIds()->getId( $dataItem );
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isAccessible() {
		return $this->canReplicate();
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isRebuild
	 */
	public function isRebuild( $isRebuild = true ) {
		$this->isRebuild = $isRebuild;
	}

	/**
	 * @since 3.0
	 *
	 * @return Client
	 */
	public function getConnection() {
		return $this->store->getConnection( 'elastic' );
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
	 *
	 * @param array $idList
	 */
	public function delete( array $idList, $isConcept = false ) {

		if ( $idList === [] ) {
			return;
		}

		$title = Title::newFromText( $this->origin . ':' . md5( json_encode( $idList ) ) );

		if ( !$this->canReplicate() ) {
			return IndexerRecoveryJob::pushFromParams( $title, [ 'delete' => $idList ] );
		}

		$index = $this->getIndexName(
			ElasticClient::TYPE_DATA
		);

		$params = [
			'_index' => $index,
			'_type'  => ElasticClient::TYPE_DATA
		];

		$this->bulk->clear();
		$this->bulk->head( $params );

		$time = -microtime( true );

		foreach ( $idList as $id ) {

			$this->bulk->delete( [ '_id' => $id ] );

			if ( $isConcept ) {
				$this->bulk->delete(
					[
						'_index' => $this->getIndexName( ElasticClient::TYPE_LOOKUP ),
						'_type' => ElasticClient::TYPE_LOOKUP,
						'_id' => md5( $id )
					]
				);
			}
		}

		$response = $this->bulk->execute();

		$this->logger->info(
			[
				'Indexer',
				'Deleted list',
				'procTime (in sec): {procTime}',
				'Response: {response}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'origin' => $this->origin,
				'procTime' => $time + microtime( true ),
				'response' => $response
			]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $dataItem
	 * @param array $data
	 */
	public function create( DIWikiPage $dataItem, array $data = [] ) {

		$title = $dataItem->getTitle();

		if ( !$this->canReplicate() ) {
			return IndexerRecoveryJob::pushFromParams( $title, [ 'create' => $dataItem->getHash() ] );
		}

		if ( $dataItem->getId() == 0 ) {
			$dataItem->setId( $this->getId( $dataItem ) );
		}

		if ( $dataItem->getId() == 0 ) {
			throw new RuntimeException( "Missing ID: " . $dataItem );
		}

		$connection = $this->store->getConnection( 'elastic' );

		$params = [
			'index' => $this->getIndexName( ElasticClient::TYPE_DATA ),
			'type'  => ElasticClient::TYPE_DATA,
			'id'    => $dataItem->getId()
		];

		$data['subject'] = $this->makeSubject( $dataItem );
		$response = $connection->index( $params + [ 'body' => $data ] );

		$this->logger->info(
			[
				'Indexer',
				'Create ({subject}, {id})',
				'Response: {response}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'origin' => $this->origin,
				'subject' => $dataItem->getHash(),
				'id' => $dataItem->getId(),
				'response' => $response
			]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage|Title|integer $id
	 *
	 * @return string
	 */
	public function fetchNativeData( $id ) {

		if ( $id instanceof DIWikiPage ) {
			$id = $id->getTitle();
		}

		if ( $id instanceof Title ) {
			$id = $this->revisionGuard->getLatestRevID( $id );
		}

		if ( $id == 0 ) {
			return '';
		}

		$revision = Revision::newFromId( $id );

		if ( $revision == null ) {
			return '';
		}

		$content = $revision->getContent( Revision::RAW );

		return $content->getNativeData();
	}

	/**
	 * @since 3.2
	 *
	 * @param Document $document
	 * @param string $type
	 */
	public function indexDocument( Document $document, $type = self::REQUIRE_SAFE_REPLICATION ) {

		Timer::start( __METHOD__ );

		$subject = $document->getSubject();

		if ( $type === self::REQUIRE_SAFE_REPLICATION && !$this->canReplicate() ) {
			return IndexerRecoveryJob::pushFromDocument( $document ) ;
		}

		$params = [
			'_index' => $this->getIndexName( ElasticClient::TYPE_DATA ),
			'_type'  => ElasticClient::TYPE_DATA
		];

		$this->bulk->clear();
		$this->bulk->head( $params );

		$this->bulk->infuseDocument( $document );
		$this->bulk->execute();

		$this->logger->info(
			[	'Indexer',
				'Data index completed ({subject}, {id})',
				'procTime (in sec): {procTime}',
				'Response: {response}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'origin' => $this->origin,
				'subject' => $subject->getHash(),
				'id' => $document->getId(),
				'procTime' => Timer::getElapsedTime( __METHOD__ ),
				'response' => $this->bulk->getResponse()
			]
		);
	}

	private function canReplicate() {

		$connection = $this->store->getConnection( 'elastic' );

		// Make sure a node is available and is not locked by the rebuilder
		if ( !$connection->hasLock( ElasticClient::TYPE_DATA ) && $connection->ping() ) {
			return true;
		}

		return false;
	}

	private function makeSubject( DIWikiPage $subject ) {

		$title = $subject->getDBKey();

		if ( $subject->getNamespace() !== SMW_NS_PROPERTY || $title[0] !== '_' ) {
			$title = str_replace( '_', ' ', $title );
		}

		$sort = $subject->getSortKey();
		$sort = Collator::singleton()->getSortKey( $sort );

		// Use collated sort field if available
		if ( $subject->getOption( 'sort', '' ) !== '' ) {
			$sort = $subject->getOption( 'sort' );
		}

		// This may loose some non valif UTF-8 characters as it is required by ES
		// to be strict UTF-8 otherwise the ES indexer will fail with a serialization
		// error because ES only allows UTF-8 but when the collator applies something
		// like `uca-default-u-kn` it can produce characters not valid for/by
		// ES hence the sorting compared to the SQLStore will be different (given
		// the DB stores the byte representation)
		$sort = mb_convert_encoding( $sort, 'UTF-8', 'UTF-8' );

		return [
			'title'     => $title,
			'subobject' => $subject->getSubobjectName(),
			'namespace' => $subject->getNamespace(),
			'interwiki' => $subject->getInterwiki(),
			'sortkey'   => $sort
		];
	}

}
