<?php

/**
 * Class Handling all the write and update methods for SMWSQLStore3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since SMW.storerewrite
 * @file
 * @ingroup SMWStore
 */

Class SMWSQLStore3Writers {

	/**
	 * The store used by this store writer
	 *
	 * @since SMW.storerewrite
	 * @var SMWSQLStore3
	 */
	protected $store;


	public function __construct( &$parentstore ) {
		$this->store = $parentstore;
	}


	/**
	 * @see SMWStore::deleteSubject
	 *
	 * @param Title $subject
	 */
	public function deleteSubject( Title $subject ) {
		wfProfileIn( 'SMWSQLStore3::deleteSubject (SMW)' );
		wfRunHooks( 'SMWSQLStore3::deleteSubjectBefore', array( $this, $subject ) );

		$this->store->deleteSemanticData( SMWDIWikiPage::newFromTitle( $subject ) );
		$this->store->updateRedirects( $subject->getDBkey(), $subject->getNamespace() ); // also delete redirects, may trigger update jobs!

		if ( $subject->getNamespace() == SMW_NS_CONCEPT ) { // make sure to clear caches
			$db = wfGetDB( DB_MASTER );
			$id = $this->store->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), '', false );
			$db->delete( 'smw_conc', array( 's_id' => $id ), 'SMW::deleteSubject::Conc2' );
			$db->delete( 'smw_conccache', array( 'o_id' => $id ), 'SMW::deleteSubject::Conccache' );
		}

		///FIXME: if a property page is deleted, more pages may need to be updated by jobs!
		///TODO: who is responsible for these updates? Some update jobs are currently created in SMW_Hooks, some internally in the store
		///TODO: Possibly delete ID here (at least for non-properties/categories, if not used in any place in rels2)
		///FIXME: clean internal caches here
		wfRunHooks( 'SMWSQLStore3::deleteSubjectAfter', array( $this, $subject ) );
		wfProfileOut( 'SMWSQLStore3::deleteSubject (SMW)' );
	}

	/**
	 * @see SMWStore::doDataUpdate
	 *
	 * @param SMWSemanticData $data
	 */
	public function doDataUpdate( SMWSemanticData $data ) {
		wfProfileIn( "SMWSQLStore3::updateData (SMW)" );
		wfRunHooks( 'SMWSQLStore3::updateDataBefore', array( $this, $data ) );

		$subject = $data->getSubject();

		$redirects = $data->getPropertyValues( new SMWDIProperty( '_REDI' ) );
		if ( count( $redirects ) > 0 ) {
			$redirect = end( $redirects ); // at most one redirect per page
			$this->store->updateRedirects( $subject->getDBkey(), $subject->getNamespace(), $redirect->getDBkey(), $redirect->getNameSpace() );
			wfProfileOut( "SMWSQLStore3::updateData (SMW)" );
			return; // Stop here -- no support for annotations on redirect pages!
		} else {
			$this->store->updateRedirects( $subject->getDBkey(), $subject->getNamespace() );
		}

		$sortkeyDataItems = $data->getPropertyValues( new SMWDIProperty( '_SKEY' ) );
		$sortkeyDataItem = end( $sortkeyDataItems );
		if ( $sortkeyDataItem instanceof SMWDIString ) {
			$sortkey = $sortkeyDataItem->getString();
		} else { // default sortkey
			$sortkey = str_replace( '_', ' ', $subject->getDBkey() );
		}

		// Always make an ID (pages without ID cannot be in query results, not even in fixed value queries!):
		$sid = $this->store->makeSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), $subject->getSubobjectName(), true, $sortkey );
		$updates = array(); // collect data for bulk updates; format: tableid => updatearray
		$this->prepareDBUpdates( $updates, $data, $sid, $subject );

		$db = wfGetDB( DB_MASTER );

		$oldHashes = $this->store->getPropTableHashes( $sid );
		$hashIsChanged = false;
		foreach ( SMWSQLStore3::getPropertyTables() as $tableId => $tableDeclaration ) {
			$tableName = $tableDeclaration->name;
			if ( $tableName == 'smw_conc' || $tableName == 'smw_redi' ) {
				continue;	//smw_redi and smw_conc are not considered here.
			}
			if ( array_key_exists( $tableName, $updates ) ) {
				$newHash = md5( serialize( $updates[$tableName] ) );
				if ( array_key_exists( $tableName, $oldHashes ) && $newHash === $oldHashes[$tableName] ) {
					//table was used before and value didn't change, nothing to do here
					continue;
				} else {
					//data didn't exist before or has changed
					$this->deleteTableSemanticData( $subject, $tableDeclaration );
					$db->insert( $tableName, $updates[$tableName], "SMW::updateData$tableName" );
					$oldHashes[$tableName] = $newHash;
					$hashIsChanged = true;
				}
			} elseif ( array_key_exists( $tableName, $oldHashes ) ) {
				//data existed before but not now
				$this->deleteTableSemanticData( $subject, $tableDeclaration );
				unset($oldHashes[$tableName]);
				$hashIsChanged = true;
			}
		}

		if ( $hashIsChanged ) {
			$this->store->setPropTableHashes( $sid, $oldHashes );
		}

		// Concepts are not just written but carefully updated,
		// preserving existing metadata (cache ...) for a concept:
		if ( $subject->getNamespace() == SMW_NS_CONCEPT ) {
			if ( array_key_exists( 'smw_conc', $updates ) && ( count( $updates['smw_conc'] ) != 0 ) ) {
				$up_conc2 = end( $updates['smw_conc'] );
				unset ( $up_conc2['cache_date'] );
				unset ( $up_conc2['cache_count'] );
			} else {
				$up_conc2 = array(
				     'concept_txt'   => '',
				     'concept_docu'  => '',
				     'concept_features' => 0,
				     'concept_size'  => -1,
				     'concept_depth' => -1
				);
			}

			$row = $db->selectRow(
				'smw_conc',
				array( 'cache_date', 'cache_count' ),
				array( 's_id' => $sid ),
				'SMWSQLStoreQueries::updateConst2Data'
			);

			if ( ( $row === false ) && ( $up_conc2['concept_txt'] !== '' ) ) { // insert newly given data
				$up_conc2['s_id'] = $sid;
				$db->insert( 'smw_conc', $up_conc2, 'SMW::updateConc2Data' );
			} elseif ( $row !== false ) { // update data, preserve existing entries
				$db->update( 'smw_conc', $up_conc2, array( 's_id' => $sid ), 'SMW::updateConc2Data' );
			}
		}

		// Finally update caches (may be important if jobs are directly following this call)
		$this->store->m_semdata[$sid] = SMWSql3StubSemanticData::newFromSemanticData( $data );
		// Everything that one can know.
		$this->store->m_sdstate[$sid] = array();
		foreach ( SMWSQLStore3::getPropertyTables() as $tableId => $tableDeclaration ) {
			$this->store->m_sdstate[$sid][$tableId] = true;
		}

		wfRunHooks( 'SMWSQLStore3::updateDataAfter', array( $this, $data ) );

		wfProfileOut( "SMWSQLStore3::updateData (SMW)" );
	}

	/**
	 * Extend the given update array to account for the data in the
	 * SMWSemanticData object. The subject page of the data container is
	 * ignored, and the given $sid (subject page id) is used directly. If
	 * this ID is 0, then $subject is used to find an ID. This is usually
	 * the case for all internal objects that are created in writing
	 * container values.
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
			$sid = $this->store->makeSMWPageID( $subject->getDBkey(), $subject->getNamespace(),
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

			foreach ( $data->getPropertyValues( $property ) as $di ) {
				if ( $di instanceof SMWDIError ) { // error values, ignore
					continue;
				}
				// redirects were treated above

				///TODO check needed if subject is null (would happen if a user defined proptable with !idsubject was used on an internal object -- currently this is not possible
				$uvals = $proptable->idsubject ? array( 's_id' => $sid ) :
				         array( 's_title' => $subject->getDBkey(), 's_namespace' => $subject->getNamespace() );
				if ( $proptable->fixedproperty == false ) {
					$uvals['p_id'] = $this->store->makeSMWPropertyID( $property );
				}

				if ( $di instanceof SMWDIContainer ) { // process subobjects recursively
					$subObject = $di->getSemanticData()->getSubject();
					$subObjectId = $this->prepareDBUpdates( $updates, $di->getSemanticData(), 0, $subObject );
					// Note: tables for container objects MUST have objectfields == array(<somename> => 'p')
					reset( $proptable->objectfields );
					$uvals[key( $proptable->objectfields )] = $subObjectId;
				} else {
					///since SMW.storerewrite we get the array of where conds (fieldname=>value) from the DIHander class
					//This causes a database error when called for special properties as they have different table structure
					//unknown to the DIHandlers. Do we really need different table structure for special properties?
					$diHandler = SMWDIHandlerFactory::getDataItemHandlerForDIType( $di->getDIType() );
					$uvals = array_merge( $uvals, $diHandler->getInsertValues( $di ) );
				}

				if ( !array_key_exists( $proptable->name, $updates ) ) {
					$updates[$proptable->name] = array();
				}

				$updates[$proptable->name][] = $uvals;
			}
		}

		return $sid;
	}

	/**
	 * Implementation of SMWStore::changeTitle(). In contrast to
	 * store->updateRedirects(), this function does not simply write a redirect
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
		$sid = $this->store->getSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), '', '', false );
		$tid = $this->store->getSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '', '', false );
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
				$this->store->m_idCache->moveSubobjects( $oldtitle->getDBkey(), $oldtitle->getNamespace(),
					$newtitle->getDBkey(), $newtitle->getNamespace() );
				$this->store->m_idCache->setId( $oldtitle->getDBkey(), $oldtitle->getNamespace(), '', '', 0 );
				$this->store->m_idCache->setId( $newtitle->getDBkey(), $newtitle->getNamespace(), '', '', $sid );
			} else { // make new (target) id for use in redirect table
				$sid = $this->store->makeSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '', '' );
			} // at this point, $sid is the id of the target page (according to smw_ids)

			// make redirect id for oldtitle:
			$this->store->makeSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), SMW_SQL2_SMWREDIIW, '' );
			$db->insert( 'smw_redi', array( 's_title' => $oldtitle->getDBkey(),
						's_namespace' => $oldtitle->getNamespace(),
						'o_id' => $sid ),
			             __METHOD__ );

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
			$this->store->deleteSemanticData( SMWDIWikiPage::newFromTitle( $newtitle ) );
			// Update (i.e. delete) redirects (may trigger update jobs):
			$this->store->updateRedirects( $newtitle->getDBkey(), $newtitle->getNamespace() );

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
			$this->store->m_idCache->moveSubobjects( $oldtitle->getDBkey(), $oldtitle->getNamespace(),$newtitle->getDBkey(), $newtitle->getNamespace() );

			// Write a redirect from old title to new one:
			// (this also updates references in other tables as needed.)
			/// TODO: may not be optimal for the standard case that newtitle existed and redirected to oldtitle (PERFORMANCE)
			$this->store->updateRedirects( $oldtitle->getDBkey(), $oldtitle->getNamespace(), $newtitle->getDBkey(), $newtitle->getNamespace() );
		}

		wfProfileOut( "SMWSQLStore3::changeTitle (SMW)" );
	}

	/**
	 * Delete all semantic data stored for the given subject on the specified table.
	 * Note - if the table is smw_conc or smw_redi nothing is done as doDataUpdate handles them itself
	 *
	 * @param $subject SMWDIWikiPage
	 * @param $table SMW_SQLStoreTable
	 */
	protected function deleteTableSemanticData( SMWDIWikiPage $subject, $table ) {
		if ( $subject->getSubobjectName() !== '' ) {
			return; // not needed, and would mess up data
		}

		if ( $table->name == 'smw_conc' || $table->name == 'smw_redi' ) {
			return;
		}

		$id = $this->store->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), '', false );
		if ( $id == 0 ) {
			return;
		}
		$db = wfGetDB( DB_MASTER );
		if ( $table->idsubject ) {
			$db->delete( $table->name, array( 's_id' => $id ), __METHOD__ );
		} else {
			$db->delete(
				$table->name,
				array(
					's_title' => $subject->getDBkey(),
					's_namespace' => $subject->getNamespace()
				),
				__METHOD__
			);
		}
	}
}
