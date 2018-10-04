<?php

namespace SMW\MediaWiki\Jobs;

use Hooks;
use SMW\MediaWiki\Job;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\DataTypeRegistry;
use SMW\RequestOptions;
use SMW\Enum;
use SMW\Exception\DataItemDeserializationException;
use SMWDataItem as DataItem;
use Title;

/**
 * Dispatcher to find and create individual UpdateJob instances for a specific
 * subject and its linked entities.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateDispatcherJob extends Job {

	/**
	 * Restrict dispatch process to an available pool of data
	 */
	const RESTRICTED_DISPATCH_POOL = 'restricted.disp.pool';

	/**
	 * Parameter for the secondary run to contain a list of update jobs to be
	 * inserted at once.
	 */
	const JOB_LIST = 'job-list';

	/**
	 * Size of chunks used when invoking the secondary dispatch run
	 */
	const CHUNK_SIZE = 500;

	/**
	 * @since  1.9
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 * @param integer $id job id
	 */
	public function __construct( Title $title, $params = [], $id = 0 ) {
		parent::__construct( 'smw.updateDispatcher', $title, $params, $id );
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 *
	 * @since  1.9
	 *
	 * @return boolean
	 */
	public function run() {

		$this->initServices();

		/**
		 * Retrieved a job list (most likely from a secondary dispatch run) and
		 * push each list entry into the job queue to spread the work independently
		 * from the actual dispatch process.
		 */
		if ( $this->hasParameter( self::JOB_LIST ) ) {
			return $this->push_jobs_from_list( $this->getParameter( self::JOB_LIST ) );
		}

		/**
		 * Using an entity ID to initiate some work (which if send from the DELETE
		 * will have no valid ID_TABLE reference by the time this job is run) on
		 * some secondary tables.
		 */
		if ( $this->hasParameter( '_id' ) ) {
			$this->dispatch_by_id( $this->getParameter( '_id' ) );
		}

		if ( $this->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			$this->dispatchUpdateForProperty(
				DIProperty::newFromUserLabel( $this->getTitle()->getText() )
			);

			$this->jobs[] = DIWikiPage::newFromTitle( $this->getTitle() )->getHash();
		} else {
			$this->dispatchUpdateForSubject(
				DIWikiPage::newFromTitle( $this->getTitle() )
			);
		}

		/**
		 * Create a secondary run by pushing collected jobs into a chunked queue
		 */
		if ( $this->jobs !== [] ) {
			$this->create_secondary_dispatch_run( $this->jobs );
		}

		Hooks::run( 'SMW::Job::AfterUpdateDispatcherJobComplete', [ $this ] );

		return true;
	}

	private function initServices() {

		$applicationFactory = ApplicationFactory::getInstance();
		$this->setStore( $applicationFactory->getStore() );

		$this->serializerFactory = $applicationFactory->newSerializerFactory();

		$this->isEnabledJobQueue(
			$applicationFactory->getSettings()->get( 'smwgEnableUpdateJobs' )
		);
	}

	private function dispatch_by_id( $id ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$queryDependencyLinksStoreFactory = $applicationFactory->singleton( 'QueryDependencyLinksStoreFactory' );

		$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$applicationFactory->getStore()
		);

		$count = $queryDependencyLinksStore->countDependencies(
			$id
		);

		if ( $count === 0 ) {
			return;
		}

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit(
			$count
		);

		$dependencyTargetLinks = $queryDependencyLinksStore->findDependencyTargetLinks(
			[ $id ],
			$requestOptions
		);

		foreach ( $dependencyTargetLinks as $targetLink ) {
			list( $title, $namespace, $iw, $subobjectname ) = explode( '#', $targetLink, 4 );

			// @see DIWikiPage::doUnserialize
			if ( !isset( $this->jobs[( $title . '#' . $namespace . '#' . $iw . '#' )] ) ) {
				$this->jobs[( $title . '#' . $namespace . '#' . $iw . '#' )] = true;
			}
		}
	}

	private function create_secondary_dispatch_run( $jobs ) {

		$origin = $this->getTitle()->getPrefixedText();

		foreach ( array_chunk( $jobs, self::CHUNK_SIZE, true ) as $jobList ) {
			$job = new self(
				Title::newFromText( 'UpdateDispatcher/SecondaryRun/' . md5( json_encode( $jobList ) ) ),
				[
					self::JOB_LIST => $jobList,
					'origin' => $origin,

					// We expect entities to exists that are send through the
					// dispatch to avoid creating "dead" ids on non existing (or
					// already deleted) entities
					'check_exists' => true
				]
			);

			$job->insert();
		}
	}

	private function dispatchUpdateForSubject( DIWikiPage $subject ) {

		if ( $this->getParameter( self::RESTRICTED_DISPATCH_POOL ) !== true ) {
			$this->addUpdateJobsForProperties(
				$this->store->getProperties( $subject )
			);

			$this->addUpdateJobsForProperties(
				$this->store->getInProperties( $subject )
			);
		}

		$this->addUpdateJobsFromDeserializedSemanticData();
	}

	private function dispatchUpdateForProperty( DIProperty $property ) {
		$this->addUpdateJobsForProperties( [ $property ] );
		$this->addUpdateJobsForSubjectsThatContainTypeError();
		$this->addUpdateJobsFromDeserializedSemanticData();
	}

	private function addUpdateJobsForProperties( array $properties ) {
		foreach ( $properties as $property ) {

			if ( !$property->isUserDefined() ) {
				continue;
			}

			// Before doing some work, make sure to only use page type properties
			// as a means to generate a resource (job) action
			$type = DataTypeRegistry::getInstance()->getDataItemByType(
				$property->findPropertyTypeId()
			);

			if ( $type !== DataItem::TYPE_WIKIPAGE ) {
				continue;
			}

			$requestOptions = new RequestOptions();

			// No need for a warmup since we want to keep the iterator for as
			// long as possible to only access one item at a time
			$requestOptions->setOption( Enum::SUSPEND_CACHE_WARMUP, true );

			// If we have an ID then use it to restrict the range of mactches
			// against that object reference (aka `o_id`). Of course, in case of
			// a delete action it is required that the disposer job (that removes
			// all pending references from any active table for that reference)
			// is called only after the job queue has been cleared otherwise
			// the `o_id` can no longer be a matchable ID.
			if ( $this->hasParameter( '_id' ) ) {
				$requestOptions->addExtraCondition( [ 'o_id' => $this->getParameter( '_id' ) ] );
			}

			// Best effort to find all entities to a selected property
			$subjects = $this->store->getAllPropertySubjects( $property, $requestOptions );

			$this->add_job(
				$this->apply_filter( $property, $subjects )
			);
		}
	}

	private function apply_filter( $property, $subjects ) {

		// If the an ID was provided it already restricted the list of references
		// hence avoid any further work
		if ( $this->hasParameter( '_id' ) ) {
			return $subjects;
		}

		if ( $this->getParameter( self::RESTRICTED_DISPATCH_POOL ) !== true ) {
			return $subjects;
		}

		$list = [];

		// Identify the source as base for a comparison
		$source = DIWikiPage::newFromTitle( $this->getTitle() );

		foreach ( $subjects as $subject ) {

			// #3322
			// Investigate which subjects have an actual connection to the
			// subject
			$dataItems = $this->store->getPropertyValues( $subject, $property );

			foreach ( $dataItems as $dataItem ) {
				// Make a judgment based on a literal comparison for the
				// values assigned and the now deleted entity
				if ( $dataItem instanceof DIWikiPage && $dataItem->equals( $source ) ) {
					$list[] = $subject;
				}
			}
		}

		return $list;
	}

	private function addUpdateJobsForSubjectsThatContainTypeError() {

		$subjects = $this->store->getPropertySubjects(
			new DIProperty( DIProperty::TYPE_ERROR ),
			DIWikiPage::newFromTitle( $this->getTitle() )
		);

		$this->add_job(
			$subjects
		);
	}

	private function addUpdateJobsFromDeserializedSemanticData() {

		if ( !$this->hasParameter( 'semanticData' ) ) {
			return;
		}

		$semanticData = $this->serializerFactory->newSemanticDataDeserializer()->deserialize(
			$this->getParameter( 'semanticData' )
		);

		$this->addUpdateJobsForProperties(
			$semanticData->getProperties()
		);
	}

	private function add_job( $subjects = [] ) {

		foreach ( $subjects as $subject ) {

			// Not trying to get the title here as it is waste of resources
			// as makeTitleSafe is expensive for large lists
			// $title = $subject->getTitle();

			if ( !$subject instanceof DIWikiPage ) {
				continue;
			}

			// Do not use the full subject as hash as we don't care about subobjects
			// since the root subject is enough to update all related subobjects
			// The format is the same as expected by DIWikiPage::doUnserialize
			$hash = $subject->getDBKey() . '#' . $subject->getNamespace() . '#' . $subject->getInterwiki() . '#';

			if ( !isset( $this->jobs[$hash] ) ) {
				$this->jobs[$hash] = true;
			}
		}
	}

	private function push_jobs_from_list( array $subjects ) {

		$check_exists = $this->getParameter( 'check_exists', false );

		$parameters = [
			UpdateJob::FORCED_UPDATE => true,
			'origin' => $this->getParameter( 'origin', 'UpdateDispatcherJob' )
		];

		// We expect non-duplicate subjects in the list and therefore deserialize
		// without any extra validation
		foreach ( $subjects as $key => $subject ) {

			if ( is_string( $key ) ) {
				$subject = $key;
			}

			try {
				$subject = DIWikiPage::doUnserialize( $subject );
			} catch( DataItemDeserializationException $e ) {
				continue;
			}

			if ( $check_exists && !$this->store->getObjectIds()->exists( $subject ) ) {
				continue;
			}

			if ( ( $title = $subject->getTitle() ) === null ) {
				continue;
			}

			$this->jobs[] = new UpdateJob( $title, $parameters );
		}

		$this->pushToJobQueue();

		return true;
	}

}
