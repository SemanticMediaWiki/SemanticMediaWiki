<?php

namespace SMW\SQLStore\Lookup;

use RuntimeException;
use SMW\DIProperty;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMWDIError as DIError;
use SMWRequestOptions as RequestOptions;

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
	public function isFromCache() {
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
	public function getHash() {
		return __METHOD__ . '#' . ( $this->requestOptions !== null ? $this->requestOptions->getHash() : '' );
	}

	private function selectPropertiesFromTable() {

		// the query needs to do the filtering of internal properties, else LIMIT is wrong
		$options = [ 'ORDER BY' => 'smw_sort' ];

		if ( $this->requestOptions->limit > 0 ) {
			$options['LIMIT'] = $this->requestOptions->limit;
			$options['OFFSET'] = max( $this->requestOptions->offset, 0 );
		}

		$conditions = [
			"smw_title NOT LIKE '\_%'", // #2182, exclude predefined properties
			'smw_id > ' . SQLStore::FIXED_PROPERTY_ID_UPPERBOUND,
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_proptable_hash IS NOT NULL'
		];

		$conditions['usage_count'] = 0;

		if ( $this->requestOptions->getStringConditions() ) {
			$conditions[] = $this->store->getSQLConditions( $this->requestOptions, '', 'smw_sortkey', false );
		}

		$res = $this->store->getConnection( 'mw.db' )->select(
			[ SQLStore::ID_TABLE, SQLStore::PROPERTY_STATISTICS_TABLE ],
			[ 'smw_title', 'usage_count' ],
			$conditions,
			__METHOD__,
			$options,
			[  SQLStore::ID_TABLE => [ 'INNER JOIN', [ 'smw_id=p_id' ] ] ]
		);

		return $res;
	}

	private function buildPropertyList( $res ) {

		$result = [];

		foreach ( $res as $row ) {
			$result[] = $this->addPropertyFor( $row->smw_title );
		}

		return $result;
	}

	private function addPropertyFor( $title ) {

		try {
			$property = new DIProperty( $title );
		} catch ( PropertyLabelNotResolvedException $e ) {
			$property = new DIError( new \Message( 'smw_noproperty', [ $title ] ) );
		}

		return $property;
	}

}
