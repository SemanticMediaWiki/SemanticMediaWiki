<?php

namespace SMW\SQLStore;

use SMW\EventHandler;
use SMW\DIWikiPage;

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
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->connection = $this->store->getConnection( 'mw.db' );
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
	 * @note This method does not make any assumption about the ID state and therefore
	 * has to be validated before this method is called.
	 *
	 * @since 2.4
	 *
	 * @param integer $id
	 */
	public function cleanUpTableEntriesById( $id ) {

		$this->triggerResetCacheEventBy( $id );

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
			wfDebugLog( 'smw', __METHOD__ . ' reported: ' . $e->getMessage() );
		}
	}

	private function triggerResetCacheEventBy( $id ) {

		$subject = $this->store->getObjectIds()->getDataItemById( $id );

		if ( !$subject instanceof DIWikiPage ) {
			return;
		}

		$eventHandler = EventHandler::getInstance();

		$dispatchContext = $eventHandler->newDispatchContext();
		$dispatchContext->set( 'subject', $subject );

		$eventHandler->getEventDispatcher()->dispatch(
			'cached.propertyvalues.prefetcher.reset',
			$dispatchContext
		);

		$eventHandler->getEventDispatcher()->dispatch(
			'property.specification.change',
			$dispatchContext
		);

		$eventHandler->getEventDispatcher()->dispatch(
			'factbox.cache.delete',
			$dispatchContext
		);
	}

}
