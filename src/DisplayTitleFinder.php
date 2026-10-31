<?php

namespace SMW;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Services\Exception\ServiceNotFoundException;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DisplayTitleFinder {

	private Store $store;

	private EntityCache $entityCache;

	private bool $canUse = true;

	/**
	 * @since 3.1
	 */
	public function __construct( Store $store, EntityCache $entityCache ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 */
	public function getEntityCache(): EntityCache {
		return $this->entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param bool $canUse
	 */
	public function setCanUse( $canUse ): void {
		$this->canUse = (bool)$canUse;
	}

	/**
	 * @since 3.2
	 */
	public function prefetchFromSemanticData( SemanticData $semanticData ): void {
		if ( !$this->canUse ) {
			return;
		}

		$dataItems = [];
		$dataItems[] = $semanticData->getSubject();

		foreach ( $semanticData->getProperties() as $property ) {
			$type = DataTypeRegistry::getInstance()->getDataItemByType(
				$property->findPropertyValueType()
			);

			if ( $type !== DataItem::TYPE_WIKIPAGE ) {
				continue;
			}

			$dataItems = array_merge(
				$dataItems,
				$semanticData->getPropertyValues( $property )
			);
		}

		foreach ( $semanticData->getSubSemanticData() as $subSemanticData ) {

			$dataItems[] = $subSemanticData->getSubject();

			foreach ( $subSemanticData->getProperties() as $property ) {
				$type = DataTypeRegistry::getInstance()->getDataItemByType(
					$property->findPropertyValueType()
				);

				if ( $type !== DataItem::TYPE_WIKIPAGE ) {
					continue;
				}

				$dataItems = array_merge(
					$dataItems,
					$subSemanticData->getPropertyValues( $property )
				);
			}
		}

		$this->prefetchFromList( $dataItems );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $dataItems
	 */
	public function prefetchFromList( $dataItems ): void {
		if ( !$this->canUse || !is_iterable( $dataItems ) ) {
			return;
		}

		$subjects = [];

		foreach ( $dataItems as $dataItem ) {

			if ( !$dataItem instanceof WikiPage ) {
				continue;
			}

			$subjects[$this->makeKey( $dataItem )] = $dataItem;

			// Fetch the base in case the subobject has no assignment
			if ( $dataItem->getSubobjectName() !== '' ) {
				$base = $dataItem->asBase();
				$subjects[$this->makeKey( $base )] = $base;
			}
		}

		if ( $subjects === [] ) {
			return;
		}

		$this->entityCache->getMultiWithUnionSetCallback(
			$subjects,
			EntityCache::TTL_WEEK,
			fn ( array $missing ) => $this->prefetchDisplayTitles( $missing )
		);
	}

	/**
	 * @param array<string,WikiPage> $subjects
	 *
	 * @return array<string,string|false>
	 */
	private function prefetchDisplayTitles( array $subjects ): array {
		$unCachedList = [];

		foreach ( $subjects as $subject ) {
			$base = $subject->asBase();
			$unCachedList[$subject->getSha1()] = $subject;
			$unCachedList[$base->getSha1()] = $base;
		}

		try {
			$displayTitleLookup = $this->store->service( 'DisplayTitleLookup' );
		} catch ( ServiceNotFoundException ) {
			return array_fill_keys( array_keys( $subjects ), false );
		}

		$prefetch = $displayTitleLookup->prefetchFromList(
			$unCachedList
		);

		$displayTitles = [];

		foreach ( $subjects as $key => $subject ) {
			$displayTitles[$key] = $this->findPrefetchedDisplayTitle( $prefetch, $subject );
		}

		return $displayTitles;
	}

	private function findPrefetchedDisplayTitle( array $prefetch, WikiPage $subject ): string|false {
		$sha1 = $subject->getSha1();

		// Can be NULL therefore use `array_key_exists`
		if ( !array_key_exists( $sha1, $prefetch ) ) {
			return false;
		}

		$prefetchTitle = $prefetch[$sha1];

		// Nothing found, use the base!
		if ( $prefetchTitle === null && $subject->getSubobjectName() !== '' ) {
			$sha1 = $subject->asBase()->getSha1();

			if ( !array_key_exists( $sha1, $prefetch ) ) {
				return false;
			}

			$prefetchTitle = $prefetch[$sha1];
		}

		return $prefetchTitle ?? ' ';
	}

	/**
	 * @since 3.1
	 */
	public function findDisplayTitle( WikiPage $subject ): string {
		if ( !$this->canUse ) {
			return '';
		}

		$displayTitle = $this->entityCache->getWithSetCallback(
			$subject,
			$this->makeKey( $subject ),
			EntityCache::TTL_WEEK,
			fn () => $this->findDisplayTitleFor( $subject )
		);

		return trim( $displayTitle );
	}

	private function makeKey( WikiPage $subject ): string {
		return $this->entityCache->makeKey( 'displaytitle', $subject->getHash() );
	}

	private function findDisplayTitleFor( $subject ) {
		$requestOptions = new RequestOptions();
		$requestOptions->setCaller( __METHOD__ );

		// Avoid issues in case of `false` or empty to store
		// a space
		$displayTitle = ' ';

		$dataItems = $this->store->getPropertyValues(
			$subject,
			new Property( '_DTITLE' ),
			$requestOptions
		);

		if ( $dataItems !== null && $dataItems !== [] ) {
			$displayTitle = end( $dataItems )->getString();
		} elseif ( $subject->getSubobjectName() !== '' ) {
			// Check whether the base subject has a DISPLAYTITLE
			return $this->findDisplayTitleFor( $subject->asBase() );
		}

		return $displayTitle;
	}

}
