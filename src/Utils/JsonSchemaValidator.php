<?php

namespace SMW\Utils;

use JsonSchema\Exception\ResourceNotFoundException;
use JsonSchema\Validator as SchemaValidator;
use JsonSerializable;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class JsonSchemaValidator {

	private ?SchemaValidator $schemaValidator;
	private bool $isValid = true;
	private array $errors = [];

	public function __construct( ?SchemaValidator $schemaValidator = null ) {
		$this->schemaValidator = $schemaValidator;
	}

	public function validate( JsonSerializable $data, ?string $schemaLink = null ): void {
		// Raise an error because we expect the validator to be available
		// when at the same time a schema link is present
		if ( $this->schemaValidator === null && $schemaLink !== null ) {
			$this->isValid = false;
			$this->errors[] = [
				'smw-schema-error-validation-json-validator-inaccessible',
				'justinrainbow/json-schema',
				pathinfo( $schemaLink, PATHINFO_BASENAME )
			];
		} elseif ( $this->schemaValidator !== null && $schemaLink !== null ) {
			$this->runValidation( $data, $schemaLink );
		}
	}

	private function runValidation( $data, $schemaLink ) {
		// https://github.com/justinrainbow/json-schema/issues/203
		$data = json_decode( $data->jsonSerialize() );

		// https://github.com/justinrainbow/json-schema
		try {
			$this->schemaValidator->validate(
				$data,
				(object)[ '$ref' => 'file://' . $schemaLink ]
			);

			$this->isValid = $this->schemaValidator->isValid();
			$this->errors = $this->schemaValidator->getErrors();
		} catch ( ResourceNotFoundException $e ) {
			$this->isValid = false;
			$this->errors[] = $e->getMessage();
		}
	}

	public function hasSchemaValidator(): bool {
		return $this->schemaValidator !== null;
	}

	public function isValid(): bool {
		return $this->isValid;
	}

	public function getErrors(): array {
		return $this->errors;
	}

}
