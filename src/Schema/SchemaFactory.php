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
	private $types = [];

	/**
	 * @var SchemaTypes
	 */
	private $schemaTypes;

	/**
	 * @since 3.0
	 *
	 * @param array $types
	 */
	public function __construct( array $types = [] ) {
		$this->types = $types;
	}

	/**
	 * @since 3.2
	 *
	 * @return SchemaTypes
	 */
	public function getSchemaTypes() : SchemaTypes {

		if ( $this->schemaTypes === null ) {
			$this->schemaTypes = $this->newSchemaTypes( $this->types );
		}

		return $this->schemaTypes;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return []
	 */
	public function getType( $type ) {
		return $this->getSchemaTypes()->getType( $type );
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

		$schemaTypes = $this->getSchemaTypes();

		if ( !$schemaTypes->isRegisteredType( $type ) ) {
			throw new SchemaTypeNotFoundException( $type );
		}

		$schemaType = $schemaTypes->getType( $type );

		if ( isset( $schemaType['validation_schema'] ) ) {
			$info[Schema::SCHEMA_VALIDATION_FILE] = $schemaType['validation_schema'];
		}

		if ( isset( $schemaType['__factory'] ) && is_callable( $schemaType['__factory'] ) ) {
			$schema = $schemaType['__factory']( $name, $data, $info );
		} else {
			$schema = new SchemaDefinition( $name, $data, $info );
		}

		if ( !$schema instanceof Schema ) {
			throw new SchemaConstructionFailedException( $type );
		}

		return $schema;
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
			$applicationFactory->getPropertySpecificationLookup(),
			$applicationFactory->getCache()
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

	/**
	 * @since 3.2
	 *
	 * @return SchemaFilterFactory
	 */
	public function newSchemaFilterFactory() : SchemaFilterFactory {
		return new SchemaFilterFactory();
	}

	private function newSchemaTypes( array $types ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		if ( $types === [] ) {
			$types = $settings->get( 'smwgSchemaTypes' );
		}

		$schemaTypes = new SchemaTypes(
			$settings->mung( 'smwgDir', '/data/schema' )
		);

		$schemaTypes->setHookDispatcher(
			$applicationFactory->getHookDispatcher()
		);

		$schemaTypes->registerSchemaTypes( $types );

		return $schemaTypes;
	}

}
