<?php

namespace SMW;

use SMWDataValue as DataValue;

use Title;
use ParserOutput;

/**
 * Handling semantic data exchange with a ParserOutput object
 *
 * Provides access to a semantic data container that is generated
 * either from the ParserOutput or is a newly created container
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class ParserData {

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @var $updateJobs
	 */
	private $updateJobs = true;

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 */
	public function __construct( Title $title, ParserOutput $parserOutput ) {
		$this->title = $title;
		$this->parserOutput = $parserOutput;

		$this->initData();
	}

	/**
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return DIWikiPage::newFromTitle( $this->title );
	}

	/**
	 * @since 1.9
	 *
	 * @return ParserOutput
	 */
	public function getOutput() {
		return $this->parserOutput;
	}

	/**
	 * Explicitly disable update jobs (e.g when running store update
	 * in the job queue)
	 *
	 * @since 1.9
	 */
	public function disableBackgroundUpdateJobs() {
		$this->updateJobs = false;
		return $this;
	}

	/**
	 * Returns update status
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function getUpdateStatus() {
		return $this->updateJobs;
	}

	/**
	 * Returns collected errors occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since  1.9
	 *
	 * @return array
	 */
	public function addError( array $errors ) {
		return $this->errors = array_merge( $errors, $this->errors );
	}

	/**
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 */
	public function setSemanticData( SemanticData $semanticData ) {
		$this->semanticData = $semanticData;
	}

	public function setData( SemanticData $semanticData ) {
		$this->setSemanticData( $semanticData );
	}

	/**
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->semanticData;
	}

	public function getData() {
		return $this->getSemanticData();
	}

	/**
	 * Clears all data for the given instance
	 *
	 * @since 1.9
	 */
	public function clearData() {
		$this->setData( new SemanticData( $this->getSubject() ) );
	}

	/**
	 * Update ParserOutput with processed semantic data
	 *
	 * @since 1.9
	 */
	public function updateOutput(){

		if ( $this->hasExtensionData() ) {
			$this->parserOutput->setExtensionData( 'smwdata', $this->semanticData );
		} else {
			$this->parserOutput->mSMWData = $this->semanticData;
		}
	}

	/**
	 * Adds a data value to the semantic data container
	 *
	 * @par Example:
	 * @code
	 *  $dataValue = DataValueFactory::getInstance()->newPropertyValue( $userProperty, $userValue )
	 *  $parserData->addDataValue( $dataValue )
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param SMWDataValue $dataValue
	 */
	public function addDataValue( DataValue $dataValue ) {
		$this->semanticData->addDataValue( $dataValue );
		$this->addError( $this->semanticData->getErrors() );
	}

	/**
	 * Updates the Store with data attached to the ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function updateStore() {

		$updater = new StoreUpdater( $this->semanticData );
		$updater->setUpdateJobsEnabledState( $this->getUpdateStatus() )->doUpdate();

		return true;
	}

	/**
	 * Setup the semantic data container either from the ParserOutput or
	 * if not available create an empty container
	 *
	 * @since 1.9
	 */
	protected function initData() {

		if ( $this->hasExtensionData() ) {
			$this->semanticData = $this->parserOutput->getExtensionData( 'smwdata' );
		} else {
			$this->semanticData = isset( $this->parserOutput->mSMWData ) ? $this->parserOutput->mSMWData : null;
		}

		if ( !( $this->semanticData instanceof SemanticData ) ) {
			$this->clearData();
		}
	}

	/**
	 * FIXME Remove when MW 1.21 becomes mandatory
	 */
	protected function hasExtensionData() {
		return method_exists( $this->parserOutput, 'getExtensionData' );
	}

}
