<?php

namespace SMW\Elastic\QueryEngine;

use Psr\Log\LoggerAwareTrait;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMW\Query\Language\ThingDescription;
use SMW\Query\ScoreSet;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\QueryEngine as IQueryEngine;
use SMW\Store;
use SMW\Options;
use SMW\Elastic\Connection\Client as ElasticClient;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class QueryEngine implements IQueryEngine {

	use LoggerAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var QueryFactory
	 */
	private $queryFactory;

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var FieldMapper
	 */
	private $fieldMapper;

	/**
	 * @var SortBuilder
	 */
	private $sortBuilder;

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @var array
	 */
	private $queryInfo = [];

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param Options|null $options
	 */
	public function __construct( Store $store, QueryBuilder $queryBuilder, Options $options = null ) {
		$this->store = $store;
		$this->options = $options;

		if ( $options === null ) {
			$this->options = new Options();
		}

		$this->queryFactory = ApplicationFactory::getInstance()->getQueryFactory();
		$this->fieldMapper = new FieldMapper();

		$this->queryBuilder = $queryBuilder;
		$this->sortBuilder = new SortBuilder( $store );

		$this->sortBuilder->setScoreField(
			$this->options->dotGet( 'query.score.sortfield' )
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param []
	 */
	public function getQueryInfo() {
		return $this->queryInfo;
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 *
	 * @return QueryResult
	 */
	public function getQueryResult( Query $query ) {

//		if ( ( !$this->engineOptions->get( 'smwgIgnoreQueryErrors' ) || $query->getDescription() instanceof ThingDescription ) &&

		if ( ( $query->getDescription() instanceof ThingDescription ) &&
				$query->querymode != Query::MODE_DEBUG &&
				count( $query->getErrors() ) > 0 ) {
			return $this->queryFactory->newQueryResult( $this->store, $query, array(), false );
			// NOTE: we check this here to prevent unnecessary work, but we check
			// it after query processing below again in case more errors occurred.
		} elseif ( $query->querymode == Query::MODE_NONE || $query->getLimit() < 1 ) {
			return $this->queryFactory->newQueryResult( $this->store, $query, array(), true );
		}

		$this->errors = [];

		$this->queryInfo = [
			'smw' => [],
			'elastic' => [],
			'info' => []
		];

		list( $sort, $sortFields, $isRandom, $isConstantScore ) = $this->sortBuilder->makeSortField(
			$query
		);

		$description = $query->getDescription();
		$body = [];

		$this->queryBuilder->setSortFields(
			$sortFields
		);

		$q = $this->queryBuilder->makeFromDescription(
			$description,
			$isConstantScore
		);

		$this->errors = $this->queryBuilder->getErrors();
		$this->queryInfo['elastic'] = $this->queryBuilder->getQueryInfo();

		if ( $isRandom ) {
			$q = $this->fieldMapper->function_score_random( $q );
		}

		$body = [
			// @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/search-request-source-filtering.html
			// We only want the ID, no need for all the body
			'_source' => false,

			// https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html#_track_scores
			// 'track_scores' => true,
			'from'    => $query->getOffset(),
			'size'    => $query->getLimit() + 1, // Look ahead +1,
			'query'   => $q
		];

		if ( !$isRandom && $sort !== [] ) {
			$body['sort'] = [ $sort ];
		}

		// https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html#_track_scores
		if ( $this->sortBuilder->isScoreSort() && $query->querymode !== Query::MODE_COUNT ) {
			$body['track_scores'] = true;
		}

		if ( $this->options->dotGet( 'query.profiling' ) ) {
			$body['profile'] = true;
		}

		// If at all only consider the retrieval for Special:Search queries
		if ( $query->getOption( 'is.special_search' ) !== false && $query->querymode !== Query::MODE_COUNT ) {
			$this->addHighlight( $body );
		}

		$query->addErrors( $this->errors );

		$connection = $this->store->getConnection( 'elastic' );

		$index = $connection->getIndexNameByType(
			ElasticClient::TYPE_DATA
		);

		$params = [
			'index' => $index,
			'type'  => ElasticClient::TYPE_DATA,
			'body'  => $body
		];

		$this->queryInfo['elastic'][] = $params;

		$this->queryInfo['smw'] = [
			'query' => $query->getQueryString(),
			'sort'  => $query->getSortKeys(),
			'metrics' => [
				'query size'  => $description->getSize(),
				'query depth' => $description->getDepth()
			]
		];

		switch ( $query->querymode ) {
			case Query::MODE_DEBUG:
				$result = $this->newDebugQueryResult( $params );
			break;
			case Query::MODE_COUNT:
				$result = $this->newCountQueryResult( $query, $params );
			break;
			default:
				$result = $this->newInstanceQueryResult( $query, $params );
			break;
		}

		return $result;
	}

	private function newDebugQueryResult( $params ) {

		$params['explain'] = $this->options->dotGet( 'query.debug.explain', false );

		$connection = $this->store->getConnection( 'elastic' );
		$this->queryInfo['elastic'][] = $connection->validate( $params );

		if ( ( $log = $this->queryBuilder->getDescriptionLog() ) !== [] ) {
			$this->queryInfo['smw']['description_log'] = $log;
		}

		$info = str_replace(
			[ '[', '<', '>', '\"', '\n' ],
			[ '&#91;', '&lt;', '&gt;', '&quot;', '' ],
			json_encode( $this->queryInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		);

		$html = \Html::rawElement(
			'pre',
			[
				'class' => 'smwpre smwpre-no-margin smw-debug-box'
			],
			\Html::rawElement(
				'div',
				[
					'class' => 'smw-debug-box-header'
				],
				'<big>ElasticStore debug output</big>'
			) . $info
		);

		return $html;
	}

	private function newCountQueryResult( $query, $params ) {

		$connection = $this->store->getConnection( 'elastic' );
		$result = $connection->count( $params );

		$queryResult = $this->queryFactory->newQueryResult(
			$this->store,
			$query,
			[],
			false
		);

		$count = isset( $result['count'] ) ? $result['count'] : 0;
		$queryResult->setCountValue( $count  );

		$this->queryInfo['info'] = $result;

		return $queryResult;
	}

	private function newInstanceQueryResult( $query, array $params ) {

		$connection = $this->store->getConnection( 'elastic' );
		$scoreSet = new ScoreSet();
		$excerpts = new Excerpts();

		list( $res, $errors ) = $connection->search( $params );

		$searchResult = new SearchResult( $res );

		$results = $searchResult->getResults(
			$query->getLimit()
		);

		$query->addErrors( $errors );

		$scores = $searchResult->get( 'scores' );
		$excerptList = $searchResult->get( 'excerpts' );

		// Use a bulk load via ` ... WHERE IN ...` instead of single requests
		$dataItems = $this->store->getObjectIds()->getDataItemsFromList(
			$results
		);

		// `... WHERE IN ...` doesn't guarantee to return the same order
		$listPos = array_flip( $results );
		$results = [];

		// Relocate to the original position that returned from Elastic
		foreach ( $dataItems as $dataItem ) {
			$id = $dataItem->getId();
			$results[$listPos[$id]] = $dataItem;

			if ( isset( $scores[$id] ) ) {
				$scoreSet->addScore( $dataItem->getHash(), $scores[$id] );
			}

			if ( isset( $excerptList[$id] ) ) {
				$excerpts->addExcerpt( $dataItem, $excerptList[$id] );
			}
		}

		ksort( $results );

		$queryResult = $this->queryFactory->newQueryResult(
			$this->store,
			$query,
			$results,
			$searchResult->get( 'continue' )
		);

		$queryResult->setScoreSet( $scoreSet );
		$queryResult->setExcerpts( $excerpts );

		return $queryResult;
	}

	private function addHighlight( &$body ) {

		if ( ( $type = $this->options->dotGet( 'query.special_search.highlight.fragment.type', false ) ) === false ) {
			return;
		}

		// https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
		if ( !in_array( $type, [ 'plain', 'unified', 'fvh' ] ) ) {
			return;
		}

		$body['highlight'] = [
			'number_of_fragments' => $this->options->dotGet( 'query.special_search.highlight.fragment.count', 1 ),
			'fragment_size' => $this->options->dotGet( 'query.special_search.highlight.fragment.size', 150 ),
			'fields' => [
				'attachment.content' => [ "type" => $type ],
				'text_raw' => [ "type" => $type ],
				'P*.txtField' => [ "type" => $type ]
			]
		];
	}

}
