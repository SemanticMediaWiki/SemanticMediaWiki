<?php

namespace SMW\Query\ProfileAnnotators;

use RuntimeException;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Query\ProfileAnnotator;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaLinkProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @since 3.0
	 */
	public function __construct(
		ProfileAnnotator $profileAnnotator,
		private $schemaLink,
	) {
		parent::__construct( $profileAnnotator );
	}

	/**
	 * ProfileAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues(): void {
		if ( $this->schemaLink === '' ) {
			return;
		}

		if ( !is_string( $this->schemaLink ) ) {
			throw new RuntimeException( "Expected a string as `Schema link` value!" );
		}

		$this->addSchemaLinkAnnotation( $this->schemaLink );
	}

	private function addSchemaLinkAnnotation( string $schemaLink ): void {
		$this->getSemanticData()->addPropertyObjectValue(
			new Property( '_SCHEMA_LINK' ),
			new WikiPage( $schemaLink, SMW_NS_SCHEMA )
		);
	}

}
