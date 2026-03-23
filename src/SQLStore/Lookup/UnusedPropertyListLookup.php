<?php

namespace SMW\SQLStore\Lookup;

use MediaWiki\Message\Message;
use RuntimeException;
use SMW\DataItems\DataItem;
use SMW\DataItems\Error;
use SMW\DataItems\Property;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\RequestOptions;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 * @author Nischay Nahata
 */
class UnusedPropertyListLookup implements ListLookup {

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly Store $store,
		private readonly PropertyStatisticsStore $propertyStatisticsStore,
		private readonly ?RequestOptions $requestOptions = null,
	) {
	}

	/**
	 * @since 2.2
	 *
	 * @return Property[]
	 * @throws RuntimeException
	 */
	public function fetchList(): array {
		if ( $this->requestOptions === null ) {
			throw new RuntimeException( "Missing requestOptions" );
		}

		return $this->buildPropertyList( $this->selectPropertiesFromTable() );
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function isFromCache(): bool {
		return false;
	}

	/**
	 * @since 2.2
	 *
	 * @return false|string
	 */
	public function getTimestamp() {
		return wfTimestamp( TS_UNIX );
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getHash(): string {
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
			[ SQLStore::ID_TABLE => [ 'INNER JOIN', [ 'smw_id=p_id' ] ] ]
		);

		return $res;
	}

	/**
	 * @return mixed[]
	 */
	private function buildPropertyList( $res ): array {
		$result = [];

		foreach ( $res as $row ) {
			$result[] = $this->addPropertyFor( $row->smw_title );
		}

		return $result;
	}

	private function addPropertyFor( $title ): DataItem {
		try {
			$property = new Property( $title );
		} catch ( PropertyLabelNotResolvedException ) {
			$property = new Error( new Message( 'smw_noproperty', [ $title ] ) );
		}

		return $property;
	}

}
