<?php

namespace SMW;

use Title;
use WikiPage;
use ParserOutput;
use MWException;
use Job;

use SMWStore;
use SMWDataValue;
use SMWDIWikiPage;
use SMWSemanticData;
use SMWDIProperty;
use SMWDIBlob;
use SMWDIBoolean;
use SMWDITime;

/**
 * Interface handling semantic data storage to a ParserOutput instance
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author mwjames
 */
interface IParserData {

	/**
	 * The constructor requires a Title and ParserOutput object
	 */

	/**
	 * Returns Title object
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitle();

	/**
	 * Returns ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return ParserOutput
	 */
	public function getOutput();

	/**
	 * Update ParserOoutput with processed semantic data
	 *
	 * @since 1.9
	 */
	public function updateOutput();

	/**
	 * Get semantic data
	 *
	 * @since 1.9
	 *
	 * @return SMWSemanticData
	 */
	public function getData();

	/**
	 * Clears all data for the given instance
	 *
	 * @since 1.9
	 */
	public function clearData();

	/**
	 * Updates the store with semantic data fetched from a ParserOutput object
	 *
	 * @since 1.9
	 */
	public function updateStore();

	/**
	 * Returns errors that occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getErrors();

}

/**
 * Class that provides access to the semantic data object generated from either
 * the ParserOuput or subject provided (no static binding as in SMWParseData)
 *
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ParserData extends Observer implements IParserData, DispatchableSubject {

	/**
	 * Represents Title object
	 * @var Title
	 */
	protected $title;

	/**
	 * Represents ParserOutput object
	 * @var ParserOutput
	 */
	protected $parserOutput;

	/**
	 * Represents SMWSemanticData object
	 * @var SMWSemanticData
	 */
	protected $semanticData;

	/**
	 * Represents collected errors
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Represents invoked GLOBALS
	 * @var array
	 */
	protected $options;

	/**
	 * Represents invoked $smwgEnableUpdateJobs
	 * @var $updateJobs
	 */
	protected $updateJobs = true;

	/** @var ObservableDispatcher */
	protected $dispatcher;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param \Title $title
	 * @param \ParserOutput $parserOutput
	 * @param array $options
	 */
	public function __construct( Title $title, ParserOutput $parserOutput, array $options = array() ) {
		$this->title = $title;
		$this->parserOutput = $parserOutput;
		$this->options = $options;
		$this->setData();
	}

	/**
	 * Returns Title object
	 *
	 * @since 1.9
	 *
	 * @return \Title
	 */
	public function getTitle() {
		return $this->title;
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
	 * Invokes an ObservableDispatcher object to deploy state changes to an Observer
	 *
	 * @since 1.9
	 *
	 * @param ObservableDispatcher $dispatcher
	 */
	public function setObservableDispatcher( ObservableDispatcher $dispatcher ) {
		$this->dispatcher = $dispatcher->setSubject( $this );
		return $this;
	}

	/**
	 * Returns ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return \ParserOutput
	 */
	public function getOutput() {
		return $this->parserOutput;
	}

	/**
	 * Returns SMWDIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @return \SMWDIWikiPage
	 */
	public function getSubject() {
		return SMWDIWikiPage::newFromTitle( $this->title );
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
	 * Collect and set error array
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function addError( array $errors ) {
		return $this->errors = array_merge ( $errors, $this->errors );
	}

	/**
	 * Explicitly disable update jobs (e.g when running store update
	 * in the job queue)
	 *
	 * @since 1.9
	 */
	public function disableUpdateJobs() {
		$this->updateJobs = false;
		return $this;
	}

	/**
	 * Returns instantiated semanticData container
	 *
	 * @since 1.9
	 *
	 * @return \SMWSemanticData
	 */
	public function getData() {
		return $this->semanticData;
	}

	/**
	 * Clears all data for the given instance
	 *
	 * @since 1.9
	 */
	public function clearData() {
		$this->semanticData = new SMWSemanticData( $this->getSubject() );
	}

	/**
	 * Initializes the semantic data container either from the ParserOutput or
	 * if not available a new container is being created
	 *
	 * @note MW 1.21+ use getExtensionData()
	 *
	 * @since 1.9
	 */
	protected function setData() {
		if ( method_exists( $this->parserOutput, 'getExtensionData' ) ) {
			$this->semanticData = $this->parserOutput->getExtensionData( 'smwdata' );
		} elseif ( isset( $this->parserOutput->mSMWData ) ) {
			$this->semanticData = $this->parserOutput->mSMWData;
		}

		// Setup data container
		if ( !( $this->semanticData instanceof SMWSemanticData ) ) {
			$this->semanticData = new SMWSemanticData( $this->getSubject() );
		}
	}

	/**
	 * Update ParserOutput with processed semantic data
	 *
	 * @note MW 1.21+ use setExtensionData()
	 *
	 * @since 1.9
	 *
	 * @throws MWException
	 */
	public function updateOutput(){

		if ( !( $this->semanticData instanceof SMWSemanticData ) ) {
			throw new MWException( 'The semantic data container is not available' );
		}

		if ( method_exists( $this->parserOutput, 'setExtensionData' ) ) {
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
	 *  $dataValue = DataValueFactory::newPropertyValue( $userProperty, $userValue )
	 *  $parserData->addDataValue( $dataValue )
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param SMWDataValue $dataValue
	 */
	public function addDataValue( SMWDataValue $dataValue ) {

		// FIXME Remove the addDataValue method from
		// the ParserData object
		$this->semanticData->addDataValue( $dataValue );
		$this->addError( $this->semanticData->getErrors() );
	}

	/**
	 * Updates the store with semantic data attached to a ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function updateStore() {
		$this->dispatcher->setState( 'runStoreUpdater' );
		return true;
	}

}
