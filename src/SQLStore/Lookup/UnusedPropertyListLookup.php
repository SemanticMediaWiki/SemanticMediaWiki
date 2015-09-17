<?php

namespace SMW\SQLStore\Lookup;

use SMW\InvalidPropertyException;
use SMW\Store\PropertyStatisticsStore;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\DIProperty;
use SMW\Store;
use SMWDIError as DIError;
use SMWRequestOptions as RequestOptions;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 * @author Nischay Nahata
 */
class UnusedPropertyListLookup implements ListLookup {

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
		return __METHOD__ . '#' . json_encode( (array)$this->requestOptions );
	}

	private function selectPropertiesFromTable() {

		// the query needs to do the filtering of internal properties, else LIMIT is wrong
		$options = array( 'ORDER BY' => 'smw_sortkey' );

		if ( $this->requestOptions->limit > 0 ) {
			$options['LIMIT'] = $this->requestOptions->limit;
			$options['OFFSET'] = max( $this->requestOptions->offset, 0 );
		}

		$conditions = array(
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => ''
		);

		$conditions['usage_count'] = 0;

		$idTable = $this->store->getObjectIds()->getIdTable();

		$res = $this->store->getConnection( 'mw.db' )->select(
			array( $idTable ,$this->propertyStatisticsStore->getStatisticsTable() ),
			array( 'smw_title', 'usage_count' ),
			$conditions,
			__METHOD__,
			$options,
			array( $idTable => array( 'INNER JOIN', array( 'smw_id=p_id' ) ) )
		);

		return $res;
	}

	private function buildPropertyList( $res ) {

		$result = array();

		foreach ( $res as $row ) {
			$result[] = $this->addPropertyFor( $row->smw_title );
		}

		return $result;
	}

	private function addPropertyFor( $title ) {

		try {
			$property = new DIProperty( $title );
		} catch ( InvalidPropertyException $e ) {
			$property = new DIError( new \Message( 'smw_noproperty', array( $title ) ) );
		}

		return $property;
	}

}
