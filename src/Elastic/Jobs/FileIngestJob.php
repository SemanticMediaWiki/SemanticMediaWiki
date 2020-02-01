<?php

namespace SMW\Elastic\Jobs;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Job;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\Indexer\Attachment\ScopeMemoryLimiter;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\DIWikiPage;
use Title;

/**
 * @license GNU GPL v2
 * @since 3.0
 *
 * @author mwjames
 */
class FileIngestJob extends Job {

	/**
	 * Name of the job
	 */
	const JOB_COMMAND = 'smw.elasticFileIngest';

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
	 * @param File|null $file
	 */
	public static function pushIngestJob( Title $title, array $params = [] ) {

		if ( $title->getNamespace() !== NS_FILE ) {
			return;
		}

		$params = $params + [ 'waitOnCommandLine' => true ];

		$fileIngestJob = new self(
			$title,
			array_merge( $params, self::newRootJobParams( self::JOB_COMMAND, $title ) )
		);

		$fileIngestJob->lazyPush();
	}

	/**
	 * @see Job::run
	 *
	 * @since  3.0
	 */
	public function run() {

		// Make sure the script is only executed from the command line to avoid
		// Special:RunJobs to execute a queued job
		if ( $this->waitOnCommandLineMode() ) {
			return true;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$connection = $applicationFactory->getStore()->getConnection( 'elastic' );

		// Make sure a node is available
		if ( $connection->hasLock( ElasticClient::TYPE_DATA ) || !$connection->ping() ) {

			if ( $connection->hasLock( ElasticClient::TYPE_DATA ) ) {
				$this->params['retryCount'] = 0;
			}

			return $this->requeueRetry( $connection->getConfig() );
		}

		( new ScopeMemoryLimiter() )->execute( [ $this, 'runFileIndexer' ] );

		return true;
	}

	/**
	 * @since 3.2
	 */
	public function runFileIndexer() {

		$applicationFactory = ApplicationFactory::getInstance();
		$elasticFactory = $applicationFactory->singleton( 'ElasticFactory' );

		$store = $applicationFactory->getStore();
		$connection = $store->getConnection( 'elastic' );

		$fileIndexer = $elasticFactory->newFileIndexer(
			$store,
			$elasticFactory->newIndexer()
		);

		$fileIndexer->setOrigin( __METHOD__ );

		$file = $fileIndexer->findFile(
			$this->getTitle()
		);

		// File isn't available yet (or uploaded), try again!
		if ( $file === false || $file === null ) {
			return $this->requeueRetry( $connection->getConfig() );
		}

		$subject = DIWikiPage::newFromTitle(
			$this->getTitle()
		);

		$fileIndexer->index( $subject, $file );
	}

	private function requeueRetry( $config ) {

		// Give up!
		if ( $this->getParameter( 'retryCount' ) >= $config->dotGet( 'indexer.job.file.ingest.retries' ) ) {
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

}
