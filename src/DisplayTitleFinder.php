<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DisplayTitleFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var boolean
	 */
	private $canUse = true;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param EntityCache $entityCache
	 */
	public function __construct( Store $store, EntityCache $entityCache ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $canUse
	 */
	public function getEntityCache() {
		return $this->entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $canUse
	 */
	public function setCanUse( $canUse ) {
		$this->canUse = (bool)$canUse;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $dataItems
	 */
	public function prefetchFromList ( $dataItems ) {

		if ( $this->canUse === false || !is_iterable( $dataItems ) ) {
			return;
		}

		$unCachedList = [];

		foreach ( $dataItems as $dataItem ) {

			if ( !$dataItem instanceof DIWikiPage ) {
				continue;
			}

			$key = $this->entityCache->makeKey( 'displaytitle', $dataItem->getHash() );

			if ( $this->entityCache->fetch( $key ) !== false ) {
				continue;
			}

			$unCachedList[$dataItem->getSha1()] = $dataItem;

			if ( $dataItem->getSubobjectName() === '' ) {
				continue;
			}

			// Fetch the base in case the subobject has no assignment
			$dataItem = $dataItem->asBase();
			$unCachedList[$dataItem->getSha1()] = $dataItem;
		}

		if ( $unCachedList === [] ) {
			return;
		}

		$displayTitleLookup = $this->store->service( 'DisplayTitleLookup' );

		$prefetch = $displayTitleLookup->prefetchFromList(
			$unCachedList
		);

		foreach ( $unCachedList as $sha1 => $dataItem ) {

			// Can be NULL therefore use `array_key_exists` as well
			if ( !isset( $prefetch[$sha1] ) && !array_key_exists( $sha1, $prefetch ) ) {
				continue;
			}

			$prefetchTitle = $prefetch[$sha1];

			// Nothing found, use the base!
			if ( $prefetchTitle === null && $dataItem->getSubobjectName() !== '' ) {
				$sha1 = $dataItem->asBase()->getSha1();

				if ( !isset( $prefetch[$sha1] ) && !array_key_exists( $sha1, $prefetch ) ) {
					continue;
				}

				$prefetchTitle = $prefetch[$sha1];
			}

			if ( $prefetchTitle === null ) {
				$displayTitle = ' ';
			} else {
				$displayTitle = $prefetchTitle;
			}

			$key = $this->entityCache->makeKey( 'displaytitle', $dataItem->getHash() );
			$this->entityCache->save( $key, $displayTitle, EntityCache::TTL_WEEK );
			$this->entityCache->associate( $dataItem, $key );
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return string
	 */
	public function findDisplayTitle( DIWikiPage $subject ) {

		if ( $this->canUse === false ) {
			return '';
		}

		$base = $subject->asBase();
		$key = $this->entityCache->makeKey( 'displaytitle', $subject->getHash() );

		if ( ( $displayTitle = $this->entityCache->fetch( $key ) ) !== false && $displayTitle !== null ) {
			return trim( $displayTitle );
		}

		$displayTitle = $this->findDisplayTitleFor( $subject );
		$this->entityCache->save( $key, $displayTitle, EntityCache::TTL_WEEK );

		// Connect to the base subject so that all keys can be flushed at
		// the time the subject gets altered
		$this->entityCache->associate( $base, $key );

		return trim( $displayTitle );
	}

	private function findDisplayTitleFor( $subject ) {

		// Avoid issues in case of `false` or empty to store
		// a space
		$displayTitle = ' ';

		$dataItems = $this->store->getPropertyValues(
			$subject,
			new DIProperty( '_DTITLE' )
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
