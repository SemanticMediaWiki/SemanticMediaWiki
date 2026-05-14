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

		$this->applyCursorIfRequested( $query, $sortKeys['keys'] );

		return $query;
	}

	/**
	 * Decode the optional `cursor=<token>` user parameter and apply it
	 * to the query, enforcing the Phase 3a contract:
	 *
	 *   - Default sort (`sortKeys` has only the empty-string key or is
	 *     empty): always allowed.
	 *   - Single-property sort (`sort=Foo`, one non-empty key): allowed
	 *     when the cursor's `sort_prop` field matches the request's
	 *     sort key, or when the cursor has no `sort_prop` (bootstrap /
	 *     empty payload for the first cursor-mode page).
	 *   - Multi-property sort (`sort=A,B`): rejected (Phase 3b).
	 *   - Custom sort + cursor with mismatching `sort_prop`: rejected.
	 *     The cursor was minted for a different sort, applying it here
	 *     would seek to a position that has no meaning in the new
	 *     ordering.
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

		if ( count( $customSortKeys ) > 1 ) {
			$query->addErrors( [
				'Cursor pagination (`cursor=`) is not supported with multi-property `sort=` in this release. Reduce to a single sort property, or use `offset=` instead.'
			] );
			return;
		}

		// `order=random` is fundamentally incompatible with keyset
		// pagination: there's no stable anchor to seek past. Phase 3b
		// adds `order=desc` support; `random` stays rejected.
		$requestSortKey = $customSortKeys[0] ?? '';
		$requestSortOrder = $this->resolveRequestSortOrder( $sortKeys, $requestSortKey );
		if ( $requestSortOrder === 'RANDOM' ) {
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
		// so a first-page cursor request works for both default- and
		// custom-sort queries.
		$payloadSortProp = $payload['sort_prop'] ?? '';
		if ( $payloadSortProp !== '' && $payloadSortProp !== $requestSortKey ) {
			$query->addErrors( [
				"Cursor was minted for `sort=$payloadSortProp` but the request specifies `sort=$requestSortKey`. The cursor anchor has no meaning under a different sort; re-issue without `cursor=` or align the `sort=` parameter."
			] );
			return;
		}

		// Reject mismatched sort_order, but only when the cursor has
		// an actual anchor (the `sort` field). Bootstrap cursors
		// without an anchor (e.g. `{"v":1}`) are direction-agnostic.
		// Phase 3 spike + 3a cursors carry an anchor but no
		// `sort_order` field; treat their absence as ASC because
		// those cursors were minted under ASC (the only mode
		// available pre-3b).
		if ( isset( $payload['sort'] ) ) {
			$payloadSortOrder = $payload['sort_order'] ?? 'ASC';
			if ( $payloadSortOrder !== $requestSortOrder ) {
				$query->addErrors( [
					"Cursor was minted for `order=$payloadSortOrder` but the request specifies `order=$requestSortOrder`. The cursor anchor seeks in the wrong direction under the new order; re-issue without `cursor=` or align the `order=` parameter."
				] );
				return;
			}
		}

		$query->setCursorAfter( $payload );
	}

	/**
	 * Resolve the canonical sort order for the active sort key. For a
	 * custom-property sort (`sort=Foo`), use that key's order. For
	 * default sort (`''` key only), use the empty key's order. Falls
	 * back to ASC when neither is set.
	 */
	private function resolveRequestSortOrder( array $sortKeys, string $requestSortKey ): string {
		if ( $requestSortKey !== '' && isset( $sortKeys[$requestSortKey] ) ) {
			return $sortKeys[$requestSortKey];
		}
		return $sortKeys[''] ?? 'ASC';
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
	 * @return 'ASC'[]|'DESC'[]|'RANDOM'[]
	 */
	private function normalize_order( array $orderParameters ): array {
		$orders = [];

		foreach ( $orderParameters as $key => $order ) {
			$order = strtolower( trim( $order ) );
			if ( ( $order == 'descending' ) || ( $order == 'reverse' ) || ( $order == 'desc' ) ) {
				$orders[$key] = 'DESC';
			} elseif ( ( $order == 'random' ) || ( $order == 'rand' ) ) {
				$orders[$key] = 'RANDOM';
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
