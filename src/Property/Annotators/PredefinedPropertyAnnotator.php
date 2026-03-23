<?php

namespace SMW\Property\Annotators;

use SMW\DataItems\Blob;
use SMW\DataItems\Boolean;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\WikiPage;
use SMW\PageInfo;
use SMW\Property\Annotator;
use SMW\PropertyRegistry;

/**
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotator extends PropertyAnnotatorDecorator {

	private array $predefinedPropertyList = [];

	/**
	 * @since 1.9
	 */
	public function __construct(
		Annotator $propertyAnnotator,
		private readonly PageInfo $pageInfo,
	) {
		parent::__construct( $propertyAnnotator );
	}

	/**
	 * @since 2.3
	 *
	 * @param array $predefinedPropertyList
	 */
	public function setPredefinedPropertyList( array $predefinedPropertyList ): void {
		$this->predefinedPropertyList = $predefinedPropertyList;
	}

	protected function addPropertyValues() {
		$cachedProperties = [];

		foreach ( $this->predefinedPropertyList as $propertyId ) {

			if ( $this->isRegisteredPropertyId( $propertyId, $cachedProperties ) ) {
				continue;
			}

			$propertyDI = new Property( $propertyId );

			if ( $this->getSemanticData()->getPropertyValues( $propertyDI ) !== [] ) {
				$cachedProperties[$propertyId] = true;
				continue;
			}

			$dataItem = $this->createDataItemByPropertyId( $propertyId );

			if ( $dataItem instanceof DataItem ) {
				$cachedProperties[$propertyId] = true;
				$this->getSemanticData()->addPropertyObjectValue( $propertyDI, $dataItem );
			}
		}
	}

	protected function isRegisteredPropertyId( $propertyId, $cachedProperties ): bool {
		return ( PropertyRegistry::getInstance()->getPropertyValueTypeById( $propertyId ) === '' ) ||
			array_key_exists( $propertyId, $cachedProperties );
	}

	protected function createDataItemByPropertyId( $propertyId ): Blob|WikiPage|Boolean|Time|null|false {
		$dataItem = null;

		switch ( $propertyId ) {
			case Property::TYPE_MODIFICATION_DATE:
				$dataItem = Time::newFromTimestamp( $this->pageInfo->getModificationDate() );
				break;
			case Property::TYPE_CREATION_DATE:
				$dataItem = Time::newFromTimestamp( $this->pageInfo->getCreationDate() );
				break;
			case Property::TYPE_NEW_PAGE:
				$dataItem = new Boolean( $this->pageInfo->isNewPage() );
				break;
			case Property::TYPE_LAST_EDITOR:
				$dataItem = $this->pageInfo->getLastEditor() ? WikiPage::newFromTitle( $this->pageInfo->getLastEditor() ) : null;
				break;
			case Property::TYPE_MEDIA : // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				$dataItem = $this->pageInfo->isFilePage() && $this->pageInfo->getMediaType() !== '' && $this->pageInfo->getMediaType() !== null ? new Blob( $this->pageInfo->getMediaType() ) : null;
				// @codingStandardsIgnoreEnd
				break;
			case Property::TYPE_MIME : // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				$dataItem = $this->pageInfo->isFilePage() && $this->pageInfo->getMimeType() !== '' && $this->pageInfo->getMimeType() !== null ? new Blob( $this->pageInfo->getMimeType() ) : null;
				// @codingStandardsIgnoreEnd
				break;
		}

		return $dataItem;
	}

}
