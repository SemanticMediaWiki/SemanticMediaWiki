<?php

namespace SMW\Query\Processor;

use SMW\DataValueFactory;
use SMW\DataValues\PropertyValue;
use SMW\Localizer\Localizer;
use SMW\Query\Query;
use SMW\Query\QueryContext;
use SMW\QueryFactory;
use SMW\SQLStore\QueryEngine\CursorEncoder;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class QueryCreator implements QueryContext {

	private array $params = [];

	/**
	 * @see smwgQFeatures
	 * @var int
	 */
	private $queryFeatures = 0;

	/**
	 * @see smwgQConceptFeatures
	 * @var int
	 */
	private $conceptFeatures = 0;

	/**
	 * @since 2.5
	 */
	public function __construct(
		private readonly QueryFactory $queryFactory,
		private $defaultNamespaces = null,
		private $defaultLimit = 50,
	) {
	}

	/**
	 * @since 2.5
	 *
	 * @param int $queryFeatures
	 */
	public function setQFeatures( $queryFeatures ): void {
		$this->queryFeatures = $queryFeatures;
	}

	/**
	 * @since 2.5
	 *
	 * @param int $conceptFeatures
	 */
	public function setQConceptFeatures( $conceptFeatures ): void {
		$this->conceptFeatures = $conceptFeatures;
	}

	/**
	 * Parse a query string given in SMW's query language to create an Query.
	 * Parameters are given as key-value-pairs in the given array. The parameter
	 * $context defines in what context the query is used, which affects certaim
	 * general settings.
	 *
	 * @since 2.5
	 *
	 * @param string $queryString
	 * @param array $params
	 *
	 * @return Query
	 */
	public function create( $queryString, array $params = [] ) {
		$this->params = $params;
		$context = $this->getParam( 'context', self::INLINE_QUERY );

		$queryParser = $this->queryFactory->newQueryParser(
			$context == self::CONCEPT_DESC ? $this->conceptFeatures : $this->queryFeatures
		);

		$contextPage = $this->getParam( 'contextPage', null );
		$queryMode = $this->getParam( 'queryMode', self::MODE_INSTANCES );

		$queryParser->setContextPage( $contextPage );
		$queryParser->setDefaultNamespaces( $this->defaultNamespaces );

		$query = $this->queryFactory->newQuery(
			$queryParser->getQueryDescription( $queryString ),
			$context
		);

		$query->setQueryToken( $queryParser->getQueryToken() );
		$query->setQueryString( $queryString );
		$query->setContextPage( $contextPage );
		$query->setQueryMode( $queryMode );

		$query->setExtraPrintouts(
			$this->getParam( 'extraPrintouts', [] )
		);

		$query->setMainLabel(
			$this->getParam( 'mainLabel', '' )
		);

		$query->setQuerySource(
			$this->getParam( 'source', null )
		);

		$query->setOption(
			'self.reference',
			$queryParser->containsSelfReference()
		);

		// keep parsing or other errors for later output
		$query->addErrors(
			$queryParser->getErrors()
		);

		// set sortkeys, limit, and offset
		$query->setOffset(
			max( 0, (int)trim( $this->getParam( 'offset', 0 ) ) + 0 )
		);

		$query->setLimit(
			max( 0, (int)trim( $this->getParam( 'limit', $this->defaultLimit ) ) + 0 ),
			$queryMode != self::MODE_COUNT
		);

		$sortKeys = $this->getSortKeys(
			$this->getParam( 'sort', [] ),
			$this->getParam( 'order', [] ),
			$this->getParam( 'defaultSort', 'ASC' )
		);

		$query->addErrors(
			$sortKeys['errors']
		);

		$query->setSortKeys(
			$sortKeys['keys']
		);

		if ( $this->isUnsortedRequested() ) {
			$query->setOption( Query::SORT_DISABLED, true );
		}

		$this->applyCursorIfRequested( $query, $sortKeys['keys'] );

		return $query;
	}

	/**
	 * Decode the optional `cursor=<token>` user parameter and apply it
	 * to the query, enforcing the Phase 3b-iii contract:
	 *
	 *   - Default sort (`sortKeys` has only the empty-string key or is
	 *     empty): always allowed.
	 *   - Single-property sort (`sort=Foo`, one non-empty key): allowed
	 *     when the cursor's `sort_prop` field matches the request's
	 *     sort key, or when the cursor has no `sort_prop` (bootstrap /
	 *     empty payload for the first cursor-mode page).
	 *   - Multi-property sort (`sort=A,B`): allowed, including mixed
	 *     directions per level (`order=asc,desc`).
	 *   - Custom sort + cursor with mismatching `sort_prop`: rejected.
	 *     The cursor was minted for a different sort, applying it here
	 *     would seek to a position that has no meaning in the new
	 *     ordering.
	 *   - `order=random`: rejected. No stable anchor to seek past.
	 *
	 * Malformed cursor tokens (any decode failure) are surfaced as an
	 * error rather than silently ignored, to avoid the "expected
	 * next-page got first-page" surprise for bot clients.
	 *
	 * @param Query $query
	 * @param array $sortKeys As returned by `getSortKeys()['keys']`.
	 *   An empty-string key (`'' => 'ASC'`) is "sort by page"; a
	 *   non-empty-string key is a property sort.
	 */
	private function applyCursorIfRequested( Query $query, array $sortKeys ): void {
		$token = (string)$this->getParam( 'cursor', '' );
		if ( $token === '' ) {
			return;
		}

		// `order=none` yields an unsorted query; keyset pagination needs a
		// stable order to seek past, so the combination is rejected.
		if ( $this->isUnsortedRequested() ) {
			$query->addErrors( [
				'Cursor pagination (`cursor=`) is not supported with `order=none`. Remove `cursor=` or choose an explicit sort.'
			] );
			return;
		}

		// `array_values` re-indexes so `[0]` is the first custom-sort
		// property regardless of any preceding empty-string key (the
		// `sort=,SomeProperty` page-pivoted form produces a `sortKeys`
		// array whose `array_keys` is `['', 'SomeProperty']` — without
		// re-indexing the filter returns `[1 => 'SomeProperty']` and
		// `[0]` resolves to null, false-rejecting cursors on page 2).
		$customSortKeys = array_values( array_filter(
			array_keys( $sortKeys ),
			static fn ( $key ) => $key !== ''
		) );

		// Phase 3b-iii: mixed per-level directions are supported.
		// `order=random` remains incompatible (no stable anchor to seek
		// past), and a single RANDOM among other directions still
		// rejects the whole request.
		$requestSortOrders = $this->resolveRequestSortOrders( $sortKeys, $customSortKeys );
		if ( in_array( 'RANDOM', $requestSortOrders, true ) ) {
			$query->addErrors( [
				'Cursor pagination (`cursor=`) is not supported with `order=random`. Reverse the request to use `order=asc` or `order=desc`.'
			] );
			return;
		}

		if ( $query->getQueryMode() === self::MODE_COUNT ) {
			$query->addErrors( [
				'Cursor pagination (`cursor=`) is not supported with `format=count`. Remove `cursor=` or switch to a non-count format.'
			] );
			return;
		}

		$payload = CursorEncoder::decode( $token );
		if ( $payload === null ) {
			$query->addErrors( [
				'Malformed `cursor=` token (rejected as unrecognised or from a newer schema version).'
			] );
			return;
		}

		// Reject mismatched sort_prop. The empty-payload bootstrap
		// cursor has no `sort_prop` and therefore matches any sort,
		// so a first-page cursor request works for default-, single-,
		// and multi-sort queries.
		//
		// Single-sort cursors carry `sort_prop` as a scalar string;
		// multi-sort cursors (Phase 3b-ii) as an array of property
		// keys. Both shapes normalise to an array here for comparison.
		$payloadSortProp = $payload['sort_prop'] ?? null;
		if ( $payloadSortProp !== null ) {
			$payloadProps = is_array( $payloadSortProp ) ? $payloadSortProp : [ $payloadSortProp ];
			if ( $payloadProps !== $customSortKeys ) {
				$payloadDesc = is_array( $payloadSortProp )
					? implode( ',', $payloadSortProp )
					: $payloadSortProp;
				$requestDesc = implode( ',', $customSortKeys );
				$query->addErrors( [
					"Cursor was minted for `sort=$payloadDesc` but the request specifies `sort=$requestDesc`. The cursor anchor has no meaning under a different sort; re-issue without `cursor=` or align the `sort=` parameter."
				] );
				return;
			}
		}

		// Reject mismatched sort_order, but only when the cursor has
		// an actual anchor (the `sort` field). Bootstrap cursors
		// without an anchor (e.g. `{"v":1}`) are direction-agnostic.
		// Phase 3 spike + 3a cursors carry an anchor but no
		// `sort_order` field; treat their absence as ASC because
		// those cursors were minted under ASC (the only mode
		// available pre-3b).
		//
		// Shapes that may appear in `sort_order`:
		//   - missing: pre-3b cursor, treat as ASC for all levels
		//   - string (e.g. "DESC"): uniform across all levels
		//   - array (e.g. ["ASC","DESC"]): one direction per level
		// Both sides are normalised to per-level arrays for the
		// comparison so a uniform 3b-i/3b-ii cursor round-trips
		// against a mixed-direction request would correctly mismatch.
		if ( isset( $payload['sort'] ) ) {
			$payloadSortOrders = $this->normalisePayloadSortOrders(
				$payload['sort_order'] ?? null,
				count( $requestSortOrders )
			);
			if ( $payloadSortOrders !== $requestSortOrders ) {
				$payloadDesc = implode( ',', $payloadSortOrders );
				$requestDesc = implode( ',', $requestSortOrders );
				$query->addErrors( [
					"Cursor was minted for `order=$payloadDesc` but the request specifies `order=$requestDesc`. The cursor anchor seeks in the wrong direction under the new order; re-issue without `cursor=` or align the `order=` parameter."
				] );
				return;
			}
		}

		$query->setCursorAfter( $payload );
	}

	/**
	 * Resolve the per-level sort orders for the active sort keys.
	 * Returns an array with one direction per level, in declaration
	 * order. For default sort (no custom sort keys), the returned
	 * array has one element: the empty-key's order. Falls back to
	 * ASC when an entry is unset.
	 *
	 * Each element is one of "ASC", "DESC", or "RANDOM" (values are
	 * normalised upstream by `normalize_order()`).
	 *
	 * @param array $sortKeys
	 * @param array $customSortKeys Custom (non-empty) sort key names
	 *   in declaration order, re-indexed from 0.
	 *
	 * @return string[]
	 */
	private function resolveRequestSortOrders( array $sortKeys, array $customSortKeys ): array {
		if ( $customSortKeys === [] ) {
			return [ $sortKeys[''] ?? 'ASC' ];
		}
		$orders = [];
		foreach ( $customSortKeys as $key ) {
			$orders[] = $sortKeys[$key] ?? 'ASC';
		}
		return $orders;
	}

	/**
	 * Normalise the cursor payload's `sort_order` field to a
	 * per-level array. Handles three shapes:
	 *
	 *   - null (pre-3b cursor, no field): ASC for every level
	 *   - string ("DESC"): uniform direction across every level
	 *   - array (["ASC","DESC"]): per-level, padded with ASC if
	 *     shorter than the request's level count (defensive)
	 *
	 * Each value is validated against the allowed direction set and
	 * coerced to "ASC" if it falls outside; this keeps any
	 * user-controlled cursor payload out of downstream error messages
	 * and out of the SQL builder's per-level operator selection.
	 *
	 * @param string|array|null $payloadSortOrder
	 * @param int $levelCount Active sort level count from the request
	 *
	 * @return string[]
	 */
	private function normalisePayloadSortOrders( string|array|null $payloadSortOrder, int $levelCount ): array {
		if ( $payloadSortOrder === null ) {
			return array_fill( 0, $levelCount, 'ASC' );
		}
		if ( is_string( $payloadSortOrder ) ) {
			return array_fill( 0, $levelCount, $this->coerceSortDirection( $payloadSortOrder ) );
		}
		// Already an array; pad with ASC to match the request length.
		// A length mismatch will fail the mismatch check downstream
		// (the values won't line up), surfacing the malformation as
		// an `order=` mismatch error.
		$normalised = [];
		foreach ( array_values( $payloadSortOrder ) as $value ) {
			$normalised[] = $this->coerceSortDirection( $value );
		}
		while ( count( $normalised ) < $levelCount ) {
			$normalised[] = 'ASC';
		}
		return $normalised;
	}

	/**
	 * Map an untrusted sort direction string from a cursor payload
	 * onto the small allowed set ("ASC", "DESC", "RANDOM"). Anything
	 * else collapses to "ASC" so attacker-controlled payload values
	 * cannot reach error messages or the predicate builder.
	 */
	private function coerceSortDirection( mixed $value ): string {
		if ( !is_string( $value ) ) {
			return 'ASC';
		}
		$upper = strtoupper( $value );
		return in_array( $upper, [ 'ASC', 'DESC', 'RANDOM' ], true ) ? $upper : 'ASC';
	}

	/**
	 * @since 2.5
	 *
	 * @param array $sortParameters
	 * @param array $orderParameters
	 * @param string $defaultSort
	 *
	 * @return array ( keys => array(), errors => array() )
	 */
	private function getSortKeys( array $sortParameters, array $orderParameters, $defaultSort ): array {
		$sortKeys = [];
		$sortErros = [];

		$orders = $this->normalize_order( $orderParameters );

		// `order=none` disables sorting entirely. An empty sort-key map
		// makes the query engine emit no ORDER BY clause, so the result
		// LIMIT can short-circuit instead of sorting the whole set.
		if ( in_array( 'NONE', $orders, true ) ) {
			return [ 'keys' => [], 'errors' => [] ];
		}

		foreach ( $sortParameters as $sort ) {
			$sortKey = false;

			// An empty string indicates we mean the page, such as element 0 on the next line.
			// sort=,Some property
			if ( trim( $sort ) === '' ) {
				$sortKey = '';
			} else {

				$propertyValue = DataValueFactory::getInstance()->newDataValueByType( PropertyValue::TYPE_ID );
				$propertyValue->setOption( PropertyValue::OPT_QUERY_CONTEXT, true );

				$propertyValue->setUserValue(
					$this->normalize_sort( trim( $sort ) )
				);

				if ( $propertyValue->isValid() ) {
					$sortKey = $propertyValue->getDataItem()->getKey();
				} else {
					$sortErros = array_merge( $sortErros, $propertyValue->getErrors() );
				}
			}

			if ( $sortKey !== false ) {
				$order = $orders === [] ? $defaultSort : array_shift( $orders );
				$sortKeys[$sortKey] = $order;
			}
		}

		// If more sort arguments are provided then properties, assume the first one is for the page.
		// TODO: we might want to add errors if there is more then one.
		if ( !array_key_exists( '', $sortKeys ) && $orders !== [] ) {
			$sortKeys[''] = array_shift( $orders );
		}

		return [ 'keys' => $sortKeys, 'errors' => $sortErros ];
	}

	/**
	 * Whether the request asked for an unsorted query via `order=none`.
	 */
	private function isUnsortedRequested(): bool {
		return in_array( 'NONE', $this->normalize_order( $this->getParam( 'order', [] ) ), true );
	}

	/**
	 * @return 'ASC'[]|'DESC'[]|'RANDOM'[]|'NONE'[]
	 */
	private function normalize_order( array $orderParameters ): array {
		$orders = [];

		foreach ( $orderParameters as $key => $order ) {
			$order = strtolower( trim( $order ) );
			if ( ( $order == 'descending' ) || ( $order == 'reverse' ) || ( $order == 'desc' ) ) {
				$orders[$key] = 'DESC';
			} elseif ( ( $order == 'random' ) || ( $order == 'rand' ) ) {
				$orders[$key] = 'RANDOM';
			} elseif ( $order == 'none' ) {
				$orders[$key] = 'NONE';
			} else {
				$orders[$key] = 'ASC';
			}
		}

		return $orders;
	}

	private function normalize_sort( string $sort ): string {
		return Localizer::getInstance()->getNsText( NS_CATEGORY ) == mb_convert_case( $sort, MB_CASE_TITLE ) ? '_INST' : $sort;
	}

	private function getParam( string $key, $default ) {
		return $this->params[$key] ?? $default;
	}

}
