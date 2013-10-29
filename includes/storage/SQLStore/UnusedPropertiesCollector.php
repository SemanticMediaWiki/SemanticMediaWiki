<?php

namespace SMW\SQLStore;

use SMW\Store\CacheableResultCollector;

use SMW\InvalidPropertyException;
use SMW\SimpleDictionary;
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
 * @ingroup SQLStore
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Nischay Nahata
 */
class UnusedPropertiesCollector extends CacheableResultCollector {

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
	 * Returns unused properties
	 *
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	public function runCollector() {
		return $this->getProperties( $this->doQuery() );
	}

	/**
	 * @see CacheableObjectCollector::cacheSetup
	 *
	 * @since 1.9
	 *
	 * @return ObjectDictionary
	 */
	protected function cacheSetup() {
		return new SimpleDictionary( array(
			'id'      => array( 'smwgUnusedPropertiesCache', $this->requestOptions ),
			'type'    => $this->settings->get( 'smwgCacheType' ),
			'enabled' => $this->settings->get( 'smwgUnusedPropertiesCache' ),
			'expiry'  => $this->settings->get( 'smwgUnusedPropertiesCacheExpiry' )
		) );
	}

	/**
	 * @since 1.9
	 *
	 * @return array
	 */
	protected function doQuery() {
		Profiler::In( __METHOD__ );

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

		Profiler::Out( __METHOD__ );
		return $res;
	}

	/**
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	protected function getProperties( $res ) {
		Profiler::In( __METHOD__ );

		$result = array();

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
