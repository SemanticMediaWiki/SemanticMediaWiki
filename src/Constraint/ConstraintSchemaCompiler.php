<?php

namespace SMW\Constraint;

use SMW\DIProperty;
use SMW\Localizer\Message;
use SMW\Property\SpecificationLookup;
use SMW\Schema\SchemaFinder;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaCompiler {

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly SchemaFinder $schemaFinder,
		private readonly SpecificationLookup $propertySpecificationLookup,
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @param array $constraintSchema
	 *
	 * @return string
	 */
	public function prettify( array $constraintSchema ): string {
		if ( $constraintSchema === [] ) {
			return '';
		}

		return str_replace( [ '\\\\' ], [ '\\' ], json_encode( $constraintSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param DIProperty $property
	 *
	 * @return 'PROPERTY_CONSTRAINT_SCHEMA'[]|non-empty-array[]
	 */
	public function compileConstraintSchema( DIProperty $property ): array {
		$constraintSchema = [];

		$this->constraint_schema( $property, $constraintSchema );
		$this->allowed_values( $property, $constraintSchema );
		$this->allowed_pattern( $property, $constraintSchema );
		$this->unique_value_constraint( $property, $constraintSchema );

		if ( $constraintSchema === [] ) {
			return [];
		}

		$constraintSchema = [
			'type' => 'PROPERTY_CONSTRAINT_SCHEMA',
			'constraints' => [
				'type_constraint' => $property->findPropertyValueType()
			] + $constraintSchema['constraints']
		];

		return $constraintSchema;
	}

	private function constraint_schema( $property, &$constraintSchema ): void {
		$schemaList = $this->schemaFinder->getConstraintSchema(
			$property
		);

		if ( $schemaList !== null ) {
			$constraintSchema = $schemaList->merge( $schemaList );
		}
	}

	private function allowed_values( $property, &$constraintSchema ): void {
		$allowedValues = $this->propertySpecificationLookup->getAllowedValues(
			$property
		);

		$allowedListValues = $this->propertySpecificationLookup->getAllowedListValues(
			$property
		);

		$allowed_values = [];

		foreach ( $allowedValues as $allowedValue ) {
			$allowed_values[] = $allowedValue->getString();
		}

		foreach ( $allowedListValues as $allowedValue ) {
			$allowed_values[] = $allowedValue->getString();
		}

		if ( $allowed_values === [] ) {
			return;
		}

		if ( !isset( $constraintSchema['constraints'] ) ) {
			$constraintSchema['constraints'] = [];
		}

		if ( !isset( $constraintSchema['constraints']['allowed_values'] ) ) {
			$constraintSchema['constraints']['allowed_values'] = [];
		}

		$constraintSchema['constraints']['allowed_values'] = array_merge(
			$constraintSchema['constraints']['allowed_values'],
			$allowed_values
		);
	}

	private function allowed_pattern( $property, &$constraintSchema ): void {
		$allowed_pattern = $this->propertySpecificationLookup->getAllowedPatternBy(
			$property
		);

		if ( $allowed_pattern === '' || $allowed_pattern === null ) {
			return;
		}

		$contents = Message::get( 'smw_allows_pattern' );
		$pattern = [];

		$parts = array_map( 'trim', preg_split( "([\n][\s]?)", $contents ) );

		// Get definition from first line
		array_shift( $parts );

		foreach ( $parts as $part ) {

			if ( strpos( $part, '|' ) === false ) {
				continue;
			}

			[ $reference, $regex ] = explode( '|', $part, 2 );

			if ( $reference === $allowed_pattern ) {
				$pattern = $regex;
			}
		}

		if ( $pattern === '' ) {
			return;
		}

		$constraintSchema['constraints']['allowed_pattern'] = [ $allowed_pattern => $pattern ];
	}

	private function unique_value_constraint( $property, &$constraintSchema ): void {
		$unique_value_constraint = $this->propertySpecificationLookup->hasUniquenessConstraint(
			$property
		);

		if ( $unique_value_constraint === false || $unique_value_constraint === null ) {
			return;
		}

		$constraintSchema['constraints']['unique_value_constraint'] = $unique_value_constraint;
	}

}
