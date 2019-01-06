<?php

namespace SMW\DataValues\ValueValidators;

use SMWDataValue as DataValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertySpecificationConstraintValueValidator implements ConstraintValueValidator {

	/**
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

	/**
	 * @var array
	 */
	private static $inMemoryLabelToLanguageTracer = [];

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function hasConstraintViolation() {
		return $this->hasConstraintViolation;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function validate( $dataValue ) {

		$this->hasConstraintViolation = false;

		if (
			!$dataValue instanceof DataValue ||
			$dataValue->getProperty() === null ||
			$dataValue->getContextPage() === null ||
			$dataValue->getContextPage()->getNamespace() !== SMW_NS_PROPERTY ) {
			return $this->hasConstraintViolation;
		}

		if ( $dataValue->getProperty()->getKey() === '_PPLB' ) {
			return $this->doValidateCodifiedPreferredPropertyLabelConstraints( $dataValue );
		}
	}

	private function doValidateCodifiedPreferredPropertyLabelConstraints( $dataValue ) {

		// Annotated but not enabled
		if ( !$dataValue->isEnabledFeature( SMW_DV_PPLB ) ) {
			return $dataValue->addErrorMsg(
				[
					'smw-datavalue-feature-not-supported',
					'SMW_DV_PPLB'
				]
			);
		}

		$value = $dataValue->toArray();
		$dbKey = $dataValue->getContextPage()->getDBKey();

		// Language has been already assigned!
		if ( ( $isKnownBy = $this->isKnownByLabelAndLanguage( $value, $dbKey ) ) !== false ) {
			$dataValue->addErrorMsg(
				[
					'smw-property-preferred-label-language-combination-exists',
					$value['_TEXT'],
					$value['_LCODE'],
					$isKnownBy
				]
			);
		}
	}

	private function isKnownByLabelAndLanguage( $value, $dbkey ) {

		$lang = isset( $value['_LCODE'] ) ? $value['_LCODE'] : false;

		if ( !isset( self::$inMemoryLabelToLanguageTracer[$dbkey] ) ) {
			self::$inMemoryLabelToLanguageTracer[$dbkey] = [];
		}

		if ( $lang && !isset( self::$inMemoryLabelToLanguageTracer[$dbkey][$lang] ) ) {
			self::$inMemoryLabelToLanguageTracer[$dbkey][$lang] = $value['_TEXT'];
		}

		if ( $lang && self::$inMemoryLabelToLanguageTracer[$dbkey][$lang] !== $value['_TEXT'] ) {
			return self::$inMemoryLabelToLanguageTracer[$dbkey][$lang];
		}

		return false;
	}

}
