<?php

namespace SMW\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
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
class UpdateDispatcherJob extends JobBase {

	/**
	 * Size of chunks used when invoking the secondary dispatch run
	 */
	const CHUNK_SIZE = 500;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @since  1.9
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 * @param integer $id job id
	 */
	public function __construct( Title $title, $params = array(), $id = 0 ) {
		parent::__construct( 'SMW\UpdateDispatcherJob', $title, $params, $id );
		$this->removeDuplicates = true;
		$this->applicationFactory = ApplicationFactory::getInstance();

		$this->setStore( $this->applicationFactory->getStore() );
	}

	/**
	 * @see Job::run
	 *
	 * @since  1.9
	 *
	 * @return boolean
	 */
	public function run() {

		if ( $this->hasParameter( 'job-list' ) ) {
			return $this->createUpdateJobsFromListBySecondaryRun(
				$this->getParameter( 'job-list' )
			);
		}

		if ( $this->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			$this->dispatchUpdateForProperty(
				DIProperty::newFromUserLabel( $this->getTitle()->getText() )
			);
		} else {
			$this->dispatchUpdateForSubject(
				DIWikiPage::newFromTitle( $this->getTitle() )
			);
		}

		// Push generated job list into a secondary dispatch run
		if ( $this->jobs !== array() ) {

			foreach ( array_chunk( $this->jobs, self::CHUNK_SIZE, true ) as $jobs ) {

				$updateDispatcherJob = new self(
					Title::newFromText( 'UpdateDispatcherJobForSecondaryRun' ),
					array( 'job-list' => $jobs )
				);

				$updateDispatcherJob->insert();
			}
		}

		return true;
	}

	/**
	 * @see Job::insert
	 *
	 * @since 1.9
	 * @codeCoverageIgnore
	 */
	public function insert() {
		if ( $this->applicationFactory->getSettings()->get( 'smwgEnableUpdateJobs' ) ) {
			parent::insert();
		}
	}

	private function dispatchUpdateForSubject( DIWikiPage $subject ) {

		$this->addUpdateJobsForProperties(
			$this->store->getProperties( $subject )
		);

		$this->addUpdateJobsForProperties(
			$this->store->getInProperties( $subject )
		);

		$this->addUpdateJobsFromDeserializedSemanticData();
	}

	private function dispatchUpdateForProperty( DIProperty $property ) {

		$this->addUpdateJobsForProperties( array( $property ) );

		// Hook deprecated with SMW 1.9 and will vanish with SMW 1.11
		wfRunHooks( 'smwUpdatePropertySubjects', array( &$this->jobs ) );

		// Hook since 1.9
		wfRunHooks( 'SMW::Job::updatePropertyJobs', array( &$this->jobs, $property ) );

		$this->addUpdateJobsForSubjectsThatContainTypeError();
		$this->addUpdateJobsFromDeserializedSemanticData();
	}

	private function addUpdateJobsForProperties( array $properties ) {
		foreach ( $properties as $property ) {

			if ( !$property->isUserDefined() ) {
				continue;
			}

			$this->addUniqueSubjectsToUpdateJobList(
				$this->store->getAllPropertySubjects( $property )
			);
		}
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

		$semanticData = $this->applicationFactory->newSerializerFactory()->newSemanticDataDeserializer()->deserialize(
			$this->getParameter( 'semanticData' )
		);

		$this->addUpdateJobsForProperties(
			$semanticData->getProperties()
		);
	}

	private function addUniqueSubjectsToUpdateJobList( array $subjects = array() ) {

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

	private function createUpdateJobsFromListBySecondaryRun( array $listOfSubjects ) {

		$subjects = array_keys( $listOfSubjects );

		// We are confident that as this point we only have valid, non-duplicate
		// subjects in the list and therefore can be deserialized without any
		// extra validation
		foreach ( $subjects as $subject ) {
			$this->jobs[] = new UpdateJob(
				DIWikiPage::doUnserialize( $subject )->getTitle()
			);
		}

		$this->pushToJobQueue();

		return true;
	}

}
