<?php

namespace SMW\Elastic\Indexer;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Job;
use SMW\Elastic\ElasticFactory;
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
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = [] ) {
		parent::__construct( 'smw.elasticFileIngest', $title, $params );
		$this->removeDuplicates = true;
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
		$store = $applicationFactory->getStore();

		$connection = $store->getConnection( 'elastic' );

		// Make sure a node is available
		if ( $connection->hasLock( ElasticClient::TYPE_DATA ) || !$connection->ping() ) {

			if ( $connection->hasLock( ElasticClient::TYPE_DATA ) ) {
				$this->params['retryCount'] = 0;
			}

			return $this->requeueRetry( $connection->getConfig() );
		}

		$elasticFactory = new ElasticFactory();

		$indexer = $elasticFactory->newIndexer(
			$store
		);

		$fileIndexer = $indexer->getFileIndexer();

		$fileIndexer->setOrigin( __METHOD__ );

		$fileIndexer->setLogger(
			$applicationFactory->getMediaWikiLogger( 'smw-elastic' )
		);

		$file = wfFindFile( $this->getTitle() );

		// File isn't available yet (or uploaded), try again!
		if ( $file === false ) {
			return $this->requeueRetry( $connection->getConfig() );
		}

		// It has been observed that when this job is run, the job runner can
		// return with "Fatal error: Allowed memory size of ..." which in most
		// cases happen when large files are involved therefore temporary lift
		// the limitation!
		$memory_limit = ini_get( 'memory_limit' );

		if ( wfShorthandToInteger( $memory_limit ) < wfShorthandToInteger( '1024M' ) ) {
			ini_set( 'memory_limit', '1024M' );
		}

		$fileIndexer->index(
			DIWikiPage::newFromTitle( $this->getTitle() ),
			$file
		);

		ini_set( 'memory_limit', $memory_limit );

		return true;
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
