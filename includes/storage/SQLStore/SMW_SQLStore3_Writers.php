<?php

use SMW\ApplicationFactory;
use SMW\ChangePropListener;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\Deferred\ChangeTitleUpdate;
use SMW\SemanticData;
use SMW\Parameters;
use SMW\SQLStore\PropertyStatisticsTable;
use SMW\SQLStore\PropertyTableRowDiffer;

/**
 * Class Handling all the write and update methods for SMWSQLStore3.
 *
 * @note Writing may also require some reading operations. Operations that are
 * only needed in helper methods of this class should be implemented here, not
 * in SMWSQLStore3Readers.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since 1.8
 * @ingroup SMWStore
 */
class SMWSQLStore3Writers {

	/**
	 * The store used by this store writer.
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	protected $store;

	/**
	 * @var SQLStoreFactory
	 */
	private $factory;

	/**
	 * @var PropertyTableRowDiffer
	 */
	private $propertyTableRowDiffer;

	/**
	 * @var PropertyTableUpdater
	 */
	private $propertyTableUpdater;

	/**
	 * @var SemanticDataLookup
	 */
	private $semanticDataLookup;

	/**
	 * @var IdChanger
	 */
	private $idChanger;

	/**
	 * @since 1.8
	 *
	 * @param SMWSQLStore3 $parentStore
	 * @param SQLStoreFactory $factory
	 */
	public function __construct( SMWSQLStore3 $parentStore, $factory ) {
		$this->store = $parentStore;
		$this->factory = $factory;
		$this->propertyTableRowDiffer = $this->factory->newPropertyTableRowDiffer();
		$this->propertyTableUpdater = $this->factory->newPropertyTableUpdater();
		$this->semanticDataLookup = $this->factory->newSemanticDataLookup();
		$this->idChanger = $this->factory->newIdChanger();
	}

	/**
	 * @see SMWStore::deleteSubject
	 *
	 * @since 1.8
	 * @param Title $title
	 */
	public function deleteSubject( Title $title ) {

		// @deprecated since 2.1, use 'SMW::SQLStore::BeforeDeleteSubjectComplete'
		\Hooks::run( 'SMWSQLStore3::deleteSubjectBefore', [ $this->store, $title ] );

		\Hooks::run( 'SMW::SQLStore::BeforeDeleteSubjectComplete', [ $this->store, $title ] );

		// Fetch all possible matches (including any duplicates created by
		// incomplete rollback or DB deadlock)
		$idList = $this->store->getObjectIds()->findAllEntitiesThatMatch(
			$title->getDBkey(),
			$title->getNamespace()
		);

		$extensionList = array_flip( $idList );
		$subject = DIWikiPage::newFromTitle( $title );

		$emptySemanticData = new SemanticData( $subject );
		$emptySemanticData->setOption( SemanticData::PROC_DELETE, true );

		$subobjectListFinder = $this->factory->newSubobjectListFinder();

		foreach ( $idList as $id ) {
			$this->doDelete( $id, $subject, $subobjectListFinder, $extensionList );
			$this->doDataUpdate( $emptySemanticData );

			if ( $this->store->service( 'PropertyTableIdReferenceFinder' )->hasResidualPropertyTableReference( $id ) === false ) {
				// Mark subject/subobjects with a special IW, the final removal is being
				// triggered by the `EntityRebuildDispatcher`
				$this->store->getObjectIds()->updateInterwikiField(
					$id,
					$subject,
					SMW_SQL3_SMWDELETEIW
				);
			} else {
				// Convert the subject into a simple object instance
				$this->store->getObjectIds()->setPropertyTableHashes(
					$id,
					null
				);
			}
		}

		$extensionList = array_keys( $extensionList );

		$this->store->extensionData['delete.list'] = $extensionList;

		// @deprecated since 2.1, use 'SMW::SQLStore::AfterDeleteSubjectComplete'
		\Hooks::run( 'SMWSQLStore3::deleteSubjectAfter', [ $this->store, $title ] );

		\Hooks::run( 'SMW::SQLStore::AfterDeleteSubjectComplete', [ $this->store, $title ] );
	}

	private function doDelete( $id, $subject, $subobjectListFinder, &$extensionList ) {

		$this->semanticDataLookup->invalidateCache( $id );

		if ( $subject->getNamespace() === SMW_NS_CONCEPT ) { // make sure to clear caches
			$db = $this->store->getConnection();

			$db->delete(
				SMWSQLStore3::CONCEPT_TABLE,
				[ 's_id' => $id ],
				'SMW::deleteSubject::Conc'
			);

			$db->delete(
				SMWSQLStore3::CONCEPT_CACHE_TABLE,
				[ 'o_id' => $id ],
				'SMW::deleteSubject::Conccache'
			);
		}

		$subject->setId( $id );

		foreach( $subobjectListFinder->find( $subject ) as $subobject ) {
			$extensionList[$subobject->getId()] = true;

			$this->store->getObjectIds()->updateInterwikiField(
				$subobject->getId(),
				$subobject,
				SMW_SQL3_SMWDELETEIW
			);
		}
	}

	/**
	 * @see SMWStore::doDataUpdate
	 *
	 * @since 1.8
	 * @param SMWSemanticData $data
	 */
	public function doDataUpdate( SMWSemanticData $semanticData ) {
		\Hooks::run( 'SMWSQLStore3::updateDataBefore', [ $this->store, $semanticData ] );

		$subject = $semanticData->getSubject();

		$connection = $this->store->getConnection( 'mw.db' );

		// MW 1.33+
		$connection->beginSectionTransaction( __METHOD__ );

		$subobjectListFinder = $this->factory->newSubobjectListFinder();

		$changeOp = $this->factory->newChangeOp(
			$subject
		);

		$this->propertyTableRowDiffer->setChangeOp(
			$changeOp
		);

		$changePropListener = $this->factory->newChangePropListener();
		$hierarchyLookup = $this->factory->newHierarchyLookup();

		// #2698
		$hierarchyLookup->addListenersTo(
			$changePropListener
		);

		$changePropListener->loadListeners(
			$this->store
		);

		if ( $semanticData->getOption( SemanticData::OPT_CHECK_REMNANT_ENTITIES ) ) {
			$this->propertyTableRowDiffer->checkRemnantEntities( true );
		}

		// Update data about our main subject
		$this->doFlatDataUpdate( $semanticData );
		$sid = $subject->getId();

		// Update data about our subobjects
		$subSemanticData = $semanticData->getSubSemanticData();
		$connection = $this->store->getConnection( 'mw.db' );

		foreach( $subSemanticData as $subobjectData ) {
			$this->doFlatDataUpdate( $subobjectData );
		}

		$deleteList = [];

		// Mark subobjects without reference to be deleted
		foreach( $subobjectListFinder->find( $subject ) as $subobject ) {
			if( !$semanticData->hasSubSemanticData( $subobject->getSubobjectName() ) ) {

				$this->doFlatDataUpdate( new SemanticData( $subobject ) );
				$deleteList[] = $subobject->getId();

				$this->store->getObjectIds()->updateInterwikiField(
					$subobject->getId(),
					$subobject,
					SMW_SQL3_SMWDELETEIW
				);
			}
		}

		if ( ( $rev_id = $semanticData->getExtensionData( 'revision_id' ) ) !== null ) {
			$this->store->getObjectIds()->updateRevField( $sid, $rev_id );
		}

		// Store the diff in cache so any post processing has a chance to find
		// what entities and values were changed
		$changeDiff = $changeOp->newChangeDiff();
		$changeDiff->save( ApplicationFactory::getInstance()->getCache() );

		$changePropListener->callListeners();

		$this->store->extensionData['delete.list'] = $deleteList;
		$this->store->extensionData['change.diff'] = $changeDiff;

		// Deprecated since 2.3, use SMW::SQLStore::AfterDataUpdateComplete
		\Hooks::run( 'SMWSQLStore3::updateDataAfter', [ $this->store, $semanticData ] );

		\Hooks::run( 'SMW::SQLStore::AfterDataUpdateComplete', [
			$this->store,
			$semanticData,
			$changeOp
		] );

		$connection->endSectionTransaction( __METHOD__ );
	}

	/**
	 * Update the store to contain the given data, without taking any
	 * subobject data into account.
	 *
	 * @since 1.8
	 * @param SMWSemanticData $data
	 */
	protected function doFlatDataUpdate( SMWSemanticData $data ) {
		$subject = $data->getSubject();

		// Take care of redirects
		$redirects = $data->getPropertyValues( new SMW\DIProperty( '_REDI' ) );

		if ( count( $redirects ) > 0 ) {
			$redirect = end( $redirects ); // at most one redirect per page
			$this->updateRedirects(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$redirect->getDBkey(),
				$redirect->getNameSpace()
			);
			// Stop here:
			// * no support for annotations on redirect pages
			// * updateRedirects takes care of deleting any previous data
			return;
		} else {
			$this->updateRedirects(
				$subject->getDBkey(),
				$subject->getNamespace()
			);
		}

		// Find an approriate sortkey, the field is influenced by various
		// elements incl. DEFAULTSORT and can be altered without modifying
		// any other annotation.
		$sortKey = $this->makeSortKey( $subject, $data );

		// Always fetch an ID which is either recalled from cache or is created.
		// Doing so ensures that the sortkey and namespace data are updated
		// to both the DB and the cache.
		$sid = $this->store->getObjectIds()->makeSMWPageID(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectName(),
			true,
			$sortKey,
			true
		);

		$subject->setSortKey( $sortKey );
		$subject->setId( $sid );

		// Find any potential duplicate entries for the current subject and
		// if matched, mark them as to be deleted
		$idList = $this->store->getObjectIds()->findAllEntitiesThatMatch(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectName()
		);

		foreach ( $idList as $id ) {
			if ( $id != $sid ) {
				$this->store->getObjectIds()->updateInterwikiField(
					$id,
					$subject,
					SMW_SQL3_SMWDELETEIW
				);
			}
		}

		// Take care of all remaining property table data
		list( $insertRows, $deleteRows, $newHashes ) = $this->propertyTableRowDiffer->computeTableRowDiff(
			$sid,
			$data
		);

		$params = new Parameters(
			[
				'insert_rows' => $insertRows,
				'delete_rows' => $deleteRows,
				'new_hashes'  => $newHashes
			]
		);

		$this->propertyTableUpdater->update( $sid, $params );

		if ( $redirects === [] && $subject->getSubobjectName() === ''  ) {

			$dataItemFromId = $this->store->getObjectIds()->getDataItemById( $sid );

			// If for some reason the internal redirect marker is still set but no
			// redirect annotations are known then do update the interwiki field
			if ( $dataItemFromId !== null && $dataItemFromId->getInterwiki() === SMW_SQL3_SMWREDIIW ) {
				$this->store->getObjectIds()->updateInterwikiField( $sid, $subject );
			}
		}

		// Update caches (may be important if jobs are directly following this call)
		$this->semanticDataLookup->setLookupCache( $sid, $data );
	}

	private function makeSortKey( $subject, $data ) {

		// Don't mind the delete process
		if ( $data->getOption( SemanticData::PROC_DELETE ) ) {
			return '';
		}

		$property = new DIProperty( '_SKEY' );

		// Take care of the sortkey
		$pv = $data->getPropertyValues( $property );
		$dataItem = end( $pv );

		if ( $dataItem instanceof SMWDIBlob ) {
			$sortkey = $dataItem->getString();
		} elseif ( $data->getExtensionData( 'sort.extension' ) !== null ) {
			$sortkey = $data->getExtensionData( 'sort.extension' );
		} else {
			$sortkey = $subject->getSortKey();
		}

		// Extend the subobject sortkey in case no @sortkey was given for an
		// entity
		if ( $subject->getSubobjectName() !== '' && !$dataItem instanceof SMWDIBlob ) {

			// Add sort data from some dedicated containers (of a record or
			// reference type etc.) otherwise use the sobj name as extension
			// to distinguish each entity
			if ( $data->getExtensionData( 'sort.data' ) !== null ) {
				$sortkey .= '#' . $data->getExtensionData( 'sort.data' );
			} else {
				$sortkey .= '#' . $subject->getSubobjectName();
			}
		}

		// #649 Be consistent about how sortkeys are stored therefore always
		// normalize even for usages like {{DEFAULTSORT: Foo_bar }}
		$sortkey = str_replace( '_', ' ', $sortkey );

		return $sortkey;
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
	public function changeTitle( Title $oldTitle, Title $newTitle, $pageId, $redirectId = 0 ) {
		global $smwgQEqualitySupport;

		\Hooks::run(
			'SMW::SQLStore::BeforeChangeTitleComplete',
			[ $this->store, $oldTitle, $newTitle, $pageId, $redirectId ]
		);

		$db = $this->store->getConnection();

		// get IDs but do not resolve redirects:
		$sid = $this->store->getObjectIds()->getSMWPageID(
			$oldTitle->getDBkey(),
			$oldTitle->getNamespace(),
			'',
			'',
			false
		);

		$tid = $this->store->getObjectIds()->getSMWPageID(
			$newTitle->getDBkey(),
			$newTitle->getNamespace(),
			'',
			'',
			false
		);

		// Easy case: target not used anywhere yet, just hijack its title for our current id
		if ( ( $tid == 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
			// This condition may not hold even if $newtitle is
			// currently unused/non-existing since we keep old IDs.
			// If equality support is off, then this simple move
			// does too much; fall back to general case below.
			if ( $sid != 0 ) { // change id entry to refer to the new title
				// Note that this also changes the reference for internal objects (subobjects)
				$db->update(
					SMWSql3SmwIds::TABLE_NAME,
					[
						'smw_title' => $newTitle->getDBkey(),
						'smw_namespace' => $newTitle->getNamespace(),
						'smw_iw' => ''
					],
					[
						'smw_title' => $oldTitle->getDBkey(),
						'smw_namespace' => $oldTitle->getNamespace(),
						'smw_iw' => ''
					],
					__METHOD__
				);

				$this->store->getObjectIds()->moveSubobjects(
					$oldTitle->getDBkey(),
					$oldTitle->getNamespace(),
					$newTitle->getDBkey(),
					$newTitle->getNamespace()
				);

				$this->store->getObjectIds()->setCache(
					$oldTitle->getDBkey(),
					$oldTitle->getNamespace(),
					'',
					'',
					0,
					''
				);

				// We do not know the new sortkey, so just clear the cache:
				$this->store->getObjectIds()->deleteCache(
					$newTitle->getDBkey(),
					$newTitle->getNamespace(),
					'',
					''
				);

			} else { // make new (target) id for use in redirect table
				$sid = $this->store->getObjectIds()->makeSMWPageID(
					$newTitle->getDBkey(),
					$newTitle->getNamespace(),
					'',
					''
				);
			} // at this point, $sid is the id of the target page (according to the IDs table)

			// make redirect id for oldtitle:
			$this->store->getObjectIds()->makeSMWPageID(
				$oldTitle->getDBkey(),
				$oldTitle->getNamespace(),
				SMW_SQL3_SMWREDIIW,
				''
			);

			$this->store->getObjectIds()->addRedirect(
				$sid,
				$oldTitle->getDBkey(),
				$oldTitle->getNamespace()
			);

			$propertyStatisticsStore = $this->factory->newPropertyStatisticsStore();

			$propertyStatisticsStore->addToUsageCount(
				$this->store->getObjectIds()->getSMWPropertyID( new SMW\DIProperty( '_REDI' ) ),
				1
			);

			/// NOTE: there is the (bad) case that the moved page is a redirect. As chains of
			/// redirects are not supported by MW or SMW, the above is maximally correct in this case too.
			/// NOTE: this temporarily leaves existing redirects to oldtitle point to newtitle as well, which
			/// will be lost after the next update. Since double redirects are an error anyway, this is not
			/// a bad behavior: everything will continue to work until the existing redirects are updated,
			/// which will hopefully be done to fix the double redirect.
		} else { // General move method: should always be correct
			// (equality support respected when updating redirects)

			// Delete any existing data (including redirects) from old title
			$emptyNewSemanticData = new SMWSemanticData( SMWDIWikiPage::newFromTitle( $oldTitle ) );
			$this->doDataUpdate( $emptyNewSemanticData );

			// Move all data of old title to new position:
			if ( $sid != 0 ) {
				$this->idChanger->change(
					$sid,
					$tid,
					$oldTitle->getNamespace(),
					$newTitle->getNamespace(),
					true,
					false
				);
			}

			// Associate internal objects (subobjects) with the new title:
			$table = $db->tableName( SMWSql3SmwIds::TABLE_NAME );

			$values = [
				'smw_title' => $newTitle->getDBkey(),
				'smw_namespace' => $newTitle->getNamespace(),
				'smw_iw' => ''
			];

			$sql = "UPDATE $table SET " . $db->makeList( $values, LIST_SET ) .
				' WHERE smw_title = ' . $db->addQuotes( $oldTitle->getDBkey() ) . ' AND ' .
				'smw_namespace = ' . $db->addQuotes( $oldTitle->getNamespace() ) . ' AND ' .
				'smw_iw = ' . $db->addQuotes( '' ) . ' AND ' .
				'smw_subobject != ' . $db->addQuotes( '' ); // The "!=" is why we cannot use MW array syntax here

			$db->query( $sql, __METHOD__ );

			$this->store->getObjectIds()->moveSubobjects(
				$oldTitle->getDBkey(),
				$oldTitle->getNamespace(),
				$newTitle->getDBkey(),
				$newTitle->getNamespace()
			);

			// $redirid == 0 means that the oldTitle was not supposed to be a redirect
			// (oldTitle is delete from the db) but instead of deleting all
			// references we will still copy data from old to new during updateRedirects()
			// and clear the semantic data container for the oldTitle instance
			// to ensure that no ghost references exists for an deleted oldTitle
			// @see Title::moveTo(), createRedirect
			if ( $redirectId == 0 ) {

				// Delete any existing data (including redirects) from old title
				$this->updateRedirects(
					$oldTitle->getDBkey(),
					$oldTitle->getNamespace()
				);

			} else {

				// Write a redirect from old title to new one:
				// (this also updates references in other tables as needed.)
				// TODO: may not be optimal for the standard case that newtitle
				// existed and redirected to oldtitle (PERFORMANCE)
				$this->updateRedirects(
					$oldTitle->getDBkey(),
					$oldTitle->getNamespace(),
					$newTitle->getDBkey(),
					$newTitle->getNamespace()
				);

			}

		}

		if ( $redirectId == 0 ) {
			$oldTitle = null;
		}

		ChangeTitleUpdate::addUpdate( $oldTitle, $newTitle );
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
	protected function updateRedirects( $subject_t, $subject_ns, $curtarget_t = '', $curtarget_ns = -1 ) {
		global $smwgQEqualitySupport;

		$count = 0; //track count changes for redi property
		$db = $this->store->getConnection();
		$tableFieldUpdater = $this->factory->newTableFieldUpdater();

		// *** First get id of subject, old redirect target, and current (new) redirect target ***//

		$sid_sort = '';

		// find real id of subject, if any
		$sid = $this->store->getObjectIds()->getSMWPageIDandSort(
			$subject_t,
			$subject_ns,
			'',
			'',
			$sid_sort,
			false
		);

		/// NOTE: $sid can be 0 here; this is useful to know since it means that fewer table updates are needed
		$new_tid = $curtarget_t ? ( $this->store->getObjectIds()->makeSMWPageID( $curtarget_t, $curtarget_ns, '', '', false ) ) : 0; // real id of new target, if given

		$old_tid = $this->store->getObjectIds()->findRedirect(
			$subject_t,
			$subject_ns
		);

		/// NOTE: $old_tid and $new_tid both (intentionally) ignore further redirects: no redirect chains

		if ( $old_tid == $new_tid ) { // no change, all happy
			return ( $new_tid == 0 ) ? $sid : $new_tid;
		} // note that this means $old_tid != $new_tid in all cases below

		// *** Make relevant changes in property tables (don't write the new redirect yet) ***//
		$jobs = [];

		if ( ( $old_tid == 0 ) && ( $sid != 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) { // new redirect
			// $smwgQEqualitySupport requires us to change all tables' page references from $sid to $new_tid.
			// Since references must not be 0, we don't have to do this is $sid == 0.
			$this->idChanger->change(
				$sid,
				$new_tid,
				$subject_ns,
				$curtarget_ns,
				false,
				true
			);

		} elseif ( $old_tid != 0 ) { // existing redirect is changed or deleted

			$count--;

			$this->store->getObjectIds()->updateRedirect(
				$old_tid,
				$subject_t,
				$subject_ns
			);
		}

		// *** Finally, write the new redirect data ***//

		if ( $new_tid != 0 ) { // record a new redirect
			// Redirecting done right:
			// (1) make a new ID with iw SMW_SQL3_SMWREDIIW or
			//     change iw field of current ID in this way,
			// (2) write smw_fpt_redi table,
			// (3) update canonical cache.
			// This order must be obeyed unless you really understand what you are doing!

			if ( ( $old_tid == 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
				// mark subject as redirect (if it was no redirect before)
				if ( $sid == 0 ) { // every redirect page must have an ID
					$sid = $this->store->getObjectIds()->makeSMWPageID(
						$subject_t,
						$subject_ns,
						SMW_SQL3_SMWREDIIW,
						'',
						false
					);
				} else {
					$sha1 = $this->store->getObjectIds()->computeSha1(
						[ $subject_t, $subject_ns, SMW_SQL3_SMWREDIIW , '' ]
					);

					$tableFieldUpdater->updateIwField(
						$sid,
						SMW_SQL3_SMWREDIIW,
						$sha1
					);

					$this->store->getObjectIds()->setCache(
						$subject_t,
						$subject_ns,
						'',
						'',
						0,
						''
					);

					$this->store->getObjectIds()->setCache(
						$subject_t,
						$subject_ns,
						SMW_SQL3_SMWREDIIW,
						'',
						$sid,
						$sid_sort
					);
				}
			}

			$this->store->getObjectIds()->addRedirect(
				$new_tid,
				$subject_t,
				$subject_ns
			);

			$count++;

		} else { // delete old redirect
			// This case implies $old_tid != 0 (or we would have new_tid == old_tid above).
			// Therefore $subject had a redirect, and it must also have an ID.
			// This shows that $sid != 0 here.
			if ( $smwgQEqualitySupport != SMW_EQ_NONE ) { // mark subject as non-redirect

				$sha1 = $this->store->getObjectIds()->computeSha1(
					[ $subject_t, $subject_ns, '' , '' ]
				);

				$tableFieldUpdater->updateIwField(
					$sid,
					'',
					$sha1
				);

				$this->store->getObjectIds()->setCache(
					$subject_t,
					$subject_ns,
					SMW_SQL3_SMWREDIIW,
					'',
					0,
					''
				);

				$this->store->getObjectIds()->setCache(
					$subject_t,
					$subject_ns,
					'',
					'',
					$sid,
					$sid_sort
				);
			}
		}

		// *** Flush some caches to be safe, though they are not essential in runs with redirect updates ***//
		$this->semanticDataLookup->invalidateCache( $sid );
		$this->semanticDataLookup->invalidateCache( $new_tid );
		$this->semanticDataLookup->invalidateCache( $old_tid );

		// *** Update reference count for _REDI property ***//
		$propertyStatisticsStore = $this->factory->newPropertyStatisticsStore();

		$propertyStatisticsStore->addToUsageCount(
			$this->store->getObjectIds()->getSMWPropertyID( new SMW\DIProperty( '_REDI' ) ),
			$count
		);

		return ( $new_tid == 0 ) ? $sid : $new_tid;
	}

}
