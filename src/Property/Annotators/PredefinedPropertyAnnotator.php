<?php

namespace SMW\Property\Annotators;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\PageInfo;
use SMW\PropertyAnnotator;
use SMW\PropertyRegistry;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDITime as DITime;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var PageInfo
	 */
	private $pageInfo;

	/**
	 * @var array
	 */
	private $predefinedPropertyList = [];

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param PageInfo $pageInfo
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, PageInfo $pageInfo ) {
		parent::__construct( $propertyAnnotator );
		$this->pageInfo = $pageInfo;
	}

	/**
	 * @since 2.3
	 *
	 * @param array $predefinedPropertyList
	 */
	public function setPredefinedPropertyList( array $predefinedPropertyList ) {
		$this->predefinedPropertyList = $predefinedPropertyList;
	}

	protected function addPropertyValues() {

		$cachedProperties = [];

		foreach ( $this->predefinedPropertyList as $propertyId ) {

			if ( $this->isRegisteredPropertyId( $propertyId, $cachedProperties ) ) {
				continue;
			}

			$propertyDI = new DIProperty( $propertyId );

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

	protected function isRegisteredPropertyId( $propertyId, $cachedProperties ) {
		return ( PropertyRegistry::getInstance()->getPropertyValueTypeById( $propertyId ) === '' ) ||
			array_key_exists( $propertyId, $cachedProperties );
	}

	protected function createDataItemByPropertyId( $propertyId ) {

		$dataItem = null;

		switch ( $propertyId ) {
			case DIProperty::TYPE_MODIFICATION_DATE :
				$dataItem = DITime::newFromTimestamp( $this->pageInfo->getModificationDate() );
				break;
			case DIProperty::TYPE_CREATION_DATE :
				$dataItem = DITime::newFromTimestamp( $this->pageInfo->getCreationDate() );
				break;
			case DIProperty::TYPE_NEW_PAGE :
				$dataItem = new DIBoolean( $this->pageInfo->isNewPage() );
				break;
			case DIProperty::TYPE_LAST_EDITOR :
				$dataItem = $this->pageInfo->getLastEditor() ? DIWikiPage::newFromTitle( $this->pageInfo->getLastEditor() ) : null;
				break;
			case DIProperty::TYPE_MEDIA : // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				$dataItem = $this->pageInfo->isFilePage() && $this->pageInfo->getMediaType() !== '' && $this->pageInfo->getMediaType() !== null ? new DIBlob( $this->pageInfo->getMediaType() ) : null;
				// @codingStandardsIgnoreEnd
				break;
			case DIProperty::TYPE_MIME : // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				$dataItem = $this->pageInfo->isFilePage() && $this->pageInfo->getMimeType() !== '' && $this->pageInfo->getMimeType() !== null  ? new DIBlob( $this->pageInfo->getMimeType() ) : null;
				// @codingStandardsIgnoreEnd
				break;
		}

		return $dataItem;
	}

}
