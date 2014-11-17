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
 * Collects properties from a store entity
 *
 * @ingroup SQLStore
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Nischay Nahata
 */
class PropertiesCollector extends CacheableResultCollector {

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
			'id'      => array( 'smwgPropertiesCache', (array)$this->requestOptions ),
			'type'    => $this->settings->get( 'smwgCacheType' ),
			'enabled' => $this->settings->get( 'smwgPropertiesCache' ),
			'expiry'  => $this->settings->get( 'smwgPropertiesCacheExpiry' )
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

		$conditions = array(
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => '',
		);

		if ( $this->requestOptions !== null ) {

			if ( $this->requestOptions->limit > 0 ) {
				$options['LIMIT'] = $this->requestOptions->limit;
				$options['OFFSET'] = max( $this->requestOptions->offset, 0 );
			}

			if ( $this->requestOptions->getStringConditions() ) {
				$conditions[] = $this->store->getSQLConditions( $this->requestOptions, '', 'smw_title', false );
			}

		}

		$res = $this->dbConnection->select(
			$this->store->getObjectIds()->getIdTable(),
			array(
				'smw_id',
				'smw_title'
			),
			$conditions,
			__METHOD__,
			$options
		);

		Profiler::Out( __METHOD__ );
		return $res;
	}

	/**
	 * Collect all properties in the SMW IDs table (based on their namespace) and
	 * getting their usage from the property statistics table.
	 *
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	protected function getProperties( $res ) {
		Profiler::In( __METHOD__ );

		$result = array();
		$propertyIds = array();

		foreach ( $res as $row ) {
			$propertyIds[] = (int)$row->smw_id;
		}

		$statsTable = new PropertyStatisticsTable(
			$this->store->getConnection( 'mw.db' ),
			$this->store->getStatisticsTable()
		);

		$usageCounts = $statsTable->getUsageCounts( $propertyIds );

		foreach ( $res as $row ) {

			try {
				$property = new DIProperty( $row->smw_title );
			} catch ( InvalidPropertyException $e ) {
				$property = new SMWDIError( new Message( 'smw_noproperty', array( $row->smw_title ) ) );
			}

			// If there is no key entry in the usageCount table for that
			// particular property it is to be counted with usage 0
			$count = array_key_exists( (int)$row->smw_id, $usageCounts ) ? $usageCounts[(int)$row->smw_id] : 0;
			$result[] = array( $property, $count );
		}

		Profiler::Out( __METHOD__ );
		return $result;
	}

}
