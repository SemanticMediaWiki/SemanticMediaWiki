<?php

namespace SMW\Query;

use MediaWiki\Html\Html;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use RuntimeException;
use SMW\Localizer\Message;
use SMW\Query\Result\StringResult;
use SMW\QueryEngine;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * @license GPL-2.0-or-later
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

	private int $features = 0;

	private bool $isFromCache = false;

	/**
	 * @var bool|null
	 */
	private static $isConnected;

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly array $parameters = [],
		private ?HttpRequestFactory $httpRequestFactory = null,
		private ?WANObjectCache $cache = null
	) {
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
	 * @param int $flag
	 *
	 * @return bool
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

		$source = $query->getQuerySource();
		if ( !isset( $this->parameters['url'] ) ) {
			throw new RuntimeException( "Missing a remote URL for $source" );
		}

		$this->initServices();

		if ( !$this->canConnect( $this->parameters['url'] ) ) {
			return $this->error( 'smw-remote-source-unavailable', $this->parameters['url'] );
		}

		$result = $this->fetch( $query );

		$isFromCache = $this->isFromCache;
		$isDisabled = false;
		$count = 0;
		$hasFurtherResults = false;

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

			[ $count, $hasFurtherResults ] = $this->findExtraInformation(
				$result
			);

			$result = substr( $result, 0, strpos( $result, self::REQUEST_ID ) );
		}

		// Add an information note depending on the context before the actual output
		$callback = function ( $result, array $options ) use( $isFromCache, $isDisabled, $source ) {
			$options['source'] = $source;
			$options['is.cached'] = $isFromCache;
			$options['is.disabled'] = $isDisabled;

			return $this->format_result( $result, $options );
		};

		$stringResult = new StringResult( $result, $query, $hasFurtherResults );
		$stringResult->setPreOutputCallback( $callback );
		$stringResult->setFromCache( $isFromCache );
		$stringResult->setCount( $count );

		if ( $query->getQueryMode() === Query::MODE_COUNT ) {
			$stringResult->setCountValue( $result );
		}

		return $stringResult;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $result
	 * @param array $options
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

	private function findExtraInformation( &$result ) {
		$count = 0;
		$hasFurtherResults = false;

		preg_match_all( '/<!--(.*?)-->/', $result, $matches );

		if ( $matches !== [] ) {
			foreach ( $matches[1] as $val ) {
				[ $k, $v ] = explode( ':', $val );

				if ( $k === 'COUNT' ) {
					$count = intval( $v );
				} elseif ( $k === 'FURTHERRESULTS' ) {
					$hasFurtherResults = (bool)$v;
				}
			}

			foreach ( $matches[0] as $val ) {
				$result = str_replace( $val, '', $result );
			}
		}

		return [ $count, $hasFurtherResults ];
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

	private function initServices(): void {
		if ( $this->httpRequestFactory === null ) {
			$this->httpRequestFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
		}

		if ( $this->cache === null && isset( $this->parameters['cache'] ) ) {
			$this->cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		}
	}

	private function error() {
		$params = func_get_args();

		return Html::errorBox(
			Message::get( $params, Message::PARSE, Message::USER_LANGUAGE ),
			'',
			$params[0]
		);
	}

	private function canConnect( string $url ): bool {
		if ( self::$isConnected === null ) {
			$request = $this->httpRequestFactory->create( $url, [
				'method' => 'HEAD',
				'followRedirects' => true,
			], __METHOD__ );

			self::$isConnected = $request->execute()->isOK();
		}

		return self::$isConnected;
	}

	private function fetch( Query $query ): string {
		$params = $this->buildPostParams( $query );
		$default = $params['default'] ?? '';
		unset( $params['default'] );

		$url = $this->parameters['url'];
		$caller = __METHOD__;

		$doFetch = function () use ( $url, $params, $default, $caller ) {
			$request = $this->httpRequestFactory->create( $url, [
				'method' => 'POST',
				'postData' => http_build_query( $params ),
				'sslVerifyCert' => false,
			], $caller );

			$status = $request->execute();
			$output = $request->getContent();

			if ( !$status->isOK() ) {
				$output = '';
			}

			return $output !== '' ? $output : $default;
		};

		if ( isset( $this->parameters['cache'] ) ) {
			$cacheKey = $this->cache->makeKey(
				'smw', 'remote-request',
				md5( $url . serialize( $params ) )
			);

			$this->isFromCache = true;

			$result = $this->cache->getWithSetCallback(
				$cacheKey,
				$this->parameters['cache'],
				function () use ( $doFetch ) {
					$this->isFromCache = false;
					return $doFetch();
				}
			);

			return $result;
		}

		$this->isFromCache = false;
		return $doFetch();
	}

	private function buildPostParams( Query $query ): array {
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
		$params['default'] = $default;

		return $params;
	}

}
