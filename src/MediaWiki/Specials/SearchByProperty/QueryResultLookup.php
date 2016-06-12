<?php

namespace SMW\MediaWiki\Specials\SearchByProperty;

use SMW\DataValueFactory;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\PrintRequest as PrintRequest;
use SMW\Store;
use SMWQuery as Query;
use SMWRequestOptions as RequestOptions;

/**
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author Denny Vrandecic
 * @author Daniel Herzig
 * @author Markus Kroetzsch
 * @author mwjames
 */
class QueryResultLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 2.1
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 2.1
	 *
	 * @param  QueryOptions $pageRequestOptions
	 *
	 * @return array of array(SMWWikiPageValue, SMWDataValue) with the
	 * first being the entity, and the second the value
	 */
	public function doQuery( PageRequestOptions $pageRequestOptions ) {

		$requestOptions = new RequestOptions();
		$requestOptions->limit = $pageRequestOptions->limit + 1;
		$requestOptions->offset = $pageRequestOptions->offset;
		$requestOptions->sort = true;

		if ( $pageRequestOptions->value === null || !$pageRequestOptions->value->isValid() ) {
			$res = $this->doQueryForNonValue( $pageRequestOptions, $requestOptions );
		} else {
			$res = $this->doQueryForExactValue( $pageRequestOptions, $requestOptions );
		}

		$results = array();

		$dataValueFactory = DataValueFactory::getInstance();

		foreach ( $res as $result ) {
			$results[] = array(
				$dataValueFactory->newDataValueByItem( $result, null ),
				$pageRequestOptions->value
			);
		}

		return $results;
	}

	/**
	 * Returns all results that have a value near to the searched for value
	 * on the property, ordered, and sorted by ending with the smallest
	 * one.
	 *
	 * @param QueryOptions $pageRequestOptions
	 * @param integer $count How many entities have the exact same value on the property?
	 * @param integer $greater Should the values be bigger? Set false for smaller values.
	 *
	 * @return array of array of SMWWikiPageValue, SMWDataValue with the
	 * first being the entity, and the second the value
	 */
	public function doQueryForNearbyResults( PageRequestOptions $pageRequestOptions, $count, $greater = true ) {

		$comparator = $greater ? SMW_CMP_GRTR : SMW_CMP_LESS;
		$sortOrder = $greater ? 'ASC' : 'DESC';

		if ( $pageRequestOptions->value !== null && $pageRequestOptions->value->getTypeID() === '_txt' && strlen( $pageRequestOptions->valueString ) > 72 ) {
			$comparator = SMW_CMP_LIKE;
		}

		if ( $pageRequestOptions->valueString === '' || $pageRequestOptions->valueString === null ) {
			$description = new ThingDescription();
		} else {
			$description = new ValueDescription(
				$pageRequestOptions->value->getDataItem(),
				$pageRequestOptions->property->getDataItem(),
				$comparator
			);
		}

		$someProperty = new SomeProperty(
			$pageRequestOptions->property->getDataItem(),
			$description
		);

		$query = new Query( $someProperty );

		$query->setLimit( $pageRequestOptions->limit );
		$query->setOffset( $pageRequestOptions->offset );
		$query->sort = true;
		$query->sortkeys = array(
			$pageRequestOptions->property->getDataItem()->getKey() => $sortOrder
		);

		// Note: printrequests change the caption of properties they
		// get (they expect properties to be given to them).
		// Since we want to continue using the property for our
		// purposes, we give a clone to the print request.
		$printouts = array(
			new PrintRequest( PrintRequest::PRINT_THIS, '' ),
			new PrintRequest( PrintRequest::PRINT_PROP, '', clone $pageRequestOptions->property )
		);

		$query->setExtraPrintouts( $printouts );

		$queryResults = $this->store->getQueryResult( $query );

		$result = array();

		while ( $resultArrays = $queryResults->getNext() ) {
			$r = array();

			foreach ( $resultArrays as $resultArray ) {
				$r[] = $resultArray->getNextDataValue();
			}
			// Note: if results have multiple values for the property
			// then this code just pick the first, which may not be
			// the reason why the result is shown here, i.e., it could
			// be out of order.
			$result[] = $r;
		}

		if ( !$greater ) {
			$result = array_reverse( $result );
		}

		return $result;
	}

	private function doQueryForNonValue( PageRequestOptions $pageRequestOptions, RequestOptions $requestOptions ) {
		return $this->store->getPropertyValues(
			null,
			$pageRequestOptions->property->getDataItem(),
			$requestOptions
		);
	}

	private function doQueryForExactValue( PageRequestOptions $pageRequestOptions, RequestOptions $requestOptions ) {
		return $this->store->getPropertySubjects(
			$pageRequestOptions->property->getDataItem(),
			$pageRequestOptions->value->getDataItem(),
			$requestOptions
		);
	}

}
