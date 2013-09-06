<?php

namespace SMW;

use SMWDataValue;

use Title;
use ParserOutput;
use MWException;

/**
 * Handling semantic data exchange with a ParserOutput object
 *
 * @since 1.9
 *
 * @file
 *
 * @author mwjames
 * @author Markus Krötzsch
 */

/**
 * Class that provides access to the semantic data object generated from either
 * the ParserOuput or subject provided (no static binding as in SMWParseData)
 *
 * @ingroup SMW
 */
class ParserData extends Observer implements DispatchableSubject {

	/** @var Title */
	protected $title;

	/** @var ParserOutput */
	protected $parserOutput;

	/** @var SemanticData */
	protected $semanticData;

	/** @var array */
	protected $errors = array();

	/** @var $updateJobs */
	protected $updateJobs = true;

	/** @var ObservableDispatcher */
	protected $dispatcher;

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 */
	public function __construct( Title $title, ParserOutput $parserOutput ) {
		$this->title = $title;
		$this->parserOutput = $parserOutput;
		$this->setData();
	}

	/**
	 * Returns Title object
	 *
	 * @since 1.9
	 *
	 * @return Title
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
	 * @return ParserOutput
	 */
	public function getOutput() {
		return $this->parserOutput;
	}

	/**
	 * Returns DIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return DIWikiPage::newFromTitle( $this->title );
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
	 * @return \SemanticData
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
		$this->semanticData = new SemanticData( $this->getSubject() );
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
		if ( !( $this->semanticData instanceof SemanticData ) ) {
			$this->semanticData = new SemanticData( $this->getSubject() );
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

		if ( !( $this->semanticData instanceof SemanticData ) ) {
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
