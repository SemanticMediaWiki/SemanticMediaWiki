<?php

namespace SMW\SQLStore\Rebuilder;

use Hooks;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\PropertyRegistry;
use SMW\SemanticData;
use SMW\Utils\Lru;
use SMW\MediaWiki\TitleFactory;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\SQLStore;
use Title;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 * @author mwjames
 */
class Rebuilder {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var EntityValidator
	 */
	private $entityValidator;

	/**
	 * @var PropertyTableIdReferenceDisposer
	 */
	private $propertyTableIdReferenceDisposer;

	/**
	 * @var JobFactory
	 */
	private $jobFactory;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var array|false
	 */
	private $namespaces = false;

	/**
	 * @var integer
	 */
	private $iterationLimit = 1;

	/**
	 * @var integer
	 */
	private $progress = 1;

	/**
	 * @var array
	 */
	private $dispatchedEntities = [];

	/**
	 * @var array
	 */
	private $updateJobs = [];

	/**
	 * @var Lru
	 */
	private $lru;

	/**
	 * @since 2.3
	 *
	 * @param SQLStore $store
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( SQLStore $store, TitleFactory $titleFactory, EntityValidator $entityValidator ) {
		$this->store = $store;
		$this->titleFactory = $titleFactory;
		$this->entityValidator = $entityValidator;
		$this->propertyTableIdReferenceDisposer = new PropertyTableIdReferenceDisposer( $store );
		$this->jobFactory = ApplicationFactory::getInstance()->newJobFactory();
		$this->lru = new Lru( 10000 );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		$this->options = $options;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = false ) {

		if ( isset( $this->options[$key] ) ) {
			return $this->options[$key];
		}

		return $default;
	}

	/**
	 * @since 2.3
	 *
	 * @param array|false $namespaces
	 */
	public function setRestrictionToNamespaces( $namespaces ) {
		$this->namespaces = $namespaces;
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $iterationLimit
	 */
	public function setDispatchRangeLimit( $iterationLimit ) {
		$this->iterationLimit = (int)$iterationLimit;
	}

	/**
	 * @since 2.3
	 *
	 * @return integer
	 */
	public function getMaxId() {

		$db = $this->store->getConnection( 'mw.db' );

		$maxByPageId = (int)$db->selectField(
			'page',
			'MAX(page_id)',
			'',
			__METHOD__
		);

		$maxBySmwId = (int)$db->selectField(
			SQLStore::ID_TABLE,
			'MAX(smw_id)',
			'',
			__METHOD__
		);

		return max( $maxByPageId, $maxBySmwId );
	}

	/**
	 * Decimal between 0 and 1 to indicate the overall progress of the rebuild
	 * process
	 *
	 * @since 2.3
	 *
	 * @return integer
	 */
	public function getEstimatedProgress() {
		return $this->progress;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getDispatchedEntities() {
		return $this->dispatchedEntities;
	}

	/**
	 * Dispatching of a single or a chunk of ids in either online or batch mode
	 * using the job scheduler.
	 *
	 * @since 2.3
	 *
	 * @param integer &$id
	 */
	public function rebuild( &$id ) {

		$this->updateJobs = [];
		$this->dispatchedEntities = [];

		$this->entityValidator->setNamespaceRestriction(
			$this->namespaces
		);

		// was nothing done in this run?
		$emptyRange = true;

		$this->matchAsTitle( $id );

		if ( $this->updateJobs !== [] ) {
			$emptyRange = false;
		}

		$this->matchAsSubject( $id, $emptyRange );

		// Deprecated since 2.3, use 'SMW::SQLStore::BeforeDataRebuildJobInsert'
		\Hooks::run('smwRefreshDataJobs', [ &$this->updateJobs ] );

		Hooks::run( 'SMW::SQLStore::BeforeDataRebuildJobInsert', [ $this->store, &$this->updateJobs ] );

		if ( $this->getOption( 'use-job' ) ) {
			$this->jobFactory->batchInsert( $this->updateJobs );
		} else {
			foreach ( $this->updateJobs as $job ) {
				$job->run();
			}
		}

		// -1 means that no next position is available
		$this->next_position( $id, $emptyRange );

		return $this->progress = $id > 0 ? $id / $this->getMaxId() : 1;
	}

	private function matchAsTitle( $id ) {

		// Update by MediaWiki page id --> make sure we get all pages.
		$tids = [];

		// Array of ids
		for ( $i = $id; $i < $id + $this->iterationLimit; $i++ ) {
			$tids[] = $i;
		}

		$titles = $this->titleFactory->newFromIDs( $tids );

		foreach ( $titles as $title ) {

			if ( $this->lru->get( $title->getDBKey() . '#' . $title->getNamespace() ) !== null ) {
				continue;
			}

			if ( ( $this->namespaces == false ) || ( in_array( $title->getNamespace(), $this->namespaces ) ) ) {
				$this->addJob( $title );
			}

			$this->dispatchedEntities[] = [ 't' => $title->getPrefixedDBKey() ];
		}
	}

	private function matchAsSubject( $id, &$emptyRange ) {

		// update by internal SMW id --> make sure we get all objects in SMW
		$connection = $this->store->getConnection( 'mw.db' );

		// MW 1.29+ "Exception thrown with an uncommitted database transaction ...
		// MWCallableUpdate::doUpdate: transaction round 'SMW\MediaWiki\Jobs\RefreshJob::run' already started"
		$this->propertyTableIdReferenceDisposer->waitOnTransactionIdle();

		$res = $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject',
				'smw_sortkey',
				'smw_proptable_hash',
				'smw_rev'
			],
			[
				"smw_id >= $id ",
				" smw_id < " . $connection->addQuotes( $id + $this->iterationLimit )
			],
			__METHOD__
		);

		foreach ( $res as $row ) {

			// note this even if no jobs were created
			$emptyRange = false;

			if ( !$this->entityValidator->inNamespace( $row ) ) {
				continue;
			}

			// If the reference is for some reason created as part of a not
			// supported namespace, check and clean it!
			//
			// The check is required to ensure that annotations let's say
			// [[Foo::SomeNS:Bar]] (where SomeNS is not enabled for SMW) are not
			// removed and is kept as long as a reference to `SomeNS:Bar` exists
			if ( !$this->entityValidator->isSemanticEnabled( $row ) ) {
				$this->propertyTableIdReferenceDisposer->removeOutdatedEntityReferencesById( $row->smw_id );
			} else {
				$this->checkRow( $row );
			}
		}
	}

	private function checkRow( $row ) {

		// Find page to refresh, even for special properties:
		if ( $row->smw_title != '' && $row->smw_title[0] != '_' ) {
			$titleKey = $row->smw_title;
		} elseif ( $this->entityValidator->isProperty( $row ) ) {
			$titleKey = str_replace( ' ', '_', PropertyRegistry::getInstance()->findCanonicalPropertyLabelById( $row->smw_title ) );
		} else {
			$titleKey = '';
		}

		$hash = $titleKey . '#' . $row->smw_namespace;

		if ( $row->smw_subobject !== '' && $row->smw_iw !== SMW_SQL3_SMWDELETEIW ) {

			$title = $this->titleFactory->makeTitleSafe(
				$row->smw_namespace,
				$titleKey
			);

			if ( $this->entityValidator->isDetachedSubobject( $title, $row ) ) {
				$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
			} elseif ( $this->entityValidator->isDetachedQueryRef( $row ) ) {
				$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
			} else {
				$this->addDispatchRecord( 's', $row );
			}
		} elseif ( $this->entityValidator->isPlainObjectValue( $row ) ) {
			$this->propertyTableIdReferenceDisposer->removeOutdatedEntityReferencesById( $row->smw_id );
		} elseif ( $row->smw_iw === '' && $titleKey != '' ) {

			if ( $this->lru->get( $hash ) !== null ) {
				return;
			}

			// objects representing pages
			$title = $this->titleFactory->makeTitleSafe(
				$row->smw_namespace,
				$titleKey
			);

			if ( $title !== null ) {
				$this->dispatchedEntities[] = [ 's' => $title->getPrefixedDBKey() ];
				$this->addJob( $title, $row );
			}
		} elseif ( $this->entityValidator->isRedirect( $row ) ) {

			if ( $this->lru->get( $hash ) !== null || $titleKey === '' ) {
				return;
			}

			// TODO: special treatment of redirects needed, since the store will
			// not act on redirects that did not change according to its records
			$title = $this->titleFactory->makeTitleSafe(
				$row->smw_namespace,
				$titleKey
			);

			if ( $title !== null && !$title->exists() ) {
				$this->dispatchedEntities[] = [ 's' => $title->getPrefixedDBKey() ];
				$this->addJob( $title, $row );
			}

			$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
		} elseif ( $this->entityValidator->isOutdated( $row ) ) { // remove outdated internal object references
			$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
		} elseif ( $titleKey != '' ) { // "normal" interwiki pages or outdated internal objects -- delete

			if ( $this->lru->get( $hash ) !== null ) {
				return;
			}

			$subject = new DIWikiPage( $titleKey, $row->smw_namespace, $row->smw_iw );
			$this->store->updateData( new SemanticData( $subject ) );
			$this->dispatchedEntities[] = [ 's' => $subject ];
		}

		if ( $this->entityValidator->isProperty( $row ) ) {
			$this->removeDuplicates( $row, $this->entityValidator->findDuplicates( $row ) );
		}

		if ( $this->entityValidator->hasPropertyInvalidCharacter( $row ) ) {
			$this->setDeleteFlag( $row->smw_id, $row->smw_title, $row->smw_namespace );
		}

		if ( $this->entityValidator->isRetiredProperty( $row ) ) {
			$this->setDeleteFlag( $row->smw_id, $row->smw_title, $row->smw_namespace );
		}
	}

	private function setDeleteFlag( $id, $title, $ns ) {
		$this->store->getObjectIds()->updateInterwikiField(
			$id,
			new DIWikiPage( $title, $ns, SMW_SQL3_SMWDELETEIW )
		);
	}

	private function removeDuplicates( $row, $duplicates ) {

		// Instead of copying ID's across DB tables have the re-parse to ensure
		// that all property value ID's are reassigned together while the duplicate
		// is marked for removal until the next run
		foreach ( $duplicates as $duplicate ) {

			// If titles don't match then continue because it could be that
			// Property:Foo with displaytitle foobar -> sortkey ->foobar
			// Property:Bar with displaytitle foobar -> sortkey ->foobar
			if ( $row->smw_title !== $duplicate->smw_title ) {
				continue;
			}

			$this->setDeleteFlag( $duplicate->smw_id, $row->smw_title, $row->smw_namespace );
		}
	}

	private function next_position( &$id, $emptyRange ) {

		$nextPosition = $id + $this->iterationLimit;
		$db = $this->store->getConnection( 'mw.db' );

		// nothing found, check if there will be more pages later on
		if ( $emptyRange && $nextPosition > SQLStore::FIXED_PROPERTY_ID_UPPERBOUND ) {

			$nextByPageId = (int)$db->selectField(
				'page',
				'page_id',
				"page_id >= $nextPosition",
				__METHOD__,
				[
					'ORDER BY' => "page_id ASC"
				]
			);

			$nextBySmwId = (int)$db->selectField(
				SQLStore::ID_TABLE,
				'smw_id',
				"smw_id >= $nextPosition",
				__METHOD__,
				[
					'ORDER BY' => "smw_id ASC"
				]
			);

			// Next position is determined by the pool with the maxId
			$nextPosition = $nextBySmwId != 0 && $nextBySmwId > $nextByPageId ? $nextBySmwId : $nextByPageId;
		}

		$id = $nextPosition ? $nextPosition : -1;
	}

	private function hasSkippableRevision( $title, $row = false ) {

		if ( $this->getOption( 'force-update' ) ) {
			return false;
		}

		return $this->getOption( 'revision-mode' ) && $this->entityValidator->hasLatestRevID( $title, $row );
	}

	private function addDispatchRecord( $key, $row ) {
		$this->dispatchedEntities[] = [ $key => $row->smw_title . '#' . $row->smw_namespace . '#' .$row->smw_subobject ];
	}

	private function addJob( $title, $row = false ) {

		$hash = $title->getDBKey() . '#' . $title->getNamespace();
		$this->lru->set( $hash, true );

		if ( $this->hasSkippableRevision( $title, $row = false ) ) {
			return $this->dispatchedEntities[] = [ 'skipped' => $title->getPrefixedDBKey() ];
		}

		$params = [
			'origin' => 'EntityRebuildDispatcher'
		];

		if ( $this->getOption( 'shallow-update' ) ) {
			$params += [ 'shallowUpdate' => true ];
		} elseif ( $this->getOption( 'force-update' ) ) {
			$params += [ 'forcedUpdate' => true ];
		}

		$updateJob = $this->jobFactory->newUpdateJob(
			$title,
			$params
		);

		$this->updateJobs[] = $updateJob;
	}

}
