<?php

namespace SMW;

use InvalidArgumentException;

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
	 * @param DIWikiPage $subject
	 *
	 * @return string
	 */
	public function findDisplayTitle( DIWikiPage $subject ) {

		$base = $subject->asBase();
		$key = $this->entityCache->makeKey( 'displaytitle', $subject->getHash() );

		if ( ( $displayTitle = $this->entityCache->fetch( $key ) ) !== false && $displayTitle !== null ) {
			return $displayTitle;
		}

		$displayTitle = $this->findDisplayTitleFor( $subject );
		$this->entityCache->save( $key, $displayTitle, EntityCache::TTL_WEEK );

		// Connect to the base subject so that all keys can be flushed at
		// the time the subject gets altered
		$this->entityCache->associate( $base, $key );

		return $displayTitle;
	}

	private function findDisplayTitleFor( $subject ) {

		$displayTitle = '';

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
