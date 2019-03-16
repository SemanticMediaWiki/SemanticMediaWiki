<?php

namespace SMW\DataValues\ValueValidators;

use RuntimeException;
use Psr\Log\LoggerAwareTrait;
use SMWDataValue as DataValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CompoundConstraintValueValidator implements ConstraintValueValidator {

	use LoggerAwareTrait;

	/**
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

	/**
	 * @var integer
	 */
	private $time = 0;
	private $count = 0;
	private $contextPage = '';

	/**
	 * @var array
	 */
	private $constraintValueValidators = [];

	/**
	 * @since 2.4
	 *
	 * @param ConstraintValueValidator $constraintValueValidator
	 */
	public function registerConstraintValueValidator( ConstraintValueValidator $constraintValueValidator ) {
		$this->constraintValueValidators[] = $constraintValueValidator;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function hasConstraintViolation() {
		return $this->hasConstraintViolation;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function validate( $dataValue ) {

		$this->hasConstraintViolation = false;
		$time = -microtime( true );

		if ( $this->constraintValueValidators === [] ) {
			throw new RuntimeException( "Missing a registered ConstraintValueValidator" );
		}

		// Any constraint violation by a ConstraintValueValidator registered will
		// force an immediate halt without checking any other possible constraint
		foreach ( $this->constraintValueValidators as $constraintValueValidator ) {
			$constraintValueValidator->validate( $dataValue );

			if ( $constraintValueValidator->hasConstraintViolation() ) {
				$this->hasConstraintViolation = true;
			}

			if ( $this->hasConstraintViolation ) {
				break;
			}
		}

		$this->count++;
		$this->time += microtime( true ) + $time;

		if ( $dataValue instanceof DataValue && ( $contextPage = $dataValue->getContextPage() ) !== null ) {
			$this->contextPage = $contextPage->getSerialization();
		}
	}

	function __destruct() {
		$this->logger->info(
			[ 'CompoundConstraintValueValidator', 'page: {contextPage}', 'Validation count: {count}', 'procTime (total in sec.): {time}' ],
			[ 'role' => 'developer', 'count' => $this->count, 'time' => $this->time, 'contextPage' => $this->contextPage ]
		);
	}

}
