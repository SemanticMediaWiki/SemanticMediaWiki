<?php

namespace SMW;

use Title;
use Onoi\HttpRequest\HttpRequest;
use SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher;

/**
 * During the storage of a page, sometimes it is necessary the create extra
 * processing requests that should be executed asynchronously (due to large DB
 * processing time) but without delay of the current transaction. This class
 * initiates and creates a separate request to be handled by the receiving
 * `SpecialDeferredRequestDispatcher` endpoint (if it can connect).
 *
 * `DeferredRequestDispatchManager` allows to invoke jobs independent from the job
 * scheduler with the objective to be run timely to the current transaction
 * without having to wait on the job scheduler and without blocking the current
 * request.
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class DeferredRequestDispatchManager {

	/**
	 * @var HttpRequest
	 */
	private $httpRequest;

	/**
	 * @var string
	 */
	private $url = '';

	/**
	 * Is kept static in order for the cli process to only make the check once
	 * and verify it can/cannot connect.
	 *
	 * @var boolean|null
	 */
	private static $canConnectToUrl = null;

	/**
	 * During unit tests, this parameter is set false to ensure that test execution
	 * does match expected results.
	 *
	 * @var boolean
	 */
	private $enabledHttpDeferredJobRequestState = true;

	/**
	 * @since 2.3
	 *
	 * @param HttpRequest $httpRequest
	 */
	public function __construct( HttpRequest $httpRequest ) {
		$this->httpRequest = $httpRequest;
		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_URL, SpecialDeferredRequestDispatcher::getTargetURL() );
	}

	/**
	 * @since 2.3
	 */
	public function reset() {
		self::$canConnectToUrl = null;
		$this->enabledHttpDeferredJobRequestState = true;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $enabledHttpDeferredJobRequestState
	 */
	public function setEnabledHttpDeferredJobRequestState( $enabledHttpDeferredJobRequestState ) {
		$this->enabledHttpDeferredJobRequestState = (bool)$enabledHttpDeferredJobRequestState;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $type
	 * @param Title $title
	 * @param array $parameters
	 */
	public function dispatchJobRequestFor( $type, Title $title, $parameters = array() ) {

		if ( !$this->doPreliminaryCheckForType( $type, $parameters ) ) {
			return null;
		}

		$dispatchableCallbackJob = $this->getDispatchableCallbackJobFor( $type );

		// Build sessionToken as source verification during the POST request
		$parameters['timestamp'] = time();
		$parameters['sessionToken'] = SpecialDeferredRequestDispatcher::getSessionToken( $parameters['timestamp'] );

		if ( $this->enabledHttpDeferredJobRequestState && $this->canConnectToUrl() ) {
			return $this->doDispatchAsyncJobFor( $type, $title, $parameters, $dispatchableCallbackJob );
		}

		call_user_func_array(
			$dispatchableCallbackJob,
			array( $title, $parameters )
		);

		return true;
	}

	private function getDispatchableCallbackJobFor( $type ) {

		$jobFactory = ApplicationFactory::getInstance()->newJobFactory();

		if ( $type === 'SMW\ParserCachePurgeJob' ) {
			$callback = function ( $title, $parameters ) use ( $jobFactory ) {

				$purgeParserCacheJob = $jobFactory->newParserCachePurgeJob(
					$title,
					$parameters
				);

				$purgeParserCacheJob->insert();
			};
		}

		if ( $type === 'SMW\UpdateJob' ) {
			$callback = function ( $title, $parameters ) use ( $jobFactory ) {
				$updateJob = $jobFactory->newUpdateJob(
					$title,
					$parameters
				);

				$updateJob->run();
			};
		}

		return $callback;
	}

	private function doPreliminaryCheckForType( $type, array $parameters ) {

		if ( $type !== 'SMW\ParserCachePurgeJob' && $type !== 'SMW\UpdateJob' ) {
			return false;
		}

		if ( $type === 'SMW\ParserCachePurgeJob' && ( !isset( $parameters['idlist'] ) || $parameters['idlist'] === array() ) ) {
			return false;
		}

		return true;
	}

	private function canConnectToUrl() {

		if ( self::$canConnectToUrl !== null ) {
			return self::$canConnectToUrl;
		}

		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_SSL_VERIFYPEER, false );

		return self::$canConnectToUrl = $this->httpRequest->ping();
	}

	private function doDispatchAsyncJobFor( $type, $title, $parameters, $dispatchableCallbackJob ) {

		$parameters['async-job'] = array(
			'type'  => $type,
			'title' => $title->getPrefixedDBkey()
		);

		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_METHOD, 'POST' );
		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_CONTENT_TYPE, "application/x-www-form-urlencoded" );
		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_CONTENT, 'parameters=' . json_encode( $parameters ) );
		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_CONNECTION_FAILURE_REPEAT, 2 );

		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_ON_COMPLETED_CALLBACK, function( $requestResponse ) {
			wfDebugLog( 'smw', 'SMW\DeferredRequestDispatchManager: ' . json_encode( $requestResponse->getList() ) . "\n" );
		} );

		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_ON_FAILED_CALLBACK, function( $requestResponse ) use ( $dispatchableCallbackJob, $title, $type, $parameters ) {
			wfDebugLog( 'smw', "SMW\DeferredRequestDispatchManager: Connection to SpecialDeferredRequestDispatcher failed therefore adding {$type} for " . $title->getPrefixedDBkey() . "\n" );
			call_user_func_array( $dispatchableCallbackJob, array( $title, $parameters ) );
		} );

		$this->httpRequest->execute();

		return true;
	}

}
