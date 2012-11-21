<?php
/**
 * @file
 * @ingroup SMWStore
 * @since 1.8
 * @author Markus Krötzsch
 */

/**
 * Class to access the SMW IDs table in SQLStore3.
 * Provides transparent in-memory caching facilities.
 *
 * Documentation for the SMW IDs table: This table is a dictionary that
 * assigns integer IDs to pages, properties, and other objects used by SMW.
 * All tables that refer to such objects store these IDs instead. If the ID
 * information is lost (e.g., table gets deleted), then the data stored in SMW
 * is no longer meaningful: all tables need to be dropped, recreated, and
 * refreshed to get back to a working database.
 *
 * The table has a column for storing interwiki prefixes, used to refer to
 * pages on external sites (like in MediaWiki). This column is also used to
 * mark some special objects in the table, using "interwiki prefixes" that
 * cannot occur in MediaWiki:
 *
 * - Rows with iw SMW_SQL3_SMWREDIIW are similar to normal entries for
 * (internal) wiki pages, but the iw indicates that the page is a redirect, the
 * (target of which should be sought using the smw_fpt_redi table.
 *
 * - The (unique) row with iw SMW_SQL3_SMWBORDERIW just marks the border
 * between predefined ids (rows that are reserved for hardcoded ids built into
 * SMW) and normal entries. It is no object, but makes sure that SQL's auto
 * increment counter is high enough to not add any objects before that marked
 * "border".
 *
 * @note Do not call the constructor of SMWDIWikiPage using data from the SMW
 * IDs table; use SMWDIHandlerWikiPage::dataItemFromDBKeys() instead. The table
 * does not always contain data as required wiki pages. Especially predefined
 * properties are represented by language-independent keys rather than proper
 * titles. SMWDIHandlerWikiPage takes care of this.
 *
 * @since 1.8
 *
 * @ingroup SMWStore
 */
class SMWSql3SmwIds {

	/**
	 * Name of the table to store IDs in.
	 *
	 * @note This should never change. Existing wikis will have to drop and
	 * rebuild their SMW tables completely to recover from any change here.
	 */
	const tableName = 'smw_object_ids';

	/**
	 * Id for which property table hashes are cached, if any.
	 *
	 * @since 1.8
	 * @var integer
	 */
	protected $hashCacheId = 0;

	/**
	 * Cached property table hashes for $hashCacheId.
	 *
	 * @since 1.8
	 * @var string
	 */
	protected $hashCacheContents = '';

	/**
	 * Maximal number of cached property IDs.
	 *
	 * @since 1.8
	 * @var integer
	 */
	public static $PROP_CACHE_MAX_SIZE = 250;

	/**
	 * Maximal number of cached non-property IDs.
	 *
	 * @since 1.8
	 * @var integer
	 */
	public static $PAGE_CACHE_MAX_SIZE = 500;

	protected $selectrow_sort_debug = 0;
	protected $selectrow_redi_debug = 0;
	protected $prophit_debug = 0;
	protected $propmiss_debug = 0;
	protected $reghit_debug = 0;
	protected $regmiss_debug = 0;
	static protected $singleton_debug = null;

	/**
	 * Parent SMWSQLStore3.
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	public $store;

	/**
	 * Cache for property IDs.
	 *
	 * @note Tests indicate that it is more memory efficient to have two
	 * arrays (IDs and sortkeys) than to have one array that stores both
	 * values in some data structure (other than a single string).
	 *
	 * @since 1.8
	 * @var array
	 */
	protected $prop_ids = array();

	/**
	 * Cache for property sortkeys.
	 *
	 * @since 1.8
	 * @var array
	 */
	protected $prop_sortkeys = array();

	/**
	 * Cache for non-property IDs.
	 *
	 * @since 1.8
	 * @var array
	 */
	protected $regular_ids = array();

	/**
	 * Cache for non-property sortkeys.
	 *
	 * @since 1.8
	 * @var array
	 */
	protected $regular_sortkeys = array();

	/**
	 * Use pre-defined ids for Very Important Properties, avoiding frequent
	 * ID lookups for those.
	 *
	 * @note These constants also occur in the store. Changing them will
	 * require to run setup.php again. They can also not be larger than 50.
	 *
	 * @since 1.8
	 * @var array
	 */
	public static $special_ids = array(
		'_TYPE' => 1,
		'_URI'  => 2,
		'_INST' => 4,
		'_UNIT' => 7,
		'_IMPO' => 8,
		'_CONV' => 12,
		'_SERV' => 13,
		'_PVAL' => 14,
		'_REDI' => 15,
		'_SUBP' => 17,
		'_SUBC' => 18,
		'_CONC' => 19,
		'_SF_DF' => 20, // Semantic Form's default form property
		'_SF_AF' => 21,  // Semantic Form's alternate form property
		'_ERRP' => 22,
// 		'_1' => 23, // properties for encoding (short) lists
// 		'_2' => 24,
// 		'_3' => 25,
// 		'_4' => 26,
// 		'_5' => 27,
		'_LIST' => 28,
		'_MDAT' => 29,
		'_CDAT' => 30,
		'_NEWP' => 31,
		'_LEDT' => 32,
		// properties related to query management
		'_ASK'   =>  33,
		'_ASKST' =>  34,
		'_ASKFO' =>  35,
		'_ASKSI' =>  36,
		'_ASKDE' =>  37,
	);

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 * @param SMWSQLStore3 $store
	 */
	public function __construct( SMWSQLStore3 $store ) {
		$this->store = $store;
		// Yes, this is a hack, but we only use it for convenient debugging:
		self::$singleton_debug = $this;
	}

	/**
	 * Find the numeric ID used for the page of the given title,
	 * namespace, interwiki, and subobject. If $canonical is set to true,
	 * redirects are taken into account to find the canonical alias ID for
	 * the given page. If no such ID exists, 0 is returned. The Call-By-Ref
	 * parameter $sortkey is set to the current sortkey, or to '' if no ID
	 * exists.
	 *
	 * If $fetchhashes is true, the property table hash blob will be
	 * retrieved in passing if the opportunity arises, and cached
	 * internally. This will speed up a subsequent call to
	 * getPropertyTableHashes() for this id. This should only be done
	 * if such a call is intended, both to safe the previous cache and
	 * to avoid extra work (even if only a little) to fill it.
	 *
	 * @since 1.8
	 * @param string $title DB key
	 * @param integer $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param string $sortkey call-by-ref will be set to sortkey
	 * @param boolean $canonical should redirects be resolved?
	 * @param boolean $fetchHashes should the property hashes be obtained and cached?
	 * @return integer SMW id or 0 if there is none
	 */
	public function getSMWPageIDandSort( $title, $namespace, $iw, $subobjectName, &$sortkey, $canonical, $fetchHashes = false ) {
		$id = $this->getPredefinedData( $title, $namespace, $iw, $subobjectName, $sortkey );
		if ( $id != 0 ) {
			return $id;
		} else {
			return $this->getDatabaseIdAndSort( $title, $namespace, $iw, $subobjectName, $sortkey, $canonical, $fetchHashes );
		}
	}

	/**
	 * Find the numeric ID used for the page of the given normalized title,
	 * namespace, interwiki, and subobjectName. Predefined IDs are not
	 * taken into account (however, they would still be found correctly by
	 * an avoidable database read if they are stored correctly in the
	 * database; this should always be the case). In all other aspects, the
	 * method works just like getSMWPageIDandSort().
	 *
	 * @since 1.8
	 * @param string $title DB key
	 * @param integer $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param string $sortkey call-by-ref will be set to sortkey
	 * @param boolean $canonical should redirects be resolved?
	 * @param boolean $fetchHashes should the property hashes be obtained and cached?
	 * @return integer SMW id or 0 if there is none
	 */
	protected function getDatabaseIdAndSort( $title, $namespace, $iw, $subobjectName, &$sortkey, $canonical, $fetchHashes ) {
		global $smwgQEqualitySupport;

		$id = $this->getCachedId( $title, $namespace, $iw, $subobjectName );
		if ( $id !== false ) { // cache hit
			$sortkey = $this->getCachedSortKey( $title, $namespace, $iw, $subobjectName );
		} elseif ( $iw == SMW_SQL3_SMWREDIIW && $canonical &&
			$smwgQEqualitySupport != SMW_EQ_NONE && $subobjectName === '' ) {
			$id = $this->getRedirectId( $title, $namespace );
			if ( $id != 0 ) {
				$db = wfGetDB( DB_SLAVE );
				if ( $fetchHashes ) {
					$select = array( 'smw_sortkey', 'smw_proptable_hash' );
				} else {
					$select = array( 'smw_sortkey' );
				}
				$row = $db->selectRow( SMWSql3SmwIds::tableName, $select,
						array( 'smw_id' => $id ), __METHOD__ );
				if ( $row !== false ) {
					$sortkey = $row->smw_sortkey;
					if ( $fetchHashes ) {
						$this->setPropertyTableHashesCache( $id, $row->smw_proptable_hash );
					}
				} else { // inconsistent DB; just recover somehow
					$sortkey = str_replace( '_', ' ', $title );
				}
			} else {
				$sortkey = '';
			}
			$this->setCache( $title, $namespace, $iw, $subobjectName, $id, $sortkey );
		} else {
			$db = wfGetDB( DB_SLAVE );
			if ( $fetchHashes ) {
				$select = array( 'smw_id', 'smw_sortkey', 'smw_proptable_hash' );
			} else {
				$select = array( 'smw_id', 'smw_sortkey' );
			}
			$row = $db->selectRow( SMWSql3SmwIds::tableName, $select, array(
					'smw_title' => $title,
					'smw_namespace' => $namespace,
					'smw_iw' => $iw,
					'smw_subobject' => $subobjectName
				), __METHOD__ );
			$this->selectrow_sort_debug++;

			if ( $row !== false ) {
				$id = $row->smw_id;
				$sortkey = $row->smw_sortkey;
				if ( $fetchHashes ) {
					$this->setPropertyTableHashesCache( $id, $row->smw_proptable_hash);
				}
			} else {
				$id = 0;
				$sortkey = '';
			}
			$this->setCache( $title, $namespace, $iw, $subobjectName, $id, $sortkey );
		}

		if ( $id == 0 && $subobjectName == '' && $iw == '' ) { // could be a redirect; check
			$id = $this->getSMWPageIDandSort( $title, $namespace, SMW_SQL3_SMWREDIIW, $subobjectName, $sortkey, $canonical, $fetchHashes );
		}

		return $id;
	}

	/**
	 * Convenience method for calling getSMWPageIDandSort without
	 * specifying a sortkey (if not asked for).
	 *
	 * @since 1.8
	 * @param string $title DB key
	 * @param integer $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param boolean $canonical should redirects be resolved?
	 * @param boolean $fetchHashes should the property hashes be obtained and cached?
	 * @return integer SMW id or 0 if there is none
	 */
	public function getSMWPageID( $title, $namespace, $iw, $subobjectName, $canonical = true, $fetchHashes = false ) {
		$sort = '';
		return $this->getSMWPageIDandSort( $title, $namespace, $iw, $subobjectName, $sort, $canonical, $fetchHashes );
	}

	/**
	 * Return the ID that a page redirects to. This is only used internally
	 * and it is not cached since the results will affect the SMW IDs table
	 * cache, which will prevent duplicate queries for the same redirect
	 * anyway.
	 *
	 * @since 1.8
	 * @param string $title DB key
	 * @param integer $namespace
	 * @return integer
	 */
	protected function getRedirectId( $title, $namespace ) {
		$db = wfGetDB( DB_SLAVE );
		$row = $db->selectRow( 'smw_fpt_redi', 'o_id',
			array( 's_title' => $title, 's_namespace' => $namespace ), __METHOD__ );
		$this->selectrow_redi_debug++;
		return ( $row === false ) ? 0 : $row->o_id;
	}

	/**
	 * Find the numeric ID used for the page of the given title, namespace,
	 * interwiki, and subobjectName. If $canonical is set to true,
	 * redirects are taken into account to find the canonical alias ID for
	 * the given page. If no such ID exists, a new ID is created and
	 * returned. In any case, the current sortkey is set to the given one
	 * unless $sortkey is empty.
	 *
	 * @note Using this with $canonical==false can make sense, especially when
	 * the title is a redirect target (we do not want chains of redirects).
	 * But it is of no relevance if the title does not have an id yet.
	 *
	 * @since 1.8
	 * @param string $title DB key
	 * @param integer $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param boolean $canonical should redirects be resolved?
	 * @param string $sortkey call-by-ref will be set to sortkey
	 * @param boolean $fetchHashes should the property hashes be obtained and cached?
	 * @return integer SMW id or 0 if there is none
	 */
	public function makeSMWPageID( $title, $namespace, $iw, $subobjectName, $canonical = true, $sortkey = '', $fetchHashes = false ) {
		$id = $this->getPredefinedData( $title, $namespace, $iw, $subobjectName, $sortkey );
		if ( $id != 0 ) {
			return $id;
		} else {
			return $this->makeDatabaseId( $title, $namespace, $iw, $subobjectName, $canonical, $sortkey, $fetchHashes );
		}
	}

	/**
	 * Find the numeric ID used for the page of the given normalized title,
	 * namespace, interwiki, and subobjectName. Predefined IDs are not
	 * taken into account (however, they would still be found correctly by
	 * an avoidable database read if they are stored correctly in the
	 * database; this should always be the case). In all other aspects, the
	 * method works just like makeSMWPageID(). Especially, if no ID exists,
	 * a new ID is created and returned.
	 *
	 * @since 1.8
	 * @param string $title DB key
	 * @param integer $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param boolean $canonical should redirects be resolved?
	 * @param string $sortkey call-by-ref will be set to sortkey
	 * @param boolean $fetchHashes should the property hashes be obtained and cached?
	 * @return integer SMW id or 0 if there is none
	 */
	protected function makeDatabaseId( $title, $namespace, $iw, $subobjectName, $canonical, $sortkey, $fetchHashes ) {

		$oldsort = '';
		$id = $this->getDatabaseIdAndSort( $title, $namespace, $iw, $subobjectName, $oldsort, $canonical, $fetchHashes );

		if ( $id == 0 ) {
			$db = wfGetDB( DB_MASTER );
			$sortkey = $sortkey ? $sortkey : ( str_replace( '_', ' ', $title ) );

			$db->insert(
				SMWSql3SmwIds::tableName,
				array(
					'smw_id' => $db->nextSequenceValue( 'smw_ids_smw_id_seq' ),
					'smw_title' => $title,
					'smw_namespace' => $namespace,
					'smw_iw' => $iw,
					'smw_subobject' => $subobjectName,
					'smw_sortkey' => $sortkey
				),
				__METHOD__
			);

			$id = $db->insertId();

			// Properties also need to be in the property statistics table
			if( $namespace == SMW_NS_PROPERTY ) {
				$db->insert(
					SMWSQLStore3::PROPERTY_STATISTICS_TABLE,
					array(
						'p_id' => $id,
						'usage_count' => 0
					),
					__METHOD__
				);
			}
			$this->setCache( $title, $namespace, $iw, $subobjectName, $id, $sortkey );
			if ( $fetchHashes ) {
				$this->setPropertyTableHashesCache( $id, null );
			}
		} elseif ( $sortkey !== '' && $sortkey != $oldsort ) {
			$db = wfGetDB( DB_MASTER );
			$db->update( SMWSql3SmwIds::tableName, array( 'smw_sortkey' => $sortkey ), array( 'smw_id' => $id ), __METHOD__ );
			$this->setCache( $title, $namespace, $iw, $subobjectName, $id, $sortkey );
		}

		return $id;
	}

	/**
	 * Properties have a mechanisms for being predefined (i.e. in PHP instead
	 * of in wiki). Special "interwiki" prefixes separate the ids of such
	 * predefined properties from the ids for the current pages (which may,
	 * e.g., be moved, while the predefined object is not movable).
	 *
	 * @todo This documentation is out of date. Right now, the special
	 * interwiki is used only for special properties without a label, i.e.,
	 * which cannot be shown to a user. This allows us to filter such cases
	 * from all queries that retrieve lists of properties. It should be
	 * checked that this is really the only use that this has throughout
	 * the code.
	 *
	 * @since 1.8
	 * @param SMWDIProperty $property
	 * @return string
	 */
	public function getPropertyInterwiki( SMWDIProperty $property ) {
		return ( $property->getLabel() !== '' ) ? '' : SMW_SQL3_SMWINTDEFIW;
	}

	/**
	 * Fetch the ID for an SMWDIProperty object. This method achieves the
	 * same as getSMWPageID(), but avoids additional normalization steps
	 * that have already been performed when creating an SMWDIProperty
	 * object.
	 *
	 * @note There is no distinction between properties and inverse
	 * properties here. A property and its inverse have the same ID in SMW.
	 *
	 * @param SMWDIProperty $property
	 * @return integer
	 */
	public function getSMWPropertyID( SMWDIProperty $property ) {
		if ( array_key_exists( $property->getKey(), self::$special_ids ) ) {
			return self::$special_ids[$property->getKey()];
		} else {
			$sortkey = '';
			return $this->getDatabaseIdAndSort( $property->getKey(), SMW_NS_PROPERTY, $this->getPropertyInterwiki( $property ), '', $sortkey, true, false );
		}
	}

	/**
	 * Fetch and possibly create the ID for an SMWDIProperty object. The
	 * method achieves the same as getSMWPageID() but avoids additional
	 * normalization steps that have already been performed when creating
	 * an SMWDIProperty object.
	 *
	 * @see getSMWPropertyID
	 * @param SMWDIProperty $property
	 * @return integer
	 */
	public function makeSMWPropertyID( SMWDIProperty $property ) {
		if ( array_key_exists( $property->getKey(), self::$special_ids ) ) {
			return self::$special_ids[$property->getKey()];
		} else {
			$sortkey = '';
			return $this->makeDatabaseId( $property->getKey(), SMW_NS_PROPERTY, $this->getPropertyInterwiki( $property ), '', true, $property->getLabel(), false );
		}
	}

	/**
	 * Normalize the information for an SMW object (page etc.) and return
	 * the predefined ID if any. All parameters are call-by-reference and
	 * will be changed to perform any kind of built-in normalization that
	 * SMW requires. This mainly applies to predefined properties that
	 * should always use their property key as a title, have fixed
	 * sortkeys, etc. Some very special properties also have fixed IDs that
	 * do not require any DB lookups. In such cases, the method returns
	 * this ID; otherwise it returns 0.
	 *
	 * @note This function could be extended to account for further kinds
	 * of normalization and predefined ID. However, both getSMWPropertyID
	 * and makeSMWPropertyID must then also be adjusted to do the same.
	 *
	 * @since 1.8
	 * @param string $title DB key
	 * @param integer $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName
	 * @param string $sortkey
	 * @return integer predefined id or 0 if none
	 */
	protected function getPredefinedData( &$title, &$namespace, &$iw, &$subobjectName, &$sortkey ) {
		if ( $namespace == SMW_NS_PROPERTY &&
			( $iw == '' || $iw == SMW_SQL3_SMWINTDEFIW ) && $title != '' ) {

			// Check if this is a predefined property:
			if ( $title{0} != '_' ) {
				// This normalization also applies to
				// subobjects of predefined properties.
				$newTitle = SMWDIProperty::findPropertyID( str_replace( '_', ' ', $title ) );
				if ( $newTitle ) {
					$title = $newTitle;
					$sortkey = SMWDIProperty::findPropertyLabel( $title );
					if ( $sortkey == '' ) {
						$iw = SMW_SQL3_SMWINTDEFIW;
					}
				}
			}

			// Check if this is a property with a fixed SMW ID:
			if ( $subobjectName == '' && array_key_exists( $title, self::$special_ids ) ) {
				return self::$special_ids[$title];
			}
		}

		return 0;
	}

	/**
	 * Change an internal id to another value. If no target value is given, the
	 * value is changed to become the last id entry (based on the automatic id
	 * increment of the database). Whatever currently occupies this id will be
	 * moved consistently in all relevant tables. Whatever currently occupies
	 * the target id will be ignored (it should be ensured that nothing is
	 * moved to an id that is still in use somewhere).
	 *
	 * @since 1.8
	 * @param integer $curid
	 * @param integer $targetid
	 */
	public function moveSMWPageID( $curid, $targetid = 0 ) {
		$db = wfGetDB( DB_MASTER );

		$row = $db->selectRow( SMWSql3SmwIds::tableName, '*', array( 'smw_id' => $curid ), __METHOD__ );

		if ( $row === false ) return; // no id at current position, ignore

		if ( $targetid == 0 ) { // append new id
			$db->insert(
				SMWSql3SmwIds::tableName,
				array(
					'smw_id' => $db->nextSequenceValue( 'smw_ids_smw_id_seq' ),
					'smw_title' => $row->smw_title,
					'smw_namespace' => $row->smw_namespace,
					'smw_iw' => $row->smw_iw,
					'smw_subobject' => $row->smw_subobject,
					'smw_sortkey' => $row->smw_sortkey
				),
				__METHOD__
			);
			$targetid = $db->insertId();
		} else { // change to given id
			$db->insert( SMWSql3SmwIds::tableName,
				array( 'smw_id' => $targetid,
					'smw_title' => $row->smw_title,
					'smw_namespace' => $row->smw_namespace,
					'smw_iw' => $row->smw_iw,
					'smw_subobject' => $row->smw_subobject,
					'smw_sortkey' => $row->smw_sortkey
				),
				__METHOD__
			);
		}

		$db->delete( SMWSql3SmwIds::tableName, array( 'smw_id' => $curid ), 'SMWSQLStore3::moveSMWPageID' );

		$this->setCache( $row->smw_title, $row->smw_namespace, $row->smw_iw,
			$row->smw_subobject, $targetid, $row->smw_sortkey );

		$this->store->changeSMWPageID( $curid, $targetid, $row->smw_namespace, $row->smw_namespace );
	}

	/**
	 * Add or modify a cache entry. The key consists of the
	 * parameters $title, $namespace, $interwiki, and $subobject. The
	 * cached data is $id and $sortkey.
	 *
	 * @since 1.8
	 * @param string $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 * @param integer $id
	 * @param string $sortkey
	 */
	public function setCache( $title, $namespace, $interwiki, $subobject, $id, $sortkey ) {
		if ( strpos( $title, ' ' ) !== false ) {
			throw new MWException("Somebody tried to use spaces in a cache title! ($title)");
		}
		if ( $namespace == SMW_NS_PROPERTY && $interwiki == '' && $subobject == '' ) {
			$this->checkPropertySizeLimit();
			$this->prop_ids[$title] = $id;
			$this->prop_sortkeys[$title] = $sortkey;
		} else {
			$hashKey = self::getRegularHashKey( $title, $namespace, $interwiki, $subobject );
			$this->checkRegularSizeLimit();
			$this->regular_ids[$hashKey] = $id;
			$this->regular_sortkeys[$hashKey] = $sortkey;
		}
		if ( $interwiki == SMW_SQL3_SMWREDIIW ) { // speed up detection of redirects when fetching IDs
			$this->setCache(  $title, $namespace, '', $subobject, 0, '' );
		}
	}

	/**
	 * Get a cached SMW ID, or false if no cache entry is found.
	 *
	 * @since 1.8
	 * @param string $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 * @return integer|boolean
	 */
	protected function getCachedId( $title, $namespace, $interwiki, $subobject ) {
		if ( $namespace == SMW_NS_PROPERTY && $interwiki == '' && $subobject == '' ) {
			if ( array_key_exists( $title, $this->prop_ids ) ) {
				$this->prophit_debug++;
				return $this->prop_ids[$title];
			} else {
				$this->propmiss_debug++;
				return false;
			}
		} else {
			$hashKey = self::getRegularHashKey( $title, $namespace, $interwiki, $subobject );
			if ( array_key_exists( $hashKey, $this->regular_ids ) ) {
				$this->reghit_debug++;
				return $this->regular_ids[$hashKey];
			} else {
				$this->regmiss_debug++;
				return false;
			}
		}
	}

	/**
	 * Get a cached SMW sortkey, or false if no cache entry is found.
	 *
	 * @since 1.8
	 * @param string $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 * @return string|boolean
	 */
	protected function getCachedSortKey( $title, $namespace, $interwiki, $subobject ) {
		if ( $namespace == SMW_NS_PROPERTY && $interwiki == '' && $subobject == '' ) {
			if ( array_key_exists( $title, $this->prop_sortkeys ) ) {
				return $this->prop_sortkeys[$title];
			} else {
				return false;
			}
		} else {
			$hashKey = self::getRegularHashKey( $title, $namespace, $interwiki, $subobject );
			if ( array_key_exists( $hashKey, $this->regular_sortkeys ) ) {
				return $this->regular_sortkeys[$hashKey];
			} else {
				return false;
			}
		}
	}

	/**
	 * Remove any cache entry for the given data.
	 *
	 * @since 1.8
	 * @param string $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 */
	protected function deleteCache( $title, $namespace, $interwiki, $subobject ) {
		if ( $namespace == SMW_NS_PROPERTY && $interwiki == '' && $subobject == '' ) {
			unset( $this->regular_ids[$title] );
			unset( $this->regular_sortkeys[$title] );
		} else {
			$hashKey = $this->getHashKey( $title, $namespace, $interwiki, $subobject );
			unset( $this->regular_ids[$hashKey] );
			unset( $this->regular_sortkeys[$hashKey] );
		}
	}

	/**
	 * Move all cached information about subobjects.
	 *
	 * @todo This method is neither efficient nor very convincing
	 * architecturally; it should be redesigned.
	 *
	 * @since 1.8
	 * @param string $oldtitle
	 * @param integer $oldnamespace
	 * @param string $newtitle
	 * @param integer $newnamespace
	 */
	public function moveSubobjects( $oldtitle, $oldnamespace, $newtitle, $newnamespace ) {
		// Currently we have no way to change title and namespace across all entries.
		// Best we can do is clear the cache to avoid wrong hits:
		if ( $oldnamespace == SMW_NS_PROPERTY || $newnamespace == SMW_NS_PROPERTY ) {
			$this->prop_ids = array();
			$this->prop_sortkeys = array();
		}
		if ( $oldnamespace != SMW_NS_PROPERTY || $newnamespace != SMW_NS_PROPERTY ) {
			$this->regular_ids = array();
			$this->regular_sortkeys = array();
		}
	}

	/**
	 * Delete all cached information.
	 *
	 * @since 1.8
	 */
	public function clearCaches() {
		$this->prop_ids = array();
		$this->prop_sortkeys = array();
		$this->regular_ids = array();
		$this->regular_sortkeys = array();
	}

	/**
	 * Ensure that the property ID and sortkey caches have space to insert
	 * at least one more element. If not, some other entries will be unset.
	 *
	 * @since 1.8
	 */
	protected function checkPropertySizeLimit() {
		if ( count( $this->prop_ids ) >= self::$PROP_CACHE_MAX_SIZE ) {
			$keys = array_rand( $this->prop_ids, 10 );
			foreach ( $keys as $key ) {
				unset( $this->prop_ids[$key] );
				unset( $this->prop_sortkeys[$key] );
			}
		}
	}

	/**
	 * Ensure that the non-property ID and sortkey caches have space to
	 * insert at least one more element. If not, some other entries will be
	 * unset.
	 *
	 * @since 1.8
	 */
	protected function checkRegularSizeLimit() {
		if ( count( $this->regular_ids ) >= self::$PAGE_CACHE_MAX_SIZE ) {
			$keys = array_rand( $this->regular_ids, 10 );
			foreach ( $keys as $key ) {
				unset( $this->regular_ids[$key] );
				unset( $this->regular_sortkeys[$key] );
			}
		}
	}

	/**
	 * Get the hash key for regular (non-property) pages.
	 *
	 * @since 1.8
	 * @param string $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 * @return string
	 */
	protected static function getRegularHashKey( $title, $namespace, $interwiki, $subobject ) {
		return "$title#$namespace#$interwiki#$subobject";
	}

	/**
	 * Return an array of hashes with table names as keys. These
	 * hashes are used to compare new data with old data for each
	 * property-value table when updating data
	 *
	 * @since 1.8
	 * @param integer $sid ID of the page as stored in the SMW IDs table
	 * @return array
	 */
	public function getPropertyTableHashes( $sid ) {
		if ( $this->hashCacheId == $sid ) {
			//print "Cache hit! " . $this->hitcount++ . "\n";
			$hash = $this->hashCacheContents;
		} elseif ( $sid !== 0 ) {
			//print "Cache miss! $sid is not {$this->hashCacheId} " . $this->misscount++ . "\n";
			$db = wfGetDB( DB_SLAVE );
			$row = $db->selectRow(
				SMWSql3SmwIds::tableName,
				array( 'smw_proptable_hash' ),
				'smw_id=' . $sid ,
				__METHOD__
			);
			if ( $row !== false ) {
				$hash = $row->smw_proptable_hash;
			}
		} else { // $sid == 0
			$hash = null;
		}

		if ( !is_null( $hash ) ) {
			return unserialize( $hash );
		} else {
			return array();
		}
	}

	/**
	 * Update the proptable_hash for a given page.
	 *
	 * @since 1.8
	 * @param integer $sid ID of the page as stored in SMW IDs table
	 * @param array of hash values with tablename as keys
	 */
	public function setPropertyTableHashes( $sid, array $newTableHashes ) {
		$db = wfGetDB( DB_MASTER );
		$propertyTableHash = serialize( $newTableHashes );
		$db->update(
			SMWSql3SmwIds::tableName,
			array( 'smw_proptable_hash' => $propertyTableHash ),
			array( 'smw_id' => $sid ),
			__METHOD__
		);
		if ( $sid == $this->hashCacheId ) {
			$this->setPropertyTableHashesCache( $sid, $propertyTableHash );
		}
	}

	/**
	 * Temporarily cache a property tablehash that has been retrieved for
	 * the given SMW ID.
	 *
	 * @since 1.8
	 * @param $id integer
	 * @param $propertyTableHash string
	 */
	protected function setPropertyTableHashesCache( $id, $propertyTableHash ) {
		if ( $id == 0 ) return; // never cache 0
		//print "Cache set for $id.\n";
		$this->hashCacheId = $id;
		$this->hashCacheContents = $propertyTableHash;
	}

	/**
	 * Simple helper method for debugging cache performance. Prints
	 * statistics about the SMWSql3SmwIds object created last.
	 * The following code can be used in LocalSettings.php to enable
	 * this in a wiki:
	 *
	 * $wgHooks['SkinAfterContent'][] = 'showCacheStats';
	 * function showCacheStats() {
	 *   SMWSql3SmwIds::debugDumpCacheStats();
	 *   return true;
	 * }
	 *
	 * @note This is a debugging/profiling method that no published code
	 * should rely on.
	 *
	 * @since 1.8
	 */
	public static function debugDumpCacheStats() {
		$that = self::$singleton_debug;
		if ( is_null( $that ) ) return;

		$debugString =
			"Statistics for SMWSql3SmwIds:\n" .
			"- Executed {$that->selectrow_sort_debug} selects for sortkeys.\n" .
			"- Executed {$that->selectrow_redi_debug} selects for redirects.\n" .
			"- Regular cache hits: {$that->reghit_debug} misses: {$that->regmiss_debug}";
		if ( $that->regmiss_debug + $that->reghit_debug > 0 ) {
			$debugString .= " rate: " . round( $that->reghit_debug/( $that->regmiss_debug + $that->reghit_debug ), 3 );
		}
		$debugString .= " cache size: " . count( $that->regular_ids ) . "\n" ;
		$debugString .= "- Property cache hits: {$that->prophit_debug} misses: {$that->propmiss_debug}";
		if ( $that->propmiss_debug + $that->prophit_debug > 0 ) {
			$debugString .= " rate: " . round( $that->prophit_debug/( $that->propmiss_debug + $that->prophit_debug ), 3 );
		}
		$debugString .= " cache size: " . count( $that->prop_ids ) . "\n";
		wfDebug( $debugString );
	}

}