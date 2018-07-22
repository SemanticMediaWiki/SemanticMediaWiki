<?php

namespace SMW\Elastic\Connection;

use Onoi\Cache\Cache;
use Onoi\Cache\NullCache;
use Psr\Log\NullLogger;
use RuntimeException;
use SMW\Options;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DummyClient extends Client {

	/**
	 * @var Client
	 */
	private $client;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @since 3.0
	 *
	 * @param ElasticClient $client
	 * @param Cache|null $cache
	 * @param Options|null $options
	 */
	public function __construct( $client = null, Cache $cache = null, Options $options = null ) {
		$this->client = $client;
		$this->cache = $cache;
		$this->options = $options;

		if ( $this->cache === null ) {
			$this->cache = new NullCache();
		}

		if ( $this->options === null ) {
			$this->options = new Options();
		}

		$this->logger = new NullLogger();
	}

	/**
	 * @see Client::getConfig
	 */
	public function getConfig() {
		return $this->options;
	}

	/**
	 * @see Client::getIndexName
	 */
	public function getIndexName( $type ) {
		return '';
	}

	/**
	 * @see Client::getIndexDefByType
	 */
	public function getIndexDefByType( $type ) {
		return '';
	}

	/**
	 * @see Client::getIndexDefFileModificationTimeByType
	 */
	public function getIndexDefFileModificationTimeByType( $type ) {
		return 0;
	}

	/**
	 * @see Client::getSoftwareInfo
	 */
	public function getSoftwareInfo() {
		return [
			'component' => "[https://www.elastic.co/products/elasticsearch Elasticsearch]",
			'version' => null
		];
	}

	/**
	 * @see Client::info
	 */
	public function info() {
		return [];
	}

	/**
	 * @see Client::stats
	 */
	public function stats( $type = 'indices', $params = [] ) {
		return [];
	}

	/**
	 * @see Client::cat
	 */
	public function cat( $type, $params = [] ) {
		return [];
	}

	/**
	 * @see Client::hasIndex
	 */
	public function hasIndex( $type, $useCache = true ) {
		return true;
	}

	/**
	 * @see Client::createIndex
	 */
	public function createIndex( $type ) {}

	/**
	 * @see Client::deleteIndex
	 */
	public function deleteIndex( $type ) {}

	/**
	 * @see Client::putSettings
	 */
	public function putSettings( array $params ) {}

	/**
	 * @see Client::putMapping
	 */
	public function putMapping( array $params ) {}

	/**
	 * @see Client::getMapping
	 */
	public function getMapping( array $params ) {
		return [];
	}

	/**
	 * @see Client::getSettings
	 */
	public function getSettings( array $params ) {
		return [];
	}

	/**
	 * @see Client::refresh
	 */
	public function refresh( array $params ) {}

	/**
	 * @see Client::validate
	 */
	public function validate( array $params ) {
		return [];
	}

	/**
	 * @see Client::ping
	 */
	public function ping() {
		return false;
	}

	/**
	 * @see Client::quick_ping
	 */
	public function quick_ping( $timeout = 2 ) {
		return false;
	}

	/**
	 * @see Client::exists
	 */
	public function exists( array $params ) {
		return false;
	}

	/**
	 * @see Client::get
	 */
	public function get( array $params ) {
		return [];
	}

	/**
	 * @see Client::delete
	 */
	public function delete( array $params ) {
		return [];
	}

	/**
	 * @see Client::update
	 */
	public function update( array $params ) {}

	/**
	 * @see Client::index
	 */
	public function index( array $params ) {}

	/**
	 * @see Client::bulk
	 */
	public function bulk( array $params ) {}

	/**
	 * @see Client::count
	 */
	public function count( array $params ) {
		return 0;
	}

	/**
	 * @see Client::search
	 */
	public function search( array $params ) {
		return [ [], [] ];
	}

	/**
	 * @see Client::explain
	 */
	public function explain( array $params ) {
		return [];
	}

	/**
	 * @see Client::setLock
	 */
	public function setLock( $type, $version ) {}

	/**
	 * @see Client::hasLock
	 */
	public function hasLock( $type ) {
		return false;
	}

	/**
	 * @see Client::getLock
	 */
	public function getLock( $type ) {
		return false;
	}

	/**
	 * @see Client::getLock
	 */
	public function releaseLock( $type ) {}

}
