<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DeferredConstraintCheckUpdateJob extends Job {

	const JOB_COMMAND = 'smw.deferredConstraintCheckUpdateJob';

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = [] ) {
		parent::__construct( self::JOB_COMMAND, $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param array $params
	 *
	 * @return boolean
	 */
	public static function pushJob( Title $title, $params = [] ) {

		$deferredConstraintCheckUpdateJob = new self(
			$title,
			self::newRootJobParams( self::JOB_COMMAND, $title ) + [ 'waitOnCommandLine' => true ] + $params
		);

		$deferredConstraintCheckUpdateJob->insert();

		return true;
	}

	/**
	 * @see Job::run
	 *
	 * @since 3.1
	 */
	public function run() {

		if ( $this->waitOnCommandLineMode() ) {
			return true;
		}

		$updateJob = new UpdateJob(
			$this->getTitle(),
			$this->params + [ 'origin' => 'DeferredConstraintCheckUpdateJob' ]
		);

		$updateJob->run();

		return true;
	}

}
