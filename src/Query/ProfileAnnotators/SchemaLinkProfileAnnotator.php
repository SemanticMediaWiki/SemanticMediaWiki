<?php

namespace SMW\Query\ProfileAnnotators;

use RuntimeException;
use SMW\DIProperty;
use SMW\DIWikiPage;
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
	protected function addPropertyValues() {
		if ( $this->schemaLink === '' ) {
			return;
		}

		if ( !is_string( $this->schemaLink ) ) {
			throw new RuntimeException( "Expected a string as `Schema link` value!" );
		}

		$this->addSchemaLinkAnnotation( $this->schemaLink );
	}

	private function addSchemaLinkAnnotation( $schemaLink ) {
		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_SCHEMA_LINK' ),
			new DIWikiPage( $schemaLink, SMW_NS_SCHEMA )
		);
	}

}
