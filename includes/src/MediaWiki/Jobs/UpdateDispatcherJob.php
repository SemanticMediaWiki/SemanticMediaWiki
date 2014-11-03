<?php

namespace SMW\MediaWiki\Jobs;

use SMW\SerializerFactory;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Profiler;

use Title;
use Job;

/**
 * Dispatcher class to invoke UpdateJob's
 *
 * Can be run either in deferred or immediate mode to restore the data parity
 * between a property and its attached subjects
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateDispatcherJob extends JobBase {

	/** @var Store */
	protected $store = null;

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
	}

	/**
	 * @see Job::run
	 *
	 * @since  1.9
	 *
	 * @return boolean
	 */
	public function run() {
		Profiler::In( __METHOD__, true );

		/**
		 * @var Store
		 */
		$this->store = ApplicationFactory::getInstance()->getStore();

		if ( $this->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			$this->dispatchUpdateForProperty( DIProperty::newFromUserLabel( $this->getTitle()->getText() ) )->pushToJobQueue();
		}

		$this->dispatchUpdateForSubject( DIWikiPage::newFromTitle( $this->getTitle() ) )->pushToJobQueue();

		Profiler::Out( __METHOD__, true );
		return true;
	}

	/**
	 * @see Job::insert
	 *
	 * @since 1.9
	 * @codeCoverageIgnore
	 */
	public function insert() {
		if ( ApplicationFactory::getInstance()->getSettings()->get( 'smwgEnableUpdateJobs' ) ) {
			parent::insert();
		}
	}

	/**
	 * @since 1.9.0.1
	 *
	 * @param DIWikiPage $subject
	 */
	protected function dispatchUpdateForSubject( DIWikiPage $subject ) {
		Profiler::In( __METHOD__, true );

		$this->addUpdateJobsForProperties( $this->store->getProperties( $subject ) );
		$this->addUpdateJobsForProperties( $this->store->getInProperties( $subject ) );

		$this->addUpdateJobsFromSerializedData();

		Profiler::Out( __METHOD__, true );
		return $this;
	}

	/**
	 * Generates list of involved subjects
	 *
	 * @since 1.9
	 *
	 * @param DIProperty $property
	 */
	protected function dispatchUpdateForProperty( DIProperty $property ) {
		Profiler::In( __METHOD__, true );

		$this->addUpdateJobsForProperties( array( $property ) );

		// Hook deprecated with SMW 1.9 and will vanish with SMW 1.11
		wfRunHooks( 'smwUpdatePropertySubjects', array( &$this->jobs ) );

		// Hook since 1.9
		wfRunHooks( 'SMW::Job::updatePropertyJobs', array( &$this->jobs, $property ) );

		$this->addUpdateJobsForPropertyWithTypeError();
		$this->addUpdateJobsFromSerializedData();

		Profiler::Out( __METHOD__, true );
		return $this;
	}

	protected function addUpdateJobsForProperties( array $properties ) {
		foreach ( $properties as $property ) {

			if ( $property->isUserDefined() ) {
				$this->addUniqueUpdateJobs( $this->store->getAllPropertySubjects( $property ) );
			}

		}
	}

	protected function addUpdateJobsForPropertyWithTypeError() {
		$subjects = $this->store->getPropertySubjects(
			new DIProperty( DIProperty::TYPE_ERROR ),
			DIWikiPage::newFromTitle( $this->getTitle() )
		);

		$this->addUniqueUpdateJobs( $subjects );
	}

	protected function addUpdateJobsFromSerializedData() {
		if ( $this->hasParameter( 'semanticData' ) ) {
			$this->addUpdateJobsForProperties(
				ApplicationFactory::getInstance()->newSerializerFactory()->deserialize( $this->getParameter( 'semanticData' ) )->getProperties()
			);
		}
	}

	protected function addUniqueUpdateJobs( array $subjects = array() ) {

		foreach ( $subjects as $subject ) {

			$title = $subject->getTitle();

			if ( $title instanceof Title ) {
				$this->jobs[$title->getPrefixedDBkey()] = new UpdateJob( $title );
			}
		}
	}

}
