<?php

namespace SMW\MediaWiki\Api\Tasks;

use SMW\DataItems\WikiPage;
use SMW\MediaWiki\JobQueue;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class JobListTask extends Task {

	/**
	 * $allowedJobs lists the job types the post-edit process may run, keyed by
	 * type; a caller cannot run any type outside this set.
	 *
	 * @since 3.1
	 */
	public function __construct(
		private readonly JobQueue $jobQueue,
		private readonly array $allowedJobs = []
	) {
	}

	/**
	 * Runs queued jobs on behalf of the caller, so the caller must be
	 * authorized to edit the subject page rather than merely holding a
	 * wiki-wide right. The legitimate post-edit flow acts on the page the
	 * caller just saved.
	 *
	 * @since 7.3.1
	 */
	public function getAuthorizationSubject( array $parameters ): ?string {
		return $parameters['subject'] ?? '';
	}

	/**
	 * Only the job types the post-edit process itself emits may be run, so a
	 * caller cannot pop and run arbitrary queued jobs of its own choosing.
	 *
	 * @since 7.3.1
	 */
	public function requestedWorkIsPermitted( array $parameters ): bool {
		$requested = $parameters['jobs'] ?? [];

		if ( !is_array( $requested ) ) {
			return true;
		}

		return array_diff_key( $requested, $this->allowedJobs ) === [];
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ): array {
		if ( !isset( $parameters['subject'] ) || $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = WikiPage::doUnserialize( $parameters['subject'] );
		$title = $subject->getTitle();

		if ( $title === null ) {
			return [ 'done' => false ];
		}

		$jobList = [];

		if ( isset( $parameters['jobs'] ) ) {
			$jobList = $parameters['jobs'];
		}

		$log = $this->jobQueue->runFromQueue(
			$jobList
		);

		return [ 'done' => true, 'log' => $log ];
	}

}
