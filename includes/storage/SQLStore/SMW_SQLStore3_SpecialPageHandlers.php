<?php
/**
 * @file
 * @ingroup SMWStore
 * @since 1.8
 */

/**
 * Class Handling all the Special Page methods for SMWSQLStore3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since 1.8
 * @ingroup SMWStore
 */
class SMWSQLStore3SpecialPageHandlers {

	/**
	 * The store used by this specialPageHandler
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	protected $store;


	public function __construct( SMWSQLStore3 $parentstore ) {
		$this->store = $parentstore;
	}

	/**
	 * Implementation of SMWStore::getPropertiesSpecial(). It works by
	 * querying for all properties in the SMW IDs table (based on their
	 * namespace) and getting their usage from the property statistics
	 * table. When asking for unused properties, the result does not
	 * include the usage count (which is always 0 then).
	 *
	 * @bug Properties that are used as super properties of others are reported as unused now.
	 *
	 * @see SMWStore::getPropertiesSpecial()
	 * @see SMWStore::getUnusedPropertiesSpecial()
	 * @since 1.8
	 * @param SMWRequestOptions $requestoptions
	 * @param boolean $unusedProperties
	 * @return array
	 */
	public function getPropertiesSpecial( SMWRequestOptions $requestoptions = null, $unusedProperties = false ) {
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

		$conds = array( 'smw_namespace' => SMW_NS_PROPERTY, 'smw_iw' => '' );
		if( $unusedProperties ) {
			$conds['usage_count'] = 0;
		}
		$res = $dbr->select(
				array( SMWSql3SmwIds::tableName, SMWSQLStore3::PROPERTY_STATISTICS_TABLE ),
				array( 'smw_title', 'usage_count' ),
				$conds,
				__METHOD__,
				$options,
				array( SMWSql3SmwIds::tableName => array( 'INNER JOIN', array( 'smw_id=p_id' ) ) )
		);

		$result = array();
		foreach ( $res as $row ) {
			try {
				$property = new SMWDIProperty( $row->smw_title );
			} catch ( SMWDataItemException $e ) {
				// The following is not ideal, but more changes are needed for better error reporting:
				// * The code needs to make assumptions about the context in which the message is used (content language, escaping)
				// * The message text is not ideal for this situation.
				$property = new SMWDIError( array( wfMessage( 'smw_noproperty', $row->smw_title )->inContentLanguage()->text() ) );
			}
			$result[] = $unusedProperties ? $property : array( $property, $row->usage_count );
		}

		$dbr->freeResult( $res );
		wfProfileOut( "SMWSQLStore3::getPropertiesSpecial (SMW)" );
		return $result;
	}

	/**
	 * Implementation of SMWStore::getUnusedPropertiesSpecial(). It works by
	 * calling getPropertiesSpecial() with additional parameters.
	 *
	 * @see SMWStore::getUnusedPropertiesSpecial()
	 * @since 1.8
	 * @param SMWRequestOptions $requestoptions
	 * @return array
	 */
	public function getUnusedPropertiesSpecial( SMWRequestOptions $requestoptions = null ) {
		wfProfileIn( "SMWSQLStore3::getUnusedPropertiesSpecial (SMW)" );
		$result = $this->getPropertiesSpecial( $requestoptions, true );
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

		if ( !$proptable->isFixedPropertyTable() ) { // anything else would be crazy, but let's fail gracefully even if the whole world is crazy
			$dbr = wfGetDB( DB_SLAVE );

			$options = $this->store->getSQLOptions( $requestoptions, 'title' );
			$options['ORDER BY'] = 'count DESC';

			$res = $dbr->select( // TODO: this is not how JOINS should be specified in the select function
				$dbr->tableName( $proptable->getName() ) . ' INNER JOIN ' .
					$dbr->tableName( SMWSql3SmwIds::tableName ) . ' ON p_id=smw_id LEFT JOIN ' .
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
		$res = $dbr->select( $typetable->getName(), 'COUNT(s_id) AS count', array(), 'SMW::getStatistics' );
		$row = $dbr->fetchObject( $res );
		$result['DECLPROPS'] = $row->count;
		$dbr->freeResult( $res );

		// count property uses by counting rows in property tables,
		// count used properties by counting distinct properties in each table
		$result['PROPUSES'] = 0;
		$result['USEDPROPS'] = 0;

		foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
			/// Note: subproperties that are part of container values are counted individually;
			/// It does not seem to be important to filter them by adding more conditions.
			$res = $dbr->select( $proptable->getName(), 'COUNT(*) AS count', '', 'SMW::getStatistics' );
			$row = $dbr->fetchObject( $res );
			$result['PROPUSES'] += $row->count;
			$dbr->freeResult( $res );

			if ( !$proptable->isFixedPropertyTable() ) {
				$res = $dbr->select( $proptable->getName(), 'COUNT(DISTINCT(p_id)) AS count', '', 'SMW::getStatistics' );
				$row = $dbr->fetchObject( $res );
				$result['USEDPROPS'] += $row->count;
			} else {
				$res = $dbr->select( $proptable->getName(), '*', '', 'SMW::getStatistics', array( 'LIMIT' => 1 ) );
				if ( $dbr->numRows( $res ) > 0 )  $result['USEDPROPS']++;
			}

			$dbr->freeResult( $res );
		}

		wfProfileOut( 'SMWSQLStore3::getStatistics (SMW)' );
		return $result;
	}
}
