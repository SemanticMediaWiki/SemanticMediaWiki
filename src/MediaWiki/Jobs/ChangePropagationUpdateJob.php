<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use Title;
use SMW\DIWikiPage;

/**
 * Make sufficient use of the job table by only tracking remaining jobs without
 * any detail on an individual update count.
 *
 * Use `ChangePropagationUpdateJob` to easily count the jobs and distinguish them
 * from other `UpdateJob`.
 *
 * `JobQueueGroup::singleton()->get( 'SMW\ChangePropagationUpdateJob' )->getSize()`
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationUpdateJob extends Job {

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = [], $jobType = null ) {

		if ( $jobType === null ) {
			$jobType = 'smw.changePropagationUpdate';
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
