<?php

namespace SMW\MediaWiki\Jobs;

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
class ChangePropagationUpdateJob extends JobBase {

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\ChangePropagationUpdateJob', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 *
	 * @since 3.0
	 */
	public function run() {

		ChangePropagationDispatchJob::removeProcessMarker(
			DIWikiPage::newFromTitle( $this->getTitle() )
		);

		$updateJob = new UpdateJob(
			$this->getTitle(),
			$this->params
		);

		$updateJob->run();

		return true;
	}

}
