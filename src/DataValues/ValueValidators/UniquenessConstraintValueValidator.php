<?php

namespace SMW\DataValues\ValueValidators;

use SMW\ApplicationFactory;
use SMW\CachedPropertyValuesPrefetcher;
use SMW\DIWikiPage;
use SMWDataValue as DataValue;

/**
 * @private
 *
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
class UniquenessConstraintValueValidator implements ConstraintValueValidator {

	/**
	 * @var CachedPropertyValuesPrefetcher
	 */
	private $cachedPropertyValuesPrefetcher;

	/**
	 * @var QueryFactory
	 */
	private $queryFactory;

	/**
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

	/**
	 * @since 2.4
	 *
	 * @param CachedPropertyValuesPrefetcher $cachedPropertyValuesPrefetcher
	 */
	public function __construct( CachedPropertyValuesPrefetcher $cachedPropertyValuesPrefetcher = null ) {
		$this->cachedPropertyValuesPrefetcher = $cachedPropertyValuesPrefetcher;

		if ( $this->cachedPropertyValuesPrefetcher === null ) {
			$this->cachedPropertyValuesPrefetcher = ApplicationFactory::getInstance()->getCachedPropertyValuesPrefetcher();
		}

		$this->queryFactory = ApplicationFactory::getInstance()->newQueryFactory();
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function hasConstraintViolation() {
		return $this->hasConstraintViolation;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function validate( $dataValue ) {

		$this->hasConstraintViolation = false;

		if ( !$this->canValidate( $dataValue ) ) {
			return $this->hasConstraintViolation;
		}

		$property = $dataValue->getProperty();

		if ( !$dataValue->getPropertySpecificationLookup()->hasUniquenessConstraintFor( $property ) ) {
			return $this->hasConstraintViolation;
		}

		$blobStore = $this->cachedPropertyValuesPrefetcher->getBlobStore();
		$dataItem = $dataValue->getDataItem();

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
			$dataValue->addErrorMsg(
				array(
					'smw-datavalue-uniqueness-constraint-error',
					$property->getLabel(),
					$dataValue->getWikiValue(),
					$wikiPage->getTitle()->getPrefixedText()
				)
			);

			$this->hasConstraintViolation = true;
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

	private function canValidate( $dataValue ) {
		return $dataValue instanceof DataValue && $dataValue->getProperty() !== null && $dataValue->getContextPage() !== null && $dataValue->isEnabledFeature( SMW_DV_PVUC );
	}

}
