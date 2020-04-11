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

		$this->taskFactory = new TaskFactory();
		$task = $this->taskFactory->newByType( $params['task'], $this->getUser() );

		// If the `uselang` isn't set then inject the language from the
		// logged-in user
		if ( !isset( $parameters['uselang'] ) || $parameters['uselang'] === '' ) {
			$parameters['uselang'] = $this->getLanguage()->getCode();
		}

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
		$taskFactory = new TaskFactory();

		return [
			'task' => [
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => $taskFactory->getAllowedTypes()
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
	 * @see ApiBase::getExamplesMessages
	 *
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=smwtask&task=update&params={ "subject": "Foo" }'
				=> 'smw-apihelp-smwtask-example-update'
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getExamplesMessages
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.semantic-mediawiki.org/wiki/Help:API:smwtask';
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
