<?php

namespace SMW;

use Title;
use Onoi\HttpRequest\HttpRequest;
use SMW\MediaWiki\Specials\SpecialAsyncJobDispatcher;

/**
 * During the storage of a page, sometimes it is necessary the create extra
 * processing requests that should be executed asynchronously (due to large DB
 * processing time) but without delay of the current transaction. This class
 * initiates and creates a separate request to be handled by the receiving
 * `SpecialAsyncJobDispatcher` endpoint (if it can connect).
 *
 * `AsyncJobDispatchManager` allows to invoke jobs independent from the job
 * scheduler with the objective to be run timely to the current transaction
 * without having to wait on the job scheduler and without blocking the current
 * request.
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class AsyncJobDispatchManager {

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
	private $enabledAsyncUsageState = true;

	/**
	 * @since 2.3
	 *
	 * @param HttpRequest $httpRequest
	 */
	public function __construct( HttpRequest $httpRequest) {
		$this->httpRequest = $httpRequest;
		$this->url = SpecialAsyncJobDispatcher::getTargetURL();
	}

	/**
	 * @since 2.3
	 */
	public function reset() {
		self::$canConnectToUrl = null;
		$this->enabledAsyncUsageState = true;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $enabledAsyncUsageState
	 */
	public function setEnabledAsyncUsageState( $enabledAsyncUsageState ) {
		$this->enabledAsyncUsageState = (bool)$enabledAsyncUsageState;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $type
	 * @param Title $title
	 * @param array $parameters
	 */
	public function dispatchJobFor( $type, Title $title, $parameters = array() ) {

		if ( !$this->doPreliminaryCheckForType( $type, $parameters ) ) {
			return null;
		}

		$dispatchableCallbackJob = $this->getDispatchableCallbackJobFor( $type );

		// Build sessionToken as source verification during the POST request
		$parameters['timestamp'] = time();
		$parameters['sessionToken'] = SpecialAsyncJobDispatcher::getSessionToken( $parameters['timestamp'] );

		if ( $this->enabledAsyncUsageState && $this->canConnectToUrl() ) {
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

		$this->httpRequest->setOption( CURLOPT_URL, $this->url );
		$this->httpRequest->setOption( CURLOPT_SSL_VERIFYPEER, false );

		return self::$canConnectToUrl = $this->httpRequest->ping();
	}

	private function doDispatchAsyncJobFor( $type, $title, $parameters, $dispatchableCallbackJob ) {

		$parameters['async-job'] = array(
			'type'  => $type,
			'title' => $title->getPrefixedDBkey()
		);

		$async = function ( $url, $params = array() ) use( $title, $dispatchableCallbackJob ) {
			$post_params = array();
			$httpMessage = '';

			foreach ( $params as $key => $val ) {

				if ( is_array( $val ) ) {
					$val = implode( '|', $val );
				}

				$post_params[] = $key . '=' . urlencode( $val );
			}

			$post_string = implode( '&', $post_params );
			$parts = parse_url( $url );

			$remoteSocket = $parts['host'] . ':' .  ( isset( $parts['port'] ) ? $parts['port'] : 80 );

			$res = @stream_socket_client(
				$remoteSocket,
				$errno,
				$errstr,
				30,
				STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT
			);

			if ( !$res ) {
				wfDebugLog( 'smw', __METHOD__  . " $errstr ($errno)". "\n" );
				call_user_func_array( $dispatchableCallbackJob, array( $title, $params ) );
			} else {

				$httpMessage .= "POST " . $parts['path'] . " HTTP/1.1\r\n";
				$httpMessage .= "Host: " . $parts['host'] . "\r\n";
				$httpMessage .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$httpMessage .= "Content-Length: " . strlen( $post_string ) . "\r\n";
				$httpMessage .= "Connection: Close\r\n\r\n";
				$httpMessage .= $post_string;

				if ( !@fwrite( $res, $httpMessage ) ) {
					wfDebugLog( 'smw', __METHOD__  . " connection to {$remoteSocket} failed, try again " . "\n" );
					if ( !@fwrite( $res, $httpMessage ) ) {
						wfDebugLog( 'smw', __METHOD__  . " connection to {$remoteSocket} failed again, add job for " . $title->getPrefixedDBkey() . "\n" );
						call_user_func_array( $dispatchableCallbackJob, array( $title, $params ) );
					}
				}

				fclose( $res );
			}
		};

		call_user_func_array( $async, array( $this->url, $parameters ) );

		return true;
	}

}
