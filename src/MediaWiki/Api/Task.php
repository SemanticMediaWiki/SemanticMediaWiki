<?php

namespace SMW\MediaWiki\Api;

use ApiBase;

/**
 * Module to support various tasks initiate using the API interface
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Task extends ApiBase {

	const CACHE_NAMESPACE = 'smw:api:task';

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function makeCacheKey( $key ) {
		return smwfCacheKey( self::CACHE_NAMESPACE, [ $key ] );
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();

		$parameters = json_decode(
			$params['params'],
			true
		);

		if ( json_last_error() !== JSON_ERROR_NONE || !is_array( $parameters ) ) {

			// 1.29+
			if ( method_exists( $this, 'dieWithError' ) ) {
				$this->dieWithError( [ 'smw-api-invalid-parameters' ] );
			} else {
				$this->dieUsageMsg( 'smw-api-invalid-parameters' );
			}
		}

		$taskFactory = new TaskFactory();
		$task = $taskFactory->newByType( $params['task'] );

		$results = $task->process(
			$parameters
		);

		$this->getResult()->addValue(
			null,
			'task',
			$results
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'task' => [
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => TaskFactory::getAllowedTypes()
			],
			'params' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
			],
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getParamDescription
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return [
			'task' => 'Defines the task type',
			'params' => 'JSON encoded parameters that matches the selected type requirement'
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getDescription
	 *
	 * @return array
	 */
	public function getDescription() {
		return [
			'Semantic MediaWiki API module to invoke and execute tasks (for internal use only)'
		];
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
		return [
			'api.php?action=smwtask&task=update&params={ "subject": "Foo" }',
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ':' . SMW_VERSION;
	}

}
