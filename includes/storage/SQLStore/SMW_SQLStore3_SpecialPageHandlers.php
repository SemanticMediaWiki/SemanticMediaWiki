<?php

/**
 * Class Handling all the Special Page methods for SMWSQLStore3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since SMW.storerewrite
 * @file
 * @ingroup SMWStore
 */

Class SMWSQLStore3SpecialPageHandlers {

	/**
	 * The store used by this specialPageHandler
	 *
	 * @since SMW.storerewrite
	 * @var SMWSQLStore3
	 */
	protected $store;


	public function __construct( &$parentstore ) {
		$this->store = $parentstore;
	}

	/**
	 * Implementation of SMWStore::getPropertiesSpecial(). It works by
	 * querying for all properties in smw_ids by identifying from the
	 * namespace and then counting their usage one by one by querying their tables.
	 */
	public function getPropertiesSpecial( $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore3::getPropertiesSpecial (SMW)" );
		$dbr = wfGetDB( DB_SLAVE );
		// the query needs to do the filtering of internal properties, else LIMIT is wrong

		$options = array( 'ORDER BY' => 'smw_sortkey' );
		if ( $requestoptions !== null ) {
			if ( $requestoptions->limit > 0 ) {
				$options['LIMIT'] = $requestoptions->limit;
				$options['OFFSET'] = max( $requestoptions->offset, 0 );
			}
		}

		$res = $dbr->select(
				'smw_ids',
				array( 'smw_id', 'smw_title', 'smw_sortkey' ),
				'smw_namespace = ' . $dbr->addQuotes( SMW_NS_PROPERTY ) . ' AND smw_iw = ' .
					$dbr->addQuotes( '' ) . ' OR smw_iw = ' . $dbr->addQuotes( SMW_SQL3_SMWPREDEFIW ),
				__METHOD__,
				$options
		);

		$result = array();
		$proptables = SMWSQLStore3::getPropertyTables();

		foreach ( $res as $row ) {
			$di = new SMWDIProperty( $row->smw_title );
			$tableId = SMWSQLStore3::findPropertyTableID( $di );
			$proptable = $proptables[$tableId];
			$propRow = $dbr->selectRow(
					$proptable->name,
					'Count(*) as count',
					$proptable->fixedproperty ? array() : array('p_id' => $row->smw_id ),
					__METHOD__
			);
			$result[] = array( $di, $propRow->count );
		}

		$dbr->freeResult( $res );
		wfProfileOut( "SMWSQLStore3::getPropertiesSpecial (SMW)" );

		return $result;
	}

	/**
	 * Implementation of SMWStore::getUnusedPropertiesSpecial(). It works by
	 * creating a temporary table with all property pages from which all used
	 * properties are then deleted. This is still a costy operation, and some
	 * slower but lessdemanding way of getting at this data is required for
	 * larger wikis.
	 */
	public function getUnusedPropertiesSpecial( $requestoptions = null ) {
		global $wgDBtype;

		wfProfileIn( "SMWSQLStore3::getUnusedPropertiesSpecial (SMW)" );
		$dbr = wfGetDB( DB_SLAVE );

		// we use a temporary table for executing this costly operation on the DB side
		$smw_tmp_unusedprops = $dbr->tableName( 'smw_tmp_unusedprops' );
		if ( $wgDBtype == 'postgres' ) { // PostgresQL: no in-memory tables available
			$sql = "CREATE OR REPLACE FUNCTION create_smw_tmp_unusedprops() RETURNS void AS "
				   . "$$ "
				   . "BEGIN "
				   . " IF EXISTS(SELECT NULL FROM pg_tables WHERE tablename='smw_tmp_unusedprops' AND schemaname = ANY (current_schemas(true))) "
				   . " THEN DELETE FROM " . $smw_tmp_unusedprops . "; "
				   . " ELSE "
				   . "  CREATE TEMPORARY TABLE " . $smw_tmp_unusedprops . " ( title text ); "
				   . " END IF; "
				   . "END; "
				   . "$$ "
				   . "LANGUAGE 'plpgsql'; "
				   . "SELECT create_smw_tmp_unusedprops(); ";
		} else { // MySQL: use temporary in-memory table
			$sql = "CREATE TEMPORARY TABLE " . $smw_tmp_unusedprops . "( title VARCHAR(255) ) ENGINE=MEMORY";
		}

		$dbr->query( $sql, __METHOD__ );

		$dbr->insertSelect( $smw_tmp_unusedprops, 'page', array( 'title' => 'page_title' ),
		                  array( "page_namespace" => SMW_NS_PROPERTY ),  __METHOD__ );

		$smw_ids = $dbr->tableName( 'smw_ids' );

		// all predefined properties are assumed to be used:
		$dbr->deleteJoin( $smw_tmp_unusedprops, $smw_ids, 'title', 'smw_title', array( 'smw_iw' => SMW_SQL3_SMWPREDEFIW ), __METHOD__ );

		// all tables occurring in some property table are used:
		foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
			if ( $proptable->fixedproperty == false ) {
				// MW does not seem to have a wrapper for this:
				if ( $wgDBtype == 'postgres' ) { // PostgresQL: don't repeat the FROM table in USING
					$sql = "DELETE FROM $smw_tmp_unusedprops USING " .
						$dbr->tableName( $proptable->name ) .
						" INNER JOIN $smw_ids ON p_id=smw_id WHERE" .
						" title=smw_title AND smw_iw=" . $dbr->addQuotes( '' );
				} else {
					$sql = "DELETE FROM $smw_tmp_unusedprops USING " .
					"$smw_tmp_unusedprops INNER JOIN " . $dbr->tableName( $proptable->name ) .
					" INNER JOIN $smw_ids ON p_id=smw_id AND title=smw_title" .
					" AND smw_iw=" . $dbr->addQuotes( '' );
				}
				$dbr->query( $sql, __METHOD__ );
			} // else: todo
		}

		// properties that have subproperties are considered to be used
		$propertyTables = SMWSQLStore3::getPropertyTables();
		$subPropertyTableId = SMWSQLStore3::$special_tables['_SUBP'];
		$subPropertyTable = $propertyTables[$subPropertyTableId];

		// Again we have no fitting MW wrapper here:
		if ( $wgDBtype == 'postgres' ) { // PostgresQL: don't repeat the FROM table in USING
			$sql = "DELETE FROM $smw_tmp_unusedprops USING " .
				$dbr->tableName( $subPropertyTable->name ) .
				" INNER JOIN $smw_ids ON o_id=smw_id WHERE title=smw_title";
		} else {
			$sql = "DELETE $smw_tmp_unusedprops.* FROM $smw_tmp_unusedprops," .
				$dbr->tableName( $subPropertyTable->name ) .
				" INNER JOIN $smw_ids ON o_id=smw_id WHERE title=smw_title";
		}
		$dbr->query( $sql, __METHOD__ );

		// properties that are redirects are considered to be used:
		//   (a stricter and more costy approach would be to delete only redirects to used properties;
		//    this would need to be done with an addtional query in the above loop)
		// The redirect table is a fixed part of this store, no need to find its name.
		$dbr->deleteJoin( $smw_tmp_unusedprops, 'smw_redi', 'title', 's_title', array( 's_namespace' => SMW_NS_PROPERTY ), __METHOD__ );

		$options = $this->store->getSQLOptions( $requestoptions, 'title' );
		$options['ORDER BY'] = 'title';
		$res = $dbr->select( $smw_tmp_unusedprops, 'title', '', __METHOD__, $options );

		$result = array();

		foreach ( $res as $row ) {
			$result[] = new SMWDIProperty( $row->title );
		}

		$dbr->freeResult( $res );

		$dbr->query( "DROP " . ( $wgDBtype == 'postgres' ? '' : 'TEMPORARY' ) .
			" TABLE $smw_tmp_unusedprops", __METHOD__ );
		wfProfileOut( "SMWSQLStore3::getUnusedPropertiesSpecial (SMW)" );

		return $result;
	}

	/**
	 * Implementation of SMWStore::getWantedPropertiesSpecial(). Like all
	 * WantedFoo specials, this function is very resource intensive and needs
	 * to be cached on medium/large wikis.
	 *
	 * @param SMWRequestOptions $requestoptions
	 *
	 * @return array of array( SMWDIProperty, int )
	 */
	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		global $smwgPDefaultType;

		wfProfileIn( "SMWSQLStore3::getWantedPropertiesSpecial (SMW)" );

		// Note that Wanted Properties must have the default type.
		$proptables = SMWSQLStore3::getPropertyTables();
		$proptable = $proptables[SMWSQLStore3::findTypeTableId( $smwgPDefaultType )];

		$result = array();

		if ( $proptable->fixedproperty == false ) { // anything else would be crazy, but let's fail gracefully even if the whole world is crazy
			$dbr = wfGetDB( DB_SLAVE );

			$options = $this->store->getSQLOptions( $requestoptions, 'title' );
			$options['ORDER BY'] = 'count DESC';

			$res = $dbr->select( // TODO: this is not how JOINS should be specified in the select function
				$dbr->tableName( $proptable->name ) . ' INNER JOIN ' .
					$dbr->tableName( 'smw_ids' ) . ' ON p_id=smw_id LEFT JOIN ' .
					$dbr->tableName( 'page' ) . ' ON (page_namespace=' .
					$dbr->addQuotes( SMW_NS_PROPERTY ) . ' AND page_title=smw_title)',
				'smw_title, COUNT(*) as count',
				'smw_id > 50 AND page_id IS NULL GROUP BY smw_title',
				'SMW::getWantedPropertiesSpecial',
				$options
			);

			foreach ( $res as $row ) {
				$result[] = array( new SMWDIProperty( $row->smw_title ), $row->count );
			}
		}

		wfProfileOut( "SMWSQLStore3::getWantedPropertiesSpecial (SMW)" );

		return $result;
	}

	public function getStatistics() {
		wfProfileIn( 'SMWSQLStore3::getStatistics (SMW)' );

		$dbr = wfGetDB( DB_SLAVE );
		$result = array();
		$proptables = SMWSQLStore3::getPropertyTables();

		// count number of declared properties by counting "has type" annotations
		$typeprop = new SMWDIProperty( '_TYPE' );
		$typetable = $proptables[SMWSQLStore3::findPropertyTableID( $typeprop )];
		$res = $db->select( $typetable->name, 'COUNT(s_id) AS count', array(), 'SMW::getStatistics' );
		$row = $db->fetchObject( $res );
		$result['DECLPROPS'] = $row->count;
		$dbr->freeResult( $res );

		// count property uses by counting rows in property tables,
		// count used properties by counting distinct properties in each table
		$result['PROPUSES'] = 0;
		$result['USEDPROPS'] = 0;

		foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
			/// Note: subproperties that are part of container values are counted individually;
			/// It does not seem to be important to filter them by adding more conditions.
			$res = $dbr->select( $proptable->name, 'COUNT(*) AS count', '', 'SMW::getStatistics' );
			$row = $dbr->fetchObject( $res );
			$result['PROPUSES'] += $row->count;
			$dbr->freeResult( $res );

			if ( $proptable->fixedproperty == false ) {
				$res = $dbr->select( $proptable->name, 'COUNT(DISTINCT(p_id)) AS count', '', 'SMW::getStatistics' );
				$row = $dbr->fetchObject( $res );
				$result['USEDPROPS'] += $row->count;
			} else {
				$res = $dbr->select( $proptable->name, '*', '', 'SMW::getStatistics', array( 'LIMIT' => 1 ) );
				if ( $dbr->numRows( $res ) > 0 )  $result['USEDPROPS']++;
			}

			$dbr->freeResult( $res );
		}

		wfProfileOut( 'SMWSQLStore3::getStatistics (SMW)' );
		return $result;
	}
}
