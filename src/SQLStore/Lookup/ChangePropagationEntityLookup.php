<?php

namespace SMW\SQLStore\Lookup;

use RuntimeException;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\Store;

/**
 * Find all entities related to a change propagation (only expected
 * to be used by `ChangePropagationDispatchJob`).
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationEntityLookup {

	/**
	 * @var bool
	 */
	private $isTypePropagation = false;

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly IteratorFactory $iteratorFactory,
	) {
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $isTypePropagation
	 */
	public function isTypePropagation( $isTypePropagation ): void {
		$this->isTypePropagation = (bool)$isTypePropagation;
	}

	/**
	 * @since 3.0
	 *
	 * @param Property|WikiPage $entity
	 *
	 * @return Iterator
	 * @throws RuntimeException
	 */
	public function findAll( $entity ) {
		if ( $entity instanceof Property ) {
			return $this->findByProperty( $entity );
		} elseif ( $entity instanceof WikiPage ) {
			return $this->findByCategory( $entity );
		}

		throw new RuntimeException( 'Cannot match the entity type.' );
	}

	/**
	 * @since 3.0
	 *
	 * @param Property $property
	 *
	 * @return Iterator
	 */
	public function findByProperty( Property $property ) {
		$dataItems = [];
		$appendIterator = $this->iteratorFactory->newAppendIterator();

		$res = $this->store->getAllPropertySubjects(
			$property
		);

		$appendIterator->add(
			$res
		);

		// Select any remaining references that are hidden or have been left out
		// during an update
		$appendIterator->add(
			$this->fetchOtherReferencesOnTypePropagation( $property )
		);

		$dataItems = $this->store->getPropertySubjects(
			new Property( Property::TYPE_ERROR ),
			$property->getCanonicalDiWikiPage()
		);

		$appendIterator->add(
			$dataItems
		);

		return $appendIterator;
	}

	/**
	 * @since 3.0
	 *
	 * @param WikiPage $category
	 *
	 * @return Iterator
	 */
	public function findByCategory( WikiPage $category ) {
		$appendIterator = $this->iteratorFactory->newAppendIterator();

		$property = new Property( '_INST' );

		$appendIterator->add(
			$this->store->getPropertySubjects( $property, $category )
		);

		// Only direct antecedents
		$dataItems = $this->store->getPropertyValues(
			$category,
			new Property( '_SUBC' )
		);

		foreach ( $dataItems as $dataItem ) {
			$appendIterator->add(
				$this->store->getPropertySubjects( $property, $dataItem )
			);
		}

		return $appendIterator;
	}

	private function fetchOtherReferencesOnTypePropagation( Property $property ) {
		// Find other references only on a type propagation (which causes a
		// change of table/id assignments) for entity references
		if ( $this->isTypePropagation === false ) {
			return [];
		}

		$connection = $this->store->getConnection( 'mw.db' );
		$pid = $this->store->getObjectIds()->getSMWPropertyID( $property );

		$dataItemTables = $this->store->getPropertyTableInfoFetcher()->getDefaultDataItemTables();
		$idList = [];

		// Matches may temporary create duplicates in regrads to
		// Store::getAllPropertySubjects but it will be dealt with by the
		// deduplication in the ChangePropagationUpdateJob
		foreach ( $dataItemTables as $tableName ) {

			// Select any references that are hidden or remained active
			$rows = $connection->select(
				$tableName,
				[
					's_id'
				],
				[
					'p_id' => $pid
				],
				__METHOD__
			);

			foreach ( $rows as $row ) {
				$idList[] = $row->s_id;
			}
		}

		if ( $idList === [] ) {
			return $idList;
		}

		return $this->store->getObjectIds()->getDataItemPoolHashListFor( $idList );
	}

}
