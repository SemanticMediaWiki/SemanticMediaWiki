<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Context\RequestContext;
use SMW\DataItems\WikiPage;
use SMW\Exception\DataItemException;
use SMW\MediaWiki\Api\Tasks\Task as TaskHandler;
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

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		private readonly TaskFactory $taskFactory
	) {
		parent::__construct( $main, $action );
	}

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

		$task = $this->taskFactory->newByType( $params['task'], $this->getUser() );

		// Authorize before running. This module is not otherwise
		// access-controlled: `needsToken( 'csrf' )` is satisfied by the public
		// anonymous token and does not gate on rights.
		$this->authorizeTask( $task, $parameters );

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
	 * Authorize the caller for a task. A task that acts on a caller-supplied
	 * page (via getAuthorizationSubject) is authorized against that specific
	 * page, so a caller cannot drive the task for a title it may not edit
	 * merely by holding a wiki-wide right. Every other task gates on its
	 * global getRequiredPermission() right.
	 */
	private function authorizeTask( TaskHandler $task, array $parameters ): void {
		$subject = $task->getAuthorizationSubject( $parameters );

		if ( $subject === null ) {
			$this->checkUserRightsAny( $task->getRequiredPermission() );
		} else {
			try {
				$title = WikiPage::doUnserialize( $subject )->getTitle();
			} catch ( DataItemException ) {
				$title = null;
			}

			if ( $title === null ) {
				$this->dieWithError( [ 'smw-api-invalid-parameters' ] );
			}

			if ( !$this->getAuthority()->authorizeWrite( 'edit', $title ) ) {
				$this->dieWithError( 'apierror-permissiondenied-generic', 'permissiondenied' );
			}
		}

		if ( !$task->requestedWorkIsPermitted( $parameters ) ) {
			$this->dieWithError( 'apierror-permissiondenied-generic', 'permissiondenied' );
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams(): array {
		return [
			'task' => [
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_TYPE => $this->taskFactory->getAllowedTypes(),
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
