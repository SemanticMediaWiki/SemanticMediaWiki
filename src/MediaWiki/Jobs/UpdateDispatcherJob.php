<?php

namespace SMW\MediaWiki\Jobs;

use Hooks;
use SMW\MediaWiki\Job;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\DataTypeRegistry;
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
	 * Restict disptach process on available pool of data
	 */
	const RESTRICTED_DISPATCH_POOL = 'restricted.disp.pool';

	/**
	 * Restict disptach process on available pool of data
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

		$this->setStore(
			ApplicationFactory::getInstance()->getStore()
		);

		$this->isEnabledJobQueue(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgEnableUpdateJobs' )
		);
	}

	/**
	 * @see Job::run
	 *
	 * @since  1.9
	 *
	 * @return boolean
	 */
	public function run() {

		if ( $this->hasParameter( self::JOB_LIST ) ) {
			return $this->createUpdateJobsFromJobList(
				$this->getParameter( self::JOB_LIST )
			);
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

		// Push generated job list into a secondary dispatch run
		if ( $this->jobs !== [] ) {
			$this->createSecondaryDispatchRunWithChunkedJobList();
		}

		Hooks::run( 'SMW::Job::AfterUpdateDispatcherJobComplete', [ $this ] );

		return true;
	}

	private function createSecondaryDispatchRunWithChunkedJobList() {
		foreach ( array_chunk( $this->jobs, self::CHUNK_SIZE, true ) as $jobList ) {

			$hash = md5( json_encode( $jobList ) );

			$job = new self(
				Title::newFromText( 'UpdateDispatcherChunkedJobList::' . $hash ),
				[ self::JOB_LIST => $jobList ]
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

			if ( $type !== \SMWDataItem::TYPE_WIKIPAGE ) {
				continue;
			}

			// Best effort to find all entities to a selected property
			$subjects = $this->store->getAllPropertySubjects( $property );

			$this->addUniqueSubjectsToUpdateJobList(
				$this->apply_filter( $property, $subjects )
			);
		}
	}

	private function apply_filter( $property, $subjects ) {

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

		$this->addUniqueSubjectsToUpdateJobList(
			$subjects
		);
	}

	private function addUpdateJobsFromDeserializedSemanticData() {

		if ( !$this->hasParameter( 'semanticData' ) ) {
			return;
		}

		$semanticData = ApplicationFactory::getInstance()->newSerializerFactory()->newSemanticDataDeserializer()->deserialize(
			$this->getParameter( 'semanticData' )
		);

		$this->addUpdateJobsForProperties(
			$semanticData->getProperties()
		);
	}

	private function addUniqueSubjectsToUpdateJobList( $subjects = [] ) {

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

	private function createUpdateJobsFromJobList( array $subjects ) {

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
				$title = DIWikiPage::doUnserialize( $subject )->getTitle();
			} catch( \SMW\Exception\DataItemDeserializationException $e ) {
				continue;
			}

			if ( $title === null ) {
				continue;
			}

			$this->jobs[] = new UpdateJob( $title, $parameters );
		}

		$this->pushToJobQueue();

		return true;
	}

}
