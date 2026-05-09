<?php

namespace SMW\Elastic\Connection;

use Elasticsearch\Client as ElasticClient;
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
	 * @since 3.0
	 */
	public function __construct(
		// @phan-suppress-next-line PhanUndeclaredTypeParameter,PhanUndeclaredTypeProperty
		protected ?ElasticClient $client = null,
		private ?Cache $cache = null,
		private ?Config $config = null,
	) {
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
	public function getConfig(): ?Config {
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
	public function getIndexDefFileModificationTimeByType( $type ): int {
		return 0;
	}

	/**
	 * @see Client::getSoftwareInfo
	 */
	public function getSoftwareInfo(): array {
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
	public function cat( $type, $params = [] ): array {
		return [];
	}

	/**
	 * @see Client::hasIndex
	 */
	public function hasIndex( $type, $useCache = true ): bool {
		return true;
	}

	/**
	 * @see Client::createIndex
	 */
	public function createIndex( $type ): string {
		return '';
	}

	/**
	 * @see Client::deleteIndex
	 */
	public function deleteIndex( $index ): void {
	}

	/**
	 * @see Client::putSettings
	 */
	public function putSettings( array $params ): void {
	}

	/**
	 * @see Client::putMapping
	 */
	public function putMapping( array $params ): void {
	}

	/**
	 * @see Client::getMapping
	 */
	public function getMapping( array $params ): array {
		return [];
	}

	/**
	 * @see Client::getSettings
	 */
	public function getSettings( array $params ): array {
		return [];
	}

	/**
	 * @see Client::refresh
	 */
	public function refresh( array $params ): void {
	}

	/**
	 * @see Client::validate
	 */
	public function validate( array $params ): array {
		return [];
	}

	/**
	 * @see Client::ping
	 */
	public function ping(): bool {
		return false;
	}

	/**
	 * @see Client::quick_ping
	 */
	public function quick_ping( $timeout = 2 ): bool {
		return false;
	}

	/**
	 * @see Client::exists
	 */
	public function exists( array $params ): bool {
		return false;
	}

	/**
	 * @see Client::get
	 */
	public function get( array $params ): array {
		return [];
	}

	/**
	 * @see Client::delete
	 */
	public function delete( array $params ): array {
		return [];
	}

	/**
	 * @see Client::update
	 */
	public function update( array $params ): void {
	}

	/**
	 * @see Client::index
	 */
	public function index( array $params ): void {
	}

	/**
	 * @see Client::bulk
	 */
	public function bulk( array $params ): void {
	}

	/**
	 * @see Client::count
	 */
	public function count( array $params ): array {
		return [];
	}

	/**
	 * @see Client::search
	 */
	public function search( array $params ): array {
		return [ [], [] ];
	}

	/**
	 * @see Client::explain
	 */
	public function explain( array $params ): array {
		return [];
	}

	/**
	 * @see Client::updateAliases
	 */
	public function updateAliases( array $params ): void {
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
	public function openIndex( string $index ): void {
	}

	/**
	 * @see Client::closeIndex
	 */
	public function closeIndex( string $index ): void {
	}

	/**
	 * @see Client::hasMaintenanceLock
	 */
	public function hasMaintenanceLock(): bool {
		return false;
	}

	/**
	 * @see Client::setMaintenanceLock
	 */
	public function setMaintenanceLock(): void {
	}

	/**
	 * @see Client::setLock
	 */
	public function setLock( $type, $version ): void {
	}

	/**
	 * @see Client::hasLock
	 */
	public function hasLock( $type ): bool {
		return false;
	}

	/**
	 * @see Client::getLock
	 */
	public function getLock( $type ): bool {
		return false;
	}

	/**
	 * @see Client::getLock
	 */
	public function releaseLock( $type ): void {
	}

}
