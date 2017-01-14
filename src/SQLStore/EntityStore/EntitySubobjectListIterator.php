<?php

namespace SMW\SQLStore\EntityStore;

use SMW\IteratorFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDataItem as DataItem;
use SMW\SQLStore\SQLStore;
use IteratorAggregate;
use RuntimeException;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EntitySubobjectListIterator implements IteratorAggregate {

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
	 * @var string|null
	 */
	private $skipOn = array();

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
	 * @since 2.5
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return Iterator
	 */
	public function newListIteratorFor( DIWikiPage $subject, $skipOn = array() ) {
		$this->skipOn = $skipOn;
		$this->subject = $subject;

		return $this->getIterator();
	}

	/**
	 * @see IteratorAggregate::getIterator
	 *
	 * @since 2.5
	 *
	 * @return Iterator
	 */
	public function getIterator() {

		if ( $this->subject === null ) {
			throw new RuntimeException( "Subject is not initialized" );
		}

		return $this->newMappingIterator( $this->subject );
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
		$dbKey = $subject->getDBkey();

		// #1955 Ensure to match a possible predefined property
		// (Modification date -> _MDAT)
		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$dbKey = DIProperty::newFromUserLabel( $subject->getDBkey() )->getKey();
		}

		$condition = 'smw_title = ' . $connection->addQuotes( $dbKey ) . ' AND ' .
			'smw_namespace = ' . $connection->addQuotes( $subject->getNamespace() ) . ' AND ' .
			'smw_iw = ' . $connection->addQuotes( $subject->getInterwiki() ) . ' AND ' .
			// The "!=" is why we cannot use MW array syntax here
			'smw_subobject != ' . $connection->addQuotes( '' );

		foreach ( $this->skipOn as $skipOn ) {
			$condition .= ' AND smw_subobject != ' . $connection->addQuotes( $skipOn );
		}

		$res = $connection->select(
			$connection->tablename( SQLStore::ID_TABLE ),
			array(
				'smw_id',
				'smw_subobject',
				'smw_sortkey'
			),
			$condition,
			__METHOD__
		);

		return $this->iteratorFactory->newResultIterator( $res );
	}

}
