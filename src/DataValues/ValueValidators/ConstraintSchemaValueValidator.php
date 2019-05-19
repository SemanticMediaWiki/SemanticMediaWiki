<?php

namespace SMW\DataValues\ValueValidators;

use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Schema\SchemaFinder;
use SMW\Constraint\ConstraintCheckRunner;
use SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaValueValidator implements ConstraintValueValidator {

	/**
	 * @var ConstraintCheckRunner
	 */
	private $constraintCheckRunner;

	/**
	 * @var SchemaFinder
	 */
	private $schemaFinder;

	/**
	 * @var []
	 */
	private $schemaLists = [];

	/**
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

	/**
	 * @var boolean
	 */
	private $postUpdateCheck = false;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @var DIWikiPage
	 */
	private $contextPage;

	/**
	 * @since 3.1
	 *
	 * @param ConstraintCheckRunner $schemaFinder
	 * @param SchemaFinder $schemaFinder
	 */
	public function __construct( ConstraintCheckRunner $constraintCheckRunner, SchemaFinder $schemaFinder ) {
		$this->constraintCheckRunner = $constraintCheckRunner;
		$this->schemaFinder = $schemaFinder;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = $isCommandLineMode;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function hasConstraintViolation() {
		return $this->hasConstraintViolation;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function validate( $dataValue ) {

		$this->hasConstraintViolation = false;

		if ( !$dataValue instanceof DataValue || $dataValue->getProperty() === null ) {
			return;
		}

		$schemaList = null;
		$dataItems = [];

		$property = $dataValue->getProperty();

		if ( $property->getKey() === '_INST' ) {
			$dataItem = $dataValue->getDataItem();
		} else {
			$dataItem = $property;
		}

		$key = $dataItem->getSerialization();

		if ( !isset( $this->schemaLists[$key] ) ) {
			$schemaList = $this->schemaFinder->getConstraintSchema( $dataItem );

			if ( !$schemaList instanceof SchemaList && $dataItems !== [] ) {
				$schemaList = new SchemaList();
			}

			foreach ( $dataItems as $di ) {
				$schemaList->add( $this->schemaFinder->getConstraintSchema( $di ) );
			}

			$this->schemaLists[$key] = $schemaList;
		}

		$schemaList = $this->schemaLists[$key];

		if ( $schemaList === null ) {
			return;
		}

		$this->constraintCheckRunner->load(
			$key,
			$schemaList
		);

		$this->constraintCheckRunner->check( $dataValue );
		$this->hasConstraintViolation = $this->constraintCheckRunner->hasViolation();

		if ( $this->constraintCheckRunner->hasDeferrableConstraint() ) {
			$this->triggerDeferredCheck( $dataValue->getContextPage() );
		}
	}

	private function triggerDeferredCheck( $contextPage ) {

		if ( $contextPage === null || $this->isCommandLineMode ) {
			return;
		}

		DeferredConstraintCheckUpdateJob::pushJob( $contextPage->getTitle() );
	}

}
