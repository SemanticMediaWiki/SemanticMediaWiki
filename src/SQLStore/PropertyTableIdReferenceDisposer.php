<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\EventHandler;
use SMW\Iterators\ResultIterator;

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
	 * @var boolean
	 */
	private $redirectRemoval = false;

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
	 * @since 3.0
	 *
	 * @param boolean $redirectRemoval
	 */
	public function setRedirectRemoval( $redirectRemoval ) {
		$this->redirectRemoval = $redirectRemoval;
	}

	/**
	 * @since 2.5
	 */
	public function waitOnTransactionIdle() {
		$this->onTransactionIdle = true;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public function isDisposable( $id ) {
		return $this->store->getPropertyTableIdReferenceFinder()->hasResidualReferenceForId( $id ) === false;
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

		$this->cleanUpSecondaryReferencesById( $id, false );
	}

	/**
	 * @since 2.5
	 *
	 * @return ResultIterator
	 */
	public function newOutdatedEntitiesResultIterator() {

		$res = $this->connection->select(
			SQLStore::ID_TABLE,
			[ 'smw_id' ],
			[ 'smw_iw' => SMW_SQL3_SMWDELETEIW ],
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

		if ( $this->onTransactionIdle ) {
			return $this->connection->onTransactionIdle( function() use ( $id ) {
				$this->cleanUpReferencesById( $id );
			} );
		} else {
			$this->cleanUpReferencesById( $id );
		}
	}

	private function cleanUpReferencesById( $id ) {

		$subject = $this->store->getObjectIds()->getDataItemById( $id );
		$isRedirect = false;

		if ( $subject instanceof DIWikiPage ) {
			$isRedirect = $subject->getInterwiki() === SMW_SQL3_SMWREDIIW;

			// Use the subject without an internal 'smw-delete' iw marker
			$subject = new DIWikiPage(
				$subject->getDBKey(),
				$subject->getNamespace(),
				'',
				$subject->getSubobjectName()
			);
		}

		$this->triggerCleanUpEvents( $subject );

		$this->connection->beginAtomicTransaction( __METHOD__ );

		foreach ( $this->store->getPropertyTables() as $proptable ) {
			if ( $proptable->usesIdSubject() ) {
				$this->connection->delete(
					$proptable->getName(),
					[ 's_id' => $id ],
					__METHOD__
				);
			}

			if ( !$proptable->isFixedPropertyTable() ) {
				$this->connection->delete(
					$proptable->getName(),
					[ 'p_id' => $id ],
					__METHOD__
				);
			}

			$fields = $proptable->getFields( $this->store );

			// Match tables (including ftp_redi) that contain an object reference
			if ( isset( $fields['o_id'] ) ) {
				$this->connection->delete(
					$proptable->getName(),
					[ 'o_id' => $id ],
					__METHOD__
				);
			}
		}

		$this->cleanUpSecondaryReferencesById( $id, $isRedirect );
		$this->connection->endAtomicTransaction( __METHOD__ );

		\Hooks::run(
			'SMW::SQLStore::EntityReferenceCleanUpComplete',
			[ $this->store, $id, $subject, $isRedirect ]
		);
	}

	private function cleanUpSecondaryReferencesById( $id, $isRedirect ) {

		// When marked as redirect, don't remove the reference
		if ( $isRedirect === false || ( $isRedirect && $this->redirectRemoval ) ) {
			$this->connection->delete(
				SQLStore::ID_TABLE,
				[ 'smw_id' => $id ],
				__METHOD__
			);
		}

		$this->connection->delete(
			SQLStore::ID_AUXILIARY_TABLE,
			[ 'smw_id' => $id ],
			__METHOD__
		);

		$this->connection->delete(
			SQLStore::PROPERTY_STATISTICS_TABLE,
			[ 'p_id' => $id ],
			__METHOD__
		);

		$this->connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			[ 's_id' => $id ],
			__METHOD__
		);

		$this->connection->delete(
			SQLStore::QUERY_LINKS_TABLE,
			[ 'o_id' => $id ],
			__METHOD__
		);

		// Avoid Query: DELETE FROM `smw_ft_search` WHERE s_id = '92575'
		// Error: 126 Incorrect key file for table '.\mw@002d25@002d01\smw_ft_search.MYI'; ...
		try {
			if ( $this->connection->tableExists( SQLStore::FT_SEARCH_TABLE ) ) {
				$this->connection->delete( SQLStore::FT_SEARCH_TABLE, [ 's_id' => $id ], __METHOD__ );
			}
		} catch ( \DBError $e ) {
			ApplicationFactory::getInstance()->getMediaWikiLogger()->info( __METHOD__ . ' reported: ' . $e->getMessage() );
		}
	}

	private function triggerCleanUpEvents( $subject ) {

		if ( !$subject instanceof DIWikiPage ) {
			return;
		}

		// Skip any reset for subobjects where it is expected that the base
		// subject is cleaning up all related cache entries
		if ( $subject->getSubobjectName() !== '' ) {
			return;
		}

		$eventHandler = EventHandler::getInstance();
		$eventDispatcher = $eventHandler->getEventDispatcher();

		$context = [
			'context' => 'PropertyTableIdReferenceDisposal',
			'title' => $subject->getTitle(),
			'subject' => $subject
		];

		$eventDispatcher->dispatch( 'InvalidateResultCache', $context );
		$eventDispatcher->dispatch( 'InvalidateEntityCache', $context );
	}

}
