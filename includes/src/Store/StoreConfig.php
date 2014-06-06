<?php

namespace SMW\Store;

use SMW\Cache\InMemoryCache;
use SMW\Settings;

use InvalidArgumentException;

/**
 * Provide access to configurations only relevant to a Store
 *
 * @ingroup Configuration
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class StoreConfig {

	/** @var InMemoryCache */
	protected $configuration = null;

	/** @var array */
	protected $supportedConfigurationKeys = array(
		'smwgDefaultStore',
		'smwgFixedProperties',
		'smwgPageSpecialProperties',
		'smwgIgnoreQueryErrors',
		'smwgQSortingSupport',
		'smwgQRandSortingSupport',
		'smwgAutoRefreshSubject',
		'smwgQMaxLimit',
		'smwgQMaxSize',
		'smwgQConceptFeatures',
		'smwgQConceptCaching',
		'smwgQSubpropertyDepth',
		'smwgQSubcategoryDepth',
		'smwgQEqualitySupport',
		'smwgEnableUpdateJobs'
	);

	/**
	 * @since 1.9.3
	 *
	 * @param Settings|null $settings
	 */
	public function __construct( Settings $settings = null ) {
		$this->configuration = $this->initConfigurationFromSettings( $settings );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function get( $key ) {

		if ( $this->configuration->has( $key ) ) {
			return $this->configuration->get( $key );
		}

		throw new InvalidArgumentException( "Expected a valid {$key} key" );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return StoreConfig
	 */
	public function set( $key, $value ) {

		$this->configuration->set(
			$this->isSupportedConfigurationKeyOrThrowException( $key ),
			$value
		);

		return $this;
	}

	private function initConfigurationFromSettings( Settings $settings = null ) {

		if ( $settings === null ) {
			$settings = Settings::newFromGlobals();
		}

		$configuration = new InMemoryCache();

		foreach ( $this->supportedConfigurationKeys as $key ) {
			$configuration->set( $key, $settings->get( $key ) );
		}

		return $configuration;
	}

	private function isSupportedConfigurationKeyOrThrowException( $key ) {

		if ( in_array( $key, $this->supportedConfigurationKeys ) ) {
			return $key;
		}

		throw new InvalidArgumentException( "{$key} is not supported as configuration key" );
	}

}
