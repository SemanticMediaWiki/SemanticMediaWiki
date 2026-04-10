<?php

namespace SMW\Property\Annotators;

use SMW\DataItems\Property;
use SMW\Property\Annotator;
use SMW\Schema\Schema;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @since 3.0
	 */
	public function __construct(
		Annotator $propertyAnnotator,
		private readonly ?Schema $schema = null,
	) {
		parent::__construct( $propertyAnnotator );
	}

	protected function addPropertyValues(): void {
		if ( $this->schema === null ) {
			return;
		}

		$semanticData = $this->getSemanticData();

		$semanticData->addPropertyObjectValue(
			new Property( '_SCHEMA_TYPE' ),
			$this->dataItemFactory->newDIBlob( $this->schema->get( Schema::SCHEMA_TYPE ) )
		);

		$semanticData->addPropertyObjectValue(
			new Property( '_SCHEMA_DEF' ),
			$this->dataItemFactory->newDIBlob( $this->schema )
		);

		if ( ( $desc = $this->schema->get( Schema::SCHEMA_DESCRIPTION, '' ) ) !== '' ) {
			$semanticData->addPropertyObjectValue(
				new Property( '_SCHEMA_DESC' ),
				$this->dataItemFactory->newDIBlob( $desc )
			);
		}

		foreach ( $this->schema->get( Schema::SCHEMA_TAG, [] ) as $tag ) {
			$semanticData->addPropertyObjectValue(
				new Property( '_SCHEMA_TAG' ),
				$this->dataItemFactory->newDIBlob( mb_strtolower( $tag ) )
			);
		}
	}

}
