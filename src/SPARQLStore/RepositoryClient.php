<?php

namespace SMW\SPARQLStore;

use SMW\Utils\Flag;

/**
 * Provides information about the client and how to communicate with
 * its services
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class RepositoryClient {

	/**
	 * @var string
	 */
	private $name = '';

	private ?Flag $featureSet = null;

	/**
	 * @since 2.2
	 */
	public function __construct(
		private $defaultGraph,
		private $queryEndpoint,
		private $updateEndpoint = '',
		private $dataEndpoint = '',
	) {
	}

	/**
	 * @since 3.2
	 *
	 * @param int $featureSet
	 */
	public function setFeatureSet( int $featureSet ): void {
		$this->featureSet = new Flag( $featureSet );
	}

	/**
	 * @since 3.2
	 *
	 * @param int $key
	 */
	public function isFlagSet( int $key ): bool {
		return $this->featureSet !== null && $this->featureSet->is( $key );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 */
	public function setName( $name ): void {
		$this->name = $name;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getDefaultGraph() {
		return $this->defaultGraph;
	}

	/**
	 * @since 2.2
	 *
	 * @return string|false
	 */
	public function getQueryEndpoint() {
		return $this->queryEndpoint;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getUpdateEndpoint() {
		return $this->updateEndpoint;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getDataEndpoint() {
		return $this->dataEndpoint;
	}

}
