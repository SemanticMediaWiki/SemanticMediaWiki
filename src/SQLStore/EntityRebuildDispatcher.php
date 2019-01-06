<?php

namespace SMW\SQLStore;

use Hooks;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\PropertyRegistry;
use SMW\SemanticData;
use SMW\Utils\Lru;
use SMW\MediaWiki\TitleFactory;
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
	 * @var TitleFactory
	 */
	private $titleFactory;

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
	public function __construct( SQLStore $store, TitleFactory $titleFactory ) {
		$this->store = $store;
		$this->titleFactory = $titleFactory;
		$this->propertyTableIdReferenceDisposer = new PropertyTableIdReferenceDisposer( $store );
		$this->jobFactory = ApplicationFactory::getInstance()->newJobFactory();
		$this->namespaceExaminer = ApplicationFactory::getInstance()->getNamespaceExaminer();
		$this->lru = new Lru( 10000 );
	}

	/**
	 * @since 2.3
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		$this->options = $options;
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
	 * using the JoblruScheduler
	 *
	 * @since 2.3
	 *
	 * @param integer &$id
	 */
	public function rebuild( &$id ) {

		$this->updateJobs = [];
		$this->dispatchedEntities = [];

		// was nothing done in this run?
		$emptyRange = true;

		$this->match_title( $id );

		if ( $this->updateJobs !== [] ) {
			$emptyRange = false;
		}

		$this->match_subject( $id, $emptyRange );

		// Deprecated since 2.3, use 'SMW::SQLStore::BeforeDataRebuildJobInsert'
		\Hooks::run('smwRefreshDataJobs', [ &$this->updateJobs ] );

		Hooks::run( 'SMW::SQLStore::BeforeDataRebuildJobInsert', [ $this->store, &$this->updateJobs ] );

		if ( isset( $this->options['use-job'] ) && $this->options['use-job'] ) {
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

	/**
	 * @param integer $id
	 * @param UpdateJob[] &$updateJobs
	 */
	private function match_title( $id ) {

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
				$this->add_update( $title );
			}

			$this->dispatchedEntities[] = [ 't' => $title->getPrefixedDBKey() ];
		}
	}

	private function match_subject( $id, &$emptyRange ) {

		// update by internal SMW id --> make sure we get all objects in SMW
		$db = $this->store->getConnection( 'mw.db' );

		// MW 1.29+ "Exception thrown with an uncommitted database transaction ...
		// MWCallableUpdate::doUpdate: transaction round 'SMW\MediaWiki\Jobs\RefreshJob::run' already started"
		$this->propertyTableIdReferenceDisposer->waitOnTransactionIdle();

		$res = $db->select(
			\SMWSql3SmwIds::TABLE_NAME,
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
				" smw_id < " . $db->addQuotes( $id + $this->iterationLimit )
			],
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
				$titleKey = str_replace( ' ', '_', PropertyRegistry::getInstance()->findCanonicalPropertyLabelById( $row->smw_title ) );
			} else {
				$titleKey = '';
			}

			$hash = $titleKey . '#' . $row->smw_namespace;

			if ( $row->smw_subobject !== '' && $row->smw_iw !== SMW_SQL3_SMWDELETEIW ) {

				$title = $this->titleFactory->makeTitleSafe( $row->smw_namespace, $titleKey );

				// Remove tangling subobjects without a real page (created by a
				// page preview etc.) otherwise leave subobjects alone; they ought
				// to be changed with their pages
				if ( $title !== null && !$title->exists() ) {
					$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
				} else {
					$this->dispatchedEntities[] = [ 's' => $row->smw_title . '#' . $row->smw_namespace . '#' .$row->smw_subobject ];
				}
			} elseif ( $this->isPlainObjectValue( $row ) ) {
				$this->propertyTableIdReferenceDisposer->removeOutdatedEntityReferencesById( $row->smw_id );
			} elseif ( $row->smw_iw === '' && $titleKey != '' ) {

				if ( $this->lru->get( $hash ) !== null ) {
					continue;
				}

				// objects representing pages
				$title = $this->titleFactory->makeTitleSafe( $row->smw_namespace, $titleKey );

				if ( $title !== null ) {
					$this->dispatchedEntities[] = [ 's' => $title->getPrefixedDBKey() ];
					$this->add_update( $title, $row );
				}
			} elseif ( $row->smw_iw == SMW_SQL3_SMWREDIIW && $titleKey != '' ) {

				if ( $this->lru->get( $hash ) !== null ) {
					continue;
				}

				// TODO: special treatment of redirects needed, since the store will
				// not act on redirects that did not change according to its records
				$title = $this->titleFactory->makeTitleSafe( $row->smw_namespace, $titleKey );

				if ( $title !== null && !$title->exists() ) {
					$this->dispatchedEntities[] = [ 's' => $title->getPrefixedDBKey() ];
					$this->add_update( $title, $row );
				}

				$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
			} elseif ( $row->smw_iw == SMW_SQL3_SMWIW_OUTDATED || $row->smw_iw == SMW_SQL3_SMWDELETEIW ) { // remove outdated internal object references
				$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
			} elseif ( $titleKey != '' ) { // "normal" interwiki pages or outdated internal objects -- delete

				if ( $this->lru->get( $hash ) !== null ) {
					continue;
				}

				$subject = new DIWikiPage( $titleKey, $row->smw_namespace, $row->smw_iw );
				$this->store->updateData( new SemanticData( $subject ) );
				$this->dispatchedEntities[] = [ 's' => $subject ];
			}

			if ( $row->smw_namespace == SMW_NS_PROPERTY && $row->smw_iw == '' && $row->smw_subobject == '' ) {
				$this->findDuplicateProperties( $row );
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

	private function findDuplicateProperties( $row ) {

		$db = $this->store->getConnection( 'mw.db' );

		// Use the sortkey (comparing the label and not the "_..." key) in order
		// to match possible duplicate properties by label (not by key)
		$duplicates = $db->select(
			\SMWSql3SmwIds::TABLE_NAME,
			[
				'smw_id',
				'smw_title' ],
			[
				"smw_id !=" . $db->addQuotes( $row->smw_id ),
				"smw_sortkey =" . $db->addQuotes( $row->smw_sortkey ),
				"smw_namespace =" . $row->smw_namespace,
				"smw_subobject =" . $db->addQuotes( $row->smw_subobject )
			],
			__METHOD__,
			[
				'ORDER BY' => "smw_id ASC"
			]
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

	private function next_position( &$id, $emptyRange ) {

		$nextPosition = $id + $this->iterationLimit;
		$db = $this->store->getConnection( 'mw.db' );

		// nothing found, check if there will be more pages later on
		if ( $emptyRange && $nextPosition > \SMWSql3SmwIds::FXD_PROP_BORDER_ID ) {

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
				\SMWSql3SmwIds::TABLE_NAME,
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

	private function add_update( $title, $row = false ) {

		$hash = $title->getDBKey() . '#' . $title->getNamespace();
		$this->lru->set( $hash, true );

		if ( isset( $this->options['revision-mode'] ) && $this->options['revision-mode'] && !$this->options['force-update'] && $this->matchesLatestRevID( $title, $row ) ) {
			return $this->dispatchedEntities[] = [ 'skipped' => $title->getPrefixedDBKey() ];
		}

		$params = [
			'origin' => 'EntityRebuildDispatcher'
		];

		if ( isset( $this->options['shallow-update'] ) && $this->options['shallow-update'] ) {
			$params += [ 'shallowUpdate' => true ];
		} elseif ( isset( $this->options['force-update'] ) && $this->options['force-update'] ) {
			$params += [ 'forcedUpdate' => true ];
		}

		$updateJob = $this->jobFactory->newUpdateJob(
			$title,
			$params
		);

		$this->updateJobs[] = $updateJob;
	}

	private function matchesLatestRevID( $title, $row = false ) {

		$latestRevID = $title->getLatestRevID( Title::GAID_FOR_UPDATE );

		if ( $row !== false ) {
			return $latestRevID == $row->smw_rev;
		};

		$rev = $this->store->getObjectIds()->findAssociatedRev(
			$title->getDBKey(),
			$title->getNamespace(),
			$title->getInterwiki()
		);

		return $latestRevID == $rev;
	}

}
