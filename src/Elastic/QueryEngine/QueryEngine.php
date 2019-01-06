<?php

namespace SMW\Elastic\QueryEngine;

use Psr\Log\LoggerAwareTrait;
use SMW\ApplicationFactory;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Options;
use SMW\Query\Language\ThingDescription;
use SMW\Query\ScoreSet;
use SMW\QueryEngine as IQueryEngine;
use SMW\Store;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;

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
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

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
	 * @param ConditionBuilder $conditionBuilder
	 * @param Options|null $options
	 */
	public function __construct( Store $store, ConditionBuilder $conditionBuilder, Options $options = null ) {
		$this->store = $store;
		$this->options = $options;

		if ( $options === null ) {
			$this->options = new Options();
		}

		$this->queryFactory = ApplicationFactory::getInstance()->getQueryFactory();
		$this->fieldMapper = new FieldMapper();

		$this->conditionBuilder = $conditionBuilder;
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
			return $this->queryFactory->newQueryResult( $this->store, $query, [], false );
			// NOTE: we check this here to prevent unnecessary work, but we check
			// it after query processing below again in case more errors occurred.
		} elseif ( $query->querymode == Query::MODE_NONE || $query->getLimit() < 1 ) {
			return $this->queryFactory->newQueryResult( $this->store, $query, [], true );
		}

		$this->errors = [];
		$body = [];

		$this->queryInfo = [
			'smw' => [],
			'elastic' => [],
			'info' => []
		];

		list( $sort, $sortFields, $isRandom, $isConstantScore ) = $this->sortBuilder->makeSortField(
			$query
		);

		$description = $query->getDescription();

		$this->conditionBuilder->setSortFields(
			$sortFields
		);

		$params = $this->conditionBuilder->makeFromDescription(
			$description,
			$isConstantScore
		);

		$this->errors = $this->conditionBuilder->getErrors();
		$this->queryInfo['elastic'] = $this->conditionBuilder->getQueryInfo();

		if ( $isRandom ) {
			$params = $this->fieldMapper->function_score_random( $params );
		}

		$body = [
			// @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/search-request-source-filtering.html
			// We only want the ID, no need for the entire document body
			'_source' => false,
			'from'    => $query->getOffset(),
			'size'    => $query->getLimit() + 1, // Look ahead +1,
			'query'   => $params
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
		if ( $query->getOption( 'highlight.fragment' ) !== false && $query->querymode !== Query::MODE_COUNT ) {
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

		if ( ( $log = $this->conditionBuilder->getDescriptionLog() ) !== [] ) {
			$this->queryInfo['smw']['description_log'] = $log;
		}

		if ( isset( $this->queryInfo['info'] ) && $this->queryInfo['info'] === [] ) {
			unset( $this->queryInfo['info'] );
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

		if ( $query->getOption( 'native_result' ) ) {
			$query->native_result = json_encode( $res, JSON_PRETTY_PRINT |JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

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

			// In case of an update lag (Elasticsearch is near real time where
			// some shards may not yet have seen an update) make sure to hide any
			// outdated entities we retrieve from the SQL as ID master back-end
			if ( $dataItem->getInterwiki() === SMW_SQL3_SMWDELETEIW ) {
				continue;
			}

			$id = $dataItem->getId();
			$results[$listPos[$id]] = $dataItem;

			if ( isset( $scores[$id] ) ) {
				$scoreSet->addScore( $dataItem->getHash(), $scores[$id], $listPos[$id] );
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

		if ( ( $type = $this->options->dotGet( 'query.highlight.fragment.type', false ) ) === false ) {
			return;
		}

		// https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
		if ( !in_array( $type, [ 'plain', 'unified', 'fvh' ] ) ) {
			return;
		}

		$body['highlight'] = [
			'number_of_fragments' => $this->options->dotGet( 'query.highlight.fragment.number', 1 ),
			'fragment_size' => $this->options->dotGet( 'query.highlight.fragment.size', 150 ),
			'fields' => [
				'attachment.content' => [ "type" => $type ],
				'text_raw' => [ "type" => $type ],
				'P*.txtField' => [ "type" => $type ]
			]
		];
	}

}
