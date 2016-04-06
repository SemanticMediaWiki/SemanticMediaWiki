<?php

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\JobBase;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\SemanticData;
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
	 * @var PropertyTableRowDiffer|null
	 */
	private $propertyTableRowDiffer = null;

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 * @param SMWSQLStore3 $parentStore
	 */
	public function __construct( SMWSQLStore3 $parentStore ) {
		$this->store = $parentStore;
		$this->propertyTableRowDiffer = new PropertyTableRowDiffer( $this->store );
	}

	/**
	 * @see SMWStore::deleteSubject
	 *
	 * @since 1.8
	 * @param Title $title
	 */
	public function deleteSubject( Title $title ) {

		// @deprecated since 2.1, use 'SMW::SQLStore::BeforeDeleteSubjectComplete'
		\Hooks::run( 'SMWSQLStore3::deleteSubjectBefore', array( $this->store, $title ) );

		\Hooks::run( 'SMW::SQLStore::BeforeDeleteSubjectComplete', array( $this->store, $title ) );

		// Fetch all possible matches (including any duplicates created by
		// incomplete rollback or DB deadlock)
		$ids = $this->store->getObjectIds()->getListOfIdMatchesFor(
			$title->getDBkey(),
			$title->getNamespace(),
			$title->getInterwiki()
		);

		$subject = DIWikiPage::newFromTitle( $title );
		$emptySemanticData = new SemanticData( $subject );

		$subobjects = $this->getSubobjects(
			$emptySemanticData->getSubject()
		);

		$this->doDataUpdate( $emptySemanticData );

		foreach ( $ids as $id ) {
			$this->doDeleteFor( $id, $subject, $subobjects );
		}

		// @deprecated since 2.1, use 'SMW::SQLStore::AfterDeleteSubjectComplete'
		\Hooks::run( 'SMWSQLStore3::deleteSubjectAfter', array( $this->store, $title ) );

		\Hooks::run( 'SMW::SQLStore::AfterDeleteSubjectComplete', array( $this->store, $title ) );
	}

	private function doDeleteFor( $id, $subject, $subobjects ) {

		if ( $subject->getNamespace() === SMW_NS_CONCEPT ) { // make sure to clear caches
			$db = $this->store->getConnection();

			$db->delete(
				SMWSQLStore3::CONCEPT_TABLE,
				array( 's_id' => $id ),
				'SMW::deleteSubject::Conc'
			);

			$db->delete(
				SMWSQLStore3::CONCEPT_CACHE_TABLE,
				array( 'o_id' => $id ),
				'SMW::deleteSubject::Conccache'
			);
		}

		// Mark subject/subobjects with a special IW, the final removal is being
		// triggered by the `ByIdDataRebuildDispatcher`
		$this->store->getObjectIds()->updateInterwikiField(
			$id,
			$subject,
			SMW_SQL3_SMWDELETEIW
		);

		foreach( $subobjects as $smw_id => $subobject ) {
			$this->store->getObjectIds()->updateInterwikiField(
				$smw_id,
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
		\Hooks::run( 'SMWSQLStore3::updateDataBefore', array( $this->store, $semanticData ) );

		$subject = $semanticData->getSubject();


		// Reset diff before starting the update
		$this->propertyTableRowDiffer->resetCompositePropertyTableDiff();

		// Update data about our main subject
		$this->doFlatDataUpdate( $semanticData );

		// Update data about our subobjects
		$subSemanticData = $semanticData->getSubSemanticData();

		foreach( $subSemanticData as $subobjectData ) {
			$this->doFlatDataUpdate( $subobjectData );
		}

		// Delete data about other subobjects no longer used
		$subobjects = $this->getSubobjects( $subject );

		foreach( $subobjects as $smw_id => $subobject ) {
			if( !$semanticData->hasSubSemanticData( $subobject->getSubobjectName() ) ) {
				$this->doFlatDataUpdate( new SMWSemanticData( $subobject ) );

				$this->store->getObjectIds()->updateInterwikiField(
					$smw_id,
					$subobject,
					SMW_SQL3_SMWDELETEIW
				);
			}
		}

		// Deprecated since 2.3, use SMW::SQLStore::AfterDataUpdateComplete
		\Hooks::run( 'SMWSQLStore3::updateDataAfter', array( $this->store, $semanticData ) );

		\Hooks::run( 'SMW::SQLStore::AfterDataUpdateComplete', array(
			$this->store,
			$semanticData,
			$this->propertyTableRowDiffer->getCompositePropertyTableDiff()
		) );
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

		$this->store->getConnection()->beginAtomicTransaction( __METHOD__ );

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

			$this->store->getConnection()->endAtomicTransaction( __METHOD__ );

			return;
		} else {
			$this->updateRedirects(
				$subject->getDBkey(),
				$subject->getNamespace()
			);
		}

		// Take care of the sortkey
		$sortkeyDataItems = $data->getPropertyValues( new SMW\DIProperty( '_SKEY' ) );
		$sortkeyDataItem = end( $sortkeyDataItems );

		if ( $sortkeyDataItem instanceof SMWDIBlob ) {
			$sortkey = $sortkeyDataItem->getString();
		} else { // default sortkey
			$sortkey = $subject->getSortKey();
		}

		// #649 Be consistent about how sortkeys are stored therefore always
		// normalize even for usages like {{DEFAULTSORT: Foo_bar }}
		$sortkey = str_replace( '_', ' ', $sortkey );

		// Always make an ID; this also writes sortkey and namespace data
		$sid = $this->store->getObjectIds()->makeSMWPageID(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectName(),
			true,
			$sortkey,
			true
		);

		// Take care of all remaining property table data
		list( $deleteRows, $insertRows, $newHashes ) = $this->propertyTableRowDiffer->computeTableRowDiffFor(
			$sid,
			$data
		);

		$this->writePropertyTableUpdates(
			$sid,
			$deleteRows,
			$insertRows,
			$newHashes
		);

		if ( $redirects === array() && $subject->getSubobjectName() === ''  ) {

			$dataItemFromId = $this->store->getObjectIds()->getDataItemForId( $sid );

			// If for some reason the internal redirect marker is still set but no
			// redirect annotations are known then do update the interwiki field
			if ( $dataItemFromId !== null && $dataItemFromId->getInterwiki() === SMW_SQL3_SMWREDIIW ) {
				$this->store->getObjectIds()->updateInterwikiField( $sid, $subject );
			}
		}

		// Update caches (may be important if jobs are directly following this call)
		$this->setSemanticDataCache( $sid, $data );

		$this->store->getConnection()->endAtomicTransaction( __METHOD__ );

		// TODO Make overall diff SMWSemanticData containers and return them.
		// This can only be done here, since the $deleteRows/$insertRows
		// alone do not have enough information to compute this later (sortkey
		// and redirects may also change).
	}

	/**
	 * Method to get all subobjects for a given subject.
	 *
	 * @since 1.8
	 * @param SMWDIWikiPage $subject
	 *
	 * @return array of smw_id => SMWDIWikiPage
	 */
	protected function getSubobjects( SMWDIWikiPage $subject ) {

		$db = $this->store->getConnection();

		$res = $db->select(
			$db->tablename( SMWSql3SmwIds::TABLE_NAME ),
			'smw_id,smw_subobject,smw_sortkey',
			'smw_title = ' . $db->addQuotes( $subject->getDBkey() ) . ' AND ' .
			'smw_namespace = ' . $db->addQuotes( $subject->getNamespace() ) . ' AND ' .
			'smw_iw = ' . $db->addQuotes( $subject->getInterwiki() ) . ' AND ' .
			'smw_subobject != ' . $db->addQuotes( '' ), // The "!=" is why we cannot use MW array syntax here
			__METHOD__
		);

		$diHandler = $this->store->getDataItemHandlerForDIType( SMWDataItem::TYPE_WIKIPAGE );

		$subobjects = array();
		foreach ( $res as $row ) {
			$subobjects[$row->smw_id] = $diHandler->dataItemFromDBKeys( array(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$subject->getInterwiki(),
				$row->smw_sortkey,
				$row->smw_subobject
			) );
		}

		$db->freeResult( $res );

		return $subobjects;
	}

	/**
	 * Create a string key for hashing an array of values that represents a
	 * row in the database. Used to eliminate duplicates and to support
	 * diff computation. This is not stored in the database, so it can be
	 * changed without causing any problems with legacy data.
	 *
	 * @since 1.8
	 * @param array $databaseRow
	 * @return string
	 */
	protected static function makeDatabaseRowKey( array $databaseRow ) {
		// Do not use serialize(): the MW database does not round-trip
		// PHP objects reliably (they loose their type and become strings)
		$keyString = '';
		foreach ( $databaseRow as $column => $value ) {
			$keyString .= "#$column##$value#";
		}
		return md5( $keyString );
	}

	/**
	 * Update all property tables and any dependent data (hashes,
	 * statistics, etc.) by inserting/deleting the given values. The ID of
	 * the page that is updated, and the hashes of the properties must be
	 * given explicitly (the hashes could not be computed from the insert
	 * and delete data alone anyway).
	 *
	 * It is assumed and required that the tables mentioned in
	 * $tablesInsertRows and $tablesDeleteRows are the same, and that all
	 * $rows in these datasets refer to the same subject ID.
	 *
	 * @since 1.8
	 *
	 * @param integer $sid
	 * @param array $tablesInsertRows array mapping table names to arrays of rows
	 * @param array $tablesDeleteRows array mapping table names to arrays of rows
	 * @param array $newHashes
	 */
	protected function writePropertyTableUpdates( $sid, array $tablesInsertRows, array $tablesDeleteRows, array $newHashes ) {
		$propertyUseIncrements = array();

		$propertyTables = $this->store->getPropertyTables();

		foreach ( $tablesInsertRows as $tableName => $insertRows ) {
			// Note: by construction, the inserts and deletes have the same table keys.
			// Note: by construction, the inserts and deletes are currently disjoint;
			// yet we delete first to make the method more robust/versatile.
			$this->writePropertyTableRowUpdates( $propertyUseIncrements, $propertyTables[$tableName], $tablesDeleteRows[$tableName], false );
			$this->writePropertyTableRowUpdates( $propertyUseIncrements, $propertyTables[$tableName], $insertRows, true );
		}

		// If only rows are marked for deletion then modify hashs to ensure that
		// any inbalance can be corrected by the next insert operation for which
		// the newHashes are computed (seen in connection with redirects)
		if ( $tablesInsertRows === array() && $tablesDeleteRows !== array() ) {
			foreach ( $newHashes as $key => $hash ) {
				$newHashes[$key] = $hash . '.d';
			}
		}

		if ( $tablesInsertRows !== array() || $tablesDeleteRows !== array() ) {
			$this->store->smwIds->setPropertyTableHashes( $sid, $newHashes );
		}

		$statsTable = new PropertyStatisticsTable(
			$this->store->getConnection(),
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE
		);

		$statsTable->addToUsageCounts( $propertyUseIncrements );
	}

	/**
	 * Update one property table by inserting or deleting rows, and compute
	 * the changes that this entails for the property usage counts. The
	 * given rows are inserted into the table if $insert is true; otherwise
	 * they are deleted. The property usage counts are recorded in the
	 * call-by-ref parameter $propertyUseIncrements.
	 *
	 * The method assumes that all of the given rows are about the same
	 * subject. This is ensured by callers.
	 *
	 * @since 1.8
	 * @param array $propertyUseIncrements
	 * @param SMWSQLStore3Table $propertyTable
	 * @param array $rows array of rows to insert/delete
	 * @param boolean $insert
	 */
	protected function writePropertyTableRowUpdates( array &$propertyUseIncrements, SMWSQLStore3Table $propertyTable, array $rows, $insert ) {
		if ( empty( $rows ) ) {
			return;
		}

		if ( !$propertyTable->usesIdSubject() ) { // does not occur, but let's be strict
			throw new InvalidArgumentException('Operation not supported for tables without subject IDs.');
		}

		$db = $this->store->getConnection();

		if ( $insert ) {
			$db->insert(
				$propertyTable->getName(),
				$rows,
				"SMW::writePropertyTableRowUpdates-insert-{$propertyTable->getName()}"
			);
		} else {
			$this->deleteRows(
				$rows,
				$propertyTable
			);
		}

		if ( $propertyTable->isFixedPropertyTable() ) {
			$property = new SMW\DIProperty( $propertyTable->getFixedProperty() );
			$pid = $this->store->getObjectIds()->makeSMWPropertyID( $property );
		}

		foreach ( $rows as $row ) {

			if ( !$propertyTable->isFixedPropertyTable() ) {
				$pid = $row['p_id'];
			}

			if ( !array_key_exists( $pid, $propertyUseIncrements ) ) {
				$propertyUseIncrements[$pid] = 0;
			}

			$propertyUseIncrements[$pid] += ( $insert ? 1 : -1 );
		}
	}

	protected function deleteRows( array $rows, SMWSQLStore3Table $propertyTable ) {

		$condition = '';
		$db = $this->store->getConnection();

		// We build a condition that mentions s_id only once,
		// since it must be the same for all rows. This should
		// help the DBMS in selecting the rows (it would not be
		// easy for to detect that all tuples share one s_id).
		$sid = false;
		foreach ( $rows as $row ) {
			if ( $sid === false ) {
				if ( !array_key_exists( 's_id', (array)$row ) ) {
					// FIXME: The assumption that s_id is present does not hold.
					// This return is there to prevent fatal errors, but does not fix the issue of this code being broken
					return;
				}

				$sid = $row['s_id']; // 's_id' exists for all tables with $propertyTable->usesIdSubject()
			}
			unset( $row['s_id'] );
			if ( $condition != '' ) {
				$condition .= ' OR ';
			}
			$condition .= '(' . $db->makeList( $row, LIST_AND ) . ')';
		}

		$condition = "s_id=" . $db->addQuotes( $sid ) . " AND ($condition)";

		$db->delete(
			$propertyTable->getName(),
			array( $condition ),
			"SMW::writePropertyTableRowUpdates-delete-{$propertyTable->getName()}"
		);
	}

	/**
	 * Set the semantic data cache to hold exactly the given value for the
	 * given ID.
	 *
	 * @since 1.8
	 * @param integer $sid
	 * @param SMWSemanticData $semanticData
	 */
	protected function setSemanticDataCache( $sid, SMWSemanticData $semanticData ) {
		$this->store->m_semdata[$sid] = SMWSql3StubSemanticData::newFromSemanticData( $semanticData, $this->store );
		// This is everything one can know:
		$this->store->m_sdstate[$sid] = array();
		$propertyTables = $this->store->getPropertyTables();

		foreach ( $propertyTables as $tableId => $tableDeclaration ) {
			$this->store->m_sdstate[$sid][$tableId] = true;
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
	public function changeTitle( Title $oldTitle, Title $newTitle, $pageId, $redirectId = 0 ) {
		global $smwgQEqualitySupport;

		\Hooks::run(
			'SMW::SQLStore::BeforeChangeTitleComplete',
			array( $this->store, $oldTitle, $newTitle, $pageId, $redirectId )
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
					array(
						'smw_title' => $newTitle->getDBkey(),
						'smw_namespace' => $newTitle->getNamespace(),
						'smw_iw' => ''
					),
					array(
						'smw_title' => $oldTitle->getDBkey(),
						'smw_namespace' => $oldTitle->getNamespace(),
						'smw_iw' => ''
					),
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

			$this->store->getObjectIds()->addRedirectForId(
				$sid,
				$oldTitle->getDBkey(),
				$oldTitle->getNamespace()
			);

			$statsTable = new PropertyStatisticsTable(
				$db,
				SMWSQLStore3::PROPERTY_STATISTICS_TABLE
			);

			$statsTable->addToUsageCount(
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

			// Delete any existing data (including redirects) from new title
			// ($newtitle should not have data, but let's be sure)
			$emptyNewSemanticData = new SMWSemanticData( SMWDIWikiPage::newFromTitle( $newTitle ) );
			$this->doDataUpdate( $emptyNewSemanticData );

			// Move all data of old title to new position:
			if ( $sid != 0 ) {
				$this->store->changeSMWPageID(
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

			$values = array(
				'smw_title' => $newTitle->getDBkey(),
				'smw_namespace' => $newTitle->getNamespace(),
				'smw_iw' => ''
			);

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

		$this->addToDeferredUpdate( $oldTitle, $newTitle, $redirectId );
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

		$old_tid = $this->store->getObjectIds()->findRedirectIdFor(
			$subject_t,
			$subject_ns
		);

		/// NOTE: $old_tid and $new_tid both (intentionally) ignore further redirects: no redirect chains

		if ( $old_tid == $new_tid ) { // no change, all happy
			return ( $new_tid == 0 ) ? $sid : $new_tid;
		} // note that this means $old_tid != $new_tid in all cases below

		// *** Make relevant changes in property tables (don't write the new redirect yet) ***//
		$jobs = array();

		if ( ( $old_tid == 0 ) && ( $sid != 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) { // new redirect
			// $smwgQEqualitySupport requires us to change all tables' page references from $sid to $new_tid.
			// Since references must not be 0, we don't have to do this is $sid == 0.
			$this->store->changeSMWPageID(
				$sid,
				$new_tid,
				$subject_ns,
				$curtarget_ns,
				false,
				true
			);

		} elseif ( $old_tid != 0 ) { // existing redirect is changed or deleted

			$this->store->getObjectIds()->deleteRedirectEntry(
				$subject_t,
				$subject_ns
			);

			$count--;

			if ( $this->store->getUpdateJobsEnabledState() && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
				// entries that refer to old target may in fact refer to subject,
				// but we don't know which: schedule affected pages for update
				$propertyTables = $this->store->getPropertyTables();

				foreach ( $propertyTables as $proptable ) {
					if ( $proptable->getName() == 'smw_fpt_redi' ) {
						continue; // can safely be skipped
					}

					if ( $proptable->usesIdSubject() ) {
						$from   = $db->tableName( $proptable->getName() ) . ' INNER JOIN ' .
							  $db->tableName( SMWSql3SmwIds::TABLE_NAME ) . ' ON s_id=smw_id';
						$select = 'DISTINCT smw_title AS t,smw_namespace AS ns';
					} else {
						$from   = $db->tableName( $proptable->getName() );
						$select = 'DISTINCT s_title AS t,s_namespace AS ns';
					}

					if ( $subject_ns === SMW_NS_PROPERTY && !$proptable->isFixedPropertyTable() ) {

						$res = $db->select(
							$from,
							$select,
							array( 'p_id' => $old_tid ),
							__METHOD__
						);

						foreach ( $res as $row ) {
							$title = Title::makeTitleSafe( $row->ns, $row->t );
							if ( !is_null( $title ) ) {
								$jobs[] = new UpdateJob( $title );
							}
						}

						$db->freeResult( $res );
					}

					foreach ( $proptable->getFields( $this->store ) as $fieldname => $type ) {
						if ( $type == 'p' ) {

							$res = $db->select(
								$from,
								$select,
								array( $fieldname => $old_tid ),
								__METHOD__
							);

							foreach ( $res as $row ) {
								$title = Title::makeTitleSafe( $row->ns, $row->t );
								if ( !is_null( $title ) ) {
									$jobs[] = new UpdateJob( $title );
								}
							}

							$db->freeResult( $res );
						}
					}
				}

				/// NOTE: we do not update the concept cache here; this remains an offline task

			}
		}

		if ( $this->store->getUpdateJobsEnabledState() ) {
			JobBase::batchInsert( $jobs );
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
					$db->update(
						SMWSql3SmwIds::TABLE_NAME,
						array( 'smw_iw' => SMW_SQL3_SMWREDIIW ),
						array( 'smw_id' => $sid ),
						__METHOD__
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

			$this->store->getObjectIds()->addRedirectForId(
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

				$db->update(
					SMWSql3SmwIds::TABLE_NAME,
					array( 'smw_iw' => '' ),
					array( 'smw_id' => $sid ),
					__METHOD__
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
		unset( $this->store->m_semdata[$sid] );
		unset( $this->store->m_semdata[$new_tid] );
		unset( $this->store->m_semdata[$old_tid] );

		unset( $this->store->m_sdstate[$sid] );
		unset( $this->store->m_sdstate[$new_tid] );
		unset( $this->store->m_sdstate[$old_tid] );

		// *** Update reference count for _REDI property ***//
		$statsTable = new PropertyStatisticsTable(
			$db,
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE
		);

		$statsTable->addToUsageCount(
			$this->store->getObjectIds()->getSMWPropertyID( new SMW\DIProperty( '_REDI' ) ),
			$count
		);

		return ( $new_tid == 0 ) ? $sid : $new_tid;
	}

	private function addToDeferredUpdate( $oldTitle, $newTitle, $redirectId ) {

		$jobFactory = ApplicationFactory::getInstance()->newJobFactory();

		if ( $redirectId != 0 ) {
			$title = $oldTitle;
			$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredCallableUpdate( function() use( $title, $jobFactory ) {

				wfDebugLog( 'smw', 'DeferredCallableUpdate on changeTitle for ' . $title->getPrefixedDBKey() );

				$jobFactory->newUpdateJob( $title )->run();
			} );

			$deferredCallableUpdate->pushToDeferredUpdateList();
		}

		$title = $newTitle;
		$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredCallableUpdate( function() use( $title, $jobFactory ) {

			wfDebugLog( 'smw', 'DeferredCallableUpdate on changeTitle for ' . $title->getPrefixedDBKey() );

			$jobFactory->newUpdateJob( $title )->run();
		} );

		$deferredCallableUpdate->pushToDeferredUpdateList();
	}

}
