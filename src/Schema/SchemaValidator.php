<?php

namespace SMW\Schema;

use SMW\Utils\JsonSchemaValidator;

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
	public function __construct( private readonly JsonSchemaValidator $validator ) {
	}

	/**
	 * @since 3.0
	 *
	 * @param Schema|null $schema
	 *
	 * @return array
	 */
	public function validate( ?Schema $schema = null ) {
		if ( $schema === null || !is_string( $schema->info( Schema::SCHEMA_VALIDATION_FILE ) ) ) {
			return [];
		}

		if ( !is_readable( $schema->info( Schema::SCHEMA_VALIDATION_FILE ) ) ) {
			return [ [ 'smw-schema-error-validation-file-inaccessible', $schema->info( Schema::SCHEMA_VALIDATION_FILE ) ] ];
		}

		$this->validator->validate(
			$schema,
			$schema->info( Schema::SCHEMA_VALIDATION_FILE )
		);

		if ( $this->validator->isValid() ) {
			return [];
		}

		return $this->validator->getErrors();
	}

}
