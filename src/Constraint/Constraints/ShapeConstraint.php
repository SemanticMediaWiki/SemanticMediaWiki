<?php

namespace SMW\Constraint\Constraints;

use RuntimeException;
use SMW\Constraint\Constraint;
use SMW\Constraint\ConstraintError;
use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;
use SMW\DataTypeRegistry;
use SMW\DataValues\DataValue;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ShapeConstraint implements Constraint {

	/**
	 * Defines the expected key in the JSON
	 */
	const CONSTRAINT_KEY = 'shape_constraint';

	/**
	 * @var bool
	 */
	private $hasViolation = false;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function hasViolation() {
		return $this->hasViolation;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return Constraint::TYPE_INSTANT;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function checkConstraint( array $constraints, $dataValue ): void {
		$this->hasViolation = false;

		if ( !$dataValue instanceof DataValue ) {
			throw new RuntimeException( "Expected a DataValue instance!" );
		}

		if ( !isset( $constraints[self::CONSTRAINT_KEY] ) ) {
			return;
		}

		// PHP 7.0+ $this->semanticData = $dataValue->getCallable( SemanticData::class )();
		$semanticData = $dataValue->getCallable( SemanticData::class );
		$this->semanticData = $semanticData();

		foreach ( $constraints[self::CONSTRAINT_KEY] as $constraint ) {
			$this->check( $constraint, $dataValue );
		}
	}

	private function check( $constraint, $dataValue ): void {
		$errors = [];

		if ( !isset( $constraint['property'] ) ) {
			return;
		}

		$property = Property::newFromUserLabel(
			$constraint['property']
		);

		if ( !$this->semanticData->hasProperty( $property ) ) {
			$errors[] = [
				'smw-constraint-violation-class-shape-constraint-missing-property',
				$dataValue->getWikiValue(),
				$property->getLabel()
			];
		}

		if ( isset( $constraint['property_type'] ) && !$this->isType( $constraint['property_type'], $property ) ) {
			$errors[] = [
				'smw-constraint-violation-class-shape-constraint-wrong-type',
				$dataValue->getWikiValue(),
				$property->getLabel(),
				$constraint['property_type']
			];
		}

		if ( isset( $constraint['max_cardinality'] ) && !$this->hasMaxCardinality( $constraint['max_cardinality'], $property ) ) {
			$errors[] = [
				'smw-constraint-violation-class-shape-constraint-invalid-max-cardinality',
				$dataValue->getWikiValue(),
				$property->getLabel(),
				$constraint['max_cardinality']
			];
		}

		if ( isset( $constraint['min_textlength'] ) && !$this->hasMinLength( $constraint['min_textlength'], $property ) ) {
			$errors[] = [
				'smw-constraint-violation-class-shape-constraint-invalid-min-length',
				$dataValue->getWikiValue(),
				$property->getLabel(),
				$constraint['min_textlength']
			];
		}

		$this->reportError( $dataValue, $errors );
	}

	private function isType( $type, $property ): bool {
		$diType = DataTypeRegistry::getInstance()->getDataItemByType(
			$property->findPropertyTypeId()
		);

		switch ( $type ) {
			case 'Text':
				$type = DataItem::TYPE_BLOB;
				break;
			case 'Date':
				$type = DataItem::TYPE_TIME;
				break;
			case 'Boolean':
				$type = DataItem::TYPE_BOOLEAN;
				break;
			case 'Geo':
				$type = DataItem::TYPE_GEO;
				break;
			case 'Page':
				$type = DataItem::TYPE_WIKIPAGE;
				break;
			case 'Number':
				$type = DataItem::TYPE_NUMBER;
				break;
			case 'URI':
				$type = DataItem::TYPE_URI;
				break;
			default:
				$type = DataItem::TYPE_NONE;
				break;
		}

		return $diType === $type;
	}

	private function hasMinLength( $minLength, $property ): bool {
		$dataItems = $this->semanticData->getPropertyValues(
			$property
		);

		if ( $dataItems === [] ) {
			return false;
		}

		foreach ( $dataItems as $dataItem ) {

			if ( !$dataItem instanceof Blob ) {
				continue;
			}

			if ( mb_strlen( $dataItem->getString() ) < $minLength ) {
				return false;
			}
		}

		return true;
	}

	private function hasMaxCardinality( $maxCardinality, $property ): bool {
		$dataItems = $this->semanticData->getPropertyValues(
			$property
		);

		if ( count( $dataItems ) > $maxCardinality ) {
			return false;
		}

		return true;
	}

	private function reportError( $dataValue, array $errors ): void {
		if ( $errors === [] ) {
			return;
		}

		$this->hasViolation = true;

		foreach ( $errors as $error ) {
			$dataValue->addError( new ConstraintError( $error ) );
		}
	}

}
