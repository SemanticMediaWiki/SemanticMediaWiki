<?php

namespace SMW\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Job;
use Title;

/**
 * Make sufficient use of the job table by only tracking remaining jobs without
 * any detail on an individual update count.
 *
 * Use `ChangePropagationUpdateJob` to easily count the jobs and distinguish them
 * from other `UpdateJob`.
 *
 * `MediaWikiServices::getInstance()->getJobQueueGroup()->get( 'SMW\ChangePropagationUpdateJob' )->getSize()`
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationUpdateJob extends Job {

	/**
	 * Identifies the job queue command
	 */
	const JOB_COMMAND = 'smw.changePropagationUpdate';

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = [], $jobType = null ) {
		if ( $jobType === null ) {
			$jobType = self::JOB_COMMAND;
		}

		parent::__construct( $jobType, $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 *
	 * @since 3.0
	 */
	public function run() {
		ChangePropagationDispatchJob::cleanUp(
			DIWikiPage::newFromTitle( $this->getTitle() )
		);

		$updateJob = new UpdateJob(
			$this->getTitle(),
			$this->params + [ 'origin' => 'ChangePropagationUpdateJob' ]
		);

		$updateJob->run();

		return true;
	}

}
