<?php

namespace SMW\Query;

use Html;
use Onoi\HttpRequest\CachedCurlRequest;
use Onoi\HttpRequest\CurlRequest;
use Onoi\HttpRequest\HttpRequest;
use RuntimeException;
use SMW\ApplicationFactory;
use SMW\Message;
use SMW\Query\Result\StringResult;
use SMW\QueryEngine;
use SMW\Site;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RemoteRequest implements QueryEngine {

	/**
	 * Send by a remote source when remote access has been disabled.
	 */
	const SOURCE_DISABLED = "\x7fsmw-remote-response-disabled\x7f";

	/**
	 * Identifies a source to support a remote request.
	 */
	const REQUEST_ID = "\x7fsmw-remote-request\x7f";

	/**
	 * @var []
	 */
	private $parameters = [];

	/**
	 * @var HttpRequest
	 */
	private $httpRequest;

	/**
	 * @var []
	 */
	private $features = [];

	/**
	 * @var []
	 */
	private static $isConnected;

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 * @param HttpRequest|null $httpRequest
	 */
	public function __construct( array $parameters = [], HttpRequest $httpRequest = null ) {
		$this->parameters = $parameters;
		$this->httpRequest = $httpRequest;
		$this->features = $GLOBALS['smwgRemoteReqFeatures'];

		if ( isset( $this->parameters['smwgRemoteReqFeatures'] ) ) {
			$this->features = $this->parameters['smwgRemoteReqFeatures'];
		}
	}

	/**
	 * @since 3.0
	 */
	public function clear() {
		self::$isConnected = null;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $flag
	 *
	 * @return boolean
	 */
	public function hasFeature( $flag ) {
		return ( ( (int)$this->features & $flag ) == $flag );
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 *
	 * @return StringResult|string
	 */
	public function getQueryResult( Query $query ) {

		if ( $query->isEmbedded() && $query->getLimit() == 0 ) {
			return $this->further_link( $query );
		}

		if ( !isset( $this->parameters['url'] ) ) {
			throw new RuntimeException( "Missing a remote URL for $source" );
		}

		$source = $query->getQuerySource();
		$this->init();

		if ( !$this->canConnect( $this->parameters['url'] ) ) {
			return $this->error( 'smw-remote-source-unavailable', $this->parameters['url'] );
		}

		$result = $this->fetch( $query );

		$isFromCache = false;
		$isDisabled = false;

		if ( $this->httpRequest instanceof CachedCurlRequest ) {
			$isFromCache = $this->httpRequest->isFromCache();
		}

		if ( $result === self::SOURCE_DISABLED ) {
			$result = $this->error( 'smw-remote-source-disabled', $source );
			$isDisabled = true;
		}

		// Find out whether the source has send an ID and hereby produces an output
		// that can be used by the `RemoteRequest`
		if ( strpos( $result, self::REQUEST_ID ) === false ) {
			$result = $this->error( 'smw-remote-source-unmatched-id', $source );
			$isDisabled = true;
		} else {
			$result = str_replace( self::REQUEST_ID, '', $result );
		}

		// Add an information note depending on the context before the actual output
		$callback = function( $result, array $options ) use( $isFromCache, $isDisabled, $source ) {

			$options['source'] = $source;
			$options['is.cached'] = $isFromCache;
			$options['is.disabled'] = $isDisabled;

			return $this->format_result( $result, $options );
		};

		$stringResult = new StringResult( $result, $query );
		$stringResult->setPreOutputCallback( $callback );
		$stringResult->setFromCache( $isFromCache );

		if ( $query->getQueryMode() === Query::MODE_COUNT ) {
			$stringResult->setCountValue( $result );
		}

		return $stringResult;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $result
	 * @param  array  $options
	 *
	 * @return string
	 */
	public function format_result( $result, array $options ) {

		// No changes to any export related output
		if ( ( isset( $options['is.disabled'] ) && $options['is.disabled'] ) || !$this->hasFeature( SMW_REMOTE_REQ_SHOW_NOTE ) ) {
			return $result;
		}

		if ( ( isset( $options['is.exportformat'] ) && $options['is.exportformat'] ) ) {
			return $result;
		}

		$msg = $options['is.cached'] ? 'smw-remote-request-note-cached' : 'smw-remote-request-note';

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-note smw-remote-query',
				'style' => 'margin-top:12px;'
			],
			Html::rawElement(
				'span',
				[
					'class' => 'smw-icon-info',
					'style' => 'margin-left: -5px; padding: 10px 12px 12px 12px;'
				]
			) . Message::get( [ $msg, $options['source'] ], Message::PARSE, Message::USER_LANGUAGE )
		) . $result;
	}

	private function further_link( $query ) {

		$link = QueryLinker::get( $query );

		// Find remaining parameters, format, template etc.
		$extraParameters = $query->getOption( 'query.params' );

		foreach ( $extraParameters as $key => $value ) {

			if ( $key === 'limit' || $value === '' ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}

			$link->setParameter( $value, $key );
		}

		return $link->getText( SMW_OUTPUT_WIKI );
	}

	private function init() {

		if ( $this->httpRequest === null && isset( $this->parameters['cache'] ) ) {
			$this->httpRequest = new CachedCurlRequest(
				curl_init(),
				ApplicationFactory::getInstance()->getCache()
			);

			$this->httpRequest->setOption(
				ONOI_HTTP_REQUEST_RESPONSECACHE_TTL,
				$this->parameters['cache']
			);

			$this->httpRequest->setOption(
				ONOI_HTTP_REQUEST_RESPONSECACHE_PREFIX,
				Site::id( 'smw:query:remote:' )
			);
		}

		if ( $this->httpRequest === null ) {
			$this->httpRequest = new CurlRequest( curl_init() );
		}
	}

	private function error() {
		$params = func_get_args();

		return Html::rawElement(
			'div',
			[
				'id' => $params[0],
				'class' => 'smw-callout smw-callout-error'
			],
			Message::get( $params, Message::PARSE, Message::USER_LANGUAGE )
		);
	}

	private function canConnect( $url ) {

		$this->httpRequest->setOption( CURLOPT_URL, $url );

		if ( self::$isConnected === null ) {
			self::$isConnected = $this->httpRequest->ping();
		}

		return self::$isConnected;
	}

	private function fetch( $query ) {

		$parameters = $query->toArray();
		$default = '';
		$params = [ 'title' => 'Special:Ask', 'q' => '', 'po' => '', 'p' => [] ];

		if ( isset( $parameters['conditions'] ) ) {
			$params['q'] = $parameters['conditions'];
		}

		if ( isset( $parameters['printouts'] ) ) {
			$params['po'] = implode( '|', $parameters['printouts'] );
		}

		if ( !isset( $parameters['parameters'] ) ) {
			$parameters['parameters'] = [];
		}

		// Find remaining parameters, format, template etc.
		$extraParameters = $query->getOption( 'query.params' );

		if ( is_array( $extraParameters ) ) {
			$parameters['parameters'] = array_merge( $parameters['parameters'], $extraParameters );
		}

		foreach ( $parameters['parameters'] as $key => $value ) {

			if ( $key === 'default' ) {
				$default = $value;
			}

			if ( $value === '' ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}

			$params['p'][] = "$key=$value";
		}

		$params['request_type'] = $query->isEmbedded() ? 'embed' : 'special_page';
		$output = '';

		$options = [
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query( $params ),
			CURLOPT_RETURNTRANSFER => 1
		];

		foreach ( $options as $key => $value ) {
			$this->httpRequest->setOption( $key, $value );
		}

		$output = $this->httpRequest->execute();

		if ( $this->httpRequest->getLastError() !== '' ) {
			$output = $this->httpRequest->getLastError();
		}

		// The remote Special:Ask doesn't return a default output hence it is done
		// at this point
		if ( $output === '' ) {
			$output = $default;
		}

		return $output;
	}

}
