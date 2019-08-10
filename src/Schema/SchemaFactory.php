<?php

namespace SMW\Schema;

use RuntimeException;
use SMW\ApplicationFactory;
use SMW\Schema\Exception\SchemaTypeNotFoundException;
use SMW\Schema\Exception\SchemaConstructionFailedException;
use SMW\Schema\Exception\SchemaParameterTypeMismatchException;
use SMW\Store;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\DIWikiPage;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaFactory {

	/**
	 * @var []
	 */
	private $schemaTypes = [];

	/**
	 * @since 3.0
	 *
	 * @param array $schemaTypes
	 */
	public function __construct( array $schemaTypes = [] ) {
		$this->schemaTypes = $schemaTypes;

		if ( $this->schemaTypes === [] ) {
			$this->schemaTypes = $GLOBALS['smwgSchemaTypes'];
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return []
	 */
	public function getType( $type ) {
		return isset( $this->schemaTypes[$type] ) ? $this->schemaTypes[$type] : [];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function isRegisteredType( $type ) {
		return isset( $this->schemaTypes[$type] );
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getRegisteredTypes() {
		return array_keys( $this->schemaTypes );
	}

	/**
	 * @since 3.0
	 *
	 * @param string|array $group
	 *
	 * @return []
	 */
	public function getRegisteredTypesByGroup( $group ) {

		$registeredTypes = [];
		$groups = (array)$group;

		foreach ( $this->schemaTypes as $type => $val ) {
			if ( isset( $val['group'] ) && in_array( $val['group'], $groups ) ) {
				$registeredTypes[] = $type;
			}
		}

		return $registeredTypes;
	}

	/**
	 * @since 3.1
	 *
	 * @param Schema|null $schema
	 */
	public function pushChangePropagationDispatchJob( Schema $schema = null ) {

		if ( $schema === null ) {
			return;
		}

		$type = $this->getType( $schema->get( 'type' ) );

		if ( !isset( $type['change_propagation'] ) || $type['change_propagation'] === false ) {
			return;
		}

		if ( !is_array( $type['change_propagation'] ) ) {
			$type['change_propagation'] = (array)$type['change_propagation'];
		}

		$subject = DIWikiPage::newFromText( $schema->getName(), SMW_NS_SCHEMA );

		foreach ( $type['change_propagation'] as $property ) {
			$params = [
				'schema_change_propagation' => true,
				'property_key' => $property,
				'origin' => 'SchemaFactory'
			];

			ChangePropagationDispatchJob::planAsJob( $subject, $params );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 * @param array|string $data
	 *
	 * @return Schema
	 * @throws RuntimeException
	 */
	public function newSchema( $name, $data ) {

		if ( is_string( $data ) ) {
			if ( ( $data = json_decode( $data, true ) ) === null || json_last_error() !== JSON_ERROR_NONE ) {
				throw new RuntimeException( "Invalid JSON format." );
			}
		}

		$type = null;
		$info = [];

		if ( isset( $data['type'] ) ) {
			$type = $data['type'];
		}

		if ( !isset( $this->schemaTypes[$type] ) ) {
			throw new SchemaTypeNotFoundException( $type );
		}

		if ( isset( $this->schemaTypes[$type]['validation_schema'] ) ) {
			$info[Schema::SCHEMA_VALIDATION_FILE] = $this->schemaTypes[$type]['validation_schema'];
		}

		if ( isset( $this->schemaTypes[$type]['__factory'] ) && is_callable( $this->schemaTypes[$type]['__factory'] ) ) {
			$schema = $this->schemaTypes[$type]['__factory']( $name, $data, $info );
		} else {
			$schema = new SchemaDefinition( $name, $data, $info );
		}

		if ( !$schema instanceof Schema ) {
			throw new SchemaConstructionFailedException( $type );
		}

		return $schema;
	}

	public static function newTest( $name, $data ) {
		return '';
	}

	/**
	 * @since 3.1
	 *
	 * @param Store|null $store
	 *
	 * @return SchemaFinder
	 */
	public function newSchemaFinder( Store $store = null ) {

		$applicationFactory = ApplicationFactory::getInstance();

		if ( $store === null ) {
			$store = $applicationFactory->getStore();
		}

		return new SchemaFinder(
			$store,
			$applicationFactory->getPropertySpecificationLookup()
		);
	}

	/**
	 * @since 3.0
	 *
	 * @return SchemaValidator
	 */
	public function newSchemaValidator() {
		return new SchemaValidator(
			ApplicationFactory::getInstance()->create( 'JsonSchemaValidator' )
		);
	}

}
