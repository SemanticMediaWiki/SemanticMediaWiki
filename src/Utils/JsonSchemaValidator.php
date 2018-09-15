<?php

namespace SMW\Utils;

use JsonSchema\Exception\ResourceNotFoundException;
use JsonSchema\Validator as SchemaValidator;
use JsonSerializable;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class JsonSchemaValidator {

	/**
	 * @var SchemaValidator
	 */
	private $schemaValidator;

	/**
	 * @var boolen
	 */
	private $isValid = true;

	/**
	 * @var []
	 */
	private $errors = [];

	/**
	 * @since 3.0
	 *
	 * @param SchemaValidator|null $schemaValidator
	 */
	public function __construct( SchemaValidator $schemaValidator = null ) {
		$this->schemaValidator = $schemaValidator;
	}

	/**
	 * @since 3.0
	 *
	 * @param JsonSerializable $data
	 * @param string|null $schemaLink
	 */
	public function validate( JsonSerializable $data, $schemaLink = null ) {

		if ( $this->schemaValidator === null || $schemaLink === null ) {
			return;
		}

		// https://github.com/justinrainbow/json-schema/issues/203
		$data = json_decode( $data->jsonSerialize() );

		// https://github.com/justinrainbow/json-schema
		try {
			$this->schemaValidator->check(
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

	/**
	 * @since 3.0
	 *
	 * @param boolean
	 */
	public function hasSchemaValidator() {
		return $this->schemaValidator !== null;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean
	 */
	public function isValid() {
		return $this->isValid;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getErrors() {
		return $this->errors;
	}

}
