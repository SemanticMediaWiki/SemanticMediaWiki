<?php

namespace SMW\Elastic\Indexer;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\DataItems\WikiPage;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Jobs\IndexerRecoveryJob;
use SMW\MediaWiki\Collator;
use SMW\MediaWiki\RevisionGuardAwareTrait;
use SMW\Store;
use SMW\Utils\Timer;

/**
 * @license GPL-2.0-or-later
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

	private ?FileIndexer $fileIndexer = null;

	private string $origin = '';

	private bool $isRebuild = false;

	private array $versions = [];

	/**
	 * @since 3.0
	 */
	public function __construct(
		private Store $store,
		private Bulk $bulk,
	) {
	}

	/**
	 * @since 3.0
	 */
	public function setVersions( array $versions ): void {
		$this->versions = $versions;
	}

	/**
	 * @since 3.0
	 */
	public function setOrigin( string $origin ): void {
		$this->origin = $origin;
	}

	/**
	 * @since 3.0
	 */
	public function getId( WikiPage $dataItem ): int {
		return $this->store->getObjectIds()->getId( $dataItem );
	}

	/**
	 * @since 3.0
	 */
	public function isAccessible(): bool {
		return $this->canReplicate();
	}

	/**
	 * @since 3.0
	 */
	public function isRebuild( bool $isRebuild = true ): void {
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
	 */
	public function getIndexName( string $type ): string {
		$index = $this->store->getConnection( 'elastic' )->getIndexName( $type );

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
	public function delete( array $idList, bool $isConcept = false ): void {
		if ( $idList === [] ) {
			return;
		}

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText(
			$this->origin . ':' . md5( json_encode( $idList ) )
		);

		if ( !$this->canReplicate() ) {
			IndexerRecoveryJob::pushFromParams( $title, [ 'delete' => $idList ] );
			return;
		}

		$params = [
			'_index' => $this->getIndexName( Client::TYPE_DATA )
		];

		$this->bulk->clear();
		$this->bulk->head( $params );

		$time = -microtime( true );

		foreach ( $idList as $id ) {

			$this->bulk->delete( [ '_id' => $id ] );

			if ( $isConcept ) {
				$this->bulk->delete(
					[
						'_index' => $this->getIndexName( Client::TYPE_LOOKUP ),
						'_id' => md5( $id )
					]
				);
			}
		}

		$this->bulk->execute();

		$response = $this->bulk->getResponse();

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
	 */
	public function create( WikiPage $dataItem, array $data = [] ): void {
		$title = $dataItem->getTitle();

		if ( !$this->canReplicate() ) {
			IndexerRecoveryJob::pushFromParams( $title, [ 'create' => $dataItem->getHash() ] );
			return;
		}

		if ( $dataItem->getId() == 0 ) {
			$dataItem->setId( $this->getId( $dataItem ) );
		}

		if ( $dataItem->getId() == 0 ) {
			throw new RuntimeException( "Missing ID: " . $dataItem );
		}

		$connection = $this->store->getConnection( 'elastic' );

		$params = [
			'index' => $this->getIndexName( Client::TYPE_DATA ),
			'id'	=> $dataItem->getId()
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
	 */
	public function fetchNativeData( WikiPage|Title|int $id ): string {
		if ( $id instanceof WikiPage ) {
			$id = $id->getTitle();
		}

		if ( $id instanceof Title ) {
			$id = $this->revisionGuard->getLatestRevID( $id );
		}

		if ( $id == 0 ) {
			return '';
		}

		$revision = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionById( $id );

		if ( $revision == null ) {
			return '';
		}

		$content = $revision->getContent( SlotRecord::MAIN, RevisionRecord::RAW );

		return $content->getNativeData();
	}

	/**
	 * @since 3.2
	 */
	public function indexDocument(
		Document $document,
		string $type = self::REQUIRE_SAFE_REPLICATION
	): void {
		Timer::start( __METHOD__ );

		$subject = $document->getSubject();

		if ( $type === self::REQUIRE_SAFE_REPLICATION && !$this->canReplicate() ) {
			IndexerRecoveryJob::pushFromDocument( $document );
			return;
		}

		$params = [
			'_index' => $this->getIndexName( Client::TYPE_DATA )
		];

		$this->bulk->clear();
		$this->bulk->head( $params );

		$this->bulk->infuseDocument( $document );
		$this->bulk->execute();

		$this->logger->info(
			[ 'Indexer',
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

	private function canReplicate(): bool {
		$connection = $this->store->getConnection( 'elastic' );

		// Make sure a node is available and is not locked by the rebuilder
		if ( !$connection->hasLock( Client::TYPE_DATA ) && $connection->ping() ) {
			return true;
		}

		return false;
	}

	private function makeSubject( WikiPage $subject ): array {
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
