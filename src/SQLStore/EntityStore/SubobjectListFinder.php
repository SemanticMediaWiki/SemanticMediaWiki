<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\SQLStore\SQLStore;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SubobjectListFinder {

	/**
	 * @var WikiPage
	 */
	private $subject;

	/**
	 * @var
	 */
	private $mappingIterator = [];

	/**
	 * @var
	 */
	private $skipConditions = [];

	/**
	 * @since 2.5
	 */
	public function __construct(
		private readonly SQLStore $store,
		private readonly IteratorFactory $iteratorFactory,
	) {
	}

	/**
	 * @since 3.0
	 *
	 * @param WikiPage $subject
	 *
	 * @return MappingIterator
	 */
	public function find( WikiPage $subject ) {
		$key = $subject->getHash() . ':' . $subject->getId();

		if ( !isset( $this->mappingIterator[$key] ) ) {
			$this->mappingIterator[$key] = $this->newMappingIterator( $subject );
		}

		return $this->mappingIterator[$key];
	}

	/**
	 * Fetch all subobjects for a given subject using a lazy-mapping iterator
	 * in order to only resolve one subobject per iteration step.
	 *
	 * @since 2.5
	 *
	 * @param WikiPage $subject
	 *
	 * @return MappingIterator
	 */
	private function newMappingIterator( WikiPage $subject ) {
		$callback = static function ( $row ) use ( $subject ) {
			// #1955
			if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
				$property = new Property( $subject->getDBkey() );
				$subobject = $property->getCanonicalDiWikiPage( $row->smw_subobject );
			} else {
				$subobject = new WikiPage(
					$subject->getDBkey(),
					$subject->getNamespace(),
					$subject->getInterwiki(),
					$row->smw_subobject
				);
			}

			$subobject->setSortKey( $row->smw_sortkey );
			$subobject->setId( $row->smw_id );

			return $subobject;
		};

		return $this->iteratorFactory->newMappingIterator(
			$this->newResultIterator( $subject ),
			$callback
		);
	}

	private function newResultIterator( WikiPage $subject ) {
		$connection = $this->store->getConnection( 'mw.db' );
		$key = $subject->getDBkey();

		// #1955 Ensure to match a possible predefined property
		// (Modification date -> _MDAT)
		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$key = Property::newFromUserLabel( $key )->getKey();
		}

		$conditions = [
			'smw_title=' . $connection->addQuotes( $key ),
			'smw_namespace=' . $connection->addQuotes( $subject->getNamespace() ),
			'smw_iw=' . $connection->addQuotes( $subject->getInterwiki() ),
			'smw_subobject!=' . $connection->addQuotes( '' )
		];

		foreach ( $this->skipConditions as $skipOn ) {
			$conditions[] = 'smw_subobject!=' . $connection->addQuotes( $skipOn );
		}

		$res = $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_subobject',
				'smw_sortkey'
			],
			implode( ' AND ', $conditions ),
			__METHOD__
		);

		return $this->iteratorFactory->newResultIterator( $res );
	}

}
