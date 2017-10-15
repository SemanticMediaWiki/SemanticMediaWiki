<?php

namespace SMW\SQLStore;

use Hooks;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\Store;
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
class EntityRebuildDispatcher {

	/**
	 * @var SQLStore
	 */
	private $store;

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
	 * @var integer
	 */
	private $updateJobParseMode;

	/**
	 * @var boolean
	 */
	private $useJobQueueScheduler = true;

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
	private $dispatchedEntities = array();

	/**
	 * @since 2.3
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->propertyTableIdReferenceDisposer = new PropertyTableIdReferenceDisposer( $store );
		$this->jobFactory = ApplicationFactory::getInstance()->newJobFactory();
		$this->namespaceExaminer = ApplicationFactory::getInstance()->getNamespaceExaminer();
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $updateJobParseMode
	 */
	public function setUpdateJobParseMode( $updateJobParseMode ) {
		$this->updateJobParseMode = $updateJobParseMode;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $useJobQueueScheduler
	 */
	public function useJobQueueScheduler( $useJobQueueScheduler ) {
		$this->useJobQueueScheduler = (bool)$useJobQueueScheduler;
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
			\SMWSql3SmwIds::TABLE_NAME,
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
	 * using the JobQueueScheduler
	 *
	 * @since 2.3
	 *
	 * @param integer &$id
	 */
	public function startRebuildWith( &$id ) {

		$updateJobs = array();
		$this->dispatchedEntities = array();

		// was nothing done in this run?
		$emptyRange = true;

		$this->createUpdateJobsForTitleIdRange( $id, $updateJobs );

		if ( $updateJobs !== array() ) {
			$emptyRange = false;
		}

		$this->createUpdateJobsForSmwIdRange( $id, $updateJobs, $emptyRange );

		// Deprecated since 2.3, use 'SMW::SQLStore::BeforeDataRebuildJobInsert'
		\Hooks::run('smwRefreshDataJobs', array( &$updateJobs ) );

		Hooks::run( 'SMW::SQLStore::BeforeDataRebuildJobInsert', array( $this->store, &$updateJobs ) );

		if ( $this->useJobQueueScheduler ) {
			$this->jobFactory->batchInsert( $updateJobs );
		} else {
			foreach ( $updateJobs as $job ) {
				$job->run();
			}
		}

		// -1 means that no next position is available
		$this->findNextIdPosition( $id, $emptyRange );

		return $this->progress = $id > 0 ? $id / $this->getMaxId() : 1;
	}

	/**
	 * @param integer $id
	 * @param UpdateJob[] &$updateJobs
	 */
	private function createUpdateJobsForTitleIdRange( $id, &$updateJobs ) {

		// Update by MediaWiki page id --> make sure we get all pages.
		$tids = array();

		// Array of ids
		for ( $i = $id; $i < $id + $this->iterationLimit; $i++ ) {
			$tids[] = $i;
		}

		$titles = Title::newFromIDs( $tids );

		foreach ( $titles as $title ) {
			if ( ( $this->namespaces == false ) || ( in_array( $title->getNamespace(), $this->namespaces ) ) ) {
				$updateJobs[] = $this->newUpdateJob( $title );
			}

			$this->dispatchedEntities[] = array( 't' => $title->getPrefixedDBKey() );
		}
	}

	/**
	 * @param integer $id
	 * @param UpdateJob[] &$updateJobs
	 * @param bool $emptyRange
	 */
	private function createUpdateJobsForSmwIdRange( $id, &$updateJobs, &$emptyRange ) {

		// update by internal SMW id --> make sure we get all objects in SMW
		$db = $this->store->getConnection( 'mw.db' );

		// MW 1.29+ "Exception thrown with an uncommited database transaction ...
		// MWCallableUpdate::doUpdate: transaction round 'SMW\MediaWiki\Jobs\RefreshJob::run' already started"
		$this->propertyTableIdReferenceDisposer->waitOnTransactionIdle();

		$res = $db->select(
			\SMWSql3SmwIds::TABLE_NAME,
			array( 'smw_id', 'smw_title', 'smw_namespace', 'smw_iw', 'smw_subobject', 'smw_sortkey', 'smw_proptable_hash' ),
			array(
				"smw_id >= $id ",
				" smw_id < " . $db->addQuotes( $id + $this->iterationLimit )
			),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$emptyRange = false; // note this even if no jobs were created

			if ( $this->namespaces && !in_array( $row->smw_namespace, $this->namespaces ) ) {
				continue;
			}

			// If the reference is for some reason created as part of a not
			// supported namespace, check and clean it!
			//
			// The check is required to ensure that annotations let's say
			// [[Foo::SomeNS:Bar]] (where SomeNS is not enabled for SMW) are not
			// removed and is kept as long as a reference to `SomeNS:Bar` exists
			if ( !$this->namespaceExaminer->isSemanticEnabled( (int)$row->smw_namespace ) ) {
				$this->propertyTableIdReferenceDisposer->removeOutdatedEntityReferencesById( $row->smw_id );
				continue;
			}

			// Find page to refresh, even for special properties:
			if ( $row->smw_title != '' && $row->smw_title{0} != '_' ) {
				$titleKey = $row->smw_title;
			} elseif ( $row->smw_namespace == SMW_NS_PROPERTY && $row->smw_iw == '' && $row->smw_subobject == '' ) {
				$titleKey = str_replace( ' ', '_', DIProperty::findPropertyLabel( $row->smw_title ) );
			} else {
				$titleKey = '';
			}

			if ( $row->smw_subobject !== '' && $row->smw_iw !== SMW_SQL3_SMWDELETEIW ) {
				// leave subobjects alone; they ought to be changed with their pages
				$this->dispatchedEntities[] = array( 's' => $row->smw_title . '#' . $row->smw_namespace . '#' .$row->smw_subobject );
			} elseif ( $this->isPlainObjectValue( $row ) ) {
				$this->propertyTableIdReferenceDisposer->removeOutdatedEntityReferencesById( $row->smw_id );
			} elseif ( $row->smw_iw === '' && $titleKey != '' ) {
				// objects representing pages
				$title = Title::makeTitleSafe( $row->smw_namespace, $titleKey );

				if ( $title !== null ) {
					$this->dispatchedEntities[] = array( 's' => $title->getPrefixedDBKey() );
					$updateJobs[] = $this->newUpdateJob( $title );
				}

			} elseif ( $row->smw_iw == SMW_SQL3_SMWREDIIW && $titleKey != '' ) {
				// TODO: special treatment of redirects needed, since the store will
				// not act on redirects that did not change according to its records
				$title = Title::makeTitleSafe( $row->smw_namespace, $titleKey );

				if ( $title !== null && !$title->exists() ) {
					$this->dispatchedEntities[] = array( 's' => $title->getPrefixedDBKey() );
					$updateJobs[] = $this->newUpdateJob( $title );
				}
			} elseif ( $row->smw_iw == SMW_SQL3_SMWIW_OUTDATED || $row->smw_iw == SMW_SQL3_SMWDELETEIW ) { // remove outdated internal object references
				$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
			} elseif ( $titleKey != '' ) { // "normal" interwiki pages or outdated internal objects -- delete
				$diWikiPage = new DIWikiPage( $titleKey, $row->smw_namespace, $row->smw_iw );
				$emptySemanticData = new SemanticData( $diWikiPage );
				$this->store->updateData( $emptySemanticData );
				$this->dispatchedEntities[] = array( 's' => $diWikiPage );
			}

			if ( $row->smw_namespace == SMW_NS_PROPERTY && $row->smw_iw == '' && $row->smw_subobject == '' ) {
				$this->markPossibleDuplicateProperties( $row );
			}
		}

		$db->freeResult( $res );
	}

	private function isPlainObjectValue( $row ) {

		// A rogue title should never happen
		if ( $row->smw_title === '' && $row->smw_proptable_hash === null ) {
			return true;
		}

		return $row->smw_iw != SMW_SQL3_SMWDELETEIW &&
			$row->smw_iw != SMW_SQL3_SMWREDIIW &&
			$row->smw_iw != SMW_SQL3_SMWIW_OUTDATED &&
			// Leave any pre-defined property (_...) untouched
			$row->smw_title != '' &&
			$row->smw_title{0} != '_' &&
			// smw_proptable_hash === null means it is not a subject but an object value
			$row->smw_proptable_hash === null;
	}

	private function markPossibleDuplicateProperties( $row ) {

		$db = $this->store->getConnection( 'mw.db' );

		// Use the sortkey (comparing the label and not the "_..." key) in order
		// to match possible duplicate properties by label (not by key)
		$duplicates = $db->select(
			\SMWSql3SmwIds::TABLE_NAME,
			array( 'smw_id', 'smw_title' ),
			array(
				"smw_id !=" . $db->addQuotes( $row->smw_id ),
				"smw_sortkey =" . $db->addQuotes( $row->smw_sortkey ),
				"smw_namespace =" . $row->smw_namespace,
				"smw_subobject =" . $db->addQuotes( $row->smw_subobject )
			),
			__METHOD__,
			array( 'ORDER BY' => "smw_id ASC" )
		);

		if ( $duplicates === false ) {
			return;
		}

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

			$this->store->getObjectIds()->updateInterwikiField(
				$duplicate->smw_id,
				new DIWikiPage( $row->smw_title, $row->smw_namespace, SMW_SQL3_SMWDELETEIW )
			);
		}
	}

	private function findNextIdPosition( &$id, $emptyRange ) {

		$nextPosition = $id + $this->iterationLimit;
		$db = $this->store->getConnection( 'mw.db' );

		// nothing found, check if there will be more pages later on
		if ( $emptyRange && $nextPosition > \SMWSql3SmwIds::FXD_PROP_BORDER_ID ) {

			$nextByPageId = (int)$db->selectField(
				'page',
				'page_id',
				"page_id >= $nextPosition",
				__METHOD__,
				array( 'ORDER BY' => "page_id ASC" )
			);

			$nextBySmwId = (int)$db->selectField(
				\SMWSql3SmwIds::TABLE_NAME,
				'smw_id',
				"smw_id >= $nextPosition",
				__METHOD__,
				array( 'ORDER BY' => "smw_id ASC" )
			);

			// Next position is determined by the pool with the maxId
			$nextPosition = $nextBySmwId != 0 && $nextBySmwId > $nextByPageId ? $nextBySmwId : $nextByPageId;
		}

		$id = $nextPosition ? $nextPosition : -1;
	}

	private function newUpdateJob( $title ) {
		return $this->jobFactory->newUpdateJob( $title, array( 'pm' => $this->updateJobParseMode ) );
	}

}
