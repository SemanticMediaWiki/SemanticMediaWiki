<?php

namespace SMW\SQLStore;

use SMW\EventHandler;
use SMW\DIWikiPage;
use SMW\Iterators\ResultIterator;
use SMW\ApplicationFactory;

/**
 * @private
 *
 * Class responsible for the clean-up (aka disposal) of any outdated table entries
 * that are contained in either the ID_TABLE or related property tables with
 * reference to a matchable ID.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableIdReferenceDisposer {

	/**
	 * @var SQLStore
	 */
	private $store = null;

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var boolean
	 */
	private $onTransactionIdle = false;

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->connection = $this->store->getConnection( 'mw.db' );
	}

	/**
	 * @note MW 1.29+ showed transaction collisions when executed using the
	 * JobQueue in connection with purging the BagOStuff cache, use
	 * 'onTransactionIdle' to isolate the execution for some of the tasks.
	 *
	 * @since 2.5
	 */
	public function waitOnTransactionIdle() {
		$this->onTransactionIdle = true;
	}

	/**
	 * Use case: After a property changed its type (_wpg -> _txt), object values in the
	 * ID table are not removed at the time of the conversion process.
	 *
	 * Before an attempt to remove the ID from entity tables, it is secured that no
	 * references exists for the ID.
	 *
	 * @note This method does not check for an ID being object or subject value
	 * and has to be done prior calling this routine.
	 *
	 * @since 2.4
	 *
	 * @param integer $id
	 */
	public function removeOutdatedEntityReferencesById( $id ) {

		if ( $this->store->getPropertyTableIdReferenceFinder()->hasResidualReferenceForId( $id ) ) {
			return null;
		}

		$this->doRemoveEntityReferencesById( $id );
	}

	/**
	 * @since 2.5
	 *
	 * @return ResultIterator
	 */
	public function newOutdatedEntitiesResultIterator() {

		$res = $this->connection->select(
			SQLStore::ID_TABLE,
			array( 'smw_id' ),
			array( 'smw_iw' => SMW_SQL3_SMWDELETEIW ),
			__METHOD__
		);

		return new ResultIterator( $res );
	}

	/**
	 * @since 2.5
	 *
	 * @param stdClass $row
	 */
	public function cleanUpTableEntriesByRow( $row ) {

		if ( !isset( $row->smw_id ) ) {
			return;
		}

		$this->cleanUpTableEntriesById( $row->smw_id );
	}

	/**
	 * @note This method does not make any assumption about the ID state and therefore
	 * has to be validated before this method is called.
	 *
	 * @since 2.4
	 *
	 * @param integer $id
	 */
	public function cleanUpTableEntriesById( $id ) {

		$subject = $this->store->getObjectIds()->getDataItemById( $id );

		if ( $subject instanceof DIWikiPage ) {
			// Use the subject without an internal 'smw-delete' iw marker
			$subject = new DIWikiPage(
				$subject->getDBKey(),
				$subject->getNamespace(),
				'',
				$subject->getSubobjectName()
			);
		}

		$this->triggerCleanUpEvents( $subject, $this->onTransactionIdle );

		$this->connection->beginAtomicTransaction( __METHOD__ );

		foreach ( $this->store->getPropertyTables() as $proptable ) {
			if ( $proptable->usesIdSubject() ) {
				$this->connection->delete(
					$proptable->getName(),
					array( 's_id' => $id ),
					__METHOD__
				);
			}

			if ( !$proptable->isFixedPropertyTable() ) {
				$this->connection->delete(
					$proptable->getName(),
					array( 'p_id' => $id ),
					__METHOD__
				);
			}

			$fields = $proptable->getFields( $this->store );

			// Match tables (including ftp_redi) that contain an object reference
			if ( isset( $fields['o_id'] ) ) {
				$this->connection->delete(
					$proptable->getName(),
					array( 'o_id' => $id ),
					__METHOD__
				);
			}
		}

		$this->doRemoveEntityReferencesById( $id );
		$this->connection->endAtomicTransaction( __METHOD__ );
	}

	private function doRemoveEntityReferencesById( $id ) {

		$this->connection->delete(
			SQLStore::ID_TABLE,
			array( 'smw_id' => $id ),
			__METHOD__
		);

		$this->connection->delete(
			SQLStore::PROPERTY_STATISTICS_TABLE,
			array( 'p_id' => $id ),
			__METHOD__
		);

		$this->connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			array( 's_id' => $id ),
			__METHOD__
		);

		$this->connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			array( 'o_id' => $id ),
			__METHOD__
		);

		// Avoid Query: DELETE FROM `smw_ft_search` WHERE s_id = '92575'
		// Error: 126 Incorrect key file for table '.\mw@002d25@002d01\smw_ft_search.MYI'; ...
		try {
			$this->connection->delete(
				SQLStore::FT_SEARCH_TABLE,
				array( 's_id' => $id ),
				__METHOD__
			);
		} catch ( \DBError $e ) {
			ApplicationFactory::getInstance()->getMediaWikiLogger()->info( __METHOD__ . ' reported: ' . $e->getMessage() );
		}
	}

	private function triggerCleanUpEvents( $subject, $onTransactionIdle ) {

		if ( !$subject instanceof DIWikiPage ) {
			return;
		}

		if ( $onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function() use( $subject ) {
				$this->triggerCleanUpEvents( $subject, false );
			} );
		}

		$eventHandler = EventHandler::getInstance();

		$dispatchContext = $eventHandler->newDispatchContext();
		$dispatchContext->set( 'subject', $subject->asBase() );
		$dispatchContext->set( 'context', 'PropertyTableIdReferenceDisposal' );

		$eventHandler->getEventDispatcher()->dispatch(
			'cached.prefetcher.reset',
			$dispatchContext
		);

		$eventHandler->getEventDispatcher()->dispatch(
			'factbox.cache.delete',
			$dispatchContext
		);

		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$eventHandler->getEventDispatcher()->dispatch(
				'property.specification.change',
				$dispatchContext
			);
		}
	}

}
