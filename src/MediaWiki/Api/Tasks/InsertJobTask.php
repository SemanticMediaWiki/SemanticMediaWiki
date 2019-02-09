<?php

namespace SMW\MediaWiki\Api\Tasks;

use SMW\Store;
use SMW\DIWikiPage;
use SMW\MediaWiki\JobFactory;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class InsertJobTask extends Task {

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
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ) {

		if ( $parameters['subject'] === '' ) {
			return ['done' => false ];
		}

		$subject = DIWikiPage::doUnserialize( $parameters['subject'] );
		$title = $subject->getTitle();

		if ( $title === null ) {
			return ['done' => false ];
		}

		if ( !isset( $parameters['job'] ) ) {
			return ['done' => false ];
		}

		$params = [];

		if ( isset( $parameters['parameters'] ) ) {
			$params = $parameters['parameters'];
		}

		$job = $this->jobFactory->newByType(
			$parameters['job'],
			$title,
			$params
		);

		$job->insert();

		return [ 'done' => true ];
	}

}
