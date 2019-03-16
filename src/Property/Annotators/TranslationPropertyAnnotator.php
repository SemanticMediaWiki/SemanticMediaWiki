<?php

namespace SMW\Property\Annotators;

use SMW\PropertyAnnotator;
use SMW\DataModel\ContainerSemanticData;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TranslationPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @var array|null
	 */
	private $translation;

	/**
	 * @var array
	 */
	private $predefinedPropertyList = [];

	/**
	 * @since 3.0
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param array|null $translation
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, $translation ) {
		parent::__construct( $propertyAnnotator );
		$this->translation = $translation;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $predefinedPropertyList
	 */
	public function setPredefinedPropertyList( array $predefinedPropertyList ) {
		$this->predefinedPropertyList = array_flip( $predefinedPropertyList );
	}

	protected function addPropertyValues() {

		// Expected identifiers, @see https://gerrit.wikimedia.org/r/387548
		if ( !is_array( $this->translation ) || !isset( $this->predefinedPropertyList['_TRANS'] ) ) {
			return;
		}

		$containerSemanticData = null;

		if ( isset( $this->translation['languagecode'] ) ) {
			$languageCode = $this->translation['languagecode'];
			$containerSemanticData = $this->newContainerSemanticData( $languageCode );

			// Translation.Language code
			$containerSemanticData->addPropertyObjectValue(
				$this->dataItemFactory->newDIProperty( '_LCODE' ),
				$this->dataItemFactory->newDIBlob( $languageCode )
			);
		}

		if ( isset( $this->translation['sourcepagetitle'] ) && $this->translation['sourcepagetitle'] instanceof Title ) {
			// Translation.Translation source
			$containerSemanticData->addPropertyObjectValue(
				$this->dataItemFactory->newDIProperty( '_TRANS_SOURCE' ),
				$this->dataItemFactory->newDIWikiPage( $this->translation['sourcepagetitle'] )
			);
		}

		if ( isset( $this->translation['messagegroupid'] ) ) {
			// Translation.Translation group
			$containerSemanticData->addPropertyObjectValue(
				$this->dataItemFactory->newDIProperty( '_TRANS_GROUP' ),
				$this->dataItemFactory->newDIBlob( $this->translation['messagegroupid'] )
			);
		}

		if ( $containerSemanticData !== null ) {
			$this->getSemanticData()->addPropertyObjectValue(
				$this->dataItemFactory->newDIProperty( '_TRANS' ),
				$this->dataItemFactory->newDIContainer( $containerSemanticData )
			);
		}
	}

	private function newContainerSemanticData( $languageCode ) {

		$dataItem = $this->getSemanticData()->getSubject();
		$subobjectName = 'trans.' . $languageCode;

		$subject = $this->dataItemFactory->newDIWikiPage(
			$dataItem->getDBkey(),
			$dataItem->getNamespace(),
			$dataItem->getInterwiki(),
			$subobjectName
		);

		return $this->dataItemFactory->newContainerSemanticData( $subject );
	}

}
