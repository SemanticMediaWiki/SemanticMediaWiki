<?php

namespace SMW\MediaWiki\Api\Tasks;

use SMW\Store;
use SMW\Enum;
use SMW\DIWikiPage;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateTask extends Task {

	/**
	 * @var JobFactory
	 */
	private $jobFactory;

	/**
	 * @since 3.1
	 *
	 * @param JobFactory $jobFactory
	 */
	public function __construct( JobFactory $jobFactory ) {
		$this->jobFactory = $jobFactory;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return array
	 */
	public function process( array $parameters ) {

		if ( !isset( $parameters['subject'] ) || $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = DIWikiPage::doUnserialize( $parameters['subject'] );
		$title = $subject->getTitle();
		$log = [];

		if ( $title === null ) {
			return ['done' => false ];
		}

		// Each single update is required to allow for a cascading computation
		// where one query follows another to ensure that results are updated
		// according to the value dependency of the referenced annotations that
		// rely on a computed (#ask) value
		if ( !isset( $parameters['ref'] ) ) {
			$parameters['ref'] = [ $subject->getHash() ];
		}

		$isPost = isset( $parameters['post'] ) ? $parameters['post'] : false;
		$origin = [];

		if ( isset( $parameters['origin'] ) ) {
			$origin = [ 'origin' => $parameters['origin'] ];
		}

		foreach ( $parameters['ref'] as $ref ) {
			$updateJob = $this->jobFactory->newUpdateJob(
				$title,
				[
					UpdateJob::FORCED_UPDATE => true,
					Enum::OPT_SUSPEND_PURGE => false,
					'ref' => $ref
				] + $origin
			);

			if ( $isPost ) {
				$updateJob->insert();
			} else {
				$updateJob->run();
			}
		}

		return [ 'done' => true, 'log' => $log ];
	}

}
