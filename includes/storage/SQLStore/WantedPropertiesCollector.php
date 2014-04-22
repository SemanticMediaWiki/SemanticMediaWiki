<?php

namespace SMW\SQLStore;

use SMW\Store\CacheableResultCollector;

use SMW\InvalidPropertyException;
use SMW\SimpleDictionary;
use SMW\DIProperty;
use SMW\Profiler;
use SMW\Settings;
use SMW\Store;

use SMWDIError;

use DatabaseBase;
use Message;
/**
 * Collects wanted properties from a store entity
 *
 * @ingroup SQLStore
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Nischay Nahata
 */
class WantedPropertiesCollector extends CacheableResultCollector {

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

		// Wanted Properties must have the default type
		$this->propertyTable = $this->findPropertyTableByType( $this->settings->get( 'smwgPDefaultType' ), true );

		// anything else would be crazy, but let's fail gracefully even if the whole world is crazy
		if ( $this->propertyTable->isFixedPropertyTable() ) {
			return array();
		}

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
			'id'      => array( 'smwgWantedPropertiesCache', $this->settings->get( 'smwgPDefaultType' ), $this->requestOptions ),
			'type'    => $this->settings->get( 'smwgCacheType' ),
			'enabled' => $this->settings->get( 'smwgWantedPropertiesCache' ),
			'expiry'  => $this->settings->get( 'smwgWantedPropertiesCacheExpiry' )
		) );
	}

	/**
	 * Returns wanted properties
	 *
	 * @note This function is very resource intensive and needs to be cached on
	 * medium/large wikis.
	 *
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	protected function doQuery() {
		Profiler::In( __METHOD__ );

		$options = $this->store->getSQLOptions( $this->requestOptions, 'title' );
		$options['ORDER BY'] = 'count DESC';

		// TODO: this is not how JOINS should be specified in the select function
		$res = $this->dbConnection->select(
			$this->dbConnection->tableName( $this->propertyTable->getName() ) . ' INNER JOIN ' .
				$this->dbConnection->tableName( $this->store->getObjectIds()->getIdTable() ) . ' ON p_id=smw_id LEFT JOIN ' .
				$this->dbConnection->tableName( 'page' ) . ' ON (page_namespace=' .
				$this->dbConnection->addQuotes( SMW_NS_PROPERTY ) . ' AND page_title=smw_title)',
			'smw_title, COUNT(*) as count',
			'smw_id > 50 AND page_id IS NULL GROUP BY smw_title',
			__METHOD__,
			$options
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

			$result[] = array( $property, $row->count );
		}

		Profiler::Out( __METHOD__ );
		return $result;
	}

}
