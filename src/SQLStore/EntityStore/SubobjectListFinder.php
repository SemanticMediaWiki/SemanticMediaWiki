<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\IteratorFactory;
use SMW\SQLStore\SQLStore;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SubobjectListFinder {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @var []
	 */
	private $mappingIterator = [];

	/**
	 * @var []
	 */
	private $skipConditions = [];

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 * @param IteratorFactory $iteratorFactory
	 */
	public function __construct( SQLStore $store, IteratorFactory $iteratorFactory ) {
		$this->store = $store;
		$this->iteratorFactory = $iteratorFactory;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return MappingIterator
	 */
	public function find( DIWikiPage $subject ) {

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
	 * @param DIWikiPage $subject
	 *
	 * @return MappingIterator
	 */
	private function newMappingIterator( DIWikiPage $subject ) {

		$callback = function( $row ) use ( $subject ) {

			// #1955
			if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
				$property = new DIProperty( $subject->getDBkey() );
				$subobject = $property->getCanonicalDiWikiPage( $row->smw_subobject );
			} else {
				$subobject = new DIWikiPage(
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

	private function newResultIterator( DIWikiPage $subject ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$key = $subject->getDBkey();

		// #1955 Ensure to match a possible predefined property
		// (Modification date -> _MDAT)
		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$key = DIProperty::newFromUserLabel( $key )->getKey();
		}

		$conditions = [
			'smw_title='      . $connection->addQuotes( $key ),
			'smw_namespace='  . $connection->addQuotes( $subject->getNamespace() ),
			'smw_iw='         . $connection->addQuotes( $subject->getInterwiki() ),
			'smw_subobject!=' . $connection->addQuotes( '' )
		];

		foreach ( $this->skipConditions as $skipOn ) {
			$conditions[] = 'smw_subobject!=' . $connection->addQuotes( $skipOn );
		}

		$res = $connection->select(
			$connection->tablename( SQLStore::ID_TABLE ),
			[
				'smw_id',
				'smw_subobject',
				'smw_sortkey'
			],
			implode( ' AND ' , $conditions ),
			__METHOD__
		);

		return $this->iteratorFactory->newResultIterator( $res );
	}

}
