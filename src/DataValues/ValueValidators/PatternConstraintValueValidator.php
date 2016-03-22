<?php

namespace SMW\DataValues\ValueValidators;

use SMW\DataValues\ValueParserFactory;
use SMW\Message;
use SMWDataValue as DataValue;
use SMW\DIProperty;
use SMW\ApplicationFactory;
use SMW\Localizer;

/**
 * To suppport regular expressions in connection with the `Allows pattern`
 * property.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PatternConstraintValueValidator  implements ConstraintValueValidator {

	/**
	 * @var AllowsPatternContentParser
	 */
	private $allowsPatternContentParser;

	/**
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

	/**
	 * @since 2.4
	 */
	public function __construct() {
		$this->allowsPatternContentParser = ValueParserFactory::getInstance()->newAllowsPatternContentParser();
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

		if (
			!$dataValue instanceof DataValue ||
			$dataValue->getProperty() === null ||
			!$dataValue->isEnabledFeature( SMW_DV_PVAP ) ) {
			return $this->hasConstraintViolation;
		}

		if ( ( $reference = $dataValue->getPropertySpecificationLookup()->getAllowedPatternFor( $dataValue->getProperty() ) ) === '' ) {
			return $this->hasConstraintViolation;
		}

		$content = $this->allowsPatternContentParser->parse(
			$reference
		);

		if ( !$content ) {
			return $this->hasConstraintViolation;
		}

		// Prevent a possible remote code execution vulnerability in connection
		// with PCRE
		$pattern = str_replace( array( '/e' ), array( '' ), trim( $content ) );

		$this->doPregMatch(
			$pattern,
			$dataValue,
			$reference
		);
	}

	private function doPregMatch( $pattern, $dataValue, $reference ) {

		// Add a mandatory backslash
		if ( $pattern !== '' && $pattern{0} !== '/' ) {
			$pattern = '/' . $pattern;
		}

		if ( substr( $pattern, -1 ) !== '/' ) {
			$pattern = $pattern . '/';
		}

		if ( !preg_match( $pattern, $dataValue->getDataItem()->getSortKey() ) ) {
			$dataValue->addErrorMsg(
				array(
					'smw-datavalue-allows-pattern-mismatch',
					$dataValue->getWikiValue(),
					$reference
				)
			);

			$this->hasConstraintViolation = true;
		}
	}

}
