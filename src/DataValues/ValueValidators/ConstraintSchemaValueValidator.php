<?php

namespace SMW\DataValues\ValueValidators;

use SMW\Constraint\ConstraintCheckRunner;
use SMW\DataItems\WikiPage;
use SMW\DataValues\DataValue;
use SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob;
use SMW\Schema\SchemaFinder;
use SMW\Schema\SchemaList;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaValueValidator implements ConstraintValueValidator {

	/**
	 * @var
	 */
	private array $schemaLists = [];

	/**
	 * @var bool
	 */
	private $hasConstraintViolation = false;

	private bool $postUpdateCheck = false;

	/**
	 * @var bool
	 */
	private $isCommandLineMode = false;

	/**
	 * @var WikiPage
	 */
	private $contextPage;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly ConstraintCheckRunner $constraintCheckRunner,
		private readonly SchemaFinder $schemaFinder,
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @param bool $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ): void {
		$this->isCommandLineMode = $isCommandLineMode;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function hasConstraintViolation(): bool {
		return $this->hasConstraintViolation;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function validate( $dataValue ): void {
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
				$schemaList = new SchemaList( [] );
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

	private function triggerDeferredCheck( $contextPage ): void {
		if ( $contextPage === null || $this->isCommandLineMode ) {
			return;
		}

		DeferredConstraintCheckUpdateJob::pushJob( $contextPage->getTitle() );
	}

}
