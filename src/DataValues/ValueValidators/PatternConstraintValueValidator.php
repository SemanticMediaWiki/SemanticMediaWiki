<?php

namespace SMW\DataValues\ValueValidators;

use SMW\ApplicationFactory;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMWDataValue as DataValue;

/**
 * To support regular expressions in connection with the `Allows pattern`
 * property.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PatternConstraintValueValidator implements ConstraintValueValidator {

	/**
	 * @var AllowsPatternContentParser
	 */
	private $allowsPatternValueParser;

	/**
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

	/**
	 * @since 2.4
	 *
	 * @param AllowsPatternValueParser $allowsPatternValueParser
	 */
	public function __construct( AllowsPatternValueParser $allowsPatternValueParser ) {
		$this->allowsPatternValueParser = $allowsPatternValueParser;
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

		if ( ( $reference = ApplicationFactory::getInstance()->getPropertySpecificationLookup()->getAllowedPatternBy( $dataValue->getProperty() ) ) === '' ) {
			return $this->hasConstraintViolation;
		}

		$content = $this->allowsPatternValueParser->parse(
			$reference
		);

		if ( !$content ) {
			return $this->hasConstraintViolation;
		}

		// Prevent a possible remote code execution vulnerability in connection
		// with PCRE
		$pattern = str_replace( [ '/e' ], [ '' ], trim( $content ) );

		$this->doPregMatch(
			$pattern,
			$dataValue,
			$reference
		);
	}

	private function doPregMatch( $pattern, $dataValue, $reference ) {

		// Convert escaping as in /\d{4}
		$pattern = str_replace( "/\\", "\\", $pattern );

		// Add a mandatory backslash
		if ( $pattern !== '' && $pattern[0] !== '/' ) {
			$pattern = '/' . $pattern;
		}

		if ( substr( $pattern, -1 ) !== '/' ) {
			$pattern = $pattern . '/';
		}

		// @to suppress any errors caused by an invalid regex, the user should
		// test the expression before making it available
		if ( !@preg_match( $pattern, $dataValue->getDataItem()->getSortKey() ) ) {
			$dataValue->addErrorMsg(
				[
					'smw-datavalue-allows-pattern-mismatch',
					$dataValue->getWikiValue(),
					$reference
				]
			);

			$this->hasConstraintViolation = true;
		}
	}

}
