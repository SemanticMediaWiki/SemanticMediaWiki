<?php

namespace SMW;

use ParserOutput;
use SMWDataValue as DataValue;
use Title;

/**
 * Handling semantic data exchange with a ParserOutput object
 *
 * Provides access to a semantic data container that is generated
 * either from the ParserOutput or is a newly created container
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

		$this->initSemanticData();
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
		return $this->getSemanticData()->getSubject();
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
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public function canModifySemanticData() {

		// getExtensionData returns null if no value was set for this key
		if (
			$this->hasExtensionData() &&
			$this->parserOutput->getExtensionData( 'smw-blockannotation' ) !== null &&
			$this->parserOutput->getExtensionData( 'smw-blockannotation' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function getUpdateJobState() {
		return $this->updateJobs;
	}

	/**
	 * @deprecated since 2.1, use getUpdateJobState
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
	 */
	public function addError( $error ) {
		$this->errors = array_merge( $this->errors, (array)$error );
	}

	/**
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 */
	public function setSemanticData( SemanticData $semanticData ) {
		$this->semanticData = $semanticData;
	}

	/**
	 * @deprecated since 2.0, use setSemanticData
	 */
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

	/**
	 * @deprecated since 2.0, use getSemanticData
	 */
	public function getData() {
		return $this->getSemanticData();
	}

	/**
	 * @since 2.1
	 */
	public function setEmptySemanticData() {
		$this->setSemanticData( new SemanticData( DIWikiPage::newFromTitle( $this->title ) ) );
	}

	/**
	 * @deprecated since 2.1, use setEmptySemanticData
	 */
	public function clearData() {
		$this->setEmptySemanticData();
	}

	/**
	 * @deprecated since 2.1, use pushSemanticDataToParserOutput
	 */
	public function updateOutput() {
		$this->pushSemanticDataToParserOutput();
	}

	/**
	 * @since 2.1
	 *
	 * @param ParserOutput|null
	 */
	public function importFromParserOutput( ParserOutput $parserOutput = null ) {

		if ( $parserOutput === null ) {
			return;
		}

		$semanticData = $this->fetchDataFromParserOutput( $parserOutput );

		// Only import data that is known to be different
		if ( $semanticData !== null &&
			$this->getSubject()->equals( $semanticData->getSubject() ) &&
			$semanticData->getHash() !== $this->getSemanticData()->getHash() ) {

			$this->getSemanticData()->importDataFrom( $semanticData );
		}
	}

	/**
	 * @since 2.1
	 */
	public function pushSemanticDataToParserOutput() {

		$this->setSemanticDataStateToParserOutputProperty();

		if ( $this->hasExtensionData() ) {
			return $this->parserOutput->setExtensionData( 'smwdata', $this->semanticData );
		}

		$this->parserOutput->mSMWData = $this->semanticData;
	}

	/**
	 * @since 2.1
	 */
	public function setSemanticDataStateToParserOutputProperty() {

		$this->parserOutput->setTimestamp( wfTimestampNow() );

		$this->parserOutput->setProperty(
			'smw-semanticdata-status',
			$this->semanticData->getProperties() !== array()
		);
	}

	/**
	 * @see SemanticData::addDataValue
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
	 * @private This method is not for public use
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function updateStore( $deferredUpdate = false ) {

		$storeUpdater = ApplicationFactory::getInstance()->newStoreUpdater( $this->semanticData );

		$storeUpdater->setUpdateJobsEnabledState(
			$this->getUpdateJobState()
		);

		DeferredCallableUpdate::releasePendingUpdates();

		if ( $deferredUpdate ) {
			$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredCallableUpdate( function() use( $storeUpdater ) {
				wfDebugLog( 'smw', 'DeferredCallableUpdate on ParserData::updateStore' );
				$storeUpdater->doUpdate();
			} );

			$deferredCallableUpdate->pushToDeferredUpdateList();
		} else {
			$storeUpdater->doUpdate();
		}

		return true;
	}

	/**
	 * @note ParserOutput::setLimitReportData
	 *
	 * @since 2.4
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function addLimitReport( $key, $value ) {

		// FIXME 1.22+
		if ( !method_exists( $this->parserOutput, 'setLimitReportData' ) ) {
			return null;
		}

		$this->parserOutput->setLimitReportData( 'smw-limitreport-' . $key, $value );
	}

	/**
	 * FIXME Remove when MW 1.21 becomes mandatory
	 */
	protected function hasExtensionData() {
		return method_exists( $this->parserOutput, 'getExtensionData' );
	}

	/**
	 * Setup the semantic data container either from the ParserOutput or
	 * if not available create an empty container
	 */
	private function initSemanticData() {

		$this->semanticData = $this->fetchDataFromParserOutput( $this->parserOutput );

		if ( !( $this->semanticData instanceof SemanticData ) ) {
			$this->setEmptySemanticData();
		}
	}

	private function fetchDataFromParserOutput( ParserOutput $parserOutput ) {

		if ( $this->hasExtensionData() ) {
			$semanticData = $parserOutput->getExtensionData( 'smwdata' );
		} else {
			$semanticData = isset( $parserOutput->mSMWData ) ? $parserOutput->mSMWData : null;
		}

		return $semanticData;
	}

}
