<?php

namespace SMW\SQLStore\Lookup;

use SMW\Store;
use SMW\Store\PropertyStatisticsStore;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\DIProperty;
use SMWDIError as DIError;
use SMWRequestOptions as RequestOptions;
use SMW\InvalidPropertyException;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class PropertyUsageListLookup implements ListLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertyStatisticsStore
	 */
	private $propertyStatisticsStore;

	/**
	 * @var RequestOptions
	 */
	private $requestOptions;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param PropertyStatisticsStore $propertyStatisticsStore
	 * @param RequestOptions $requestOptions|null
	 */
	public function __construct( Store $store, PropertyStatisticsStore $propertyStatisticsStore, RequestOptions $requestOptions = null ) {
		$this->store = $store;
		$this->propertyStatisticsStore = $propertyStatisticsStore;
		$this->requestOptions = $requestOptions;
	}

	/**
	 * @since 2.2
	 *
	 * @return DIProperty[]
	 * @throws RuntimeException
	 */
	public function fetchList() {

		if ( $this->requestOptions === null ) {
			throw new RuntimeException( "Missing requestOptions" );
		}

		return $this->buildPropertyList( $this->selectPropertiesFromTable() );
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function isCached() {
		return false;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getTimestamp() {
		return wfTimestamp( TS_UNIX );
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getLookupIdentifier() {
		return 'smwgPropertiesCache#' . json_encode( (array)$this->requestOptions );
	}

	private function selectPropertiesFromTable() {

		// the query needs to do the filtering of internal properties, else LIMIT is wrong
		$options = array( 'ORDER BY' => 'smw_sortkey' );

		$conditions = array(
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => '',
		);

		if ( $this->requestOptions->limit > 0 ) {
			$options['LIMIT'] = $this->requestOptions->limit;
			$options['OFFSET'] = max( $this->requestOptions->offset, 0 );
		}

		if ( $this->requestOptions->getStringConditions() ) {
			$conditions[] = $this->store->getSQLConditions( $this->requestOptions, '', 'smw_title', false );
		}

		$res = $this->store->getConnection( 'mw.db' )->select(
			$this->store->getObjectIds()->getIdTable(),
			array(
				'smw_id',
				'smw_title'
			),
			$conditions,
			__METHOD__,
			$options
		);

		return $res;
	}

	private function buildPropertyList( $res ) {

		$result = array();
		$propertyIds = array();

		foreach ( $res as $row ) {
			$propertyIds[] = (int)$row->smw_id;
		}

		$usageCounts = $this->propertyStatisticsStore->getUsageCounts( $propertyIds );

		foreach ( $res as $row ) {
			$result[] = $this->addPropertyRowToList( $row, $usageCounts );
		}

		return $result;
	}

	private function addPropertyRowToList( $row, $usageCounts ) {

		try {
			$property = new DIProperty( $row->smw_title );
		} catch ( InvalidPropertyException $e ) {
			$property = new DIError( new \Message( 'smw_noproperty', array( $row->smw_title ) ) );
		}

		// If there is no key entry in the usageCount table for that
		// particular property it is to be counted with usage 0
		$count = array_key_exists( (int)$row->smw_id, $usageCounts ) ? $usageCounts[(int)$row->smw_id] : 0;
		return array( $property, $count );
	}

}
