<?php

namespace SMW\SQLStore\Lookup;

use MediaWiki\Message\Message;
use RuntimeException;
use SMW\DataItems\Error;
use SMW\DataItems\Property;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\Lookup\ListLookup;
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

	use KeysetPaginationTrait;

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
		$db = $this->store->getConnection( 'mw.db' );

		$queryBuilder = $db->newSelectQueryBuilder()
			->from( SQLStore::PROPERTY_STATISTICS_TABLE )
			->fields( [ 'smw_id', 'smw_title', 'smw_sort' ] )
			->join( SQLStore::ID_TABLE, null, 'smw_id=p_id' )
			->where( [
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_iw' => '',
				'smw_subobject' => '',
				'usage_count' => 0,
			] )
			->andWhere( "smw_title NOT LIKE '\_%'" ) // #2182, exclude predefined properties
			->andWhere( 'smw_id > ' . SQLStore::FIXED_PROPERTY_ID_UPPERBOUND )
			->andWhere( 'smw_proptable_hash IS NOT NULL' )
			->caller( __METHOD__ );

		if ( $this->requestOptions->getStringConditions() ) {
			$queryBuilder->andWhere(
				$this->store->getSQLConditions( $this->requestOptions, '', 'smw_sortkey', false )
			);
		}

		// Fetch one extra row to detect whether more results exist
		if ( $this->requestOptions->limit > 0 ) {
			$queryBuilder->limit( $this->requestOptions->limit + 1 );
		}

		$this->applyCursorPagination( $queryBuilder, $db );

		return $queryBuilder->fetchResultSet();
	}

	/**
	 * @return Property[]|Error[]
	 */
	private function buildPropertyList( $res ): array {
		$result = [];
		$rows = [];

		foreach ( $res as $row ) {
			$rows[] = $row;
		}

		$isReversed = $this->requestOptions->getCursorBefore() !== null;
		$limit = $this->requestOptions->limit;

		// Trim the extra lookahead row used to detect more results
		if ( $limit > 0 && count( $rows ) > $limit ) {
			array_pop( $rows );
			$this->requestOptions->setCursorHasMore( true );
		}

		if ( $isReversed ) {
			$rows = array_reverse( $rows );
		}

		foreach ( $rows as $row ) {
			try {
				$property = new Property( $row->smw_title );
			} catch ( PropertyLabelNotResolvedException ) {
				$property = new Error( new Message( 'smw_noproperty', [ $row->smw_title ] ) );
			}

			$property->id = $row->smw_id ?? -1;
			$result[] = $property;
		}

		if ( $rows !== [] ) {
			$this->requestOptions->setFirstCursor( (int)$rows[0]->smw_id );
			$this->requestOptions->setLastCursor( (int)$rows[count( $rows ) - 1]->smw_id );
		}

		return $result;
	}

}
