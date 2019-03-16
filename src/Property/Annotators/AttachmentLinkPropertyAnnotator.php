<?php

namespace SMW\Property\Annotators;

use SMW\PropertyAnnotator;
use SMW\DIWikiPage;
use SMW\DIProperty;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class AttachmentLinkPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var []
	 */
	private $attachments;

	/**
	 * @var array
	 */
	private $predefinedPropertyList = [];

	/**
	 * @since 3.1
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param array|null $attachments
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, $attachments ) {
		parent::__construct( $propertyAnnotator );
		$this->attachments = $attachments;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $predefinedPropertyList
	 */
	public function setPredefinedPropertyList( array $predefinedPropertyList ) {
		$this->predefinedPropertyList = array_flip( $predefinedPropertyList );
	}

	protected function addPropertyValues() {

		if ( !is_array( $this->attachments ) || !isset( $this->predefinedPropertyList['_ATTCH_LINK'] ) ) {
			return;
		}

		$semanticData = $this->getSemanticData();
		$property = $this->dataItemFactory->newDIProperty( '_ATTCH_LINK' );

		foreach ( $this->attachments as $attachment => $v ) {
			$semanticData->addPropertyObjectValue(
				$property,
				$this->dataItemFactory->newDIWikiPage( $attachment, NS_FILE )
			);
		}

	}
}
