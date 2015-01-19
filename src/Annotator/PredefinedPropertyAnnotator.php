<?php

namespace SMW\Annotator;

use SMW\ApplicationFactory;
use SMW\PropertyAnnotator;
use SMW\PageInfo;
use SMW\DIProperty;
use SMW\DIWikiPage;

use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDITime as DITime;

/**
 * Handling predefined property annotations
 *
 * @ingroup SMW
 *
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
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param PageInfo $pageInfo
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, PageInfo $pageInfo ) {
		parent::__construct( $propertyAnnotator );
		$this->pageInfo = $pageInfo;
	}

	protected function addPropertyValues() {

		$predefinedProperties = ApplicationFactory::getInstance()->getSettings()->get( 'smwgPageSpecialProperties' );
		$cachedProperties = array();

		foreach ( $predefinedProperties as $propertyId ) {

			if ( $this->isRegisteredPropertyId( $propertyId, $cachedProperties ) ) {
				continue;
			}

			$propertyDI = new DIProperty( $propertyId );

			if ( $this->getSemanticData()->getPropertyValues( $propertyDI ) !== array() ) {
				$cachedProperties[ $propertyId ] = true;
				continue;
			}

			$dataItem = $this->createDataItemByPropertyId( $propertyId );

			if ( $dataItem instanceof DataItem ) {
				$cachedProperties[ $propertyId ] = true;
				$this->getSemanticData()->addPropertyObjectValue( $propertyDI, $dataItem );
			}
		}
	}

	protected function isRegisteredPropertyId( $propertyId, $cachedProperties ) {
		return ( DIProperty::getPredefinedPropertyTypeId( $propertyId ) === '' ) ||
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
			case DIProperty::TYPE_MEDIA :
				$dataItem = $this->pageInfo->isFilePage() && $this->pageInfo->getMediaType() !== '' && $this->pageInfo->getMediaType() !== null ? new DIBlob( $this->pageInfo->getMediaType() ) : null;
				break;
			case DIProperty::TYPE_MIME :
				$dataItem = $this->pageInfo->isFilePage() && $this->pageInfo->getMimeType() !== '' && $this->pageInfo->getMimeType() !== null  ? new DIBlob( $this->pageInfo->getMimeType() ) : null;
				break;
		}

		return $dataItem;
	}

}
