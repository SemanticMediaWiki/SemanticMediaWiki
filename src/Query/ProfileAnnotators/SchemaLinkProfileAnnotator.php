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
	 * @var string
	 */
	private $schemaLink = '';

	/**
	 * @since 3.0
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 * @param string $schemaLink
	 */
	public function __construct( ProfileAnnotator $profileAnnotator, $schemaLink ) {
		parent::__construct( $profileAnnotator );
		$this->schemaLink = $schemaLink;
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
