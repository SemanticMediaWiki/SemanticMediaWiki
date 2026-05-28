<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Title\Title;
use Onoi\Cache\Cache;
use Psr\Log\LoggerInterface;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Export\Exporter;
use SMW\IteratorFactory;
use SMW\MediaWiki\Job;
use SMW\MediaWiki\JobFactory;
use SMW\Property\SpecificationLookup as PropertySpecificationLookup;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\Lookup\ChangePropagationEntityLookup;
use SMW\Store;

/**
 * `ChangePropagationDispatchJob` dispatches update jobs via `ChangePropagationUpdateJob`
 * to allow isolating the execution and count pending jobs without using an extra
 * tracking mechanism during an update process.
 *
 * `ChangePropagationUpdateJob` (and hereby ChangePropagationClassUpdateJob) itself
 * relies on the `UpdateJob` to initiate the update.
 *
 * `ChangePropagationDispatchJob` is responsible for:
 *
 * - Select entities that are being connected to a property specification
 *   change
 * - Once the selection process has been finalized, update the property with the
 *   new specification (which has been locked before this update)
 *
 * Due to the possibility that a large list of entities can be connected to a
 * property and its change, an iterative or recursive processing is not viable
 * (as the changed specification should be available as soon as possible) therefore
 * the selection process will move the result of entities to chunked temp files
 * to avoid having to use a DB connection during the process (has been observed
 * during tests that would lead to an out-of-memory) to store a list of
 * entities that require an update.
 *
 * Partial DI: Store, Cache, PropertySpecificationLookup and IteratorFactory
 * are injected via the JobClasses ObjectFactory spec. The PSR-3 logger is
 * still resolved lazily via `LoggerFactory::getInstance( 'smw' )` rather than
 * constructor injection.
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationDispatchJob extends Job {

	/**
	 * Size of rows stored in a temp file
	 */
	const CHUNK_SIZE = 1000;

	/**
	 * Temp marker namespace
	 */
	const CACHE_NAMESPACE = 'smw:chgprop';

	/**
	 * Property keys whose change-propagation diffs do not affect dependents'
	 * stored SMW data. For these, dependent per-entity update jobs use
	 * shallowUpdate (parser-cache purge only) instead of forcedUpdate
	 * (full re-parse + re-store).
	 *
	 * Verified against the data model:
	 *  - _SUBC, _SUBP: hierarchies walked at query time via HierarchyTempTableBuilder
	 *  - _PDESC, _PPLB: display-only labels/descriptions
	 *
	 * Excluded as storage-affecting: _TYPE/_CONV/_UNIT/_REDI/_LIST. The
	 * non-obvious case is _LIST: it shapes how record-property values are
	 * decomposed into sub-property values at store time
	 * (see Property/SpecificationLookup::getFieldListBy).
	 *
	 * Excluded as constraint-adjacent (stored _ERRT may depend):
	 * _PVAL/_PVUC/_PVALI/_PVAP/_PREC.
	 */
	private const SHALLOW_SET = [ '_SUBC', '_SUBP', '_PDESC', '_PPLB' ];

	/**
	 * @since 3.0
	 */
	public function __construct(
		Title $title,
		array $params,
		Store $store,
		private readonly Cache $cache,
		private readonly PropertySpecificationLookup $propertySpecificationLookup,
		private readonly IteratorFactory $iteratorFactory,
		private readonly JobFactory $jobFactory
	) {
		parent::__construct( 'smw.changePropagationDispatch', $title, $params );
		$this->setStore( $store );
		$this->removeDuplicates = true;
	}

	/**
	 * Called from PropertyChangePropagationNotifier
	 *
	 * @since 3.0
	 *
	 * @param WikiPage $subject The Property or Category page whose spec changed.
	 * @param array $params Recognized keys:
	 *  - 'isTypePropagation' (bool): set when the diff was on _TYPE; widens the
	 *    entity-lookup orphan scan in ChangePropagationEntityLookup.
	 *  - 'diffKeys' (string[]): every watched-property key that diffed. Used by
	 *    chooseUpdateStrategy() to decide whether per-entity jobs go shallow.
	 *  - 'data' (string): newline-separated subject hashes for a chunked
	 *    secondary-dispatch pass (populated by pushChangePropagationDispatchJob).
	 *  - 'schema_change_propagation' (mixed): present when the dispatch was
	 *    triggered by a Schema edit (not a Property/Category page edit);
	 *    'property_key' is consulted in this branch.
	 *  - 'property_key' (string): the property key for schema-driven dispatch.
	 *
	 * @return bool
	 */
	public static function planAsJob( WikiPage $subject, array $params = [] ): bool {
		Exporter::getInstance()->resetCacheBy( $subject );
		ApplicationFactory::getInstance()->getPropertySpecificationLookup()->invalidateCache(
			$subject
		);

		$changePropagationDispatchJob = ApplicationFactory::getInstance()->getJobFactory()->newChangePropagationDispatchJob(
			$subject->getTitle(),
			$params
		);
		$changePropagationDispatchJob->lazyPush();

		return true;
	}

	/**
	 * @since 3.0
	 */
	public static function cleanUp( WikiPage $subject ): void {
		$namespace = $subject->getNamespace();

		if ( $namespace !== SMW_NS_PROPERTY && $namespace !== NS_CATEGORY ) {
			return;
		}

		ApplicationFactory::getInstance()->getCache()->delete(
			smwfCacheKey(
				self::CACHE_NAMESPACE,
				$subject->getHash()
			)
		);
	}

	/**
	 * @since 3.0
	 */
	public static function hasPendingJobs( WikiPage $subject ): bool {
		$applicationFactory = ApplicationFactory::getInstance();
		$jobQueue = $applicationFactory->getJobQueue();

		$jobType = 'smw.changePropagationDispatch';

		if ( $jobQueue->hasPendingJob( $jobType ) ) {
			return true;
		}

		if ( $subject->getNamespace() === NS_CATEGORY ) {
			$jobType = 'smw.changePropagationClassUpdate';
		} else {
			$jobType = 'smw.changePropagationUpdate';
		}

		if ( $jobQueue->hasPendingJob( $jobType ) ) {
			return true;
		}

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			$subject->getHash()
		);

		return $applicationFactory->getCache()->fetch( $key ) > 0;
	}

	/**
	 * Use as very simple heuristic to count pending jobs for the overall change
	 * propagation. The count will indicate any job related to the change propagation
	 * and does not distinguish by changes to a specific property.
	 *
	 * @since 3.0
	 */
	public static function getPendingJobsCount( WikiPage $subject ): int {
		$applicationFactory = ApplicationFactory::getInstance();
		$jobQueue = $applicationFactory->getJobQueue();

		$jobType = 'smw.changePropagationDispatch';
		$count = 0;

		if ( $jobQueue->hasPendingJob( $jobType ) ) {
			$count = $jobQueue->getQueueSize( $jobType );
		}

		if ( $subject->getNamespace() === NS_CATEGORY ) {
			$jobType = 'smw.changePropagationClassUpdate';
		} else {
			$jobType = 'smw.changePropagationUpdate';
		}

		$count += $jobQueue->getQueueSize( $jobType );

		// Fallback for when JobQueue::getQueueSize doesn't yet contain the
		// updated stats
		if ( $count == 0 && self::hasPendingJobs( $subject ) ) {
			$key = smwfCacheKey(
				self::CACHE_NAMESPACE,
				$subject->getHash()
			);

			$count = $applicationFactory->getCache()->fetch( $key );
		}

		return $count;
	}

	/**
	 * @see Job::run
	 *
	 * @since 3.0
	 */
	public function run(): bool {
		$subject = WikiPage::newFromTitle( $this->getTitle() );

		if ( $this->hasParameter( 'data' ) ) {
			return $this->dispatchFromData( $subject, $this->getParameter( 'data' ) );
		}

		if ( $this->hasParameter( 'schema_change_propagation' ) ) {
			return $this->dispatchFromSchema( $subject, $this->getParameter( 'property_key' ) );
		}

		$this->findAndDispatch();

		return true;
	}

	private function findAndDispatch(): void {
		$namespace = $this->getTitle()->getNamespace();

		if ( $namespace !== SMW_NS_PROPERTY && $namespace !== NS_CATEGORY ) {
			return;
		}

		$subject = WikiPage::newFromTitle( $this->getTitle() );

		$this->getLogger()->info(
			'ChangePropagationDispatchJob on ' . $subject->getHash()
		);

		$changePropagationEntityLookup = new ChangePropagationEntityLookup(
			$this->store,
			$this->iteratorFactory
		);

		$changePropagationEntityLookup->isTypePropagation(
			$this->getParameter( 'isTypePropagation' )
		);

		if ( $namespace === SMW_NS_PROPERTY ) {
			$entity = Property::newFromUserLabel( $this->getTitle()->getText() );
		} elseif ( $namespace === NS_CATEGORY ) {
			$entity = $subject;
		}

		if ( !isset( $entity ) ) {
			return;
		}

		$appendIterator = $changePropagationEntityLookup->findAll(
			$entity
		);

		// Refresh the property page once more on the last dispatch
		$appendIterator->add(
			[ $subject ]
		);

		// After relevant subjects has been selected, commit the changes to the
		// property so that the lock can be removed and any new specification
		// (type, allows values etc.) are available upon executing individual
		// jobs.
		$this->commitSpecificationChangePropagationAsJob(
			$subject,
			$appendIterator->count()
		);

		$chunkedIterator = $this->iteratorFactory->newChunkedIterator(
			$appendIterator,
			self::CHUNK_SIZE
		);

		$i = 0;

		foreach ( $chunkedIterator as $chunk ) {
			$this->pushChangePropagationDispatchJob( $i++, $chunk );
		}
	}

	private function pushChangePropagationDispatchJob( int $num, $chunk ): void {
		$data = [];

		// Filter any subobject
		foreach ( $chunk as $val ) {
			$data[] = ( $val instanceof WikiPage ? $val->asBase()->getHash() : $val );
		}

		// Filter duplicates
		$contents = implode( "\n", array_keys( array_flip( $data ) ) );

		$checkSum = md5( $contents );

		$params = [ 'data' => $contents ];

		// Carry diffKeys forward so the second-stage dispatch can choose the
		// correct update strategy (shallowUpdate vs forcedUpdate) when it calls
		// scheduleChangePropagationUpdateJobFromList().
		$diffKeys = $this->getParameter( 'diffKeys' );
		if ( is_array( $diffKeys ) && $diffKeys !== [] ) {
			$params['diffKeys'] = $diffKeys;
		}

		$changePropagationDispatchJob = $this->jobFactory->newChangePropagationDispatchJob(
			$this->getTitle(),
			$params + self::newRootJobParams(
				"ChangePropagationDispatchJob:smw_chgprop_$num\_tmp:$checkSum"
			)
		);

		$changePropagationDispatchJob->lazyPush();
	}

	private function dispatchFromData( WikiPage $subject, $data ): bool {
		$property = Property::newFromUserLabel(
			$this->getTitle()->getText()
		);

		$this->store->getSemanticData( $subject );

		$key = smwfCacheKey( self::CACHE_NAMESPACE, $subject->getHash() );

		// SemanticData hasn't been updated, re-enter the cycle to ensure that
		// the update of the property took place
		if ( $this->cache->fetch( $key ) === false ) {

			$this->cache->save( $key, 1, 60 * 60 * 24 );
			$params = $this->params;

			$changePropagationDispatchJob = $this->jobFactory->newChangePropagationDispatchJob(
				$this->getTitle(),
				$params
			);

			$changePropagationDispatchJob->insert();

			$this->getLogger()->info(
				'ChangePropagationDispatchJob missing update marker, retry on ' . $subject->getHash()
			);

			return true;
		}

		// @see ChangePropagationDispatchJob::pushChangePropagationDispatchJob
		$dataItems = explode( "\n", (string)$data );

		$this->scheduleChangePropagationUpdateJobFromList(
			$dataItems
		);

		return true;
	}

	private function dispatchFromSchema( WikiPage $subject, $property_key ): bool {
		// Find all properties that point to the schema and hereby require
		// an update (!! using the inverse relationship)
		$dataItems = $this->store->getPropertyValues(
			$subject,
			new Property( $property_key, true )
		);

		// Scheduling the actual dispatch for those properties connected to
		// the schema change
		foreach ( $dataItems as $dataItem ) {
			$changePropagationDispatchJob = $this->jobFactory->newChangePropagationDispatchJob(
				$dataItem->getTitle(),
				[]
			);

			$changePropagationDispatchJob->insert();
		}

		return true;
	}

	private function scheduleChangePropagationUpdateJobFromList( array $dataItems ): void {
		$strategy = $this->chooseUpdateStrategy();

		$this->getLogger()->info(
			'ChangePropagationDispatchJob strategy {strategy} for diffKeys {diffKeys}',
			[
				'method' => __METHOD__,
				'strategy' => $strategy,
				'diffKeys' => json_encode( $this->getParameter( 'diffKeys' ) ?? [] ),
			]
		);

		foreach ( $dataItems as $dataItem ) {

			if ( $dataItem === '' ) {
				continue;
			}

			$title = WikiPage::doUnserialize( $dataItem )->getTitle();

			$changePropagationUpdateJob = $this->newChangePropagationUpdateJob(
				$title,
				[
					$strategy => true
				]
			);

			$changePropagationUpdateJob->insert();
		}
	}

	/**
	 * Returns the update strategy to use for per-entity propagation jobs in the
	 * current dispatch. When every key in the `diffKeys` parameter is in
	 * SHALLOW_SET, dependents only need a parser-cache purge; otherwise a full
	 * re-parse is required.
	 */
	private function chooseUpdateStrategy(): string {
		$diffKeys = $this->getParameter( 'diffKeys' );

		if ( !is_array( $diffKeys ) || $diffKeys === [] ) {
			return UpdateJob::FORCED_UPDATE;
		}

		foreach ( $diffKeys as $key ) {
			if ( !in_array( $key, self::SHALLOW_SET, true ) ) {
				return UpdateJob::FORCED_UPDATE;
			}
		}

		return UpdateJob::SHALLOW_UPDATE;
	}

	private function commitSpecificationChangePropagationAsJob( WikiPage $subject, $count ): void {
		$connection = $this->store->getConnection( 'mw.db' );
		$transactionTicket = $connection->getEmptyTransactionTicket( __METHOD__ );

		$changePropagationUpdateJob = $this->newChangePropagationUpdateJob(
			$subject->getTitle(),
			[
				UpdateJob::CHANGE_PROP => $subject->getSerialization(),
				UpdateJob::FORCED_UPDATE => true
			]
		);

		$changePropagationUpdateJob->run();

		// Make sure changes are committed before continuing processing
		$connection->commitAndWaitForReplication( __METHOD__, $transactionTicket );

		// Add temporary update marker
		// 24h ttl and it is expected that the JobQueue will run within this time
		// frame so that the JobQueueGroup::getSize can catch up with the update
		// marker.
		//
		// The marker will be removed after running the ChangePropagationUpdateJob
		// on the same subject.
		$this->cache->save(
			smwfCacheKey( self::CACHE_NAMESPACE, $subject->getHash() ),
			$count,
			60 * 60 * 24
		);

		$this->propertySpecificationLookup->invalidateCache( $subject );

		// Make sure the cache is reset in case runJobs.php --wait is used to avoid
		// reusing outdated type assignments
		$this->store->clear();
	}

	private function newChangePropagationUpdateJob( ?Title $title, array $parameters ): Job {
		$namespace = $this->getTitle()->getNamespace();
		$parameters += [ 'origin' => 'ChangePropagationDispatchJob' ];

		if ( $namespace === NS_CATEGORY ) {
			return $this->jobFactory->newChangePropagationClassUpdateJob( $title, $parameters );
		}

		return $this->jobFactory->newChangePropagationUpdateJob(
			$title,
			$parameters
		);
	}

	private function getLogger(): LoggerInterface {
		if ( $this->logger === null ) {
			$this->logger = LoggerFactory::getInstance( 'smw' );
		}

		return $this->logger;
	}

}
