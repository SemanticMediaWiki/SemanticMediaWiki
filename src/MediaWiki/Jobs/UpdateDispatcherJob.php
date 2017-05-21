<?php

namespace SMW\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use Title;
use Hooks;

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
	 * Restict disptach process on available pool of data
	 */
	const RESTRICTED_DISPATCH_POOL = 'restricted.disp.pool';

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
	public function __construct( Title $title, $params = array(), $id = 0 ) {
		parent::__construct( 'SMW\UpdateDispatcherJob', $title, $params, $id );
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

		if ( $this->hasParameter( 'job-list' ) ) {
			return $this->createUpdateJobsFromJobList(
				$this->getParameter( 'job-list' )
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
		if ( $this->jobs !== array() ) {
			$this->createSecondaryDispatchRunWithChunkedJobList();
		}

		Hooks::run( 'SMW::Job::AfterUpdateDispatcherJobComplete', array( $this ) );

		return true;
	}

	private function createSecondaryDispatchRunWithChunkedJobList() {
		foreach ( array_chunk( $this->jobs, self::CHUNK_SIZE, true ) as $jobList ) {

			$hash = md5( json_encode( $jobList ) );

			$job = new self(
				Title::newFromText( 'UpdateDispatcherChunkedJobList::' . $hash ),
				array( 'job-list' => $jobList )
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
		$this->addUpdateJobsForProperties( array( $property ) );
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

		$semanticData = ApplicationFactory::getInstance()->newSerializerFactory()->newSemanticDataDeserializer()->deserialize(
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

	private function createUpdateJobsFromJobList( array $subjects ) {

		$parameters = array(
			UpdateJob::FORCED_UPDATE => true
		);

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
