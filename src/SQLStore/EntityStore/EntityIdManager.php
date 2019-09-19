<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Collator;
use SMW\PropertyRegistry;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\IdToDataItemMatchFinder;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\SQLStoreFactory;
use SMW\SQLStore\TableFieldUpdater;
use SMWDataItem as DataItem;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\Connection\Sequence;
use SMW\TypesRegistry;
use SMW\MediaWiki\Deferred\HashFieldUpdate;
use Iterator;

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
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class EntityIdManager {

	const MAX_CACHE_SIZE = 1000;
	const POOLCACHE_ID = 'smw.sqlstore';

	/**
	 * @var SQLStore
	 */
	public $store;

	/**
	 * @var SQLStoreFactory
	 */
	private $factory;

	/**
	 * @var IdToDataItemMatchFinder
	 */
	private $idMatchFinder;

	/**
	 * @var RedirectStore
	 */
	private $redirectStore;

	/**
	 * @var TableFieldUpdater
	 */
	private $tableFieldUpdater;

	/**
	 * @var array
	 */
	public static $special_ids = [];

	/**
	 * @var IdCacheManager
	 */
	private $idCacheManager;

	/**
	 * @var CacheWarmer
	 */
	private $cacheWarmer;

	/**
	 * @var IdEntityFinder
	 */
	private $idEntityFinder;

	/**
	 * @var EntityIdFinder
	 */
	private $entityIdFinder;

	/*
	 * @var SequenceMapFinder
	 */
	private $sequenceMapFinder;

	/**
	 * @var DuplicateFinder
	 */
	private $duplicateFinder;

	/**
	 * @var PropertyTableHashes
	 */
	private $propertyTableHashes;

	/**
	 * @since 1.8
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store, SQLStoreFactory $factory ) {
		$this->store = $store;
		$this->factory = $factory;
		$this->initCache();

		$this->idEntityFinder = $this->factory->newIdEntityFinder(
			$this->idCacheManager
		);

		$this->sequenceMapFinder = $this->factory->newSequenceMapFinder(
			$this->idCacheManager
		);

		$this->tableFieldUpdater = $this->factory->newTableFieldUpdater();

		self::$special_ids = TypesRegistry::getFixedProperties( 'id' );
	}

	/**
	 * @since  2.1
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return boolean
	 */
	public function isRedirect( DIWikiPage $subject ) {

		if ( $this->redirectStore === null ) {
			$this->redirectStore = $this->factory->newRedirectStore();
		}

		return $this->redirectStore->isRedirect( $subject->getDBKey(), $subject->getNamespace() );
	}

	/**
	 * @see RedirectStore::findRedirect
	 *
	 * @since 2.1
	 *
	 * @param string $title DB key
	 * @param integer $namespace
	 *
	 * @return integer
	 */
	public function findRedirect( $title, $namespace ) {

		if ( $this->redirectStore === null ) {
			$this->redirectStore = $this->factory->newRedirectStore();
		}

		return $this->redirectStore->findRedirect( $title, $namespace );
	}

	/**
	 * @see RedirectStore::addRedirect
	 *
	 * @since 2.1
	 *
	 * @param integer $id
	 * @param string $title
	 * @param integer $namespace
	 */
	public function addRedirect( $id, $title, $namespace ) {

		if ( $this->redirectStore === null ) {
			$this->redirectStore = $this->factory->newRedirectStore();
		}

		$this->redirectStore->addRedirect( $id, $title, $namespace );
	}

	/**
	 * @see RedirectStore::updateRedirect
	 *
	 * @since 3.0
	 *
	 * @param integer $id
	 * @param string $title
	 * @param integer $namespace
	 */
	public function updateRedirect( $id, $title, $namespace ) {

		if ( $this->redirectStore === null ) {
			$this->redirectStore = $this->factory->newRedirectStore();
		}

		$this->redirectStore->updateRedirect( $id, $title, $namespace );
	}

	/**
	 * @see RedirectStore::deleteRedirect
	 *
	 * @since 2.1
	 *
	 * @param string $title
	 * @param integer $namespace
	 */
	public function deleteRedirect( $title, $namespace ) {

		if ( $this->redirectStore === null ) {
			$this->redirectStore = $this->factory->newRedirectStore();
		}

		$this->redirectStore->deleteRedirect( $title, $namespace );
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
			return (int)$id;
		} else {
			return (int)$this->getDatabaseIdAndSort( $title, $namespace, $iw, $subobjectName, $sortkey, $canonical, $fetchHashes );
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

		$sha1 = $this->idCacheManager->computeSha1(
			[
				$title,
				(int)$namespace,
				$iw,
				$subobjectName
			]
		);

		$this->entityIdFinder->setFetchPropertyTableHashes(
			$fetchHashes
		);

		// Integration test "query-04-02-subproperty-dc-import-marc21.json"
		// showed a deterministic failure (due to a wrong cache id during querying
		// for redirects) hence we force to read directly from the RedirectStore
		// for objects marked as redirect
		if (
			$iw === SMW_SQL3_SMWREDIIW &&
			$smwgQEqualitySupport !== SMW_EQ_NONE &&
			$subobjectName === '' &&
			$canonical ) {
			$id = $this->findRedirect( $title, $namespace );
		} else {
			$id = $this->idCacheManager->getId( $sha1 );
		}

		// Cache hit; reload the sort
		if ( $id !== false && $id != 0 ) {
			$sortkey = $this->idCacheManager->getSort( $sha1 );
		} elseif (
			$iw === SMW_SQL3_SMWREDIIW &&
			$smwgQEqualitySupport !== SMW_EQ_NONE &&
			$subobjectName === '' &&
			$canonical ) {
			list( $id, $sortkey ) = $this->entityIdFinder->fetchFieldsFromTableById(
				$this->findRedirect( $title, $namespace ),
				$title,
				$namespace,
				$iw,
				$subobjectName,
				$sortkey
			);
		} else {
			list( $id, $sortkey ) = $this->entityIdFinder->fetchFromTableByTitle(
				$title,
				$namespace,
				$iw,
				$subobjectName,
				$sortkey
			);
		}

		// Could be a redirect; recheck
		if ( $id == 0 && $subobjectName === '' && $iw === '' ) {
			$id = $this->getDatabaseIdAndSort(
				$title,
				$namespace,
				SMW_SQL3_SMWREDIIW,
				$subobjectName,
				$sortkey,
				$canonical,
				$fetchHashes
			);
		}

		return $id;
	}

	/**
	 * @since 3.0
	 *
	 * @param DataItem $dataItem
	 *
	 * @return boolean
	 */
	public function isUnique( DataItem $dataItem ) {

		if ( $this->duplicateFinder === null ) {
			$this->duplicateFinder = $this->factory->newDuplicateFinder();
		}

		return $this->duplicateFinder->hasDuplicate( $dataItem ) === false;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function findDuplicates() {

		if ( $this->duplicateFinder === null ) {
			$this->duplicateFinder = $this->factory->newDuplicateFinder();
		}

		$ids = $this->duplicateFinder->findDuplicates(
			SQLStore::ID_TABLE
		);

		if ( $ids instanceof Iterator ) {
			$ids = iterator_to_array( $ids );
		}

		$redi = $this->duplicateFinder->findDuplicates(
			RedirectStore::TABLE_NAME
		);

		if ( $redi instanceof Iterator ) {
			$redi = iterator_to_array( $redi );
		}

		$wikipage_table = PropertyTableInfoFetcher::findTableIdForDataItemTypeId(
			DataItem::TYPE_WIKIPAGE
		);

		$page = $this->duplicateFinder->findDuplicates(
			$wikipage_table
		);

		if ( $page instanceof Iterator ) {
			$page = iterator_to_array( $page );
		}

		return [
			SQLStore::ID_TABLE => $ids,
			RedirectStore::TABLE_NAME => $redi,
			$wikipage_table => $page
		];
	}

	/**
	 * @since 2.3
	 *
	 * @param string $title DB key
	 * @param integer $namespace namespace
	 * @param string|null $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 *
	 * @param array
	 */
	public function findIdsByTitle( $title, $namespace, $iw = null, $subobjectName = '' ) {
		return $this->entityIdFinder->findIdsByTitle( $title, $namespace, $iw, $subobjectName );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage $subject
	 *
	 * @param boolean
	 */
	public function exists( DIWikiPage $subject ) {
		return $this->getId( $subject ) > 0;
	}

	/**
	 * @note SMWSql3SmwIds::getSMWPageID has some issues with the cache as it returned
	 * 0 even though an object was matchable, using this method is safer then trying
	 * to encipher getSMWPageID related methods.
	 *
	 * It uses the PoolCache which means Lru is in place to avoid memory leakage.
	 *
	 * @since 2.4
	 *
	 * @param DIWikiPage $subject
	 *
	 * @param integer
	 */
	public function getId( DIWikiPage $subject ) {

		// Try to match a predefined property
		if ( $subject->getNamespace() === SMW_NS_PROPERTY && $subject->getInterWiki() === '' ) {
			$property = DIProperty::newFromUserLabel( $subject->getDBKey() );
			$key = $property->getKey();

			// Has a fixed ID?
			if ( isset( self::$special_ids[$key] ) && is_int( self::$special_ids[$key] ) && $subject->getSubobjectName() === '' ) {
				return self::$special_ids[$key];
			}

			// Switch title for fixed properties without a fixed ID (e.g. _MIME is the smw_title)
			if ( !$property->isUserDefined() ) {
				$subject = new DIWikiPage(
					$key,
					SMW_NS_PROPERTY,
					$subject->getInterWiki(),
					$subject->getSubobjectName()
				);
			}
		}

		return $this->entityIdFinder->findIdByItem( $subject );
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
			return (int)$id;
		} else {
			return (int)$this->makeDatabaseId( $title, $namespace, $iw, $subobjectName, $canonical, $sortkey, $fetchHashes );
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
		$db = $this->store->getConnection( 'mw.db' );
		$collator = Collator::singleton();

		// Safeguard to ensure that no duplicate IDs are created
		if ( $id == 0 ) {
			$id = $this->getId( new DIWikiPage( $title, $namespace, $iw, $subobjectName ) );
		}

		$db->beginAtomicTransaction( __METHOD__ );

		if ( $id == 0 ) {
			$sortkey = $sortkey ? $sortkey : ( str_replace( '_', ' ', $title ) );

			// Bug 42659
			$sequenceValue = $db->nextSequenceValue(
				Sequence::makeSequence( SQLStore::ID_TABLE, 'smw_id' )
			);

			// #2089 (MySQL 5.7 complained with "Data too long for column")
			$sortkey = mb_substr( $sortkey, 0, 254 );

			$db->insert(
				SQLStore::ID_TABLE,
				[
					'smw_id' => $sequenceValue,
					'smw_title' => $title,
					'smw_namespace' => $namespace,
					'smw_iw' => $iw,
					'smw_subobject' => $subobjectName,
					'smw_sortkey' => $sortkey,
					'smw_sort' => $collator->getSortKey( $sortkey ),
					'smw_hash' => $this->computeSha1( [ $title, (int)$namespace, $iw, $subobjectName ] ),
					'smw_touched' => $db->timestamp()
				],
				__METHOD__
			);

			$id = (int)$db->insertId();

			// Properties also need to be in the property statistics table
			if( $namespace === SMW_NS_PROPERTY ) {

				$propertyStatisticsStore = $this->factory->newPropertyStatisticsStore(
					$db
				);

				$propertyStatisticsStore->insertUsageCount( $id, 0 );
			}

			$this->setCache( $title, $namespace, $iw, $subobjectName, $id, $sortkey );

			if ( $fetchHashes ) {
				$this->propertyTableHashes->clearPropertyTableHashCacheById( $id, null );
			}

		} elseif ( $sortkey !== '' && ( $sortkey != $oldsort || !$collator->isIdentical( $oldsort, $sortkey ) ) ) {
			$this->tableFieldUpdater->updateSortField( $id, $sortkey );
			$this->setCache( $title, $namespace, $iw, $subobjectName, $id, $sortkey );
		}

		$db->endAtomicTransaction( __METHOD__ );

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
	 * @param DIProperty $property
	 * @return string
	 */
	public function getPropertyInterwiki( DIProperty $property ) {
		return ( $property->getLabel() !== '' ) ? '' : SMW_SQL3_SMWINTDEFIW;
	}

	/**
	 * @since  2.1
	 *
	 * @param integer $sid
	 * @param DIWikiPage $subject
	 * @param integer|string|null $interwiki
	 */
	public function updateInterwikiField( $sid, DIWikiPage $subject, $interwiki = null ) {

		if ( $interwiki === null ) {
			$interwiki = $subject->getInterWiki();
		}

		$hash = [
			$subject->getDBKey(),
			(int)$subject->getNamespace(),
			$interwiki,
			$subject->getSubobjectName()
		];

		$this->tableFieldUpdater->updateIwField(
			$sid,
			$interwiki,
			$this->computeSha1( $hash )
		);

		$this->setCache(
			$subject->getDBKey(),
			$subject->getNamespace(),
			$subject->getInterWiki(),
			$subject->getSubobjectName(),
			$sid,
			$subject->getSortKey()
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage|string $title
	 * @param integer $namespace
	 * @param string $iw
	 */
	public function findAssociatedRev( $title, $namespace = '', $iw = '' ) {
		$connection = $this->store->getConnection( 'mw.db' );

		if ( $title instanceof DIWikiPage ) {
			$cond = [
				"smw_hash" => $title->getSha1()
			];
		} elseif( is_int( $title ) ) {
			$cond = [
				"smw_id" => $title
			];
		} else {
			$cond = [
				"smw_title =" . $connection->addQuotes( $title ),
				"smw_namespace =" . $connection->addQuotes( $namespace ),
				"smw_iw =" . $connection->addQuotes( $iw ),
				"smw_subobject =''"
			];
		}

		$row = $connection->selectRow(
			SQLStore::ID_TABLE,
			'smw_rev',
			$cond,
			__METHOD__
		);

		return $row === false ? 0 : $row->smw_rev;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $sid
	 * @param integer $sid
	 */
	public function updateRevField( $sid, $rev_id ) {
		$this->tableFieldUpdater->updateRevField( $sid, $rev_id );
	}

	/**
	 * Fetch the ID for an DIProperty object. This method achieves the
	 * same as getSMWPageID(), but avoids additional normalization steps
	 * that have already been performed when creating an DIProperty
	 * object.
	 *
	 * @note There is no distinction between properties and inverse
	 * properties here. A property and its inverse have the same ID in SMW.
	 *
	 * @param DIProperty $property
	 * @return integer
	 */
	public function getSMWPropertyID( DIProperty $property ) {
		$key = $property->getKey();
		$sortkey = '';

		if ( isset( self::$special_ids[$key] ) && is_int( self::$special_ids[$key] ) ) {
			return self::$special_ids[$key];
		}

		return $this->getDatabaseIdAndSort(
			$key,
			SMW_NS_PROPERTY,
			$this->getPropertyInterwiki( $property ),
			'',
			$sortkey,
			true,
			false
		);
	}

	/**
	 * Fetch and possibly create the ID for an DIProperty object. The
	 * method achieves the same as getSMWPageID() but avoids additional
	 * normalization steps that have already been performed when creating
	 * an DIProperty object.
	 *
	 * @see getSMWPropertyID
	 * @param DIProperty $property
	 * @return integer
	 */
	public function makeSMWPropertyID( DIProperty $property ) {

		$key = $property->getKey();

		if ( isset( self::$special_ids[$key] ) && is_int( self::$special_ids[$key] ) ) {
			return self::$special_ids[$key];
		}

		return (int)$this->makeDatabaseId(
			$key,
			SMW_NS_PROPERTY,
			$this->getPropertyInterwiki( $property ),
			'',
			true,
			$property->getLabel(),
			false
		);
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
			( $iw === '' || $iw == SMW_SQL3_SMWINTDEFIW ) && $title != '' ) {

			// Check if this is a predefined property:
			if ( $title[0] != '_' ) {
				// This normalization also applies to
				// subobjects of predefined properties.
				$newTitle = PropertyRegistry::getInstance()->findPropertyIdByLabel( str_replace( '_', ' ', $title ) );
				if ( $newTitle ) {
					$title = $newTitle;
					$sortkey = PropertyRegistry::getInstance()->findPropertyLabelById( $title );
					if ( $sortkey === '' ) {
						$iw = SMW_SQL3_SMWINTDEFIW;
					}
				}
			}

			// Check if this is a property with a fixed SMW ID:
			if ( $subobjectName === '' && isset( self::$special_ids[$title] ) && is_int( self::$special_ids[$title] ) ) {
				return self::$special_ids[$title];
			}
		}

		return 0;
	}

	/**
	 * @see IdChanger::move
	 *
	 * @since 1.8
	 *
	 * @param integer $curid
	 * @param integer $targetid
	 */
	public function moveSMWPageID( $curid, $targetid = 0 ) {
		$idChanger = $this->factory->newIdChanger();

		$row = $idChanger->move(
			$curid,
			$targetid
		);

		if ( $row === null ) {
			return;
		}

		$this->idCacheManager->setCache(
			$row->smw_title,
			$row->smw_namespace,
			$row->smw_iw,
			$row->smw_subobject,
			$row->smw_id,
			$row->smw_sortkey
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string|array $args
	 *
	 * @return string
	 */
	public function computeSha1( $args = '' ) {
		return IdCacheManager::computeSha1( $args );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $list
	 */
	public function warmUpCache( $list = [] ) {
		$this->cacheWarmer->fillFromList( $list );
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
		$this->idCacheManager->setCache( $title, $namespace, $interwiki, $subobject, $id, $sortkey );
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $id
	 *
	 * @return DIWikiPage|null
	 */
	public function getDataItemById( $id ) {
		return $this->idEntityFinder->getDataItemById( $id );
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $id
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return string[]
	 */
	public function getDataItemsFromList( array $idlist, RequestOptions $requestOptions = null ) {
		return $this->idEntityFinder->getDataItemsFromList( $idlist, $requestOptions );
	}

	/**
	 * @deprecated since 3.0, use SMWSql3SmwIds::getDataItemsFromList
	 */
	public function getDataItemPoolHashListFor( array $idlist, RequestOptions $requestOptions = null ) {
		return $this->idEntityFinder->getDataItemsFromList( $idlist, $requestOptions );
	}

	/**
	 * Remove any cache entry for the given data. The key consists of the
	 * parameters $title, $namespace, $interwiki, and $subobject. The
	 * cached data is $id and $sortkey.
	 *
	 * @since 1.8
	 * @param string $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 */
	public function deleteCache( $title, $namespace, $interwiki, $subobject ) {
		$this->idCacheManager->deleteCache( $title, $namespace, $interwiki, $subobject );
	}

	/**
	 * @since 3.0
	 */
	public function initCache() {

		// Tests indicate that it is more memory efficient to have two
		// arrays (IDs and sortkeys) than to have one array that stores both
		// values in some data structure (other than a single string).
		$this->idCacheManager = $this->factory->newIdCacheManager(
			self::POOLCACHE_ID,
			[
				'entity.id' => self::MAX_CACHE_SIZE,
				'entity.sort' => self::MAX_CACHE_SIZE,
				'entity.lookup' => 2000,
				'propertytable.hash' => self::MAX_CACHE_SIZE,
				'warmup.byid' => self::MAX_CACHE_SIZE,
				'sequence.map' => self::MAX_CACHE_SIZE,
			]
		);

		$this->cacheWarmer = $this->factory->newCacheWarmer(
			$this->idCacheManager
		);

		$this->propertyTableHashes = $this->factory->newPropertyTableHashes(
			$this->idCacheManager
		);

		$this->entityIdFinder = $this->factory->newEntityIdFinder(
			$this->idCacheManager,
			$this->propertyTableHashes
		);

		$this->tableFieldUpdater = $this->factory->newTableFieldUpdater();
	}

	/**
	 * Return an array of hashes with table names as keys. These
	 * hashes are used to compare new data with old data for each
	 * property-value table when updating data
	 *
	 * @since 1.8
	 *
	 * @param integer $subjectId ID of the page as stored in the SMW IDs table
	 *
	 * @return array
	 */
	public function getPropertyTableHashes( $sid ) {
		return $this->propertyTableHashes->getPropertyTableHashesById( $sid );
	}

	/**
	 * Update the proptable_hash for a given page.
	 *
	 * @since 1.8
	 * @param integer $sid ID of the page as stored in SMW IDs table
	 * @param string[] of hash values with table names as keys
	 */
	public function setPropertyTableHashes( $sid, $hash = null ) {
		$this->propertyTableHashes->setPropertyTableHashes( $sid, $hash );
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $sid
	 * @param array $dataMap
	 */
	public function setSequenceMap( $sid, array $map = null ) {
		$this->sequenceMapFinder->setMap( $sid, $map );
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $sid
	 * @param string|null $key
	 *
	 * @return array
	 */
	public function getSequenceMap( $sid, $key = null ) {

		$sequenceMap = $this->sequenceMapFinder->findMapById( $sid );

		if ( $key === null ) {
			return $sequenceMap;
		}

		if ( isset( $sequenceMap[$key] ) ) {
			return $sequenceMap[$key];
		}

		return [];
	}

	/**
	 * @since 3.1
	 *
	 * @param array $ids
	 */
	public function loadSequenceMap( array $ids ) {
		$this->sequenceMapFinder->prefetchSequenceMap( $ids );
	}

}
