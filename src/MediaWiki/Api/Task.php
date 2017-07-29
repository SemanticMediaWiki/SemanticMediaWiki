<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;

/**
 * Module to support various tasks initiate using the API interface
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Task extends ApiBase {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();
		$results = array();

		if ( $params['taskType'] === 'queryref' ) {
			$results = $this->handleQueryRefTask( $params );
		}

		$this->getResult()->addValue(
			null,
			'task',
			$results
		);
	}

	private function handleQueryRefTask( $params ) {

		if ( $params['taskParams'] === '' ) {
			return ['done' => false ];
		}

		$title = DIWikiPage::doUnserialize( $params['subject'] )->getTitle();

		if ( $title === null ) {
			return ['done' => false ];
		}

		if ( ( $qrefs = json_decode( $params['taskParams'] ) ) === array() ) {
			return ['done' => false ];
		}

		$jobFactory = ApplicationFactory::getInstance()->newJobFactory();

		// Each single update is required to allow for a cascading computation
		// where one query follows another to ensure that results are updated
		// according to the value dependency amon the referenced annotations that
		// rely on a computed (#ask) value
		foreach ( $qrefs as $qref ) {
			$updateJob = $jobFactory->newUpdateJob(
				$title,
				[
					UpdateJob::FORCED_UPDATE => true,
					'qref' => $qref
				]
			);

			$updateJob->run();
		}

		return ['done' => true ];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'taskType' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => array(
					'queryref'
				)
			),
			'subject' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'taskParams' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getParamDescription
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'taskType' => 'task type'
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getDescription
	 *
	 * @return array
	 */
	public function getDescription() {
		return array(
			'API module ...'
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::needsToken
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::mustBePosted
	 */
	public function mustBePosted() {
		return true;
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::isWriteMode
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getExamples
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=smwtask&taskType=queryref',
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
