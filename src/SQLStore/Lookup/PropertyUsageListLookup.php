<?php

namespace SMW\SQLStore\Lookup;

use MediaWiki\Message\Message;
use RuntimeException;
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
 */
class PropertyUsageListLookup implements ListLookup {

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
	 * @return Property[]|Error[]|int[]
	 * @throws RuntimeException
	 */
	public function fetchList(): array {
		if ( $this->requestOptions === null ) {
			throw new RuntimeException( "Missing requestOptions" );
		}

		return $this->getPropertyList( $this->doQueryPropertyTable() );
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
	 * @return string|false
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

	private function doQueryPropertyTable() {
		$db = $this->store->getConnection( 'mw.db' );
		$search_field = 'smw_sortkey';

		if ( $this->requestOptions->getOption( RequestOptions::SEARCH_FIELD ) ) {
			$search_field = $this->requestOptions->getOption( RequestOptions::SEARCH_FIELD );
		}

		$queryBuilder = $db->newSelectQueryBuilder()
			->from( SQLStore::ID_TABLE )
			->fields( [ 'smw_id', 'smw_title', 'smw_sort', 'usage_count' ] )
			->join( SQLStore::PROPERTY_STATISTICS_TABLE, null, 'smw_id=p_id' )
			->where( [
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_iw' => '',
				'smw_subobject' => '',
			] )
			->caller( __METHOD__ );

		if ( $this->requestOptions->getStringConditions() ) {
			$queryBuilder->andWhere(
				$this->store->getSQLConditions( $this->requestOptions, '', $search_field, false )
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
	 * @return Property[]|Error[]|int[]
	 */
	private function getPropertyList( $res ): array {
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
				$property = new Property( str_replace( ' ', '_', $row->smw_title ) );
			} catch ( PropertyLabelNotResolvedException ) {
				$property = new Error( new Message( 'smw_noproperty', [ $row->smw_title ] ) );
			}

			$property->id = $row->smw_id ?? -1;
			$result[] = [ $property, (int)$row->usage_count ];
		}

		if ( $rows !== [] ) {
			$this->requestOptions->setFirstCursor( (int)$rows[0]->smw_id );
			$this->requestOptions->setLastCursor( (int)$rows[count( $rows ) - 1]->smw_id );
		}

		return $result;
	}

}
