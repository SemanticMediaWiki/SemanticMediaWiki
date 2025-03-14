<?php

namespace SMW\MediaWiki\Specials\SearchByProperty;

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\DescriptionFactory;
use SMW\Query\PrintRequest;
use SMW\RequestOptions;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWQuery as Query;

/**
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author Denny Vrandecic
 * @author Daniel Herzig
 * @author Markus Kroetzsch
 * @author mwjames
 * @reviewer thomas-topway-it for KM-A
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
	 * @since 2.5
	 *
	 * @param QueryOptions $pageRequestOptions
	 *
	 * @return array
	 */
	public function doQueryLinksReferences( PageRequestOptions $pageRequestOptions ) {
		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( $pageRequestOptions->limit + 1 );
		$requestOptions->setOffset( $pageRequestOptions->offset );
		$requestOptions->sort = true;

		$queryDependencyLinksStoreFactory = new QueryDependencyLinksStoreFactory();

		$queryReferenceLinks = $queryDependencyLinksStoreFactory->newQueryReferenceBacklinks(
			$this->store
		);

		$queryBacklinks = $queryReferenceLinks->findReferenceLinks(
			$pageRequestOptions->value->getDataItem(),
			$requestOptions
		);

		$results = [];

		$dataValueFactory = DataValueFactory::getInstance();

		foreach ( $queryBacklinks as $result ) {
			$results[] = [
				$dataValueFactory->newDataValueByItem( DIWikiPage::doUnserialize( $result ), null ),
				$pageRequestOptions->value
			];
		}

		return $results;
	}

	/**
	 * @since 2.1
	 *
	 * @param QueryOptions $pageRequestOptions
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

		$results = [];

		$dataValueFactory = DataValueFactory::getInstance();

		foreach ( $res as $result ) {
			$results[] = [
				$dataValueFactory->newDataValueByItem( $result, null ),
				$pageRequestOptions->value
			];
		}

		return $results;
	}

	/**
	 * Returns all results that have a value near to the searched for value
	 * on the property, ordered, and sorted by ending with the smallest
	 * one.
	 *
	 * @param QueryOptions $pageRequestOptions
	 * @param int $count How many entities have the exact same value on the property?
	 * @param int $greater Should the values be bigger? Set false for smaller values.
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

		$descriptionFactory = new DescriptionFactory();

		if ( $pageRequestOptions->valueString === '' || $pageRequestOptions->valueString === null ) {
			$description = $descriptionFactory->newThingDescription();
		} else {
			$description = $descriptionFactory->newValueDescription(
				$pageRequestOptions->value->getDataItem(),
				$pageRequestOptions->property->getDataItem(),
				$comparator
			);

			$description = $descriptionFactory->newSomeProperty(
				$pageRequestOptions->property->getDataItem(),
				$description
			);
		}

		$query = new Query( $description );

		$query->setLimit( $pageRequestOptions->limit );
		$query->setOffset( $pageRequestOptions->offset );
		$query->sort = true;
		$query->sortkeys = [
			$pageRequestOptions->property->getDataItem()->getKey() => $sortOrder
		];

		// Note: printrequests change the caption of properties they
		// get (they expect properties to be given to them).
		// Since we want to continue using the property for our
		// purposes, we give a clone to the print request.
		$printout = new PrintRequest(
			PrintRequest::PRINT_PROP,
			'',
			clone $pageRequestOptions->property
		);

		// No more than one value is displayed therefore limit the query
		// to only look for one and not all
		$printout->setParameter( 'limit', 1 );

		$printouts = [
			new PrintRequest( PrintRequest::PRINT_THIS, '' ),
			$printout
		];

		$query->setExtraPrintouts( $printouts );

		$queryResults = $this->store->getQueryResult( $query );

		$result = [];

		while ( $resultArrays = $queryResults->getNext() ) {
			$r = [];

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

	private function destructureDIContainer( DIProperty $DIProperty, DataItem $dataItem, PageRequestOptions $pageRequestOptions ) {
		$multiValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$DIProperty
		);
		$properties = $multiValue->getPropertyDataItems( $DIProperty );

		// Property for the reference value
		$DIPropertyReferenceValue = $properties[0];

		// retrieve the dataitem for this property
		// as is done in the method below (through
		// pageRequestOptions). This avoids to
		// create the correct dataItem manually
		// despite some of the parameters below could
		// be unnecessary
		$requestOptions = [
			'limit'    => $pageRequestOptions->limit,
			'offset'   => $pageRequestOptions->offset,
			'property' => $DIPropertyReferenceValue->getCanonicalLabel(),
			'value'    => $pageRequestOptions->valueString,
			'nearbySearchForType' => $pageRequestOptions->nearbySearch
		];
		$pageRequestOptions = new PageRequestOptions( '', $requestOptions );
		$pageRequestOptions->initialize();

		return [ $DIPropertyReferenceValue, $pageRequestOptions->value->getDataItem() ];
	}

	private function doQueryForExactValue( PageRequestOptions $pageRequestOptions, RequestOptions $requestOptions ) {
		$pageRequestOptions->value->setOption( 'is.search', true );

		$DIProperty = $pageRequestOptions->property->getDataItem();
		$dataItem = $pageRequestOptions->value->getDataItem();

		// override $DIProperty for reference datatype
		// with the first multiValue property
		if ( DataTypeRegistry::getInstance()->isRecordType( $DIProperty->findPropertyTypeID() ) ) {
			[ $DIProperty, $dataItem ] = $this->destructureDIContainer( $DIProperty, $dataItem, $pageRequestOptions );
		}

		return $this->store->getPropertySubjects(
			$DIProperty,
			$dataItem,
			$requestOptions
		);
	}

}
