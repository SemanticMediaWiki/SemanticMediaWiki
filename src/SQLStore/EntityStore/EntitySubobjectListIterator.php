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
	 * @var MappingIterator
	 */
	private $mappingIterator;

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
	 * @see IteratorAggregate::getIterator
	 *
	 * @since 2.5
	 *
	 * @return Iterator
	 */
	public function getIterator() {

		if ( $this->mappingIterator === null ) {
			throw new RuntimeException( "MappingIterator is not initialized" );
		}

		return $this->mappingIterator;
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
	public function newMappingIterator( DIWikiPage $subject ) {

		$diHandler = $this->store->getDataItemHandlerForDIType(
			DataItem::TYPE_WIKIPAGE
		);

		$callback = function( $row ) use ( $subject, $diHandler ) {

			$subobject = $diHandler->dataItemFromDBKeys( array(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				$row->smw_sortkey,
				$row->smw_subobject
			) );

			$subobject->setId( $row->smw_id );

			return $subobject;
		};

		return $this->mappingIterator = $this->iteratorFactory->newMappingIterator(
			$this->newResultIterator( $subject ),
			$callback
		);
	}

	private function newResultIterator( DIWikiPage $subject ) {

		$connection = $this->store->getConnection();
		$dbKey = $subject->getDBkey();

		// #1955 Ensure to match a possible predefined property
		// (Modification date -> _MDAT)
		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$dbKey = DIProperty::newFromUserLabel( $subject->getDBkey() )->getKey();
		}

		$res = $connection->select(
			$connection->tablename( SQLStore::ID_TABLE ),
			array( 'smw_id' , 'smw_subobject' , 'smw_sortkey' ),
			'smw_title = ' . $connection->addQuotes( $dbKey ) . ' AND ' .
			'smw_namespace = ' . $connection->addQuotes( $subject->getNamespace() ) . ' AND ' .
			'smw_iw = ' . $connection->addQuotes( $subject->getInterwiki() ) . ' AND ' .
			'smw_subobject != ' . $connection->addQuotes( '' ), // The "!=" is why we cannot use MW array syntax here
			__METHOD__
		);

		return $this->iteratorFactory->newResultIterator( $res );
	}

}
