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

		foreach ( $this->constraintValueValidators as $constraintValueValidator ) {

			// Any constraint violation by a ConstraintValueValidator registered
			// will force an immediate halt without checking any other possible
			// constraints/validators
			if ( $this->hasConstraintViolation ) {
				break;
			}

			$constraintValueValidator->validate( $dataValue );
			$this->hasConstraintViolation = $constraintValueValidator->hasConstraintViolation();
		}

		$this->count++;
		$this->time += microtime( true ) + $time;

		if ( $dataValue instanceof DataValue && ( $contextPage = $dataValue->getContextPage() ) !== null ) {
			$this->contextPage = $contextPage->asBase()->getSerialization();
		}
	}

	function __destruct() {
		$this->logger->info(
			[ 'CompoundConstraintValueValidator', 'Page: {contextPage}', 'Validation count: {count}', 'procTime (total in sec.): {procTime}' ],
			[ 'role' => 'developer', 'count' => $this->count, 'procTime' => $this->time, 'contextPage' => $this->contextPage ]
		);
	}

}
