<?php

namespace SMW\Schema;

use JsonSchema\Exception\ResourceNotFoundException;
use JsonSchema\Validator;
use JsonSerializable;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaValidator {

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly Validator $validator ) {
	}

	/**
	 * @since 3.0
	 *
	 * @param Schema|null $schema
	 *
	 * @return array
	 */
	public function validate( ?Schema $schema = null ): array {
		if ( $schema === null || !is_string( $schema->info( Schema::SCHEMA_VALIDATION_FILE ) ) ) {
			return [];
		}

		$schemaFile = $schema->info( Schema::SCHEMA_VALIDATION_FILE );

		if ( !is_readable( $schemaFile ) ) {
			return [ [ 'smw-schema-error-validation-file-inaccessible', $schemaFile ] ];
		}

		return $this->runValidation( $schema, $schemaFile );
	}

	private function runValidation( JsonSerializable $data, string $schemaFile ): array {
		$decoded = json_decode( $data->jsonSerialize() );

		try {
			$this->validator->check(
				$decoded,
				(object)[ '$ref' => 'file://' . $schemaFile ]
			);

			if ( $this->validator->isValid() ) {
				return [];
			}

			return $this->validator->getErrors();
		} catch ( ResourceNotFoundException $e ) {
			return [ $e->getMessage() ];
		}
	}

}
