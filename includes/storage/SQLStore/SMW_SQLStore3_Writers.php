<?php
/**
 * @file
 * @ingroup SMWStore
 * @since 1.8
 */

/**
 * Class Handling all the write and update methods for SMWSQLStore3
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
	 * The store used by this store writer
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	protected $store;


	public function __construct( SMWSQLStore3 $parentstore ) {
		$this->store = $parentstore;
	}


	/**
	 * @see SMWStore::deleteSubject
	 *
	 * @param Title $subject
	 */
	public function deleteSubject( Title $subject ) {
		wfProfileIn( 'SMWSQLStore3::deleteSubject (SMW)' );
		wfRunHooks( 'SMWSQLStore3::deleteSubjectBefore', array( $this->store, $subject ) );

		$this->deleteSemanticData( SMWDIWikiPage::newFromTitle( $subject ) );
		$this->updateRedirects( $subject->getDBkey(), $subject->getNamespace() ); // also delete redirects, may trigger update jobs!

		if ( $subject->getNamespace() == SMW_NS_CONCEPT ) { // make sure to clear caches
			$db = wfGetDB( DB_MASTER );
			$id = $this->store->smwIds->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), '', false );
			$db->delete( 'smw_ftp_conc', array( 's_id' => $id ), 'SMW::deleteSubject::Conc' );
			$db->delete( 'smw_conccache', array( 'o_id' => $id ), 'SMW::deleteSubject::Conccache' );
		}

		///FIXME: if a property page is deleted, more pages may need to be updated by jobs!
		///TODO: who is responsible for these updates? Some update jobs are currently created in SMW_Hooks, some internally in the store
		///TODO: Possibly delete ID here (at least for non-properties/categories, if not used in any place in rels2)
		///FIXME: clean internal caches here
		wfRunHooks( 'SMWSQLStore3::deleteSubjectAfter', array( $this->store, $subject ) );
		wfProfileOut( 'SMWSQLStore3::deleteSubject (SMW)' );
	}

	/**
	* Method to get all subobjects for a given subject.
	*
	* since SMW 1.8
	* @param SMWDIWikiPage
	*
	* @return array of smw_id => SMWDIWikiPage
	*/
	protected function getSubobjects( SMWDIWikiPage $subject ) {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'smw_ids',
			'*',
			'smw_title = ' . $dbr->addQuotes( $subject->getDBkey() ) . ' AND ' .
				'smw_namespace = ' . $dbr->addQuotes( $subject->getNamespace() ) . ' AND ' .
				'smw_iw = ' . $dbr->addQuotes( $subject->getInterwiki() ) . ' AND ' .
				'smw_subobject != ' . $dbr->addQuotes( '' ),
			__METHOD__
		);
		$subobjects = array();

		foreach ( $res as $row ) {
					$subobjects[$row->smw_id] = new SMWDIWikiPage( $row->smw_title, $row->smw_namespace, $row->smw_iw, $row->smw_subobject );
		}

		$dbr->freeResult( $res );
		return $subobjects;
	}

	/**
	 * Update sub-SemanticData as part of doDataUpdate.
	 *
	 * @since 1.8
	 * @param SMWSemanticData $data
	 */
	protected function updateSubSemanticData( SMWSemanticData $data ) {
		$subDatas = $data->getSubSemanticData();
		foreach( $subDatas as $subobject => $subData ) {
			$this->doDataUpdate( $subData );
		}
		$subobjects = $this->getSubobjects( $data->getSubject() );
		foreach( $subobjects as $smw_id => $subobject ) {
			if( !array_key_exists( $subobject->getSubobjectName(), $subDatas ) ) {
				$this->deleteSemanticData( $subobject );
			}
		}
		//TODO run delete job (this should find out what ids are not needed and delete them)
	}

	/**
	 * @see SMWStore::doDataUpdate
	 *
	 * @param SMWSemanticData $data
	 */
	public function doDataUpdate( SMWSemanticData $data ) {
		wfProfileIn( "SMWSQLStore3::updateData (SMW)" );
		wfRunHooks( 'SMWSQLStore3::updateDataBefore', array( $this->store, $data ) );

		$subject = $data->getSubject();
		$redirects = $data->getPropertyValues( new SMWDIProperty( '_REDI' ) );
		if ( count( $redirects ) > 0 ) {
			$redirect = end( $redirects ); // at most one redirect per page
			$this->updateRedirects( $subject->getDBkey(), $subject->getNamespace(), $redirect->getDBkey(), $redirect->getNameSpace() );
			wfProfileOut( "SMWSQLStore3::updateData (SMW)" );
			return; // Stop here -- no support for annotations on redirect pages!
		} else {
			$this->updateRedirects( $subject->getDBkey(), $subject->getNamespace() );
		}

		$sortkeyDataItems = $data->getPropertyValues( new SMWDIProperty( '_SKEY' ) );
		$sortkeyDataItem = end( $sortkeyDataItems );
		if ( $sortkeyDataItem instanceof SMWDIString ) {
			$sortkey = $sortkeyDataItem->getString();
		} else { // default sortkey
			$sortkey = str_replace( '_', ' ', $subject->getDBkey() );
		}

		// Always make an ID (pages without ID cannot be in query results, not even in fixed value queries!):
		$sid = $this->store->smwIds->makeSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), $subject->getSubobjectName(), true, $sortkey, true );

		if( $subject->getSubobjectName() == '' ) {
			$this->updateSubSemanticData( $data );
		}
		$updates = array(); // collect data for bulk updates; format: tableid => updatearray
		$this->prepareDBUpdates( $updates, $data, $sid, $subject );

		$db = wfGetDB( DB_MASTER );

		$oldHashes = $this->store->smwIds->getPropertyTableHashes( $sid );

		$hashIsChanged = false;

		//old SemanticData container for this subject (This will only hold Semantic data that will be deleted)
		$oldData = new SMWSql3StubSemanticData( $subject, $this->store, false );
		//new SemanticData container for this subject (This will only hold Semantic data that will be newly added)
		$newData = new SMWSql3StubSemanticData( $subject, $this->store, false );
		//tables into which data has been changed or added (not considering the ones where data is only deleted)
		$modifiedTables = array();

		foreach ( SMWSQLStore3::getPropertyTables() as $tableId => $tableDeclaration ) {
			$tableName = $tableDeclaration->name;
			if ( $tableName == 'smw_fpt_redi' ) {
				// TODO - handle these for updating property counts
				continue; //smw_fpt_redi are not considered here.
			}

			if ( array_key_exists( $tableName, $updates ) ) {
				$newHash = md5( serialize( $updates[$tableName] ) );
				if ( array_key_exists( $tableName, $oldHashes ) && $newHash == $oldHashes[$tableName] ) {
					//table was used before and value didn't change, nothing to do here
					continue;
				} else {
					//data didn't exist before or has changed
					$this->store->getReader()->addTableSemanticData( $sid, $oldData, $tableDeclaration ); // Add data for this table

					// Concepts are not just written but carefully updated,
					// preserving existing metadata (cache ...) for a concept:
					if ( $tableName == 'smw_fpt_conc' ) {
						$row = $db->selectRow(
							'smw_fpt_conc',
							array( 'cache_date', 'cache_count' ),
							array( 's_id' => $sid ),
							'SMWSQLStoreQueries::updateConcData'
						);

						if ( ( $row === false ) && ( $updates['smw_fpt_conc']['concept_txt'] !== '' ) ) { // insert newly given data
							$db->insert( 'smw_fpt_conc', $updates['smw_fpt_conc'], 'SMW::updateConcData' );
						} elseif ( $row !== false ) { // update data, preserve existing entries
							$db->update( 'smw_fpt_conc', $updates['smw_fpt_conc'], array( 's_id' => $sid ), 'SMW::updateConcData' );
						}
					} else {
						$this->deleteTableSemanticData( $sid, $tableDeclaration );
						$db->insert( $tableName, $updates[$tableName], "SMW::updateData$tableName" );
					}
					$oldHashes[$tableName] = $newHash;
					$hashIsChanged = true;
					$modifiedTables[$tableId] = $tableDeclaration;
				}
			} elseif ( array_key_exists( $tableName, $oldHashes ) ) {
				//data existed before but not now (Concepts data is not deleted here)
				$this->store->getReader()->addTableSemanticData( $sid, $oldData, $tableDeclaration ); // Add data for this table
				$this->deleteTableSemanticData( $sid, $tableDeclaration );
				unset( $oldHashes[$tableName] );
				$hashIsChanged = true;
			}
		}

		if ( $hashIsChanged ) {
			$this->store->smwIds->setPropertyTableHashes( $sid, $oldHashes );
		}

		// Finally update caches (may be important if jobs are directly following this call)
		$this->store->m_semdata[$sid] = SMWSql3StubSemanticData::newFromSemanticData( $data, $this->store );
		// Everything that one can know.
		$this->store->m_sdstate[$sid] = array();
		foreach ( SMWSQLStore3::getPropertyTables() as $tableId => $tableDeclaration ) {
			$this->store->m_sdstate[$sid][$tableId] = true;
		}

		//Add newly added property-values to $newData
		//and remove property-values that hasn't been modified from $oldData
		foreach( $data->getProperties() as $propKey => $diProp ) {
			$propTable = $this->store->findPropertyTableID( $diProp );
			if ( !array_key_exists( $propTable, $modifiedTables ) ) {
				continue; //Properties in these table have been taken care of already
			}

			$dataPropVals = $data->getPropertyValues( $diProp );
			//remove common property-values from $oldData and add new ones to $newData
			$oldPropVals = $oldData->getPropertyValues( $diProp );
			foreach( $dataPropVals as $di ) {
				if ( in_array( $di, $oldPropVals ) ) {
					$oldData->removePropertyObjectValue( $diProp, $di );
				} else {
					$newData->addPropertyObjectValue( $diProp, $di );
				}
			}
		}
		$this->doDiffandUpdateCount( $oldData, $newData );

		wfRunHooks( 'SMWSQLStore3::updateDataAfter', array( $this->store, $data ) );
		wfProfileOut( "SMWSQLStore3::updateData (SMW)" );
	}

	/**
	 * Extend the given update array to account for the data in the
	 * SMWSemanticData object. The subject page of the data container is
	 * ignored, and the given $sid (subject page id) is used directly. If
	 * this ID is 0, then $subject is used to find an ID. This is usually
	 * the case for all internal objects (subobjects) that are created in
	 * writing sub-SemanticData.
	 *
	 * The function returns the id that was used for writing. Especially,
	 * any newly created internal id is returned.
	 *
	 * @param $updates array
	 * @param $data SMWSemanticData
	 * @param $sid integer pre-computed id if available or 0 if ID should be sought
	 * @param $subject SMWDIWikiPage subject to which the data refers
	 */
	protected function prepareDBUpdates( &$updates, SMWSemanticData $data, $sid, SMWDIWikiPage $subject ) {
		if ( $sid == 0 ) {
			$sid = $this->store->smwIds->makeSMWPageID( $subject->getDBkey(), $subject->getNamespace(),
				$subject->getInterwiki(), $subject->getSubobjectName(), true,
				str_replace( '_', ' ', $subject->getDBkey() ) . $subject->getSubobjectName() );
		}

		$proptables = SMWSQLStore3::getPropertyTables();

		foreach ( $data->getProperties() as $property ) {
			if ( ( $property->getKey() == '_SKEY' ) || ( $property->getKey() == '_REDI' ) ) {
				continue; // skip these here, we store them differently
			}

			$tableid = SMWSQLStore3::findPropertyTableID( $property );
			$proptable = $proptables[$tableid];

			///TODO check needed if subject is null (would happen if a user defined proptable with !idsubject was used on an internal object -- currently this is not possible
			$uvals = $proptable->idsubject ? array( 's_id' => $sid ) :
					 array( 's_title' => $subject->getDBkey(), 's_namespace' => $subject->getNamespace() );
			if ( $proptable->fixedproperty == false ) {
				$uvals['p_id'] = $this->store->smwIds->makeSMWPropertyID( $property );
			}
			foreach ( $data->getPropertyValues( $property ) as $di ) {
				if ( $di instanceof SMWDIError ) { // error values, ignore
					continue;
				}
				$diHandler = $this->store->getDataItemHandlerForDIType( $di->getDIType() );
				$uvals = array_merge( $uvals, $diHandler->getInsertValues( $di ) );

				if ( !array_key_exists( $proptable->name, $updates ) ) {
					$updates[$proptable->name] = array();
				}
				$updates[$proptable->name][] = $uvals;
			}
		}

		// Special handling of Concepts
		if ( $subject->getNamespace() == SMW_NS_CONCEPT && $subject->getSubobjectName() == '' ) {
			if ( array_key_exists( 'smw_fpt_conc', $updates ) && ( count( $updates['smw_fpt_conc'] ) != 0 ) ) {
				$updates['smw_fpt_conc'] = end( $updates['smw_fpt_conc'] );
				unset ( $updates['smw_fpt_conc']['cache_date'] );
				unset ( $updates['smw_fpt_conc']['cache_count'] );
			} else {
				$updates['smw_fpt_conc'] = array(
				     'concept_txt'   => '',
				     'concept_docu'  => '',
				     'concept_features' => 0,
				     'concept_size'  => -1,
				     'concept_depth' => -1
				);
			}
		}

		return $sid;
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
	 * TODO: Currently the sortkey is not moved with the remaining data. It is
	 * not possible to move it reliably in all cases: we cannot distinguish an
	 * unset sortkey from one that was set to the name of oldtitle. Maybe use
	 * update jobs right away?
	 *
	 * @param Title $oldtitle
	 * @param Title $newtitle
	 * @param integer $pageid
	 * @param integer $redirid
	 */
	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		global $smwgQEqualitySupport;
		wfProfileIn( "SMWSQLStore3::changeTitle (SMW)" );

		// get IDs but do not resolve redirects:
		$sid = $this->store->smwIds->getSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), '', '', false );
		$tid = $this->store->smwIds->getSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '', '', false );
		$db = wfGetDB( DB_MASTER );

		// Easy case: target not used anywhere yet, just hijack its title for our current id
		if ( ( $tid == 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
			// This condition may not hold even if $newtitle is
			// currently unused/non-existing since we keep old IDs.
			// If equality support is off, then this simple move
			// does too much; fall back to general case below.
			if ( $sid != 0 ) { // change id entry to refer to the new title
				// Note that this also changes the reference for internal objects (subobjects)
				$db->update( 'smw_ids', array( 'smw_title' => $newtitle->getDBkey(),
					'smw_namespace' => $newtitle->getNamespace(), 'smw_iw' => '' ),
					array( 'smw_title' => $oldtitle->getDBkey(),
					'smw_namespace' => $oldtitle->getNamespace(), 'smw_iw' => '' ),
					__METHOD__ );
				$this->store->smwIds->moveSubobjects( $oldtitle->getDBkey(), $oldtitle->getNamespace(),
					$newtitle->getDBkey(), $newtitle->getNamespace() );
				$this->store->smwIds->setCache( $oldtitle->getDBkey(), $oldtitle->getNamespace(), '', '', 0, '' );
				// We do not know the new sortkey, so just clear the cache:
				$this->store->smwIds->deleteCache( $newtitle->getDBkey(), $newtitle->getNamespace(), '', '' );
			} else { // make new (target) id for use in redirect table
				$sid = $this->store->smwIds->makeSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '', '' );
			} // at this point, $sid is the id of the target page (according to smw_ids)

			// make redirect id for oldtitle:
			$this->store->smwIds->makeSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), SMW_SQL3_SMWREDIIW, '' );
			$db->insert( 'smw_fpt_redi', array( 's_title' => $oldtitle->getDBkey(),
						's_namespace' => $oldtitle->getNamespace(),
						'o_id' => $sid ),
			             __METHOD__
			);

			$this->addToUsageCount(
				$this->store->smwIds->getSMWPropertyID( new SMWDIProperty( '_REDI' ) ),
				1
			);

			/// NOTE: there is the (bad) case that the moved page is a redirect. As chains of
			/// redirects are not supported by MW or SMW, the above is maximally correct in this case too.
			/// NOTE: this temporarily leaves existing redirects to oldtitle point to newtitle as well, which
			/// will be lost after the next update. Since double redirects are an error anyway, this is not
			/// a bad behaviour: everything will continue to work until the existing redirects are updated,
			/// which will hopefully be done to fix the double redirect.
		} else { // General move method: should always be correct
			// (equality support respected when updating redirects)

			// Delete any existing data from new title:
			// $newtitle should not have data, but let's be sure
			$this->deleteSemanticData( SMWDIWikiPage::newFromTitle( $newtitle ) );
			// Update (i.e. delete) redirects (may trigger update jobs):
			$this->updateRedirects( $newtitle->getDBkey(), $newtitle->getNamespace() );

			// Move all data of old title to new position:
			if ( $sid != 0 ) {
				$this->store->changeSMWPageID( $sid, $tid, $oldtitle->getNamespace(),$newtitle->getNamespace(), true, false );
			}

			// Associate internal objects (subobjects) with the new title:
			$table = $db->tableName( 'smw_ids' );
			$values = array( 'smw_title' => $newtitle->getDBkey(), 'smw_namespace' => $newtitle->getNamespace(), 'smw_iw' => '' );
			$sql = "UPDATE $table SET " . $db->makeList( $values, LIST_SET ) .
				' WHERE smw_title = ' . $db->addQuotes( $oldtitle->getDBkey() ) . ' AND ' .
				'smw_namespace = ' . $db->addQuotes( $oldtitle->getNamespace() ) . ' AND ' .
				'smw_iw = ' . $db->addQuotes( '' ) . ' AND ' .
				'smw_subobject != ' . $db->addQuotes( '' );
			$db->query( $sql, __METHOD__ );
// The below code can be used instead when moving to MW 1.17 (support for '!' in Database::makeList()):
// 			$db->update( 'smw_ids',
// 				array( 'smw_title' => $newtitle->getDBkey(), 'smw_namespace' => $newtitle->getNamespace(), 'smw_iw' => '' ),
// 				array( 'smw_title' => $oldtitle->getDBkey(), 'smw_namespace' => $oldtitle->getNamespace(), 'smw_iw' => '', 'smw_subobject!' => array( '' ) ), // array() needed for ! to work
// 				__METHOD__ );
			$this->store->smwIds->moveSubobjects( $oldtitle->getDBkey(), $oldtitle->getNamespace(), $newtitle->getDBkey(), $newtitle->getNamespace() );

			// Write a redirect from old title to new one:
			// (this also updates references in other tables as needed.)
			/// TODO: may not be optimal for the standard case that newtitle existed and redirected to oldtitle (PERFORMANCE)
			$this->updateRedirects( $oldtitle->getDBkey(), $oldtitle->getNamespace(), $newtitle->getDBkey(), $newtitle->getNamespace() );
		}

		wfProfileOut( "SMWSQLStore3::changeTitle (SMW)" );
	}

	/**
	 * Delete all semantic data stored for the given subject on the
	 * specified table.
	 * @note If the table is smw_fpt_conc or smw_fpt_redi nothing is done,
	 * as doDataUpdate handles them itself. Also, there is no handling of
	 * tables with idsubject set to false here.
	 *
	 * @since 1.8
	 *
	 * @param $id Integer
	 * @param $table SMWSQLStore3Table
	 */
	protected function deleteTableSemanticData( $id, SMWSQLStore3Table $table ) {
		if ( $table->name == 'smw_fpt_conc' || $table->name == 'smw_fpt_redi' ) {
			return; // not handled here
		}

		if ( $id == 0 ) {
			return;
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( $table->name, array( 's_id' => $id ), __METHOD__ );
	}

	/**
	 * Delete all semantic data stored for the given subject. Used for
	 * update purposes. Handles Subobjects recursively
	 *
	 * @param $subject SMWDIWikiPage the data of which is deleted
	 */
	public function deleteSemanticData( SMWDIWikiPage $subject ) {
		$db = wfGetDB( DB_MASTER );

		$id = $this->store->smwIds->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), $subject->getSubobjectName(), false, true );
		if ( $id == 0 ) {
			// not (directly) used anywhere yet, may be a redirect but we do not care here
			wfRunHooks( 'smwDeleteSemanticData', array( $subject ) );
			return;
		}
		//get oldData for diffing and updating property counts
		$oldData = $this->store->getSemanticData( $subject );
		$this->doDiffandUpdateCount( $oldData );

		$oldHashes = $this->store->smwIds->getPropertyTableHashes( $id );
		foreach ( SMWSQLStore3::getPropertyTables() as $tableId => $tableDeclaration ) {
			$tableName = $tableDeclaration->name;
			if ( array_key_exists( $tableName, $oldHashes ) ) {
				$this->deleteTableSemanticData( $id, $tableDeclaration );
			}
		}
		if ( $subject->getSubobjectName() !== '' ) {
			wfRunHooks( 'smwDeleteSemanticData', array( $subject ) );
			return; // Subobjects don't have subsubobjects
		}

		// also find subobjects used by this ID ...
		$oldSubobjects = $this->getSubobjects( $subject );

		// ... and delete their data as well recursively
		foreach ( $oldSubobjects as $subobject ) {
			$this->deleteSemanticData( $subobject );
		}
		//TODO: delete all unused subobjects
		wfRunHooks( 'smwDeleteSemanticData', array( $subject ) );
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
	 */
	protected function updateRedirects( $subject_t, $subject_ns, $curtarget_t = '', $curtarget_ns = -1 ) {
		global $smwgQEqualitySupport, $smwgEnableUpdateJobs;
		$count = 0; //track count changes for redi property

		// *** First get id of subject, old redirect target, and current (new) redirect target ***//

		$sid_sort = '';
		$sid = $this->store->smwIds->getSMWPageIDandSort( $subject_t, $subject_ns, '', '', $sid_sort, false ); // find real id of subject, if any
		/// NOTE: $sid can be 0 here; this is useful to know since it means that fewer table updates are needed
		$new_tid = $curtarget_t ? ( $this->store->smwIds->makeSMWPageID( $curtarget_t, $curtarget_ns, '', '', false ) ) : 0; // real id of new target, if given

		$db = wfGetDB( DB_SLAVE );
		$row = $db->selectRow( array( 'smw_fpt_redi' ), 'o_id',
				array( 's_title' => $subject_t, 's_namespace' => $subject_ns ), __METHOD__ );
		$old_tid = ( $row !== false ) ? $row->o_id : 0; // real id of old target, if any
		/// NOTE: $old_tid and $new_tid both (intentionally) ignore further redirects: no redirect chains

		if ( $old_tid == $new_tid ) { // no change, all happy
			return ( $new_tid == 0 ) ? $sid : $new_tid;
		} // note that this means $old_tid != $new_tid in all cases below

		// *** Make relevant changes in property tables (don't write the new redirect yet) ***//

		$db = wfGetDB( DB_MASTER ); // now we need to write something

		if ( ( $old_tid == 0 ) && ( $sid != 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) { // new redirect
			// $smwgQEqualitySupport requires us to change all tables' page references from $sid to $new_tid.
			// Since references must not be 0, we don't have to do this is $sid == 0.
			$this->store->changeSMWPageID( $sid, $new_tid, $subject_ns, $curtarget_ns, false, true );
		} elseif ( $old_tid != 0 ) { // existing redirect is changed or deleted
			$db->delete( 'smw_fpt_redi',
				array( 's_title' => $subject_t, 's_namespace' => $subject_ns ), __METHOD__ );
			$count--;

			if ( $smwgEnableUpdateJobs && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) {
				// entries that refer to old target may in fact refer to subject,
				// but we don't know which: schedule affected pages for update
				$jobs = array();

				foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
					if ( $proptable->name == 'smw_fpt_redi' ) continue; // can safely be skipped

					if ( $proptable->idsubject ) {
						$from   = $db->tableName( $proptable->name ) . ' INNER JOIN ' .
							  $db->tableName( 'smw_ids' ) . ' ON s_id=smw_id';
						$select = 'DISTINCT smw_title AS t,smw_namespace AS ns';
					} else {
						$from   = $db->tableName( $proptable->name );
						$select = 'DISTINCT s_title AS t,s_namespace AS ns';
					}

					if ( $subject_ns == SMW_NS_PROPERTY && !$proptable->fixedproperty ) {
						$res = $db->select( $from, $select,
							array( 'p_id' => $old_tid ), __METHOD__ );
						foreach ( $res as $row ) {
							$title = Title::makeTitleSafe( $row->ns, $row->t );
							if ( !is_null( $title ) ) {
								$jobs[] = new SMWUpdateJob( $title );
							}
						}
						$db->freeResult( $res );
					}

					foreach ( $proptable->getFields( $this->store ) as $fieldname => $type ) {
						if ( $type == 'p' ) {
							$res = $db->select( $from, $select,
								array( $fieldname => $old_tid ), __METHOD__ );
							foreach ( $res as $row ) {
								$title = Title::makeTitleSafe( $row->ns, $row->t );
								if ( !is_null( $title ) ) {
									$jobs[] = new SMWUpdateJob( $title );
								}
							}
							$db->freeResult( $res );
						}
					}
				}

				/// NOTE: we do not update the concept cache here; this remains an offline task

				/// NOTE: this only happens if $smwgEnableUpdateJobs was true above:
				Job::batchInsert( $jobs );
			}
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
					$sid = $this->store->smwIds->makeSMWPageID( $subject_t, $subject_ns,
						SMW_SQL3_SMWREDIIW, '', false );
				} else {
					$db->update( 'smw_ids', array( 'smw_iw' => SMW_SQL3_SMWREDIIW ),
						array( 'smw_id' => $sid ), __METHOD__ );
					$this->store->smwIds->setCache( $subject_t, $subject_ns, '', '', 0, '' );
					$this->store->smwIds->setCache( $subject_t, $subject_ns, SMW_SQL3_SMWREDIIW, '', $sid, $sid_sort );
				}
			}

			$db->insert( 'smw_fpt_redi', array( 's_title' => $subject_t,
				's_namespace' => $subject_ns, 'o_id' => $new_tid ), __METHOD__ );
			$count++;
		} else { // delete old redirect
			// This case implies $old_tid != 0 (or we would have new_tid == old_tid above).
			// Therefore $subject had a redirect, and it must also have an ID.
			// This shows that $sid != 0 here.
			if ( $smwgQEqualitySupport != SMW_EQ_NONE ) { // mark subject as non-redirect
				$db->update( 'smw_ids', array( 'smw_iw' => '' ), array( 'smw_id' => $sid ), __METHOD__ );
				$this->store->smwIds->setCache( $subject_t, $subject_ns, SMW_SQL3_SMWREDIIW, '', 0, '' );
				$this->store->smwIds->setCache( $subject_t, $subject_ns, '', '', $sid, $sid_sort );
			}
		}

		// *** Flush some caches to be safe, though they are not essential in runs with redirect updates ***//

		unset( $this->store->m_semdata[$sid] ); unset( $this->store->m_semdata[$new_tid] ); unset( $this->store->m_semdata[$old_tid] );
		unset( $this->store->m_sdstate[$sid] ); unset( $this->store->m_sdstate[$new_tid] ); unset( $this->store->m_sdstate[$old_tid] );

		// *** Update reference count for _REDI property ***//

		if( $count != 0 ) {
			$this->addToUsageCount(
				$this->store->smwIds->getSMWPropertyID( new SMWDIProperty( '_REDI' ) ),
				$count
			);
		}

		return ( $new_tid == 0 ) ? $sid : $new_tid;
	}

	/**
	* Updates propertyCounts using the diff ( old SemanticData and new SemanticData for a subject )
	* Old SemanticData is data before the page was edited and new SemanticData is data after edit.
	* new SemanticData is optional sometimes (as for deleting data)
	*
	* @since 1.8
	*
	* @param SemanticData $oldData
	* @param SemanticData or null $newData
	*/
	protected function doDiffandUpdateCount( $oldData, $newData = null ) {

		//Update Property Counts
		$updates = array();
		if( !is_null( $newData ) ) {
			foreach( $newData->getProperties() as $diKey => $diProp ) {
				$updates[$diKey] = array( $diProp, count( $newData->getPropertyValues( $diProp ) ) );
			}
		}

		foreach( $oldData->getProperties() as $diKey => $diProp ) {
			if ( array_key_exists( $diKey, $updates ) ) {
				$updates[$diKey][1] = $updates[$diKey][1] - count( $oldData->getPropertyValues( $diProp ) );
			} else {
				$updates[$diKey] = array( $diProp , -count( $oldData->getPropertyValues( $diProp ) ));
			}
		}

		foreach ( $updates as $update ) {
			if( $update[1] == 0 ) {
				continue;
			}
			// HOW to do this query using MW functions ??
/*
			$dbw->update(
				'smw_stats',
				array( 'usage_count' => 'usage_count + '.$update[1] ),
				array( 'pid' => $this->store->smwIds->getSMWPropertyID( $update[0] ) ),
				__METHOD__
			);
 */

			$this->addToUsageCount(
				$this->store->smwIds->getSMWPropertyID( $update[0] ),
				$update[1]
			);
		}
	}

	/**
	 * @since 1.8
	 *
	 * @param integer $propertyId
	 * @param integer $addition
	 *
	 * @return boolean Success indicator
	 */
	protected function addToUsageCount( $propertyId, $addition ) {
		$dbw = wfGetDB( DB_MASTER );

		return $dbw->update(
			'smw_stats',
			array(
				'usage_count = usage_count + ' . $dbw->addQuotes( $addition ),
			),
			array(
				'pid' => $propertyId
			),
			__METHOD__
		);
	}

}
