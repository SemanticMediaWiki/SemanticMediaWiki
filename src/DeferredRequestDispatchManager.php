<?php

namespace SMW;

use Onoi\HttpRequest\HttpRequest;
use SMW\MediaWiki\Specials\SpecialDeferredRequestDispatcher;
use SMW\MediaWiki\Jobs\JobFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Title;

/**
 * During the storage of a page, sometimes it is necessary to create extra
 * processing requests that should be executed asynchronously (due to large DB
 * processing time or is a secondary update) but without delay of the current
 * transaction. This class initiates and creates a separate request to be handled
 * by the receiving `SpecialDeferredRequestDispatcher` endpoint (if it can
 * connect).
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
class DeferredRequestDispatchManager implements LoggerAwareInterface {

	/**
	 * @var HttpRequest
	 */
	private $httpRequest;

	/**
	 * @var JobFactory
	 */
	private $jobFactory;

	/**
	 * Is kept static in order for the cli process to only make the check once
	 * and verify it can/cannot connect.
	 *
	 * @var boolean|null
	 */
	private static $canConnectToUrl = null;

	/**
	 * @var boolean
	 */
	private $isEnabledHttpDeferredRequest = true;

	/**
	 * @var boolean
	 */
	private $isPreferredWithJobQueue = false;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @var boolean
	 */
	private $isEnabledJobQueue = true;

	/**
	 * LoggerInterface
	 */
	private $logger;

	/**
	 * @since 2.3
	 *
	 * @param HttpRequest $httpRequest
	 * @param JobFactory $jobFactory
	 */
	public function __construct( HttpRequest $httpRequest, JobFactory $jobFactory ) {
		$this->httpRequest = $httpRequest;
		$this->jobFactory = $jobFactory;
	}

	/**
	 * @since 2.3
	 */
	public function reset() {
		self::$canConnectToUrl = null;
		$this->isEnabledHttpDeferredRequest = true;
		$this->isPreferredWithJobQueue = false;
		$this->isCommandLineMode = false;
		$this->isEnabledJobQueue = true;
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $isEnabledHttpDeferredRequest
	 */
	public function isEnabledHttpDeferredRequest( $isEnabledHttpDeferredRequest ) {
		$this->isEnabledHttpDeferredRequest = $isEnabledHttpDeferredRequest;
	}

	/**
	 * Certain types of jobs or tasks may prefer to be executed using the job
	 * queue therefore indicate whether the dispatcher should try opening a
	 * http request or not.
	 *
	 * @since 2.5
	 *
	 * @param boolean $isPreferredWithJobQueue
	 */
	public function isPreferredWithJobQueue( $isPreferredWithJobQueue ) {
		$this->isPreferredWithJobQueue = (bool)$isPreferredWithJobQueue;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
	 * Indicates whether MW is running in command-line mode.
	 *
	 * @since 2.5
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = $isCommandLineMode;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $isEnabledJobQueue
	 */
	public function isEnabledJobQueue( $isEnabledJobQueue ) {
		$this->isEnabledJobQueue = $isEnabledJobQueue;
	}

	/**
	 * @since 2.4
	 *
	 * @param Title|null $title
	 * @param array $parameters
	 */
	public function dispatchParserCachePurgeJobWith( Title $title = null , $parameters = array() ) {

		if ( $title === null || $parameters === array() || !isset( $parameters['idlist'] ) ) {
			return;
		}

		return $this->dispatchJobRequestWith( 'SMW\ParserCachePurgeJob', $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title|null $title
	 * @param array $parameters
	 */
	public function dispatchFulltextSearchTableUpdateJobWith( Title $title = null, $parameters = array() ) {

		if ( $title === null ) {
			return;
		}

		return $this->dispatchJobRequestWith( 'SMW\FulltextSearchTableUpdateJob', $title, $parameters );
	}

	/**
	 * @since 2.5
	 *
	 * @param Title|null $title
	 * @param array $parameters
	 */
	public function dispatchTempChangeOpPurgeJobWith( Title $title = null, $parameters = array() ) {

		if ( $title === null || $parameters === array() ) {
			return;
		}

		if ( !isset( $parameters['slot:id'] ) || $parameters['slot:id'] === null ) {
			return;
		}

		return $this->dispatchJobRequestWith( 'SMW\TempChangeOpPurgeJob', $title, $parameters );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $type
	 * @param Title|null $title
	 * @param array $parameters
	 */
	public function dispatchJobRequestWith( $type, Title $title = null, $parameters = array() ) {

		if ( $title === null || !$this->isAllowedJobType( $type ) ) {
			return null;
		}

		$this->httpRequest->setOption(
			ONOI_HTTP_REQUEST_URL,
			SpecialDeferredRequestDispatcher::getTargetURL()
		);

		$dispatchableCallbackJob = $this->createDispatchableCallbackJob(
			$type,
			$this->isEnabledJobQueue
		);

		if ( $this->canUseDeferredRequest() ) {
			return $this->doPostJobWith( $type, $title, $parameters, $dispatchableCallbackJob );
		}

		call_user_func_array(
			$dispatchableCallbackJob,
			array( $title, $parameters )
		);

		return true;
	}

	private function canUseDeferredRequest() {
		return !$this->isCommandLineMode && !$this->isPreferredWithJobQueue && $this->isEnabledHttpDeferredRequest === SMW_HTTP_DEFERRED_ASYNC && $this->canConnectToUrl();
	}

	private function createDispatchableCallbackJob( $type, $isEnabledJobQueue ) {

		$callback = function ( $title, $parameters ) use ( $type, $isEnabledJobQueue ) {

			$job = $this->jobFactory->newByType(
				$type,
				$title,
				$parameters
			);

			// Only relevant when jobs are executed during a test (PHPUnit)
			$job->isEnabledJobQueue( $isEnabledJobQueue );

			// A `run` action would executed the job with the current transaction
			// defying the idea of a deferred process therefore only directly
			// have the job run when initiate from the commandLine as transactions
			// are expected without delay and are separated
			if ( $this->isCommandLineMode || $this->isEnabledHttpDeferredRequest === SMW_HTTP_DEFERRED_SYNC_JOB ) {
				$job->run();
			} elseif ( $this->isEnabledHttpDeferredRequest === SMW_HTTP_DEFERRED_LAZY_JOB ) {
				// Buffers the job and is added at the end of MediaWiki::restInPeace
				$job->lazyPush();
			} else {
				$job->insert();
			}
		};

		return $callback;
	}

	private function isAllowedJobType( $type ) {

		$allowedJobs = array(
			'SMW\ParserCachePurgeJob',
			'SMW\UpdateJob',
			'SMW\FulltextSearchTableUpdateJob',
			'SMW\TempChangeOpPurgeJob'
		);

		return in_array( $type, $allowedJobs );
	}

	private function canConnectToUrl() {

		if ( self::$canConnectToUrl !== null ) {
			return self::$canConnectToUrl;
		}

		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_SSL_VERIFYPEER, false );

		return self::$canConnectToUrl = $this->httpRequest->ping();
	}

	private function doPostJobWith( $type, $title, $parameters, $dispatchableCallbackJob ) {

		// Build requestToken as source verification during the POST request
		$parameters['timestamp'] = time();
		$parameters['requestToken'] = SpecialDeferredRequestDispatcher::getRequestToken( $parameters['timestamp'] );

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
			$this->log( 'SMW\DeferredRequestDispatchManager: ' . json_encode( $requestResponse->getList(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		} );

		$this->httpRequest->setOption( ONOI_HTTP_REQUEST_ON_FAILED_CALLBACK, function( $requestResponse ) use ( $dispatchableCallbackJob, $title, $type, $parameters ) {
			$requestResponse->set( 'type', $parameters['async-job']['type'] );
			$requestResponse->set( 'title', $parameters['async-job']['title'] );

			$this->log( "SMW\DeferredRequestDispatchManager: Connection to SpecialDeferredRequestDispatcher failed, schedule a {$type}" );
			call_user_func_array( $dispatchableCallbackJob, array( $title, $parameters ) );
		} );

		$this->httpRequest->execute();

		return true;
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
