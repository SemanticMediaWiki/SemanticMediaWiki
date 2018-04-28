<?php

namespace SMW\SQLStore\Lookup;

use RuntimeException;
use SMW\DIProperty;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\SQLStore\PropertyStatisticsStore;
use SMWDIError as DIError;
use SMW\Message;
use SMWRequestOptions as RequestOptions;

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
	public function lookup() {

		if ( $this->requestOptions === null ) {
			throw new RuntimeException( "Missing requestOptions" );
		}

		return $this->makePropertyList( $this->fetchFromTable() );
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

	private function fetchFromTable() {

		$options = [];
		$conditions = [
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		if ( $this->requestOptions->limit > 0 ) {
			$options['LIMIT'] = min( $this->requestOptions->limit, 500 );
			$options['OFFSET'] = max( $this->requestOptions->offset, 0 );
		}

		// Avoid ORDER BY when matching a string
		if ( $this->requestOptions->getStringConditions() ) {
			$conditions[] = $this->store->getSQLConditions( $this->requestOptions, '', 'smw_sortkey', false );
		} else {
			// Query needs to do the filtering of internal properties, else LIMIT is wrong
			$options['ORDER BY'] = 'smw_sort';
		}

		$tableLookup = $this->store->service( 'table.lookup' );
		$tableLookup->setOrigin( __METHOD__ );

		$res = $tableLookup->match(
			[ SQLStore::ID_TABLE, SQLStore::PROPERTY_STATISTICS_TABLE ],
			[ 'smw_id', 'smw_title', 'usage_count' ],
			$conditions,
			$options,
			[ SQLStore::ID_TABLE => [ 'INNER JOIN', [ 'smw_id=p_id' ] ] ]
		);

		return $res;
	}

	private function makePropertyList( $res ) {

		$result = array();

		foreach ( $res as $row ) {

			try {
				$property = new DIProperty( str_replace( ' ', '_', $row->smw_title ) );
			} catch ( PropertyLabelNotResolvedException $e ) {
				$property = new DIError( Message::encode( [ 'smw_noproperty', $row->smw_title ] ) );
			}

			$property->id = isset( $row->smw_id ) ? $row->smw_id : -1;
			$result[] = array( $property, (int)$row->usage_count );
		}

		return $result;
	}

}
