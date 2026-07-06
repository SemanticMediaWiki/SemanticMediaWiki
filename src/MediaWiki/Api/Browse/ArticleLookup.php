<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\Localizer\Localizer;
use SMW\MediaWiki\Connection\Database;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleLookup extends Lookup {

	const VERSION = 1;

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly Database $connection,
		private readonly ArticleAugmentor $articleAugmentor,
	) {
	}

	/**
	 * @since 3.0
	 */
	public function getVersion(): string {
		return 'ArticleLookup:' . self::VERSION;
	}

	/**
	 * @since 3.0
	 */
	public function lookup( array $parameters ): array {
		$limit = 50;
		$offset = 0;
		$namespace = null;

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		if ( isset( $parameters['namespace'] ) ) {
			$namespace = $parameters['namespace'];
		}

		$list = [];
		$continueOffset = 0;
		$continueCursor = 0;
		$cursorMode = self::shouldUseCursorMode( $parameters );

		if ( isset( $parameters['search'] ) ) {
			if ( $cursorMode ) {
				$cursor = (int)$parameters['cursor'];
				[ $list, $continueCursor ] = $this->searchByCursor(
					$limit,
					$cursor,
					$parameters['search'],
					$namespace
				);
			} else {
				[ $list, $continueOffset ] = $this->search( $limit, $offset, $parameters['search'], $namespace );
			}
		}

		// Changing this output format requires to set a new version. The
		// `query-continue-cursor` field is byte-additive: it is only emitted
		// when the caller opted into cursor mode (by sending `cursor` in
		// the request payload). Legacy clients that follow
		// `query-continue-offset` see exactly the pre-cursor response shape.
		$res = [
			'query' => $list,
			'query-continue-offset' => $continueOffset,
			'version' => self::VERSION,
			'meta' => [
				'type'  => 'article',
				'limit' => $limit,
				'count' => count( $list )
			]
		];

		if ( $cursorMode ) {
			$res['query-continue-cursor'] = $continueCursor;
		}

		$this->articleAugmentor->augment(
			$res,
			$parameters
		);

		return $res;
	}

	private function search( int $limit, int $offset, $search, $namespace = null ): array {
		$search = $this->getSearchTerm( $search, $namespace );
		$limit += 1;

		$res = $this->connection->newSelectQueryBuilder()
			->select( [ 'page_id', 'page_namespace', 'page_title' ] )
			->from( 'page' )
			->where( [ $this->buildSearchConditions( $search, $namespace ) ] )
			->options( [
				'LIMIT' => $limit,
				'OFFSET' => $offset,
				'ORDER BY' => "page_title,page_namespace"
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$count = 0;
		$continueOffset = 0;
		$list = [];

		foreach ( $res as $row ) {

			$key = $row->page_title;
			$count++;

			if ( $count > ( $limit - 1 ) ) {
				$continueOffset = $offset + $limit;
				break;
			}

			$list[$key . '#' . $row->page_namespace] = $this->rowToListEntry( $row );
		}

		return [ $list, $continueOffset ];
	}

	/**
	 * Cursor-aware sibling of `search()`. Anchored at `page_id` (unique
	 * primary-key column of the `page` table) and walks forward in the
	 * same `(page_title, page_namespace)` total order. The keyset
	 * predicate uses an explicit OR form so MariaDB seeks the
	 * `name_title` index rather than full-scanning. See #6559 for the
	 * underlying motivation.
	 *
	 * Does NOT reuse `KeysetPaginationTrait` because that trait is tied
	 * to the `smw_object_ids` `(smw_sort, smw_id)` shape; here we walk
	 * the MW core `page` table with a different sort tuple.
	 *
	 * @since 7.0.0
	 */
	private function searchByCursor( int $limit, int $cursor, $search, $namespace = null ): array {
		$search = $this->getSearchTerm( $search, $namespace );
		$conditions = $this->buildSearchConditions( $search, $namespace );

		if ( $cursor > 0 ) {
			$anchorRow = $this->connection->newSelectQueryBuilder()
				->select( [ 'page_title', 'page_namespace' ] )
				->from( 'page' )
				->where( [ 'page_id' => $cursor ] )
				->caller( __METHOD__ )
				->fetchRow();

			if ( $anchorRow ) {
				$qTitle = $this->connection->addQuotes( $anchorRow->page_title );
				$qNs = $this->connection->addQuotes( $anchorRow->page_namespace );
				$keysetPredicate = "page_title > $qTitle OR ( page_title = $qTitle AND page_namespace > $qNs )";
				$conditions = "( $keysetPredicate ) AND ( $conditions )";
			}
			// Stale cursor (page_id no longer exists): predicate is silently
			// skipped, response falls back to the first page. Matches the
			// convention established by `KeysetPaginationTrait`.
		}

		$res = $this->connection->newSelectQueryBuilder()
			->select( [ 'page_id', 'page_namespace', 'page_title' ] )
			->from( 'page' )
			->where( [ $conditions ] )
			->options( [
				'LIMIT' => $limit + 1,
				'ORDER BY' => "page_title,page_namespace"
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$count = 0;
		$continueCursor = 0;
		$lastPageId = 0;
		$list = [];

		foreach ( $res as $row ) {

			$count++;

			if ( $count > $limit ) {
				// Lookahead row triggers the break: surface the previous
				// row's `page_id` as the next-page anchor.
				$continueCursor = $lastPageId;
				break;
			}

			$list[$row->page_title . '#' . $row->page_namespace] = $this->rowToListEntry( $row );
			$lastPageId = (int)$row->page_id;
		}

		return [ $list, $continueCursor ];
	}

	/**
	 * Build the disjunctive LIKE conditions over `page_title` (with
	 * optional `page_namespace` AND), shared by the offset and cursor
	 * paths.
	 */
	private function buildSearchConditions( string $search, $namespace ): string {
		$escapeChar = '`';

		$search = str_replace(
			[ ' ', $escapeChar, '%', '_' ],
			[ '_', "{$escapeChar}{$escapeChar}", "{$escapeChar}%", "{$escapeChar}_" ],
			$search
		);

		$conds = [
			'%' . $search . '%',
			'%' . ucfirst( $search ) . '%',
			'%' . strtoupper( $search ) . '%',
			'%' . strtolower( $search ) . '%'
		];

		$conditions = '';
		foreach ( $conds as $s ) {
			$conditions .= ( $conditions !== '' ? ' OR ' : '' ) . 'page_title LIKE ';
			$conditions .= $this->connection->addQuotes( $s );
			$conditions .= ' ESCAPE ' . $this->connection->addQuotes( $escapeChar );
		}

		if ( $namespace !== null ) {
			$conditions = 'page_namespace=' . $this->connection->addQuotes( $namespace ) . ' AND (' . $conditions . ')';
		}

		return $conditions;
	}

	private function rowToListEntry( object $row ): array {
		return [
			// Only keep the ID as internal field which is removed by the Augmentor
			'id'    => $row->page_id,
			'label' => str_replace( '_', ' ', $row->page_title ),
			'key'   => $row->page_title,
			'ns'    => $row->page_namespace
		];
	}

	private function getSearchTerm( $search, &$namespace = null ) {
		if ( strpos( $search, ':' ) !== false ) {
			[ $ns, $term ] = explode( ':', $search );

			$namespace = Localizer::getInstance()->getNsIndex( $ns );
			if ( $namespace !== false ) {
				$search = $term;
			} else {
				$namespace = null;
			}
		}

		return $search;
	}

}
