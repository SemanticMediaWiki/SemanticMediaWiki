<?php

namespace SMW\DataValues\ValueValidators;

use SMW\DataValues\DataValue;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertySpecificationConstraintValueValidator implements ConstraintValueValidator {

	private bool $hasConstraintViolation = false;

	private static array $inMemoryLabelToLanguageTracer = [];

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function hasConstraintViolation(): bool {
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

	private function doValidateCodifiedPreferredPropertyLabelConstraints( DataValue $dataValue ): void {
		// Annotated but not enabled
		if ( !$dataValue->isEnabledFeature( SMW_DV_PPLB ) ) {
			$dataValue->addErrorMsg(
				[
					'smw-datavalue-feature-not-supported',
					'SMW_DV_PPLB'
				]
			);
			return;
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

	private function isKnownByLabelAndLanguage( array $value, $dbkey ) {
		$lang = $value['_LCODE'] ?? false;

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
