<?php

namespace SMW;

use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDITime as DITime;

/**
 * Handling predefined property annotations
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotator extends PropertyAnnotatorDecorator {

	/** @var PageInfoProvider */
	protected $pageInfo;

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param PageInfoProvider $pageInfo
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, PageInfoProvider $pageInfo ) {
		parent::__construct( $propertyAnnotator );
		$this->pageInfo = $pageInfo;
	}

	/**
	 * @since 1.9
	 */
	protected function addPropertyValues() {

		$predefinedProperties = $this->withContext()->getSettings()->get( 'smwgPageSpecialProperties' );
		$cachedProperties = array();

		foreach ( $predefinedProperties as $propertyId ) {

			if ( $this->assertRegisteredPropertyId( $propertyId, $cachedProperties ) ) {
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

		$this->setState( 'updateOutput' );
	}

	/**
	 * @since  1.9
	 */
	protected function assertRegisteredPropertyId( $propertyId, $cachedProperties ) {
		return ( DIProperty::getPredefinedPropertyTypeId( $propertyId ) === '' ) ||
			array_key_exists( $propertyId, $cachedProperties );
	}

	/**
	 * @since  1.9
	 */
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
				$dataItem = $this->pageInfo->isFilePage() ? new DIBlob( $this->pageInfo->getMediaType() ) : null;
				break;
			case DIProperty::TYPE_MIME :
				$dataItem = $this->pageInfo->isFilePage() ? new DIBlob( $this->pageInfo->getMimeType() ) : null;
				break;
		}

		return $dataItem;
	}

}
