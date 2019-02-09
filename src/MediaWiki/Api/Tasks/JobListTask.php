<?php

namespace SMW\MediaWiki\Api\Tasks;

use SMW\Store;
use SMW\DIWikiPage;
use SMW\MediaWiki\JobQueue;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class JobListTask extends Task {

	/**
	 * @var JobQueue
	 */
	private $jobQueue;

	/**
	 * @since 3.1
	 *
	 * @param JobQueue $jobQueue
	 */
	public function __construct( JobQueue $jobQueue ) {
		$this->jobQueue = $jobQueue;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ) {

		if ( !isset( $parameters['subject'] ) || $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = DIWikiPage::doUnserialize( $parameters['subject'] );
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
