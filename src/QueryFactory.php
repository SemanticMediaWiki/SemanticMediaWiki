<?php

namespace SMW;

use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Description;
use SMW\Query\Parser as QueryParser;
use SMW\Query\Parser\DescriptionProcessor;
use SMW\Query\Parser\LegacyParser;
use SMW\Query\Parser\Tokenizer;
use SMW\Query\PrintRequestFactory;
use SMW\Query\ProfileAnnotatorFactory;
use SMW\Query\QueryCreator;
use SMW\Query\QueryToken;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryFactory {

	/**
	 * @since 2.5
	 *
	 * @return ProfileAnnotatorFactory
	 */
	public function newProfileAnnotatorFactory() {
		return new ProfileAnnotatorFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @param Description $description
	 * @param integer|false $context
	 *
	 * @return Query
	 */
	public function newQuery( Description $description, $context = false ) {
		return new Query( $description, $context );
	}

	/**
	 * @since 2.4
	 *
	 * @return DescriptionFactory
	 */
	public function newDescriptionFactory() {
		return new DescriptionFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @return PrintRequestFactory
	 */
	public function newPrintRequestFactory() {
		return new PrintRequestFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @return RequestOptions
	 */
	public function newRequestOptions() {
		return new RequestOptions();
	}

	/**
	 * @since 2.4
	 *
	 * @param string $string
	 * @param integer $condition
	 * @param boolean $isDisjunctiveCondition
	 *
	 * @return StringCondition
	 */
	public function newStringCondition( $string, $condition, $isDisjunctiveCondition = false ) {
		return new StringCondition( $string, $condition, $isDisjunctiveCondition );
	}

	/**
	 * @since 2.4
	 *
	 * @param integer|boolean $queryFeatures
	 *
	 * @return QueryParser
	 */
	public function newQueryParser( $queryFeatures = false ) {
		return $this->newLegacyQueryParser( $queryFeatures );
	}

	/**
	 * @since 3.0
	 *
	 * @param integer|boolean $queryFeatures
	 *
	 * @return QueryParser
	 */
	public function newLegacyQueryParser( $queryFeatures = false ) {

		if ( $queryFeatures === false ) {
			$queryFeatures = Applicationfactory::getInstance()->getSettings()->get( 'smwgQFeatures' );
		}

		return new LegacyParser(
			new DescriptionProcessor( $queryFeatures ),
			new Tokenizer(),
			new QueryToken()
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param Query $query
	 * @param DIWikiPage[]|[] $results = array()
	 * @param boolean $continue
	 *
	 * @return QueryResult
	 */
	public function newQueryResult( Store $store, Query $query, $results = [], $continue = false ) {

		$queryResult =  new QueryResult(
			$query->getDescription()->getPrintrequests(),
			$query,
			$results,
			$store,
			$continue
		);

		return $queryResult;
	}

}
