<?php

namespace SMW\Schema;

use SMW\Utils\JsonSchemaValidator;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaValidator {

	/**
	 * @var JsonSchemaValidator
	 */
	private $validator;

	/**
	 * @since 3.0
	 *
	 * @param JsonSchemaValidator $validator
	 */
	public function __construct( JsonSchemaValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @since 3.0
	 *
	 * @param Schema|null $schema
	 *
	 * @return []
	 */
	public function validate( Schema $schema = null ) {

		if ( $schema === null || !is_string( $schema->info( Schema::SCHEMA_VALIDATION_FILE ) ) ) {
			return [];
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
