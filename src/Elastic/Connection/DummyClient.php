<?php

namespace SMW\Elastic\Connection;

use Onoi\Cache\Cache;
use Onoi\Cache\NullCache;
use Psr\Log\NullLogger;
use SMW\Elastic\Config;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DummyClient extends Client {

	/**
	 * @var Client
	 */
	protected $client;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @since 3.0
	 *
	 * @param \Elasticsearch\Client|null $client
	 * @param Cache|null $cache
	 * @param Config|null $config
	 */
	public function __construct( $client = null, ?Cache $cache = null, ?Config $config = null ) {
		$this->client = $client;
		$this->cache = $cache;
		$this->config = $config;

		if ( $this->cache === null ) {
			$this->cache = new NullCache();
		}

		if ( $this->config === null ) {
			$this->config = new Config();
		}

		$this->logger = new NullLogger();
	}

	/**
	 * @see Client::getConfig
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @see Client::getIndexName
	 */
	public function getIndexName( string $type ): string {
		return '';
	}

	/**
	 * @see Client::getIndexDefinition
	 */
	public function getIndexDefinition( string $type ): string {
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
			'component' => "[https://www.elastic.co/elasticsearch/ Elasticsearch]",
			'version' => null
		];
	}

	/**
	 * @see Client::info
	 */
	public function info(): array {
		return [];
	}

	/**
	 * @see Client::stats
	 */
	public function stats( string $type = 'indices', array $params = [] ): array {
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
	public function createIndex( $type ) {
	}

	/**
	 * @see Client::deleteIndex
	 */
	public function deleteIndex( $index ) {
	}

	/**
	 * @see Client::putSettings
	 */
	public function putSettings( array $params ) {
	}

	/**
	 * @see Client::putMapping
	 */
	public function putMapping( array $params ) {
	}

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
	public function refresh( array $params ) {
	}

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
	public function update( array $params ) {
	}

	/**
	 * @see Client::index
	 */
	public function index( array $params ) {
	}

	/**
	 * @see Client::bulk
	 */
	public function bulk( array $params ) {
	}

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
	 * @see Client::updateAliases
	 */
	public function updateAliases( array $params ) {
	}

	/**
	 * @see Client::indexExists
	 */
	public function indexExists( string $index ): bool {
		return true;
	}

	/**
	 * @see Client::aliasExists
	 */
	public function aliasExists( string $index ): bool {
		return true;
	}

	/**
	 * @see Client::openIndex
	 */
	public function openIndex( string $index ) {
	}

	/**
	 * @see Client::closeIndex
	 */
	public function closeIndex( string $index ) {
	}

	/**
	 * @see Client::hasMaintenanceLock
	 */
	public function hasMaintenanceLock() {
		return false;
	}

	/**
	 * @see Client::setMaintenanceLock
	 */
	public function setMaintenanceLock() {
	}

	/**
	 * @see Client::setLock
	 */
	public function setLock( $type, $version ) {
	}

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
	public function releaseLock( $type ) {
	}

}
