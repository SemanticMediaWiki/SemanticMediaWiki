<?php

namespace SMW;

use Onoi\HttpRequest\HttpRequest;
use SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher;
use Title;

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
	private $enabledHttpDeferredRequest = true;

	/**
	 * @var boolean
	 */
	private $preferredWithJobQueue = false;

	/**
	 * @since 2.3
	 *
	 * @param HttpRequest $httpRequest
	 */
	public function __construct( HttpRequest $httpRequest ) {
		$this->httpRequest = $httpRequest;
	}

	/**
	 * @since 2.3
	 */
	public function reset() {
		self::$canConnectToUrl = null;
		$this->enabledHttpDeferredRequest = true;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $enabledHttpDeferredRequest
	 */
	public function setEnabledHttpDeferredRequest( $enabledHttpDeferredRequest ) {
		$this->enabledHttpDeferredRequest = (bool)$enabledHttpDeferredRequest;
	}

	/**
	 * Certain types of jobs or tasks may prefer to be executed using the job
	 * queue therefore indicate whether the dispatcher should try opening a
	 * http request or not.
	 *
	 * @since 2.5
	 *
	 * @param boolean $preferredWithJobQueue
	 */
	public function setPreferredWithJobQueue( $preferredWithJobQueue ) {
		$this->preferredWithJobQueue = (bool)$preferredWithJobQueue;
	}

	/**
	 * @since 2.4
	 *
	 * @param Title $title
	 * @param array $parameters
	 */
	public function dispatchParserCachePurgeJobFor( Title $title, $parameters = array() ) {
		return $this->dispatchJobRequestFor( 'SMW\ParserCachePurgeJob', $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $parameters
	 */
	public function dispatchSearchTableUpdateJobFor( Title $title, $parameters = array() ) {
		return $this->dispatchJobRequestFor( 'SMW\SearchTableUpdateJob', $title, $parameters );
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

		$this->httpRequest->setOption(
			ONOI_HTTP_REQUEST_URL,
			SpecialDeferredRequestDispatcher::getTargetURL()
		);

		$dispatchableCallbackJob = $this->getDispatchableCallbackJobFor( $type );

		// Build requestToken as source verification during the POST request
		$parameters['timestamp'] = time();
		$parameters['requestToken'] = SpecialDeferredRequestDispatcher::getRequestToken( $parameters['timestamp'] );

		if ( !$this->preferredWithJobQueue && $this->enabledHttpDeferredRequest && $this->canConnectToUrl() ) {
			return $this->doPostJobWith( $type, $title, $parameters, $dispatchableCallbackJob );
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

		if ( $type === 'SMW\SearchTableUpdateJob' ) {
			$callback = function ( $title, $parameters ) use ( $jobFactory ) {

				$purgeParserCacheJob = $jobFactory->newSearchTableUpdateJob(
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

		if ( $type !== 'SMW\ParserCachePurgeJob' && $type !== 'SMW\UpdateJob' && $type !== 'SMW\SearchTableUpdateJob' ) {
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

	private function doPostJobWith( $type, $title, $parameters, $dispatchableCallbackJob ) {

		$parameters['async-job'] = array(
			'type'  => $type,
			'title' => $title->getPrefixedDBkey()
		);

		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_METHOD, 'POST' );
		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_CONTENT_TYPE, "application/x-www-form-urlencoded" );
		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_CONTENT, 'parameters=' . json_encode( $parameters ) );
		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_CONNECTION_FAILURE_REPEAT, 2 );

		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_ON_COMPLETED_CALLBACK, function( $requestResponse ) use ( $parameters ) {
			$requestResponse->set( 'type', $parameters['async-job']['type'] );
			$requestResponse->set( 'title', $parameters['async-job']['title'] );
			wfDebugLog( 'smw', 'SMW\DeferredRequestDispatchManager: ' . json_encode( $requestResponse->getList(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
		} );

		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_ON_FAILED_CALLBACK, function( $requestResponse ) use ( $dispatchableCallbackJob, $title, $type, $parameters ) {
			$requestResponse->set( 'type', $parameters['async-job']['type'] );
			$requestResponse->set( 'title', $parameters['async-job']['title'] );

			wfDebugLog( 'smw', "SMW\DeferredRequestDispatchManager: Connection to SpecialDeferredRequestDispatcher failed on {$type} with " . json_encode( $requestResponse->getList(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ). " " . $title->getPrefixedDBkey() . "\n" );
			call_user_func_array( $dispatchableCallbackJob, array( $title, $parameters ) );
		} );

		$this->httpRequest->execute();

		return true;
	}

}
