<?php

namespace SMW\Property\Annotators;

use SMW\DIProperty;
use SMW\PropertyAnnotator;
use SMW\Schema\Schema;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var Schema
	 */
	private $schema;

	/**
	 * @since 3.0
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param Schema $schema
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, Schema $schema = null ) {
		parent::__construct( $propertyAnnotator );
		$this->schema = $schema;
	}

	protected function addPropertyValues() {

		if ( $this->schema === null ) {
			return;
		}

		$semanticData = $this->getSemanticData();

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_SCHEMA_TYPE' ),
			$this->dataItemFactory->newDIBlob( $this->schema->get( Schema::SCHEMA_TYPE ) )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_SCHEMA_DEF' ),
			$this->dataItemFactory->newDIBlob( $this->schema )
		);

		if ( ( $desc = $this->schema->get( Schema::SCHEMA_DESCRIPTION, '' ) ) !== '' ) {
			$semanticData->addPropertyObjectValue(
				new DIProperty( '_SCHEMA_DESC' ),
				$this->dataItemFactory->newDIBlob( $desc )
			);
		}

		foreach ( $this->schema->get( Schema::SCHEMA_TAG, [] ) as $tag ) {
			$semanticData->addPropertyObjectValue(
				new DIProperty( '_SCHEMA_TAG' ),
				$this->dataItemFactory->newDIBlob( mb_strtolower( $tag ) )
			);
		}
	}

}
