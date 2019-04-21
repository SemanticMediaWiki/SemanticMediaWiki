<?php

namespace SMW\SQLStore;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\MediaWiki\Deferred\ChangeTitleUpdate;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\TableFieldUpdater;
use SMW\Store;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\EntityStore\IdChanger;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class RedirectUpdater {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var IdChanger
	 */
	private $idChanger;

	/**
	 * @var TableFieldUpdater
	 */
	private $tableFieldUpdater;

	/**
	 * @var PropertyStatisticsStore
	 */
	private $propertyStatisticsStore;

	/**
	 * @var []
	 */
	private $lookupCache = [];

	/**
	 * @var boolean
	 */
	private $hasEqualitySupport = false;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param IdChanger $idChanger
	 * @param TableFieldUpdater $tableFieldUpdater
	 * @param PropertyStatisticsStore $propertyStatisticsStore
	 */
	public function __construct( Store $store, IdChanger $idChanger, TableFieldUpdater $tableFieldUpdater, PropertyStatisticsStore $propertyStatisticsStore ) {
		$this->store = $store;
		$this->idChanger = $idChanger;
		$this->tableFieldUpdater = $tableFieldUpdater;
		$this->propertyStatisticsStore = $propertyStatisticsStore;
		$this->setEqualitySupportFlag( $GLOBALS['smwgQEqualitySupport'] );
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $equalitySupport
	 */
	public function setEqualitySupportFlag( $equalitySupport ) {
		$this->hasEqualitySupport = $equalitySupport != SMW_EQ_NONE;
	}

	/**
	 * Move all cached information about subobjects.
	 *
	 * @todo This method is neither efficient nor very convincing
	 * architecturally; it should be redesigned.
	 *
	 * @since 1.8
	 * @param string $source
	 * @param integer $oldnamespace
	 * @param string $target
	 * @param integer $newnamespace
	 */
	public function moveSubobjects( $source, $oldnamespace, $target, $newnamespace ) {

		$idTable = $this->store->getObjectIds();

		// Currently we have no way to change title and namespace across all entries.
		// Best we can do is clear the cache to avoid wrong hits:
		if ( $oldnamespace != SMW_NS_PROPERTY || $newnamespace != SMW_NS_PROPERTY ) {
			$idTable->deleteCache( $source, $oldnamespace, '', '' );
			$idTable->deleteCache( $target, $newnamespace, '', '' );
		}
	}

	/**
	 * Implementation of SMWStore::changeTitle(). In contrast to
	 * updateRedirects(), this function does not simply write a redirect
	 * from the old page to the new one, but also deletes all data that may
	 * already be stored for the new title (normally the new title should
	 * belong to an empty page that has no data but at least it could have a
	 * redirect to the old page), and moves all data that exists for the old
	 * title to the new location. Thus, the function executes three steps:
	 * delete data at newtitle, move data from oldtitle to newtitle, and set
	 * redirect from oldtitle to newtitle. In some cases, the goal can be
	 * achieved more efficiently, e.g. if the new title does not occur in SMW
	 * yet: then we can just change the ID records for the titles instead of
	 * changing all data tables
	 *
	 * Note that the implementation ignores the MediaWiki IDs since this
	 * store has its own ID management. Also, the function requires that both
	 * titles are local, i.e. have empty interwiki prefix.
	 *
	 * @todo Currently the sortkey is not moved with the remaining data. It is
	 * not possible to move it reliably in all cases: we cannot distinguish an
	 * unset sortkey from one that was set to the name of oldtitle. Maybe use
	 * update jobs right away?
	 *
	 * @since 1.8
	 *
	 * @param Title $oldTitle
	 * @param Title $newTitle
	 * @param integer $pageId
	 * @param integer $redirectId
	 */
	public function doUpdate( DIWikiPage $source, DIWikiPage $target, array $options ) {

		$idTable = $this->store->getObjectIds();
		$this->lookupCache = [];

		// get IDs but do not resolve redirects:
		$sid = $idTable->getSMWPageID(
			$source->getDBkey(),
			$source->getNamespace(),
			'',
			'',
			false
		);

		$tid = $idTable->getSMWPageID(
			$target->getDBkey(),
			$target->getNamespace(),
			'',
			'',
			false
		);

		// Easy case: target not used anywhere yet, just hijack its title for our current id
		if ( ( $tid == 0 ) && $this->hasEqualitySupport ) {
			$this->updateTarget( $source, $target, $sid );
		} else { // General move method: should always be correct
			$this->moveAsRedirect( $source, $target, $sid, $tid, $options );
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param CachingSemanticDataLookup $semanticDataLookup
	 */
	public function invalidateLookupCache( CachingSemanticDataLookup $semanticDataLookup ) {
		foreach ( $this->lookupCache as $id ) {
			$semanticDataLookup->invalidateCache( $id );
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $source
	 * @param Title $target
	 * @param array $options
	 */
	public function triggerChangeTitleUpdate( Title $source, Title $target, array $options ) {

		if ( $options['redirect_id'] == 0 ) {
			$source = null;
		}

		ChangeTitleUpdate::addUpdate( $source, $target );
	}

	/**
	 * Helper method to write information about some redirect. Various updates
	 * can be necessary if redirects are resolved as identities in SMW. The
	 * title and namespace of the affected page and of its updated redirect
	 * target are given. The target can be empty ('') to delete any redirect.
	 * Returns the canonical ID that is now to be used for the subject.
	 *
	 * This method does not change the ids of the affected pages, and thus it
	 * is not concerned with updates of the data that is currently stored for
	 * the subject. Normally, a subject that is a redirect will not have other
	 * data, but this method does not depend on this.
	 *
	 * @note Please make sure you fully understand this code before making any
	 * changes here. Keeping the redirect structure consistent is important,
	 * and errors in this code can go unnoticed for quite some time.
	 *
	 * @note This method merely handles the addition or deletion of a redirect
	 * statement in the wiki. It does not assume that any page contents has
	 * been changed (e.g. moved). See changeTitle() for additional handling in
	 * this case.
	 *
	 * @todo Clean up this code.
	 *
	 * @since 1.8
	 * @param string $subject_t
	 * @param integer $subject_ns
	 * @param string $curtarget_t
	 * @param integer $curtarget_ns
	 * @return integer the new canonical ID of the subject
	 */
	public function updateRedirects( DIWikiPage $source, DIWikiPage $target = null ) {

		// Track count changes for redi property
		$count = 0;

		$connection = $this->store->getConnection( 'mw.db' );
		$idTable = $this->store->getObjectIds();

		// First get id of subject, old redirect target, and current (new)
		// redirect target
		$sid_sort = '';

		// find real id of subject, if any
		$sid = $idTable->getSMWPageIDandSort(
			$source->getDBkey(),
			$source->getNamespace(),
			'',
			'',
			$sid_sort,
			false
		);

		// NOTE: $sid can be 0 here; this is useful to know since it means that
		// fewer table updates are needed
		if ( $target !== null ) {
			$new_tid = $idTable->makeSMWPageID(
				$target->getDBkey(),
				$target->getNamespace(),
				'',
				'',
				false
			);
		} else {
			// real id of new target, if given
			$new_tid = 0;
		}

		$old_tid = $idTable->findRedirect(
			$source->getDBkey(),
			$source->getNamespace()
		);

		// NOTE: $old_tid and $new_tid both (intentionally) ignore further
		// redirects: no redirect chains

		// No changes required?
		if ( $old_tid == $new_tid ) {
			return ( $new_tid == 0 ) ? $sid : $new_tid;
		}

		// It means $old_tid != $new_tid in all cases below
		// Make relevant changes in property tables (don't write the new
		// redirect yet)
		if ( ( $old_tid == 0 ) && ( $sid != 0 ) && $this->hasEqualitySupport ) { // new redirect
			// $smwgQEqualitySupport requires us to change all tables' page references from $sid to $new_tid.
			// Since references must not be 0, we don't have to do this is $sid == 0.
			$this->idChanger->change(
				$sid,
				$new_tid,
				$source->getNamespace(),
				$target->getNamespace(),
				false,
				true
			);

		} elseif ( $old_tid != 0 ) { // existing redirect is changed or deleted

			$count--;

			$idTable->updateRedirect(
				$old_tid,
				$source->getDBkey(),
				$source->getNamespace()
			);
		}

		// Write the new redirect data

		if ( $new_tid != 0 ) { // record a new redirect
			// Redirecting done right:
			// (1) make a new ID with iw SMW_SQL3_SMWREDIIW or
			//     change iw field of current ID in this way,
			// (2) write smw_fpt_redi table,
			// (3) update canonical cache.
			// This order must be obeyed unless you really understand what you are doing!

			if ( ( $old_tid == 0 ) && $this->hasEqualitySupport ) {
				// mark subject as redirect (if it was no redirect before)
				if ( $sid == 0 ) { // every redirect page must have an ID
					$sid = $idTable->makeSMWPageID(
						$source->getDBkey(),
						$source->getNamespace(),
						SMW_SQL3_SMWREDIIW,
						'',
						false
					);
				} else {
					$sha1 = $idTable->computeSha1(
						[ $source->getDBkey(), $source->getNamespace(), SMW_SQL3_SMWREDIIW , '' ]
					);

					$this->tableFieldUpdater->updateIwField(
						$sid,
						SMW_SQL3_SMWREDIIW,
						$sha1
					);

					$idTable->setCache(
						$source->getDBkey(),
						$source->getNamespace(),
						'',
						'',
						0,
						''
					);

					$idTable->setCache(
						$source->getDBkey(),
						$source->getNamespace(),
						SMW_SQL3_SMWREDIIW,
						'',
						$sid,
						$sid_sort
					);
				}
			}

			$idTable->addRedirect(
				$new_tid,
				$source->getDBkey(),
				$source->getNamespace()
			);

			$count++;

		} else { // delete old redirect
			// This case implies $old_tid != 0 (or we would have new_tid == old_tid above).
			// Therefore $subject had a redirect, and it must also have an ID.
			// This shows that $sid != 0 here.
			if ( $this->hasEqualitySupport ) { // mark subject as non-redirect

				$sha1 = $idTable->computeSha1(
					[ $source->getDBkey(), $source->getNamespace(), '' , '' ]
				);

				$this->tableFieldUpdater->updateIwField(
					$sid,
					'',
					$sha1
				);

				$idTable->setCache(
					$source->getDBkey(),
					$source->getNamespace(),
					SMW_SQL3_SMWREDIIW,
					'',
					0,
					''
				);

				$idTable->setCache(
					$source->getDBkey(),
					$source->getNamespace(),
					'',
					'',
					$sid,
					$sid_sort
				);
			}
		}

		// Flush some caches to be safe, though they are not essential in runs
		// with redirect updates
		$this->lookupCache = [ $sid, $new_tid, $old_tid ];

		$this->propertyStatisticsStore->addToUsageCount(
			$idTable->getSMWPropertyID( new DIProperty( '_REDI' ) ),
			$count
		);

		return ( $new_tid == 0 ) ? $sid : $new_tid;
	}

	private function updateTarget( $source, $target, &$sid ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$idTable = $this->store->getObjectIds();

		// This condition may not hold even if $target is
		// currently unused/non-existing since we keep old IDs.
		// If equality support is off, then this simple move
		// does too much; fall back to general case below.
		if ( $sid != 0 ) { // change id entry to refer to the new title
			// Note that this also changes the reference for internal objects (subobjects)
			$connection->update(
				SQLStore::ID_TABLE,
				[
					'smw_title' => $target->getDBkey(),
					'smw_namespace' => $target->getNamespace(),
					'smw_iw' => ''
				],
				[
					'smw_title' => $source->getDBkey(),
					'smw_namespace' => $source->getNamespace(),
					'smw_iw' => ''
				],
				__METHOD__
			);

			$this->moveSubobjects(
				$source->getDBkey(),
				$source->getNamespace(),
				$target->getDBkey(),
				$target->getNamespace()
			);

			$idTable->setCache(
				$source->getDBkey(),
				$source->getNamespace(),
				'',
				'',
				0,
				''
			);

			// We do not know the new sortkey, so just clear the cache:
			$idTable->deleteCache(
				$target->getDBkey(),
				$target->getNamespace(),
				'',
				''
			);

		} else { // make new (target) id for use in redirect table
			$sid = $idTable->makeSMWPageID(
				$target->getDBkey(),
				$target->getNamespace(),
				'',
				''
			);
		}

		// At this point, $sid is the id of the target page (according to the IDs table)
		// make redirect id for source:
		$idTable->makeSMWPageID(
			$source->getDBkey(),
			$source->getNamespace(),
			SMW_SQL3_SMWREDIIW,
			''
		);

		 $idTable->addRedirect(
			$sid,
			$source->getDBkey(),
			$source->getNamespace()
		);

		$this->propertyStatisticsStore->addToUsageCount(
			$idTable->getSMWPropertyID( new DIProperty( '_REDI' ) ),
			1
		);

		// NOTE: there is the (bad) case that the moved page is a redirect. As chains of
		// redirects are not supported by MW or SMW, the above is maximally correct in this case too.
		// NOTE: this temporarily leaves existing redirects to source point to target as well, which
		// will be lost after the next update. Since double redirects are an error anyway, this is not
		// a bad behavior: everything will continue to work until the existing redirects are updated,
		// which will hopefully be done to fix the double redirect.
	}

	private function moveAsRedirect( $source, $target, $sid, $tid, $options ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$idTable = $this->store->getObjectIds();

		// (equality support respected when updating redirects)
		// Delete any existing data (including redirects) from old title
		$this->store->clearData( $source );

		// Move all data of old title to new position:
		if ( $sid != 0 ) {
			$this->idChanger->change(
				$sid,
				$tid,
				$source->getNamespace(),
				$target->getNamespace(),
				true,
				false
			);
		}

		// Associate internal objects (subobjects) with the new title:
		$table = $connection->tableName( SQLStore::ID_TABLE );

		$values = [
			'smw_title' => $target->getDBkey(),
			'smw_namespace' => $target->getNamespace(),
			'smw_iw' => ''
		];

		$sql = "UPDATE $table SET " . $connection->makeList( $values, LIST_SET ) .
			' WHERE smw_title = ' . $connection->addQuotes( $source->getDBkey() ) . ' AND ' .
			'smw_namespace = ' . $connection->addQuotes( $source->getNamespace() ) . ' AND ' .
			'smw_iw = ' . $connection->addQuotes( '' ) . ' AND ' .
			'smw_subobject != ' . $connection->addQuotes( '' ); // The "!=" is why we cannot use MW array syntax here

		$connection->query( $sql, __METHOD__ );

		$this->moveSubobjects(
			$source->getDBkey(),
			$source->getNamespace(),
			$target->getDBkey(),
			$target->getNamespace()
		);

		// `redirect_id` == 0 means that the source was not supposed to be a redirect
		// (source is delete from the db) but instead of deleting all
		// references we will still copy data from old to new during updateRedirects()
		// and clear the semantic data container for the source instance
		// to ensure that no ghost references exists for an deleted source
		// @see Title::moveTo(), createRedirect
		if ( $options['redirect_id'] == 0 ) {
			// Delete any existing data (including redirects) from old title
			$this->updateRedirects( $source );
		} else {
			// Write a redirect from old title to new one:
			// (this also updates references in other tables as needed.)
			// TODO: may not be optimal for the standard case that target
			// existed and redirected to source (PERFORMANCE)
			$this->updateRedirects( $source, $target );
		}
	}

}
