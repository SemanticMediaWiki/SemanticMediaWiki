<?php

namespace SMW\SQLStore;

use SMW\Store\Collector;

use SMW\InvalidPropertyException;
use SMW\ArrayAccessor;
use SMW\DIProperty;
use SMW\Settings;
use SMW\Profiler;
use SMW\Store;

use SMWDIError;

use Message;
use DatabaseBase;

/**
 * Collects unused properties from a store entity
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 * @author Nischay Nahata
 */

/**
 * Collects unused properties from a store entity
 *
 * @ingroup Collector
 * @ingroup SQLStore
 */
class UnusedPropertiesCollector extends Collector {

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/** @var DatabaseBase */
	protected $dbConnection;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param DatabaseBase $dbw
	 * @param Settings $settings
	 */
	public function __construct( Store $store, DatabaseBase $dbw, Settings $settings ) {
		$this->store = $store;
		$this->dbConnection = $dbw;
		$this->settings = $settings;
	}

	/**
	 * Factory method for an immediate instantiation of a UnusedPropertiesCollector object
	 *
	 * @par Example:
	 * @code
	 *  $properties = \SMW\SQLStore\UnusedPropertiesCollector::newFromStore( $store )
	 *  $properties->getResults();
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param $dbw Boolean or DatabaseBase:
	 * - Boolean: whether to use a dedicated DB or Slave
	 * - DatabaseBase: database connection to use
	 *
	 * @return Collector
	 */
	public static function newFromStore( Store $store, $dbw = false ) {

		$dbw = $dbw instanceof DatabaseBase ? $dbw : wfGetDB( DB_SLAVE );
		$settings = Settings::newFromGlobals();
		return new self( $store, $dbw, $settings );
	}

	/**
	 * Set-up details used for the Cache instantiation
	 *
	 * @see $smwgUnusedPropertiesCache
	 * @see $smwgUnusedPropertiesCacheExpiry
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	protected function cacheAccessor() {
		return new ArrayAccessor( array(
			'id'      => array( 'smwgUnusedPropertiesCache', $this->requestOptions ),
			'type'    => $this->settings->get( 'smwgCacheType' ),
			'enabled' => $this->settings->get( 'smwgUnusedPropertiesCache' ),
			'expiry'  => $this->settings->get( 'smwgUnusedPropertiesCacheExpiry' )
		) );
	}

	/**
	 * Returns unused properties
	 *
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	protected function doCollect() {
		Profiler::In( __METHOD__ );

		$result = array();

		// the query needs to do the filtering of internal properties, else LIMIT is wrong
		$options = array( 'ORDER BY' => 'smw_sortkey' );

		if ( $this->requestOptions !== null ) {
			if ( $this->requestOptions->limit > 0 ) {
				$options['LIMIT'] = $this->requestOptions->limit;
				$options['OFFSET'] = max( $this->requestOptions->offset, 0 );
			}
		}

		$conditions = array(
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => ''
		);

		$conditions['usage_count'] = 0;

		$res = $this->dbConnection->select(
			array( $this->store->getObjectIds()->getIdTable(), $this->store->getStatisticsTable() ),
			array( 'smw_title', 'usage_count' ),
			$conditions,
			__METHOD__,
			$options,
			array( $this->store->getObjectIds()->getIdTable() => array( 'INNER JOIN', array( 'smw_id=p_id' ) ) )
		);

		foreach ( $res as $row ) {

			try {
				$property = new DIProperty( $row->smw_title );
			} catch ( InvalidPropertyException $e ) {
				$property = new SMWDIError( new Message( 'smw_noproperty', array( $row->smw_title ) ) );
			}

			$result[] = $property;
		}

		Profiler::Out( __METHOD__ );
		return $result;
	}
}
