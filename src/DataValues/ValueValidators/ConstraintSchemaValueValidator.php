<?php

namespace SMW\DataValues\ValueValidators;

use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Schema\SchemaFinder;

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
	 * @param SchemaFinder $schemaFinder
	 */
	public function __construct( SchemaFinder $schemaFinder ) {
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

		$property = $dataValue->getProperty();
		$key = $property->getSerialization();

		if ( !isset( $this->schemaLists[$key] ) ) {
			$this->schemaLists[$key] = $this->schemaFinder->getConstraintSchema( $property );
		}

		$schemaList = $this->schemaLists[$key];

		if ( $schemaList === null ) {
			return;
		}
	}

}
