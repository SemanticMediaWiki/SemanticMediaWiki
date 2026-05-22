<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Job;
use SMW\MediaWiki\JobFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * Make sufficient use of the job table by only tracking remaining jobs without
 * any detail on an individual update count.
 *
 * Use `ChangePropagationUpdateJob` to easily count the jobs and distinguish them
 * from other `UpdateJob`.
 *
 * `MediaWikiServices::getInstance()->getJobQueueGroup()->get( 'smw.changePropagationUpdate' )->getSize()`
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

	protected JobFactory $jobFactory;

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct(
		Title $title,
		$params = [],
		?JobFactory $jobFactory = null
	) {
		parent::__construct( static::JOB_COMMAND, $title, $params );
		// Fallback for direct `new self(...)` callsites (e.g. ChangePropagationClassUpdateJob)
		// and ad-hoc instantiation in tests that bypass the JobClasses ObjectFactory spec.
		$this->jobFactory = $jobFactory ?? ApplicationFactory::getInstance()->getJobFactory();
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 *
	 * @since 3.0
	 */
	public function run(): bool {
		ChangePropagationDispatchJob::cleanUp(
			WikiPage::newFromTitle( $this->getTitle() )
		);

		$updateJob = $this->jobFactory->newUpdateJob(
			$this->getTitle(),
			array_merge( $this->params, [ 'origin' => 'ChangePropagationUpdateJob' ] )
		);

		$updateJob->run();

		return true;
	}

}
