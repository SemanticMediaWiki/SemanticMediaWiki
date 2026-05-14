<?php

namespace SMW\MediaWiki\Api\Browse;

use Exception;
use SMW\DataItems\Property;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\KeysetPaginationTrait;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\StringCondition;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ListLookup extends Lookup {

	use KeysetPaginationTrait;

	const VERSION = 1;

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly ListAugmentor $listAugmentor,
	) {
	}

	/**
	 * @since 3.0
	 */
	public function getVersion(): string {
		return 'ListLookup:' . self::VERSION;
	}

	/**
	 * @since 3.0
	 */
	public function lookup( array $parameters ): array {
		$requestOptions = $this->newRequestOptions(
			$parameters
		);

		$limit = $requestOptions->getLimit();
		$res = [];
		$continueOffset = 0;
		$continueCursor = 0;

		// Increase by one to look ahead
		$requestOptions->setLimit( $limit + 1 );
		$ns = $parameters['ns'] ?? '';

		switch ( $ns ) {
			case NS_CATEGORY:
				$type = 'category';
				break;
			case SMW_NS_PROPERTY:
				$type = 'property';
				break;
			case SMW_NS_CONCEPT:
				$type = 'concept';
				break;
			default:
				$type = 'unlisted';
				break;
		}

		if ( isset( $parameters['search'] ) ) {
			[ $res, $continueOffset, $continueCursor ] = $this->fetchFromTable( $ns, $requestOptions, $parameters );
		}

		// Changing this output format requires to set a new version. The
		// `query-continue-cursor` field is additive: clients that follow the
		// legacy `query-continue-offset` keep working unchanged. Clients that
		// opt into cursor mode by passing `cursor` in the request payload
		// follow `query-continue-cursor` instead (an `smw_id` keyset anchor).
		$res = [
			'query' => $res,
			'query-continue-offset' => $continueOffset,
			'query-continue-cursor' => $continueCursor,
			'version' => self::VERSION,
			'meta' => [
				'type'  => $type,
				'limit' => $limit,
				'count' => count( $res )
			]
		];

		$this->listAugmentor->augment(
			$res,
			$parameters
		);

		return $res;
	}

	private function newRequestOptions( array $parameters ): RequestOptions {
		$limit = 50;
		$offset = 0;
		$search = '';

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->setLimit( $limit );
		$requestOptions->setOffset( $offset );

		// Cursor mode is opt-in via the `cursor` request param. Presence of
		// the key (any value, including 0) selects cursor mode; a value > 0
		// is interpreted as `cursorAfter` (the previous response's
		// `query-continue-cursor`). The absence of the key keeps the legacy
		// OFFSET path for backward compatibility with API clients that
		// follow `query-continue-offset`.
		if ( array_key_exists( 'cursor', $parameters ) ) {
			$requestOptions->setOption( RequestOptions::CURSOR_MODE, true );
			$cursor = (int)$parameters['cursor'];
			if ( $cursor > 0 ) {
				$requestOptions->setCursorAfter( $cursor );
			}
		}

		if ( isset( $parameters['sort'] ) && strtolower( $parameters['sort'] ) !== 'asc' ) {
			$requestOptions->ascending = false;
		}

		if ( isset( $parameters['search'] ) && ( $parameters['search'] === '*' || $parameters['search'] === '?' ) ) {
			// wildcard
		} elseif ( isset( $parameters['search'] ) && isset( $parameters['strict'] ) ) {
			$search = str_replace( "*", "", (string)$parameters['search'] );

			if ( $search !== '' && $search[0] !== '_' ) {
				$search = str_replace( "_", " ", $search );
			}

			$requestOptions->addStringCondition(
				$search,
				StringCondition::COND_EQ
			);

		} elseif ( isset( $parameters['search'] ) ) {
			$search = str_replace( "*", "", (string)$parameters['search'] );

			if ( $search !== '' && $search[0] !== '_' ) {
				$search = str_replace( "_", " ", $search );
			}

			$requestOptions->addStringCondition(
				$search,
				StringCondition::STRCOND_MID
			);

			// Disjunctive condition to allow for auto searches to match foaf OR Foaf
			$requestOptions->addStringCondition(
				ucfirst( $search ),
				StringCondition::STRCOND_MID,
				true
			);

			// Allow something like FOO to match the search string `foo`
			$requestOptions->addStringCondition(
				strtoupper( $search ),
				StringCondition::STRCOND_MID,
				true
			);

			$requestOptions->addStringCondition(
				strtolower( $search ),
				StringCondition::STRCOND_MID,
				true
			);
		}

		return $requestOptions;
	}

	private function fetchFromTable( $ns, RequestOptions $requestOptions, array $parameters ): array {
		$limit = $requestOptions->getLimit() - 1;
		$list = [];
		$options = [];
		$cursorMode = (bool)$requestOptions->getOption( RequestOptions::CURSOR_MODE );

		$fields = [
			'smw_id',
			'smw_title'
		];

		// The query needs to do the filtering for internal properties, else
		// LIMIT is wrong
		if ( $cursorMode ) {
			// `smw_sort` is required for the cursor predicate's tiebreak join
			// and is the column the keyset trait orders by.
			$fields[] = 'smw_sort';
		} elseif ( isset( $parameters['sort'] ) ) {
			$options = $this->store->getSQLOptions( $requestOptions, 'smw_sort' );
			$fields[] = 'smw_sort';
		} elseif ( isset( $parameters['offset'] ) ) {
			$options = $this->store->getSQLOptions( $requestOptions, '' );
		}

		$conditions = [
			'smw_namespace' => $ns,
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		$cond = $this->store->getSQLConditions( $requestOptions, '', 'smw_sortkey', false );
		if ( $cond !== '' ) {
			$conditions[] = $cond;
			$fields[] = 'smw_sortkey';
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$queryBuilder = $connection->newSelectQueryBuilder()
			->select( $fields )
			->from( SQLStore::ID_TABLE )
			->where( $conditions )
			->caller( __METHOD__ );

		if ( $cursorMode ) {
			// The trait emits the keyset WHERE predicate and ORDER BY; the
			// LIMIT is applied here so the lookahead row (`limit + 1`) is
			// preserved across both paths.
			$queryBuilder->limit( $requestOptions->getLimit() );
			$this->applyCursorPagination( $queryBuilder, $connection, $requestOptions );
		} else {
			$queryBuilder->options( $options );
		}

		$res = $queryBuilder->fetchResultSet();

		$count = 0;
		$continueOffset = 0;
		$continueCursor = 0;
		$lastCursorId = 0;

		foreach ( $res as $row ) {

			$key = $row->smw_title;
			$count++;

			if ( $count > $limit ) {
				if ( $cursorMode ) {
					$continueCursor = $lastCursorId;
				} else {
					$continueOffset = $requestOptions->getOffset() + $limit;
				}
				break;
			}

			if ( $ns === SMW_NS_PROPERTY ) {
				try {
					$label = Property::newFromUserLabel( $row->smw_title )->getLabel();
				} catch ( Exception ) {
					continue;
				}

			} else {
				$label = str_replace( '_', ' ', $row->smw_title );
			}

			$list[$key] = [
				 // Only keep the ID as internal field which is
				 // removed by the Augmentor
				'id'    => $row->smw_id,
				'label' => $label,
				'key'   => $key
			];

			$lastCursorId = (int)$row->smw_id;
		}

		return [ $list, $continueOffset, $continueCursor ];
	}

}
