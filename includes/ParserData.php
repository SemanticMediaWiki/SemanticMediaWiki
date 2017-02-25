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
	 * Identifies the extension data
	 */
	const DATA_ID = 'smwdata';

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
	 * @var $isEnabledWithUpdateJob
	 */
	private $isEnabledWithUpdateJob = true;

	/**
	 * Identifies the origin of a request.
	 *
	 * @var string
	 */
	private $origin = '';

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
	 * @since 2.5
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
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
		$this->isEnabledWithUpdateJob = false;
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
	public function isEnabledWithUpdateJob() {
		return $this->isEnabledWithUpdateJob;
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

		// Ensure that errors are reported and recorded
		$processingErrorMsgHandler = new ProcessingErrorMsgHandler(
			$this->getSubject()
		);

		foreach ( $this->errors as $error ) {
			$processingErrorMsgHandler->addToSemanticData(
				$this->semanticData,
				$processingErrorMsgHandler->newErrorContainerFromMsg( $error )
			);
		}

		$this->setSemanticDataStateToParserOutputProperty();

		if ( $this->hasExtensionData() ) {
			return $this->parserOutput->setExtensionData( self::DATA_ID, $this->semanticData );
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
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isAnnotatedWithSemanticData() {
		return (bool)$this->parserOutput->getProperty( 'smw-semanticdata-status' );
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
	}

	/**
	 * @private This method is not for public use
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function updateStore( $enabledDeferredUpdate = false ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$storeUpdater = $applicationFactory->newStoreUpdater( $this->semanticData );

		$storeUpdater->isEnabledWithUpdateJob(
			$this->isEnabledWithUpdateJob
		);

		DeferredCallableUpdate::releasePendingUpdates();

		$deferredCallableUpdate = $applicationFactory->newDeferredCallableUpdate( function() use( $storeUpdater ) {
			$storeUpdater->doUpdate();
		} );


		$deferredCallableUpdate->setOrigin(
			__METHOD__ . ( $this->origin !== '' ? ' from ' . $this->origin : '' ) . ': ' . $this->semanticData->getSubject()->getHash()
		);

		$deferredCallableUpdate->enabledDeferredUpdate(
			$enabledDeferredUpdate
		);

		$deferredCallableUpdate->pushUpdate();

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
			$semanticData = $parserOutput->getExtensionData( self::DATA_ID );
		} else {
			$semanticData = isset( $parserOutput->mSMWData ) ? $parserOutput->mSMWData : null;
		}

		return $semanticData;
	}

}
