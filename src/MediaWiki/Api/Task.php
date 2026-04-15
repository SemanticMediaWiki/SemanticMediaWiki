<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Context\RequestContext;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Module to support various tasks initiate using the API interface
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class Task extends ApiBase {

	const CACHE_NAMESPACE = 'smw:api:task';

	private TaskFactory $taskFactory;

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function makeCacheKey( $key ): string {
		return smwfCacheKey( self::CACHE_NAMESPACE, [ $key ] );
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();

		$parameters = json_decode(
			$params['params'],
			true
		);

		if ( json_last_error() !== JSON_ERROR_NONE || !is_array( $parameters ) ) {
			$this->dieWithError( [ 'smw-api-invalid-parameters' ] );
		}

		$this->taskFactory = new TaskFactory();
		$task = $this->taskFactory->newByType( $params['task'], $this->getUser() );

		// If the `uselang` isn't set then inject the language from the
		// logged-in user
		if ( !isset( $parameters['uselang'] ) || $parameters['uselang'] === '' ) {
			$parameters['uselang'] = $this->getLanguage()->getCode();
		}

		// We must validate if the lang code is valid
		$parameters['uselang'] = RequestContext::sanitizeLangCode( $parameters['uselang'] );

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
	public function getAllowedParams(): array {
		$taskFactory = new TaskFactory();

		return [
			'task' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => $taskFactory->getAllowedTypes(),
				ApiBase::PARAM_HELP_MSG => 'apihelp-smwtask-param-task',
			],
			'params' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ApiBase::PARAM_HELP_MSG => 'apihelp-smwtask-param-params',
			],
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::needsToken
	 */
	public function needsToken(): string {
		return 'csrf';
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::mustBePosted
	 */
	public function mustBePosted(): bool {
		return true;
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::isWriteMode
	 */
	public function isWriteMode(): bool {
		return true;
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 *
	 * @return array
	 */
	protected function getExamplesMessages(): array {
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
	public function getHelpUrls(): string {
		return 'https://www.semantic-mediawiki.org/wiki/Help:API:smwtask';
	}

}
