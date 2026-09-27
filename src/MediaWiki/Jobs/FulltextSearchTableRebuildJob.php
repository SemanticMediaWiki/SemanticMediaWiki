<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use SMW\MediaWiki\Job;
use SMW\MediaWiki\JobFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableRebuildJob extends Job {

	/**
	 * @since 2.5
	 */
	public function __construct(
		Title $title,
		array $params,
		Store $store,
		private readonly JobFactory $jobFactory
	) {
		parent::__construct( 'smw.fulltextSearchTableRebuild', $title, $params );
		$this->setStore( $store );
	}

	/**
	 * Runs a rebuild job in one of three ways:
	 * by table, in batch mode or full.
	 *
	 * @see Job::run
	 *
	 * @since 2.5
	 */
	public function run(): bool {
		if ( $this->waitOnCommandLineMode() ) {
			return true;
		}

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		$searchTableRebuilder = $fulltextSearchTableFactory->newSearchTableRebuilder(
			$this->store
		);

		if ( $this->hasParameter( 'table' ) ) {
			$searchTableRebuilder->rebuildByTable( $this->getParameter( 'table' ) );
		} elseif ( $this->hasParameter( 'mode' ) && $this->getParameter( 'mode' ) === 'full' ) {
			$searchTableRebuilder->rebuild();
		} else {
			// default, including 'chunked' mode
			$this->rebuildChunk( $searchTableRebuilder );
		}

		return true;
	}

	/**
	 * Rebuilds one 'window' of subject IDs and if that wasn't the
	 * last window, queues a job for the next one in row. Ensures that
	 * - Each job does a bounded amount of work (`batchSize` subject IDs)
	 * - A job that fails or times out can simply be run again because
	 * a window replaces its own entries.
	 *
	 * @since 7.3.1
	 *
	 * Parameters: `fromSid` (default: 0) and `batchSize`
	 * (default: \SearchTableRebuilder::DEFAULT_BATCH_SIZE).
	 *
	 * @param \SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder $searchTableRebuilder
	 */
	private function rebuildChunk( $searchTableRebuilder ): void {
		$fromSid = max( 0, (int)$this->getParameter( 's', 0 ) );
		$batchSize = (int)$this->getParameter( 'n', $searchTableRebuilder::DEFAULT_BATCH_SIZE );
		$batchSize = $batchSize > 0 ? $batchSize : $searchTableRebuilder::DEFAULT_BATCH_SIZE;

		$nextSid = $searchTableRebuilder->rebuildChunk( $fromSid, $batchSize );

		if ( $nextSid === null ) {
 			return;
 		}

		$job = $this->jobFactory->newFulltextSearchTableRebuildJob(
			$this->getTitle(),
			[ 'fromSid' => $nextSid, 'batchSize' => $batchSize ]
		);
 
		$job->insert();
	}

}
