<?php
/**
 * New SQL implementation of SMW's storage abstraction layer with
 * a reduced feature set for the SMWLight version. Statistic features
 * and semantic queries are completely disabled for now.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWStore
 */


/**
 * Storage access class for using the standard MediaWiki SQL database
 * for keeping semantic data. This is a lightweight version of SMW's standard
 * storage implementation, providing only basic data storage and retrieval but
 * no querying (and no concept caching) or statistics.
 *
 * @todo This implementation is not completed yet and should be considered
 * experimental.
 *
 * @ingroup SMWStore
 */
class SMWSQLStoreLight extends SMWStore {

	/// Cache for SMWSemanticData objects, indexed by page ID
	protected $m_semdata = array();
	/// Like SMWSQLStoreLight::m_semdata, but containing arrays for indicating completeness of the SMWSemanticData objs
	protected $m_sdstate = array();
	/// >0 while getSemanticData runs, used to prevent nested calls from clearing the cache while another call runs and is about to fill it with data
	protected static $in_getSemanticData = 0;

	/// Data for which type ids should be stored in the special table?
	/// Special values must only have one DB key, stored as a 256byte string.
	private static $special_types = array(
		'__typ' => true, // Special type page type
		'__tls' => true, // Special type list for _rec properties
		'__sps' => true, // Special string type
		'__spu' => true, // Special uri type
		'__spf' => true, // Special form type (for Semantic Forms)
		'__lin' => true, // Special linear unit conversion type
		'__imp' => true, // Special import vocabulary type
	);

///// Reading methods /////

	public function getSemanticData( $subject, $filter = false ) {
		wfProfileIn( "SMWSQLStoreLight::getSemanticData (SMW)" );
		SMWSQLStoreLight::$in_getSemanticData++; // do not clear the cache when called recursively
		//*** Find out if this subject exists ***//
		if ( $subject instanceof Title ) { ///TODO: can this still occur?
			$sid = $subject->getArticleID();
			$svalue = SMWWikiPageValue::makePageFromTitle( $subject );
		} elseif ( $subject instanceof SMWWikiPageValue ) {
			$sid = $subject->isValid() ? $subject->getTitle()->getArticleID() : 0;
			$svalue = $subject;
		} else {
			$sid = 0;
		}
		if ( $sid == 0 ) { // no data, safe our time
			SMWSQLStoreLight::$in_getSemanticData--;
			wfProfileOut( "SMWSQLStoreLight::getSemanticData (SMW)" );
			return isset( $svalue ) ? ( new SMWSemanticData( $svalue ) ) : null;
		}
		//*** Prepare the cache ***//
		if ( !array_key_exists( $sid, $this->m_semdata ) ) { // new cache entry
			$this->m_semdata[$sid] = new SMWSemanticData( $svalue, false );
			$this->m_sdstate[$sid] = array();
		}
		if ( ( count( $this->m_semdata ) > 20 ) && ( SMWSQLStoreLight::$in_getSemanticData == 1 ) ) {
			// prevent memory leak;
			// It is not so easy to find the sweet spot between cache size and performance gains (both memory and time),
			// The value of 20 was chosen by profiling runtimes for large inline queries and heavily annotated pages.
			$this->m_semdata = array( $sid => $this->m_semdata[$sid] );
			$this->m_sdstate = array( $sid => $this->m_sdstate[$sid] );
		}
		//*** Read the data ***//
		$db = wfGetDB( DB_SLAVE );
		foreach ( array( 'smwsimple_data', 'smwsimple_special' ) as $tablename ) {
			if ( array_key_exists( $tablename, $this->m_sdstate[$sid] ) ) continue;
			if ( $filter !== false ) {
				$relevant = false;
				foreach ( $filter as $typeid ) {
					$relevant = $relevant || ( $tablename == SMWSQLStoreLight::findTypeTableName( $typeid ) );
				}
				if ( !$relevant ) continue;
			}
			$res = $db->select( $tablename, array( 'propname', 'value' ), array( 'pageid' => $sid ),
			                    'SMW::getSemanticData', array( 'DISTINCT' ) );
			while ( $row = $db->fetchObject( $res ) ) {
				$value = ( $tablename == 'smwsimple_special' ) ? array( $row->value ) : unserialize( $row->value );
				$this->m_semdata[$sid]->addPropertyStubValue( $row->propname, $value );
			}
			$db->freeResult( $res );
			$this->m_sdstate[$sid][$tablename] = true;
		}

		SMWSQLStoreLight::$in_getSemanticData--;
		wfProfileOut( "SMWSQLStoreLight::getSemanticData (SMW)" );
		return $this->m_semdata[$sid];
	}

	public function getPropertyValues( $subject, SMWPropertyValue $property, $requestoptions = null, $outputformat = '' ) {
		wfProfileIn( "SMWSQLStoreLight::getPropertyValues (SMW)" );
		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = clone $property;
			$noninverse->setInverse( false );
			$result = $this->getPropertySubjects( $noninverse, $subject, $requestoptions );
		} elseif ( $subject !== null ) { // subject given, use semantic data cache:
			$sd = $this->getSemanticData( $subject, array( $property->getPropertyTypeID() ) );
			$result = $this->applyRequestOptions( $sd->getPropertyValues( $property ), $requestoptions );
			if ( $outputformat != '' ) { // reformat cached values
				$newres = array();
				foreach ( $result as $dv ) {
					$ndv = clone $dv;
					$ndv->setOutputFormat( $outputformat );
					$newres[] = $ndv;
				}
				$result = $newres;
			}
		} else { // no subject given, get all values for the given property
			$tablename = SMWSQLStoreLight::findPropertyTableName( $property );
			$db = wfGetDB( DB_SLAVE );
			$res = $db->select( $tablename, array( 'value' ), array( 'propname' => $property->getDBkey() ),
			                    'SMW::getPropertyValues', $this->getSQLOptions( $requestoptions, 'value' ) + array( 'DISTINCT' ) );
			$result = array();
			while ( $row = $db->fetchObject( $res ) ) {
				$dv = SMWDataValueFactory::newPropertyObjectValue( $property );
				if ( $outputformat != '' ) $dv->setOutputFormat( $outputformat );
				$dv->setDBkeys( ( $tablename == 'smwsimple_special' ) ? array( $row->value ) : unserialize( $row->value ) );
				$result[] = $dv;
			}
			$db->freeResult( $res );
		}
		wfProfileOut( "SMWSQLStoreLight::getPropertyValues (SMW)" );
		return $result;
	}

	public function getPropertySubjects( SMWPropertyValue $property, $value, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStoreLight::getPropertySubjects (SMW)" );
		if ( $property->isInverse() ) { // inverses are working differently
			$noninverse = clone $property;
			$noninverse->setInverse( false );
			$result = $this->getPropertyValues( $value, $noninverse, $requestoptions );
			wfProfileOut( "SMWSQLStoreLight::getPropertySubjects (SMW)" );
			return $result;
		}

		// ***  First build $select, $from, and $where for the DB query  ***//
		$tablename = SMWSQLStoreLight::findPropertyTableName( $property );
		$db = wfGetDB( DB_SLAVE );
		$from = $db->tableName( 'page' ) . " AS p INNER JOIN " . $db->tableName( $tablename ) . " AS t ON t.pageid=p.page_id";
		$where = 't.propname=' . $db->addQuotes( $property->getDBkey() );
		if ( $value !== null ) {
			$valuestring = ( $tablename == 'smwsimple_special' ) ? reset( $value->getDBkeys() ) : serialize( $value->getDBkeys() );
			$where .= ' AND t.value=' . $db->addQuotes( $valuestring );
		}
		$select = array( 'p.page_title AS title', 'p.page_namespace AS namespace' );
		// ***  Now execute the query and read the results  ***//
		$result = array();
		$res = $db->select( $from, $select,
		                    $where . $this->getSQLConditions( $requestoptions, 'p.page_title', 'p.page_title' ),
							'SMW::getPropertySubjects',
		                    $this->getSQLOptions( $requestoptions, 'p.page_title' ) + array( 'DISTINCT' ) );
		while ( $row = $db->fetchObject( $res ) ) {
			$result[] = SMWWikiPageValue::makePage( $row->title, $row->namespace, $row->title );
		}
		$db->freeResult( $res );
		wfProfileOut( "SMWSQLStoreLight::getPropertySubjects (SMW)" );
		return $result;
	}

	public function getAllPropertySubjects( SMWPropertyValue $property, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStoreLight::getAllPropertySubjects (SMW)" );
		$result = $this->getPropertySubjects( $property, null, $requestoptions );
		wfProfileOut( "SMWSQLStoreLight::getAllPropertySubjects (SMW)" );
		return $result;
	}

	/**
	 * @todo Restrict this function to SMWWikiPageValue subjects.
	 */
	public function getProperties( $subject, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStoreLight::getProperties (SMW)" );
		$sid = $subject->getTitle()->getArticleID();
		if ( $sid == 0 ) { // no id, no page, no properties
			wfProfileOut( "SMWSQLStoreLight::getProperties (SMW)" );
			return array();
		}

		$db = wfGetDB( DB_SLAVE );
		$result = array();
		if ( $requestoptions !== null ) { // potentially need to get more results, since options apply to union
			$suboptions = clone $requestoptions;
			$suboptions->limit = $requestoptions->limit + $requestoptions->offset;
			$suboptions->offset = 0;
		} else {
			$suboptions = null;
		}
		foreach ( array( 'smwsimple_data', 'smwsimple_special' ) as $tablename ) {
			$res = $db->select( $tablename, 'DISTINCT propname',
				'pageid=' . $db->addQuotes($sid) . $this->getSQLConditions( $suboptions, 'propname', 'propname' ),
				'SMW::getProperties', $this->getSQLOptions( $suboptions, 'propname' ) );
			while ( $row = $db->fetchObject( $res ) ) {
				$result[] = SMWPropertyValue::makeProperty( $row->propname );
			}
			$db->freeResult( $res );
		}
		$result = $this->applyRequestOptions( $result, $requestoptions ); // apply options to overall result
		wfProfileOut( "SMWSQLStoreLight::getProperties (SMW)" );
		return $result;
	}

	/**
	 * Implementation of SMWStore::getInProperties(). This function is meant to
	 * be used for finding properties that link to wiki pages.
	 * @todo When used for other datatypes, the function may return too many
	 * properties since it selects results by comparing the stored information
	 * (DB keys) only, while not currently comparing the type of the returned
	 * property to the type of the queried data. So values with the same DB keys
	 * can be confused. This is a minor issue now since no code is known to use
	 * this function in cases where this occurs.
	 */
	public function getInProperties( SMWDataValue $value, $requestoptions = null ) {
		wfProfileIn( "SMWSQLStoreLight::getInProperties (SMW)" );
		$db = wfGetDB( DB_SLAVE );
		$result = array();
		$typeid = $value->getTypeID();

		if ( $requestoptions !== null ) { // potentially need to get more results, since options apply to union
			$suboptions = clone $requestoptions;
			$suboptions->limit = $requestoptions->limit + $requestoptions->offset;
			$suboptions->offset = 0;
		} else {
			$suboptions = null;
		}
		foreach ( array( 'smwsimple_data', 'smwsimple_special' ) as $tablename ) {
			if ( SMWSQLStoreLight::findTypeTableName( $typeid ) != $tablename ) continue;
			$valuestring = ( $tablename == 'smwsimple_special' ) ? reset( $value->getDBkeys() ) : serialize( $value->getDBkeys() );
			$where = 'value=' . $db->addQuotes( $valuestring );
			$res = $db->select( $tablename, 'DISTINCT propname', // select sortkey since it might be used in ordering (needed by Postgres)
								$where . $this->getSQLConditions( $suboptions, 'propname', 'propname' ),
								'SMW::getInProperties', $this->getSQLOptions( $suboptions, 'propname' ) );
			while ( $row = $db->fetchObject( $res ) ) {
				$result[] = SMWPropertyValue::makeProperty( $row->propname );
			}
			$db->freeResult( $res );
		}
		$result = $this->applyRequestOptions( $result, $requestoptions ); // apply options to overall result
		wfProfileOut( "SMWSQLStoreLight::getInProperties (SMW)" );
		return $result;
	}

///// Writing methods /////

	public function deleteSubject( Title $subject ) {
		wfProfileIn( 'SMWSQLStoreLight::deleteSubject (SMW)' );
		wfRunHooks( 'SMWSQLStoreLight::deleteSubjectBefore', array( $this, $subject ) );
		$this->deleteSemanticData( SMWWikiPageValue::makePageFromTitle( $subject ) );
		///FIXME: if a property page is deleted, more pages may need to be updated by jobs!
		///TODO: who is responsible for these updates? Some update jobs are currently created in SMW_Hooks, some internally in the store
		///FIXME: clean internal caches here
		wfRunHooks( 'SMWSQLStoreLight::deleteSubjectAfter', array( $this, $subject ) );
		wfProfileOut( 'SMWSQLStoreLight::deleteSubject (SMW)' );
	}

	public function updateData( SMWSemanticData $data ) {
		wfProfileIn( "SMWSQLStoreLight::updateData (SMW)" );
		wfRunHooks( 'SMWSQLStoreLight::updateDataBefore', array( $this, $data ) );
		$subject = $data->getSubject();
		$this->deleteSemanticData( $subject );
		$sid = $subject->getTitle()->getArticleID();
		$updates = array(); // collect data for bulk updates; format: tableid => updatearray
		foreach ( $data->getProperties() as $property ) {
			$tablename = SMWSQLStoreLight::findPropertyTableName( $property );
			if ( $tablename == '' ) continue;
			foreach ( $data->getPropertyValues( $property ) as $dv ) {
				if ( !$dv->isValid() ) continue;
				if ( $dv instanceof SMWContainerValue ) {
					continue;  // subobjects not supported in this store right now; maybe could simply be PHP serialized
				} else {
					$uvals = array( 'pageid' => $sid, 'propname' => $property->getDBkey(),
					                'value' => ( $tablename == 'smwsimple_special' ? reset($dv->getDBkeys()) : serialize($dv->getDBkeys()) ) );
				}
				if ( !array_key_exists( $tablename, $updates ) ) $updates[$tablename] = array();
				$updates[$tablename][] = $uvals;
			}
		}
		$db = wfGetDB( DB_MASTER );
		foreach ( $updates as $tablename => $uvals ) {
 			$db->insert( $tablename, $uvals, "SMW::updateData$tablename" );
		}

		// Finally update caches (may be important if jobs are directly following this call)
		$this->m_semdata[$sid] = clone $data;
		$this->m_sdstate[$sid] = array( 'smwsimple_data' => true , 'smwsimple_special' => true ); // everything that one can know

		wfRunHooks( 'SMWSQLStoreLight::updateDataAfter', array( $this, $data ) );
		wfProfileOut( "SMWSQLStoreLight::updateData (SMW)" );
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
	 */
	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		global $smwgQEqualitySupport;
		wfProfileIn( "SMWSQLStoreLight::changeTitle (SMW)" );
		// get IDs but do not resolve redirects:
		$sid = $this->getSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), '', false );
		$tid = $this->getSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '', false );
		$db = wfGetDB( DB_MASTER );

		if ( ( $tid == 0 ) && ( $smwgQEqualitySupport != SMW_EQ_NONE ) ) { // target not used anywhere yet, just hijack its title for our current id
			// This condition may not hold even if $newtitle is currently unused/non-existing since we keep old IDs.
			// If equality support is off, then this simple move does too much; fall back to general case below.
			if ( $sid != 0 ) { // change id entry to refer to the new title
				$db->update( 'smw_ids', array( 'smw_title' => $newtitle->getDBkey(), 'smw_namespace' => $newtitle->getNamespace(), 'smw_iw' => '' ),
				            array( 'smw_id' => $sid ), 'SMWSQLStoreLight::changeTitle' );
			} else { // make new (target) id for use in redirect table
				$sid = $this->makeSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), '' );
			} // at this point, $sid is the id of the target page (according to smw_ids)
			$this->makeSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), SMW_SQL2_SMWREDIIW ); // make redirect id for oldtitle
			$db->insert( 'smw_redi2', array( 's_title' => $oldtitle->getDBkey(), 's_namespace' => $oldtitle->getNamespace(), 'o_id' => $sid ),
			             'SMWSQLStoreLight::changeTitle' );
			$this->m_ids[" " . $oldtitle->getNamespace() . " " . $oldtitle->getDBkey() . " C"] = $sid;
			// $this->m_ids[" " . $oldtitle->getNamespace() . " " . $oldtitle->getDBkey() . " -"] = Already OK after makeSMWPageID above
			$this->m_ids[" " . $newtitle->getNamespace() . " " . $newtitle->getDBkey() . " C"] = $sid;
			$this->m_ids[" " . $newtitle->getNamespace() . " " . $newtitle->getDBkey() . " -"] = $sid;
			/// NOTE: there is the (bad) case that the moved page is a redirect. As chains of
			/// redirects are not supported by MW or SMW, the above is maximally correct in this case too.
			/// NOTE: this temporarily leaves existing redirects to oldtitle point to newtitle as well, which
			/// will be lost after the next update. Since double redirects are an error anyway, this is not
			/// a bad behaviour: everything will continue to work until the existing redirects are updated,
			/// which will hopefully be done to fix the double redirect.
		} else { // general move method that should be correct in all cases (equality support respected when updating redirects)
			// delete any existing data from new title:
			$this->deleteSemanticData( SMWWikiPageValue::makePageFromTitle( $newtitle ) ); // $newtitle should not have data, but let's be sure
			$this->updateRedirects( $newtitle->getDBkey(), $newtitle->getNamespace() ); // may trigger update jobs!
			// move all data of old title to new position:
			if ( $sid != 0 ) {
				$this->changeSMWPageID( $sid, $tid, $oldtitle->getNamespace(), $newtitle->getNamespace(), true, false );
			}
			// now write a redirect from old title to new one; this also updates references in other tables as needed
			$this->updateRedirects( $oldtitle->getDBkey(), $oldtitle->getNamespace(), $newtitle->getDBkey(), $newtitle->getNamespace() );
			/// TODO: may not be optimal for the standard case that newtitle existed and redirected to oldtitle (PERFORMANCE)
		}
		wfProfileOut( "SMWSQLStoreLight::changeTitle (SMW)" );
	}

///// Query answering /////

	function getQueryResult( SMWQuery $query ) {
		return null; // not supported by this store
	}

///// Special page functions /////

	public function getPropertiesSpecial( $requestoptions = null ) {
		return array(); // not supported by this store
	}

	public function getUnusedPropertiesSpecial( $requestoptions = null ) {
		return array(); // not supported by this store
	}

	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		return array(); // not supported by this store
	}

	public function getStatistics() {
		return array('PROPUSES' => 0, 'USEDPROPS' => 0, 'DECLPROPS' => 0 ); // not supported by this store
	}

///// Setup store /////

	public function setup( $verbose = true ) {
		$this->reportProgress( "Setting up standard database configuration for SMW ...\n\n", $verbose );
		$this->reportProgress( "Selected storage engine is \"SMWSQLStoreLight\" (or an extension thereof)\n\n", $verbose );
		$db = wfGetDB( DB_MASTER );
		$this->setupTables( $verbose, $db );
		return true;
	}

	/**
	 * Create required SQL tables. This function also performs upgrades of table contents
	 * when required.
	 */
	protected function setupTables( $verbose, $db ) {
		global $wgDBtype;
		$reportTo = $verbose ? $this : null; // Use $this to report back from static SMWSQLHelpers.

		SMWSQLHelpers::setupTable( // table for most data
			'smwsimple_data',
			array(
				'pageid' => SMWSQLHelpers::getStandardDBType( 'id' ) . ' NOT NULL',
				'propname' => SMWSQLHelpers::getStandardDBType( 'title' ) . ' NOT NULL',
				'value' => SMWSQLHelpers::getStandardDBType( 'blob' ) . ' NOT NULL'
			),
			$db,
			$reportTo
		);
		SMWSQLHelpers::setupIndex( 'smwsimple_data', array( 'pageid', 'propname,value(256)' ), $db );
		SMWSQLHelpers::setupTable( // table for data that is needed frequently and looked-up often, e.g. property type declarations
			'smwsimple_special',
			array(
				'pageid' => SMWSQLHelpers::getStandardDBType( 'id' ) . ' NOT NULL',
				'propname' => SMWSQLHelpers::getStandardDBType( 'title' ) . ' NOT NULL',
				'value' => SMWSQLHelpers::getStandardDBType( 'title' ) . ' NOT NULL'
			),
			$db,
			$reportTo
		);
		SMWSQLHelpers::setupIndex( 'smwsimple_special', array( 'pageid', 'pageid,propname', 'propname,value' ), $db );

		$this->reportProgress( "Database initialised successfully.\n\n", $verbose );
	}

	public function drop( $verbose = true ) {
		global $wgDBtype;
		$this->reportProgress( "Deleting all database content and tables generated by SMW ...\n\n", $verbose );
		$db = wfGetDB( DB_MASTER );
		$tables = array( 'smwsimple_data', 'smwsimple_special' );
		foreach ( $tables as $table ) {
			$name = $db->tableName( $table );
			$db->query( 'DROP TABLE' . ( $wgDBtype == 'postgres' ? '':' IF EXISTS' ) . $name, 'SMWSQLStoreLight::drop' );
			$this->reportProgress( " ... dropped table $name.\n", $verbose );
		}
		$this->reportProgress( "All data removed successfully.\n", $verbose );
		return true;
	}

	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		$updatejobs = array();
		$emptyrange = true; // was nothing found in this run?

		// update by MediaWiki page id --> make sure we get all pages
		$tids = array();
		for ( $i = $index; $i < $index + $count; $i++ ) { // array of ids
			$tids[] = $i;
		}
		$titles = Title::newFromIDs( $tids );
		foreach ( $titles as $title ) {
			// set $wgTitle, in case semantic data is set based
			// on values not originating from the page (such as
			// via the External Data extension)
			global $wgTitle;
			$wgTitle = $title;
			if ( ( $namespaces == false ) || ( in_array( $title->getNamespace(), $namespaces ) ) ) {
				$updatejobs[] = new SMWUpdateJob( $title );
				$emptyrange = false;
			}
		}

		// update by internal SMW id --> make sure we get all objects in SMW
		$db = wfGetDB( DB_SLAVE );
		$res = $db->select( 'smw_ids', array( 'smw_id', 'smw_title', 'smw_namespace', 'smw_iw' ),
		                   "smw_id >= $index AND smw_id < " . $db->addQuotes( $index + $count ), __METHOD__ );
		foreach ( $res as $row ) {
			$emptyrange = false; // note this even if no jobs were created
			if ( ( $namespaces != false ) && ( !in_array( $row->smw_namespace, $namespaces ) ) ) continue;
			if ( ( $row->smw_iw == '' ) || ( $row->smw_iw == SMW_SQL2_SMWREDIIW ) ) { // objects representing pages in the wiki, even special pages
				// TODO: special treament of redirects needed, since the store will not act on redirects that did not change according to its records
				$title = Title::makeTitle( $row->smw_namespace, $row->smw_title );
				if ( !$title->exists() ) {
					$updatejobs[] = new SMWUpdateJob( $title );
				}
			} elseif ( $row->smw_iw { 0 } != ':' ) { // refresh all "normal" interwiki pages by just clearing their content
				$this->deleteSemanticData( SMWWikiPageValue::makePage( $row->smw_namespace, $row->smw_title, '', $row->smw_iw ) );
			}
		}
		$db->freeResult( $res );

		wfRunHooks('smwRefreshDataJobs', array(&$updatejobs));

		if ( $usejobs ) {
			Job::batchInsert( $updatejobs );
		} else {
			foreach ( $updatejobs as $job ) {
				$job->run();
			}
		}
		$nextpos = $index + $count;
		if ( $emptyrange ) { // nothing found, check if there will be more pages later on
			$next1 = $db->selectField( 'page', 'page_id', "page_id >= $nextpos", __METHOD__, array( 'ORDER BY' => "page_id ASC" ) );
			$next2 = $db->selectField( 'smw_ids', 'smw_id', "smw_id >= $nextpos", __METHOD__, array( 'ORDER BY' => "smw_id ASC" ) );
			$nextpos = ( ( $next2 != 0 ) && ( $next2 < $next1 ) ) ? $next2:$next1;
		}
		$max1 = $db->selectField( 'page', 'MAX(page_id)', '', __METHOD__ );
		$max2 = $db->selectField( 'smw_ids', 'MAX(smw_id)', '', __METHOD__ );
		$index = $nextpos ? $nextpos: - 1;
		return ( $index > 0 ) ? $index / max( $max1, $max2 ) : 1;
	}


///// Concept caching /////

	/**
	 * Refresh the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function refreshConceptCache( $concept ) {
		return false; // not supported by this store
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function deleteConceptCache( $concept ) {
		return false; // not supported by this store
	}

	/**
	 * Return status of the concept cache for the given concept as an array
	 * with key 'status' ('empty': not cached, 'full': cached, 'no': not
	 * cachable). If status is not 'no', the array also contains keys 'size'
	 * (query size), 'depth' (query depth), 'features' (query features). If
	 * status is 'full', the array also contains keys 'date' (timestamp of
	 * cache), 'count' (number of results in cache).
	 *
	 * @param $concept Title or SMWWikiPageValue
	 */
	public function getConceptCacheStatus( $concept ) {
		return array( 'status' => 'no' ); // not supported by this store
	}


///// Helper methods, mostly protected /////

	/**
	 * Transform input parameters into a suitable array of SQL options.
	 * The parameter $valuecol defines the string name of the column to which
	 * sorting requests etc. are to be applied.
	 */
	protected function getSQLOptions( $requestoptions, $valuecol = '' ) {
		$sql_options = array();
		if ( $requestoptions !== null ) {
			if ( $requestoptions->limit > 0 ) {
				$sql_options['LIMIT'] = $requestoptions->limit;
			}
			if ( $requestoptions->offset > 0 ) {
				$sql_options['OFFSET'] = $requestoptions->offset;
			}
			if ( ( $valuecol != '' ) && ( $requestoptions->sort ) ) {
				$sql_options['ORDER BY'] = $requestoptions->ascending ? $valuecol : $valuecol . ' DESC';
			}
		}
		return $sql_options;
	}

	/**
	 * Transform input parameters into a suitable string of additional SQL conditions.
	 * The parameter $valuecol defines the string name of the column to which
	 * value restrictions etc. are to be applied.
	 * @param $requestoptions object with options
	 * @param $valuecol name of SQL column to which conditions apply
	 * @param $labelcol name of SQL column to which string conditions apply, if any
	 * @param $addand Boolean to indicate whether the string should begin with " AND " if non-empty
	 */
	protected function getSQLConditions( $requestoptions, $valuecol = '', $labelcol = '', $addand = true ) {
		$sql_conds = '';
		if ( $requestoptions !== null ) {
			$db = wfGetDB( DB_SLAVE ); /// TODO avoid doing this here again, all callers should have one
			if ( ( $valuecol != '' ) && ( $requestoptions->boundary !== null ) ) { // apply value boundary
				if ( $requestoptions->ascending ) {
					$op = $requestoptions->include_boundary ? ' >= ':' > ';
				} else {
					$op = $requestoptions->include_boundary ? ' <= ':' < ';
				}
				$sql_conds .= ( $addand ? ' AND ':'' ) . $valuecol . $op . $db->addQuotes( $requestoptions->boundary );
			}
			if ( $labelcol != '' ) { // apply string conditions
				foreach ( $requestoptions->getStringConditions() as $strcond ) {
					$string = str_replace( '_', '\_', $strcond->string );
					switch ( $strcond->condition ) {
						case SMWStringCondition::STRCOND_PRE:  $string .= '%'; break;
						case SMWStringCondition::STRCOND_POST: $string = '%' . $string; break;
						case SMWStringCondition::STRCOND_MID:  $string = '%' . $string . '%'; break;
					}
					$sql_conds .= ( ( $addand || ( $sql_conds != '' ) ) ? ' AND ':'' ) . $labelcol . ' LIKE ' . $db->addQuotes( $string );
				}
			}
		}
		return $sql_conds;
	}

	/**
	 * Not in all cases can requestoptions be forwarded to the DB using
	 * getSQLConditions() and getSQLOptions(): some data comes from caches that
	 * do not respect the options yet. This method takes an array of results
	 * (SMWDataValue objects) *of the same type* and applies the given
	 * requestoptions as appropriate.
	 */
	protected function applyRequestOptions( $data, $requestoptions ) {
		wfProfileIn( "SMWSQLStoreLight::applyRequestOptions (SMW)" );
		if ( ( count( $data ) == 0 ) || ( $requestoptions === null ) ) {
			wfProfileOut( "SMWSQLStoreLight::applyRequestOptions (SMW)" );
			return $data;
		}
		$result = array();
		$sortres = array();
		$tablename = SMWSQLStoreLight::findTypeTableName( reset( $data )->getTypeID() );

		$i = 0;
		foreach ( $data as $item ) {
			$ok = true; // keep datavalue only if this remains true
			$value = ( $tablename == 'smwsimple_special' ) ? reset( $item->getDBkeys() ) : serialize( $item->getDBkeys() );
			if ( $requestoptions->boundary !== null ) { // apply value boundary
				$strc = strcmp( $value, $requestoptions->boundary );
				if ( $requestoptions->ascending ) {
					if ( $requestoptions->include_boundary ) {
						$ok = ( $strc >= 0 );
					} else {
						$ok = ( $strc > 0 );
					}
				} else {
					if ( $requestoptions->include_boundary ) {
						$ok = ( $strc <= 0 );
					} else {
						$ok = ( $strc < 0 );
					}
				}
			}
			foreach ( $requestoptions->getStringConditions() as $strcond ) { // apply string conditions
				switch ( $strcond->condition ) {
					case SMWStringCondition::STRCOND_PRE:
						$ok = $ok && ( strpos( $value, $strcond->string ) === 0 );
						break;
					case SMWStringCondition::STRCOND_POST:
						$ok = $ok && ( strpos( strrev( $value ), strrev( $strcond->string ) ) === 0 );
						break;
					case SMWStringCondition::STRCOND_MID:
						$ok = $ok && ( strpos( $value, $strcond->string ) !== false );
						break;
				}
			}
			if ( $ok ) {
				$result[$i] = $item;
				$sortres[$i] = $value; // maybe $value could also be used as array key here
				$i++;
			}
		}
		if ( $requestoptions->sort ) {
			$flag = SORT_LOCALE_STRING;
			if ( $requestoptions->ascending ) {
				asort( $sortres, $flag );
			} else {
				arsort( $sortres, $flag );
			}
			$newres = array();
			foreach ( $sortres as $key => $value ) {
				$newres[] = $result[$key];
			}
			$result = $newres;
		}
		if ( $requestoptions->limit > 0 ) {
			$result = array_slice( $result, $requestoptions->offset, $requestoptions->limit );
		} else {
			$result = array_slice( $result, $requestoptions->offset );
		}
		wfProfileOut( "SMWSQLStoreLight::applyRequestOptions (SMW)" );
		return $result;
	}

	/**
	 * Print some output to indicate progress. The output message is given by
	 * $msg, while $verbose indicates whether or not output is desired at all.
	 */
	public function reportProgress( $msg, $verbose = true ) {
		if ( $verbose ) {
			if ( ob_get_level() == 0 ) { // be sure to have some buffer, otherwise some PHPs complain
				ob_start();
			}
			print $msg;
			ob_flush();
			flush();
		}
	}

	/**
	 * Retrieve the name of the property table that is to be used for storing
	 * values for the given property object.
	 */
	public static function findPropertyTableName( $property ) {
		return SMWSQLStoreLight::findTypeTableName( $property->getPropertyTypeID() );
	}

	/**
	 * Retrieve the name of the property table that is to be used for storing
	 * values for the given property object.
	 */
	public static function findTypeTableName( $typeid ) {
		if ( array_key_exists( $typeid, SMWSQLStoreLight::$special_types ) ) {
			return 'smwsimple_special';
		} else {
			return 'smwsimple_data';
		}
	}

	/**
	 * Delete all semantic data stored for the given subject. Used for update
	 * purposes.
	 */
	protected function deleteSemanticData( SMWWikiPageValue $subject ) {
		$db = wfGetDB( DB_MASTER );
		$id = $subject->getTitle()->getArticleID();
		if ( $id == 0 ) return; // no data can be deleted (and hopefully no data exists)
		foreach ( array( 'smwsimple_data', 'smwsimple_special' ) as $tablename ) {
			$db->delete( $tablename, array( 'pageid' => $id ), 'SMW::deleteSemanticData' );
		}
		wfRunHooks( 'smwDeleteSemanticData', array( $subject ) );
	}

}
