<?php

namespace SMW\Elastic\Jobs;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Job;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\ElasticFactory;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\Elastic\Indexer\Document;
use SMW\Utils\HmacSerializer;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class IndexerRecoveryJob extends Job {

	/**
	 * Name of the job
	 */
	const JOB_COMMAND = 'smw.elasticIndexerRecovery';

	/**
	 * Repository specific namespace
	 */
	const CACHE_NAMESPACE = 'smw:elastic:document';

	const TTL_DAY = 86400; // 24 * 3600
	const TTL_WEEK = 604800; // 7 * 24 * 3600

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = [] ) {
		parent::__construct( self::JOB_COMMAND, $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * @since 3.2
	 *
	 * @param string|array $key
	 *
	 * @return string
	 */
	public static function makeCacheKey( $subject ) {

		if ( $subject instanceof Title ) {
			$subject = DIWikiPage::newFromTitle( $subject );
		}

		return smwfCacheKey( self::CACHE_NAMESPACE, $subject->getHash() );
	}

	/**
	 * @since 3.2
	 *
	 * @param Document $document
	 */
	public static function pushFromDocument( Document $document ) {

		$cache = ApplicationFactory::getInstance()->getCache();
		$subject = $document->getSubject();

		$cache->save(
			self::makeCacheKey( $subject ),
			HmacSerializer::compress( $document ),
			self::TTL_WEEK
		);

		$indexerRecoveryJob = new IndexerRecoveryJob(
			$subject->getTitle(),
			[ 'index' => $subject->getHash() ]
		);

		$indexerRecoveryJob->insert();
	}

	/**
	 * @since 3.2
	 *
	 * @param array $params
	 */
	public static function pushFromParams( Title $title, array $params ) {

		$indexerRecoveryJob = new IndexerRecoveryJob(
			$title,
			$params
		);

		$indexerRecoveryJob->insert();
	}

	/**
	 * @see Job::run
	 *
	 * @since  3.0
	 */
	public function allowRetries() {
		return false;
	}

	/**
	 * @see Job::run
	 *
	 * @since  3.0
	 */
	public function run() {

		$applicationFactory = ApplicationFactory::getInstance();
		$store = $applicationFactory->getStore( '\SMW\SQLStore\SQLStore' );

		$connection = $store->getConnection( 'elastic' );

		// Make sure a node is available
		if ( $connection->hasLock( ElasticClient::TYPE_DATA ) || !$connection->ping() ) {

			if ( $connection->hasLock( ElasticClient::TYPE_DATA ) ) {
				$this->params['retryCount'] = 0;
			}

			return $this->requeueRetry( $connection->getConfig() );
		}

		$elasticFactory = $applicationFactory->singleton( 'ElasticFactory' );

		$this->indexer = $elasticFactory->newIndexer(
			$store
		);

		$this->indexer->setOrigin( __METHOD__ );

		$this->indexer->setLogger(
			$applicationFactory->getMediaWikiLogger( 'smw-elastic' )
		);

		if ( $this->hasParameter( 'delete' ) ) {
			$this->delete( $this->getParameter( 'delete' ) );
		}

		if ( $this->hasParameter( 'create' ) ) {
			$this->create( $this->getParameter( 'create' ) );
		}

		if ( $this->hasParameter( 'index' ) ) {
			$this->index(
				$applicationFactory->getCache(),
				$this->getParameter( 'index' )
			);
		}

		return true;
	}

	private function requeueRetry( $config ) {

		// Give up!
		if ( $this->getParameter( 'retryCount' ) >= $config->dotGet( 'indexer.job.recovery.retries' ) ) {
			return true;
		}

		if ( !isset( $this->params['retryCount'] ) ) {
			$this->params['retryCount'] = 1;
		} else {
			$this->params['retryCount']++;
		}

		if ( !isset( $this->params['createdAt'] ) ) {
			$this->params['createdAt'] = time();
		}

		$job = new self( $this->title, $this->params );
		$job->setDelay( 60 * 10 );

		$job->insert();
	}

	private function delete( array $idList ) {
		$this->indexer->delete( $idList );
	}

	private function create( $hash ) {
		$this->indexer->create( DIWikiPage::doUnserialize( $hash ) );
	}

	private function index( $cache, $hash ) {

		$key = self::makeCacheKey(
			DIWikiPage::doUnserialize( $hash )
		);

		$document = null;

		if ( ( $data = $cache->fetch( $key ) ) !== false ) {
			$document = HmacSerializer::uncompress( $data );
		}

		if ( $document instanceof Document ) {
			$this->indexer->indexDocument( $document, false );
		}

		$cache->delete( $key );
	}

}
