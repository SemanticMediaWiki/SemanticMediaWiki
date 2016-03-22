<?php

namespace SMW\DataValues;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\ApplicationFactory;
use SMW\CachedPropertyValuesPrefetcher;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWBoolValue as BooleanValue;

/**
 * Only allow values that are unique where uniqueness is establised for the first (
 * in terms of time which also entails that after a full rebuild the first value
 * found is being categorised as established value) value assigned to a property
 * (that requires this trait) and any value that compares to an establised
 * value with the same literal representation is being identified as violating the
 * uniqueness constraint.
 *
 * @note This class is optimized for performance which means that each match will be
 * cached to avoid making unnecessary query requests to the QueryEngine.
 *
 * A linked list will ensure that a subject which is associated with a unique value
 * is being purged in case it is altered or its uniqueness constraint is no longer
 * valid.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class UniquenessConstraintValue extends BooleanValue {

	/**
	 * @var CachedPropertyValuesPrefetcher
	 */
	private $cachedPropertyValuesPrefetcher;

	/**
	 * @var QueryFactory
	 */
	private $queryFactory;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 2.4
	 *
	 * @param string $typeid
	 * @param CachedPropertyValuesPrefetcher $cachedPropertyValuesPrefetcher
	 */
	public function __construct( $typeid = '', CachedPropertyValuesPrefetcher $cachedPropertyValuesPrefetcher = null ) {
		parent::__construct( '__pvuc' );
		$this->cachedPropertyValuesPrefetcher = $cachedPropertyValuesPrefetcher;

		if ( $this->cachedPropertyValuesPrefetcher === null ) {
			$this->cachedPropertyValuesPrefetcher = ApplicationFactory::getInstance()->getCachedPropertyValuesPrefetcher();
		}

		$this->queryFactory = ApplicationFactory::getInstance()->newQueryFactory();
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $userValue ) {

		if ( !$this->isEnabledFeature( SMW_DV_PVUC ) ) {
			$this->addErrorMsg(
				array(
					'smw-datavalue-feature-not-supported',
					'SMW_DV_PVUC'
				)
			);
		}

		parent::parseUserValue( $userValue );
	}

	/**
	 * @see ValueConstraintValidator::doValidate
	 *
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 */
	public function doCheckUniquenessConstraintFor( DataValue $dataValue ) {

		if ( $dataValue->getContextPage() === null || !$dataValue->isEnabledFeature( SMW_DV_PVUC ) ) {
			return;
		}

		$dataItem = $dataValue->getDataItem();
		$property = $dataValue->getProperty();

		if ( !$this->getPropertySpecificationLookup()->hasUniquenessConstraintFor( $property ) ) {
			return null;
		}

		$blobStore = $this->cachedPropertyValuesPrefetcher->getBlobStore();

		$hash = $this->cachedPropertyValuesPrefetcher->getHashFor(
			$property->getKey() . ':' . $dataItem->getHash()
		);

		$container = $blobStore->read(
			$hash
		);

		$key = 'PVUC';

		if ( !$container->has( $key ) ) {
			$page = $this->tryFindMatchResultFor(
				$hash,
				$dataValue
			);

			$container->set( $key, $page );

			$blobStore->save(
				$container
			);
		}

		$wikiPage = $container->get( $key );

		// Verify that the contextPage (where the annotation has its origin) is
		// matchable to the request and in case it is not a match inform the user
		// about the origin
		if ( $wikiPage instanceof DIWikiPage && !$dataValue->getContextPage()->equals( $wikiPage ) ) {
			$this->addErrorMsg(
				array(
					'smw-datavalue-uniqueness-constraint-error',
					$property->getLabel(),
					$dataValue->getWikiValue(),
					$wikiPage->getTitle()->getPrefixedText()
				)
			);
		}
	}

	private function tryFindMatchResultFor( $hash, $dataValue ) {

		$descriptionFactory = $this->queryFactory->newDescriptionFactory();
		$contextPage = $dataValue->getContextPage();

		// Exclude the current page from the result match to check whether another
		// page matches the condition and if so then the value can no longer be
		// assigned and is not unique
		$description = $descriptionFactory->newConjunction( array(
			$descriptionFactory->newFromDataValue( $dataValue ),
			$descriptionFactory->newValueDescription( $contextPage, null, SMW_CMP_NEQ ) // NEQ
		) );

		$query = $this->queryFactory->newQuery( $description );
		$query->setLimit( 1 );

		$dataItems = $this->cachedPropertyValuesPrefetcher->queryPropertyValuesFor(
			$query
		);

		if ( !is_array( $dataItems ) || $dataItems === array() ) {
			// No other assignments were found therefore it is assumed that at
			// the time of the query request, the "contextPage" holds a unique
			// value for the property
			$page = $contextPage;
		} else {
			$page = end( $dataItems );
		}

		// Create a linked list so that when the subject is altered or deleted
		// the related uniqueness container can be removed as well
		$blobStore = $this->cachedPropertyValuesPrefetcher->getBlobStore();

		$container = $blobStore->read(
			$this->cachedPropertyValuesPrefetcher->getRootHashFor( $page )
		);

		$container->addToLinkedList( $hash );

		$blobStore->save(
			$container
		);

		return $page;
	}

}
