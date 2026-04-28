<?php

namespace SMW\SQLStore\EntityStore;

use Iterator;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\Iterators\MappingIterator;
use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\MediaWiki\Collator;
use SMW\MediaWiki\Connection\Sequence;
use SMW\PropertyRegistry;
use SMW\RequestOptions;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\SQLStore\PropertyTable\PropertyTableHashes;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\SQLStoreFactory;
use SMW\SQLStore\TableFieldUpdater;
use SMW\TypesRegistry;
use SMW\Utils\Flag;

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
 * @license GPL-2.0-or-later
 * @since 1.8
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class EntityIdManager {

	const POOLCACHE_ID = 'smw.sqlstore';

	/**
	 * Built-in maximum entry counts for the request-scoped LRU caches that
	 * back entity ID lookups. Each pool is independent — these values are
	 * starting points that can be tuned per-pool via the
	 * `$smwgEntityCacheSizes` setting based on observed hit rates.
	 *
	 * @since 7.0.0
	 */
	public const DEFAULT_CACHE_SIZES = [
		'entity.id' => 1000,
		'entity.sort' => 1000,
		'entity.lookup' => 2000,
		'propertytable.hash' => 1000,
		'warmup.byid' => 1000,
		'sequence.map' => 1000,
		IdCacheManager::REDIRECT_SOURCE => 1000,
		IdCacheManager::REDIRECT_TARGET => 1000,
		AuxiliaryFields::COUNTMAP_CACHE_ID => 1000,
	];

	/**
	 * @var SQLStore
	 */
	public $store;

	private ?RedirectStore $redirectStore = null;

	private RedirectTargetLookup $redirectTargetLookup;

	private TableFieldUpdater $tableFieldUpdater;

	/**
	 * @var array
	 */
	public static $special_ids = [];

	private IdCacheManager $idCacheManager;

	private CacheWarmer $cacheWarmer;

	private IdEntityFinder $idEntityFinder;

	private EntityIdFinder $entityIdFinder;

	private SequenceMapFinder $sequenceMapFinder;

	private AuxiliaryFields $auxiliaryFields;

	private ?DuplicateFinder $duplicateFinder = null;

	private PropertyTableHashes $propertyTableHashes;

	private Flag $equalitySupport;

	/**
	 * @since 1.8
	 */
	public function __construct(
		SQLStore $store,
		private readonly SQLStoreFactory $factory,
	) {
		$this->store = $store;
		$this->initCache();

		$this->idEntityFinder = $this->factory->newIdEntityFinder(
			$this->idCacheManager
		);

		$this->sequenceMapFinder = $this->factory->newSequenceMapFinder(
			$this->idCacheManager
		);

		$this->auxiliaryFields = $this->factory->newAuxiliaryFields(
			$this->idCacheManager
		);

		$this->tableFieldUpdater = $this->factory->newTableFieldUpdater();

		$this->equalitySupport = new Flag( 0 );

		self::$special_ids = TypesRegistry::getFixedProperties( 'id' );
	}

	/**
	 * @since 3.2
	 *
	 * @param int $equalitySupport
	 */
	public function setEqualitySupport( int $equalitySupport ): void {
		$this->equalitySupport = new Flag( $equalitySupport );
	}

	/**
	 * This method applies changes from when the `Settings` change listener
	 * receives change events from `Settings:set`.
	 *
	 * @since 3.2
	 *
	 * @param string $key
	 * @param ChangeRecord $changeRecord
	 */
	public function applyChangesFromListener( string $key, ChangeRecord $changeRecord ): void {
		if ( $key === 'smwgQEqualitySupport' ) {
			$this->setEqualitySupport( $changeRecord->get( $key ) );
		}
	}

	/**
	 * @since 3.2
	 *
	 * @param WikiPage $target
	 * @param string|null $flag
	 *
	 * @return WikiPage|false
	 */
	public function findRedirectSource( WikiPage $target, ?string $flag = null ): WikiPage|false {
		return $this->redirectTargetLookup->findRedirectSource( $target, $flag );
	}

	/**
	 * @since  2.1
	 *
	 * @param WikiPage $subject
	 *
	 * @return bool
	 */
	public function isRedirect( WikiPage $subject ): bool {
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
	 * @param int $namespace
	 *
	 * @return int
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
	 * @param int $id
	 * @param string $title
	 * @param int $namespace
	 */
	public function addRedirect( $id, $title, $namespace ): void {
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
	 * @param int $id
	 * @param string $title
	 * @param int $namespace
	 */
	public function updateRedirect( $id, $title, $namespace ): void {
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
	 * @param int $namespace
	 */
	public function deleteRedirect( $title, $namespace ): void {
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
	 *
	 * @param string $title DB key
	 * @param int $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param string &$sortkey call-by-ref will be set to sortkey
	 * @param bool $canonical should redirects be resolved?
	 * @param bool $fetchHashes should the property hashes be obtained and cached?
	 *
	 * @return int SMW id or 0 if there is none
	 */
	public function getSMWPageIDandSort( $title, $namespace, $iw, $subobjectName, &$sortkey, $canonical, $fetchHashes = false ): int {
		$id = $this->getPredefinedData( $title, $namespace, $iw, $subobjectName, $sortkey );
		if ( $id != 0 ) {
			return $id;
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
	 *
	 * @param string $title DB key
	 * @param int $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param string &$sortkey call-by-ref will be set to sortkey
	 * @param bool $canonical should redirects be resolved?
	 * @param bool $fetchHashes should the property hashes be obtained and cached?
	 *
	 * @return int SMW id or 0 if there is none
	 */
	protected function getDatabaseIdAndSort( $title, $namespace, $iw, $subobjectName, &$sortkey, $canonical, $fetchHashes ) {
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
			$this->equalitySupport->not( SMW_EQ_NONE ) &&
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
			$this->equalitySupport->not( SMW_EQ_NONE ) &&
			$subobjectName === '' &&
			$canonical ) {
			[ $id, $sortkey ] = $this->entityIdFinder->fetchFieldsFromTableById(
				$this->findRedirect( $title, $namespace ),
				$title,
				$namespace,
				$iw,
				$subobjectName,
				$sortkey
			);
		} else {
			[ $id, $sortkey ] = $this->entityIdFinder->fetchFromTableByTitle(
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
	 * @return bool
	 */
	public function isUnique( DataItem $dataItem ): bool {
		if ( $this->duplicateFinder === null ) {
			$this->duplicateFinder = $this->factory->newDuplicateFinder();
		}

		return !$this->duplicateFinder->hasDuplicate( $dataItem );
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function findDuplicates(): array {
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
	 * @param int $namespace namespace
	 * @param string|null $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 *
	 * @return array
	 */
	public function findIdsByTitle( $title, $namespace, $iw = null, $subobjectName = '' ): array {
		return $this->entityIdFinder->findIdsByTitle( $title, $namespace, $iw, $subobjectName );
	}

	/**
	 * @since 2.4
	 *
	 * @param WikiPage $subject
	 *
	 * @return bool
	 */
	public function exists( WikiPage $subject ): bool {
		return $this->getId( $subject ) > 0;
	}

	/**
	 * @note EntityIdManager::getSMWPageID has some issues with the cache as it returned
	 * 0 even though an object was matchable, using this method is safer then trying
	 * to encipher getSMWPageID related methods.
	 *
	 * It uses the PoolCache which means Lru is in place to avoid memory leakage.
	 *
	 * @since 2.4
	 *
	 * @param WikiPage $subject
	 *
	 * @return int
	 */
	public function getId( WikiPage $subject ): int {
		// Try to match a predefined property
		if ( $subject->getNamespace() === SMW_NS_PROPERTY && $subject->getInterWiki() === '' ) {
			try {
				$property = Property::newFromUserLabel( $subject->getDBKey() );
			} catch ( PredefinedPropertyLabelMismatchException | PropertyLabelNotResolvedException ) {
				return 0;
			}

			$key = $property->getKey();

			// Has a fixed ID?
			if ( isset( self::$special_ids[$key] ) && is_int( self::$special_ids[$key] ) && $subject->getSubobjectName() === '' ) {
				return self::$special_ids[$key];
			}

			// Switch title for fixed properties without a fixed ID (e.g. _MIME is the smw_title)
			if ( !$property->isUserDefined() ) {
				$subject = new WikiPage(
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
	 *
	 * @param string $title DB key
	 * @param int $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param bool $canonical should redirects be resolved?
	 * @param bool $fetchHashes should the property hashes be obtained and cached?
	 *
	 * @return int SMW id or 0 if there is none
	 */
	public function getSMWPageID( $title, $namespace, $iw, $subobjectName, $canonical = true, $fetchHashes = false ): int {
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
	 *
	 * @param string $title DB key
	 * @param int $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param bool $canonical should redirects be resolved?
	 * @param string $sortkey call-by-ref will be set to sortkey
	 * @param bool $fetchHashes should the property hashes be obtained and cached?
	 *
	 * @return int SMW id or 0 if there is none
	 */
	public function makeSMWPageID( $title, $namespace, $iw, $subobjectName, $canonical = true, $sortkey = '', $fetchHashes = false ): int {
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
	 *
	 * @param string $title DB key
	 * @param int $namespace namespace
	 * @param string $iw interwiki prefix
	 * @param string $subobjectName name of subobject
	 * @param bool $canonical should redirects be resolved?
	 * @param string $sortkey call-by-ref will be set to sortkey
	 * @param bool $fetchHashes should the property hashes be obtained and cached?
	 *
	 * @return int SMW id or 0 if there is none
	 */
	protected function makeDatabaseId( $title, $namespace, $iw, $subobjectName, $canonical, $sortkey, $fetchHashes ): int {
		$oldsort = '';
		$id = $this->getDatabaseIdAndSort( $title, $namespace, $iw, $subobjectName, $oldsort, $canonical, $fetchHashes );
		$db = $this->store->getConnection( 'mw.db' );
		$collator = Collator::singleton();

		// Safeguard to ensure that no duplicate IDs are created
		if ( $id == 0 ) {
			$id = $this->getId( new WikiPage( $title, $namespace, $iw, $subobjectName ) );
		}

		$db->beginAtomicTransaction( __METHOD__ );

		if ( $id == 0 ) {
			$sortkey = $sortkey ?: ( str_replace( '_', ' ', $title ) );

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

			$id = $db->insertId();

			// Properties also need to be in the property statistics table
			if ( $namespace === SMW_NS_PROPERTY ) {

				$propertyStatisticsStore = $this->factory->newPropertyStatisticsStore();

				$propertyStatisticsStore->insertUsageCount( $id, 0 );
			}

			$this->setCache( $title, $namespace, $iw, $subobjectName, $id, $sortkey );

			if ( $fetchHashes ) {
				$this->propertyTableHashes->clearPropertyTableHashCacheById( $id );
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
	 *
	 * @param Property $property
	 *
	 * @return string
	 */
	public function getPropertyInterwiki( Property $property ): string {
		return ( $property->getLabel() !== '' ) ? '' : SMW_SQL3_SMWINTDEFIW;
	}

	/**
	 * @since  2.1
	 *
	 * @param int $sid
	 * @param WikiPage $subject
	 * @param int|string|null $interwiki
	 */
	public function updateInterwikiField( $sid, WikiPage $subject, $interwiki = null ): void {
		if ( $interwiki === null ) {
			$interwiki = $subject->getInterWiki();
		}

		$hash = [
			$subject->getDBKey(),
			$subject->getNamespace(),
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
	 * @param WikiPage|string $title
	 * @param int|string $namespace
	 * @param string $iw
	 */
	public function findAssociatedRev( $title, $namespace = '', $iw = '' ): int {
		$connection = $this->store->getConnection( 'mw.db' );

		if ( $title instanceof WikiPage ) {
			$cond = [
				"smw_hash" => $title->getSha1()
			];
		} elseif ( is_int( $title ) ) {
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

		return $row === false ? 0 : (int)$row->smw_rev;
	}

	/**
	 * @since 3.0
	 *
	 * @param int $sid
	 * @param int $rev_id
	 */
	public function updateRevField( $sid, $rev_id ): void {
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
	 * @param Property $property
	 *
	 * @return int
	 */
	public function getSMWPropertyID( Property $property ) {
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
	 *
	 * @param Property $property
	 *
	 * @return int
	 */
	public function makeSMWPropertyID( Property $property ): int {
		$key = $property->getKey();

		if ( isset( self::$special_ids[$key] ) && is_int( self::$special_ids[$key] ) ) {
			return self::$special_ids[$key];
		}

		return $this->makeDatabaseId(
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
	 *
	 * @param string &$title DB key
	 * @param int &$namespace namespace
	 * @param string &$iw interwiki prefix
	 * @param string &$subobjectName
	 * @param string &$sortkey
	 *
	 * @return int predefined id or 0 if none
	 */
	protected function getPredefinedData( &$title, &$namespace, &$iw, &$subobjectName, &$sortkey ): int {
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
	 * @param int $curid
	 * @param int $targetid
	 */
	public function moveSMWPageID( $curid, $targetid = 0 ): void {
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
	public function computeSha1( $args = '' ): string {
		return IdCacheManager::computeSha1( $args );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $list
	 * @param string|null $flag
	 */
	public function warmUpCache( $list = [], $flag = null ): void {
		$this->cacheWarmer->prepareCache( $list );

		if ( $flag === RedirectTargetLookup::PREPARE_CACHE ) {
			$this->redirectTargetLookup->prepareCache( (array)$list );
		}
	}

	/**
	 * Add or modify a cache entry. The key consists of the
	 * parameters $title, $namespace, $interwiki, and $subobject. The
	 * cached data is $id and $sortkey.
	 *
	 * @since 1.8
	 *
	 * @param string $title
	 * @param int $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 * @param int $id
	 * @param string $sortkey
	 */
	public function setCache( $title, $namespace, $interwiki, $subobject, $id, $sortkey ): void {
		$this->idCacheManager->setCache( $title, $namespace, $interwiki, $subobject, $id, $sortkey );
	}

	/**
	 * @since 2.1
	 *
	 * @param int $id
	 *
	 * @return WikiPage|null
	 */
	public function getDataItemById( $id ) {
		return $this->idEntityFinder->getDataItemById( $id );
	}

	/**
	 * @since 2.3
	 *
	 * @param array $idlist
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return string[]
	 */
	public function getDataItemsFromList( array $idlist, ?RequestOptions $requestOptions = null ): MappingIterator|array {
		return $this->idEntityFinder->getDataItemsFromList( $idlist, $requestOptions );
	}

	/**
	 * @deprecated since 3.0, use EntityIdManager::getDataItemsFromList
	 */
	public function getDataItemPoolHashListFor( array $idlist, ?RequestOptions $requestOptions = null ): MappingIterator|array {
		return $this->idEntityFinder->getDataItemsFromList( $idlist, $requestOptions );
	}

	/**
	 * Remove any cache entry for the given data. The key consists of the
	 * parameters $title, $namespace, $interwiki, and $subobject. The
	 * cached data is $id and $sortkey.
	 *
	 * @since 1.8
	 *
	 * @param string $title
	 * @param int $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 */
	public function deleteCache( $title, $namespace, $interwiki, $subobject ): void {
		$this->idCacheManager->deleteCache( $title, $namespace, $interwiki, $subobject );
	}

	/**
	 * @since 3.0
	 */
	public function initCache(): void {
		// Tests indicate that it is more memory efficient to have two
		// arrays (IDs and sortkeys) than to have one array that stores both
		// values in some data structure (other than a single string).
		$this->idCacheManager = $this->factory->newIdCacheManager(
			self::POOLCACHE_ID,
			$this->resolveCacheSizes()
		);

		$this->cacheWarmer = $this->factory->newCacheWarmer(
			$this->idCacheManager
		);

		$this->redirectTargetLookup = $this->factory->newRedirectTargetLookup(
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
	 * @param int $sid ID of the page as stored in the SMW IDs table
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
	 *
	 * @param int $sid ID of the page as stored in SMW IDs table
	 * @param string[]|null $hash of hash values with table names as keys
	 *
	 * @return void
	 */
	public function setPropertyTableHashes( $sid, $hash = null ): void {
		$this->propertyTableHashes->setPropertyTableHashes( $sid, $hash );
	}

	/**
	 * @since 3.2
	 *
	 * @param WikiPage[] $subjects
	 *
	 * @return FieldList
	 */
	public function preload( array $subjects ): FieldList {
		$fieldList = $this->auxiliaryFields->prefetchFieldList(
			$subjects
		);

		$this->cacheWarmer->prefetchFromList(
			$fieldList->getHashList()
		);

		return $fieldList;
	}

	/**
	 * @since 3.2
	 *
	 * @param int $sid
	 * @param array|null $sequenceMap
	 * @param array|null $countMap
	 */
	public function updateFieldMaps( $sid, ?array $sequenceMap = null, ?array $countMap = null ): void {
		$this->auxiliaryFields->setFieldMaps( $sid, $sequenceMap, $countMap );
	}

	/**
	 * @since 3.1
	 *
	 * @param int $sid
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
	public function loadSequenceMap( array $ids ): void {
		$this->sequenceMapFinder->prefetchSequenceMap( $ids );
	}

	private function resolveCacheSizes(): array {
		$configured = ApplicationFactory::getInstance()->getSettings()->safeGet( 'smwgEntityCacheSizes', [] );

		if ( !is_array( $configured ) || $configured === [] ) {
			return self::DEFAULT_CACHE_SIZES;
		}

		return array_merge( self::DEFAULT_CACHE_SIZES, $configured );
	}

}
