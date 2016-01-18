<?php

namespace SMW\DataValues;

use SMWDataValue as DataValue;
use SMW\DIProperty;
use SMW\Localizer;
use SMW\DIWikiPage;
use SMW\DataValueFactory;
use SMW\ApplicationFactory;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;
use SMWDataItem as DataItem;

/**
 * MonolingualTextValue requires two components, a language code and a
 * text.
 *
 * A text `foo@en` is expected to be invoked with a BCP47 language
 * code tag and a language dependent text component.
 *
 * Internally, the value is stored as container object that represents
 * the language code and text as separate entities in order to be queried
 * individually.
 *
 * External output representation depends on the context (wiki, html)
 * whether the language code is omitted or not.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValue extends DataValue {

	/**
	 * @var DIProperty[]|null
	 */
	private static $properties = null;

	/**
	 * @var MonolingualTextValueParser
	 */
	private $monolingualTextValueParser = null;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '_mlt_rec' );
		$this->monolingualTextValueParser = ValueParserFactory::getInstance()->newMonolingualTextValueParser();
	}

	/**
	 * @see RecordValue::setFieldProperties
	 *
	 * @param SMWDIProperty[] $properties
	 */
	public function setFieldProperties( array $properties ) {
		// Keep the interface, but the properties for this type
		// are fixed.
	}

	/**
	 * @since 2.4
	 *
	 * @return integer
	 */
	public function needsLanguageCode() {
		return ( $this->getOptionValueFor( 'smwgDVFeatures' ) & SMW_DV_MLTV_LCODE ) != 0;
	}

	/**
	 * @see DataValue::parseUserValue
	 * @note called by DataValue::setUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $userValue ) {

		list( $text, $languageCode ) = $this->getValuesFromString( $userValue );

		$languageCodeValue = $this->newLanguageCodeValue( $languageCode );

		if (
			( $languageCode !== '' && $languageCodeValue->getErrors() !== array() ) ||
			( $languageCode === '' && $this->needsLanguageCode() ) ) {
			$this->addError( $languageCodeValue->getErrors() );
			return;
		}

		$dataValues = array();

		foreach ( $this->getPropertyDataItems() as $property ) {

			if (
				( $languageCode === '' && $property->getKey() === '_LCODE' ) ||
				( $text === '' && $property->getKey() === '_TEXT' ) ) {
				continue;
			}

			$dataValue = DataValueFactory::getInstance()->newPropertyObjectValue(
				$property,
				$property->getKey() === '_LCODE' ? $languageCode : $text,
				false,
				$this->m_contextPage
			);

			$dataValues[] = $dataValue;
		}

		// Generate a hash from the normalized representation so that foo@en being
		// the same as foo@EN independent of a user input
		$containerSemanticData = $this->newContainerSemanticData( $text . '@' . $languageCode );

		foreach ( $dataValues as $dataValue ) {
			$containerSemanticData->addDataValue( $dataValue );
		}

		$this->m_dataitem = new DIContainer( $containerSemanticData );
	}

	/**
	 * @note called by MonolingualTextValueDescriptionDeserializer::deserialize
	 * and MonolingualTextValue::parseUserValue
	 *
	 * No explicit check is made on the validity of a language code and is
	 * expected to be done before calling this method.
	 *
	 * @since 2.4
	 *
	 * @param string $userValue
	 *
	 * @return array
	 */
	public function getValuesFromString( $userValue ) {
		return $this->monolingualTextValueParser->parse( $userValue );
	}

	/**
	 * @see DataValue::loadDataItem
	 *
	 * @param DataItem $dataItem
	 *
	 * @return boolean
	 */
	protected function loadDataItem( DataItem $dataItem ) {

		if ( $dataItem->getDIType() === DataItem::TYPE_CONTAINER ) {
			$this->m_dataitem = $dataItem;
			return true;
		} elseif ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE ) {
			$semanticData = new ContainerSemanticData( $dataItem );
			$semanticData->copyDataFrom( ApplicationFactory::getInstance()->getStore()->getSemanticData( $dataItem ) );
			$this->m_dataitem = new DIContainer( $semanticData );
			return true;
		}

		return false;
	}

	/**
	 * @see DataValue::getShortWikiText
	 */
	public function getShortWikiText( $linked = null ) {

		if ( $this->m_caption !== false ) {
			return $this->m_caption;
		}

		return $this->makeOutputText( 0, $linked );
	}

	/**
	 * @see DataValue::getShortHTMLText
	 */
	public function getShortHTMLText( $linker = null ) {

		if ( $this->m_caption !== false ) {
			return $this->m_caption;
		}

		return $this->makeOutputText( 1, $linker );
	}

	/**
	 * @see DataValue::getLongWikiText
	 */
	public function getLongWikiText( $linked = null ) {
		return $this->makeOutputText( 2, $linked );
	}

	/**
	 * @see DataValue::getLongHTMLText
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->makeOutputText( 3, $linker );
	}

	/**
	 * @see DataValue::getWikiValue
	 */
	public function getWikiValue() {
		return $this->makeOutputText( 4 );
	}

	/**
	 * @since 2.4
	 * @note called by SMWResultArray::getNextDataValue
	 *
	 * @return DIProperty[]
	 */
	public static function getPropertyDataItems() {

		if ( self::$properties !== null && self::$properties !== array() ) {
			return self::$properties;
		}

		foreach ( array( '_TEXT', '_LCODE' ) as  $id ) {
			self::$properties[] = new DIProperty( $id );
		}

		return self::$properties;
	}

	/**
	 * @since 2.4
	 * @note called by SMWResultArray::loadContent
	 *
	 * @return DataItem[]
	 */
	public function getDataItems() {

		if ( !$this->isValid() ) {
			return array();
		}

		$result = array();
		$index = 0;

		foreach ( $this->getPropertyDataItems() as $diProperty ) {
			$values = $this->getDataItem()->getSemanticData()->getPropertyValues( $diProperty );
			if ( count( $values ) > 0 ) {
				$result[$index] = reset( $values );
			} else {
				$result[$index] = null;
			}
			$index += 1;
		}

		return $result;
	}

	/**
	 * @since 2.4
	 *
	 * @return DataValue|null
	 */
	public function getTextValueByLanguage( $languageCode ) {

		if ( !$this->isValid() || $this->getDataItem() === array() ) {
			return null;
		}

		$semanticData = $this->getDataItem()->getSemanticData();

		$dataItems = $semanticData->getPropertyValues( new DIProperty( '_LCODE' ) );
		$dataItem = reset( $dataItems );

		if ( $dataItem->getString() !== Localizer::asBCP47FormattedLanguageCode( $languageCode ) ) {
			return null;
		}

		$dataItems = $semanticData->getPropertyValues( new DIProperty( '_TEXT' ) );
		$dataItem = reset( $dataItems );

		if ( $dataItem === false ) {
			return null;
		}

		$dataValue = DataValueFactory::getInstance()->newDataItemValue(
			$dataItem,
			new DIProperty( '_TEXT' )
		);

		return $dataValue;
	}

	protected function makeOutputText( $type = 0, $linker = null ) {

		if ( !$this->isValid() ) {
			return ( ( $type == 0 ) || ( $type == 1 ) ) ? '' : $this->getErrorText();
		}

		// For the inverse case, return the subject that contains the reference
		// for Foo annotated with [[Bar::abc@en]] -> [[-Bar::Foo]]
		if ( $this->m_property !== null && $this->m_property->isInverse() ) {

			$dataItems = $this->m_dataitem->getSemanticData()->getPropertyValues( new DIProperty(  $this->m_property->getKey() ) );
			$dataItem = reset( $dataItems );

			if ( !$dataItem ) {
				return '';
			}

			return $dataItem->getDBKey();
		}

		return $this->getFinalOutputTextFor( $type, $linker );
	}

	private function getFinalOutputTextFor( $type, $linker ) {

		$text = '';
		$languagecode = '';

		foreach ( $this->getPropertyDataItems() as $property ) {

			// If we wanted to omit the language code display for some outputs then
			// this is the point to make it happen
			if ( ( $type == 0 || $type == 4 ) && $property->getKey() === '_LCODE' ) {
			// continue;
			}

			$dataItems = $this->m_dataitem->getSemanticData()->getPropertyValues( $property );

			// Should not happen but just in case
			if ( !$dataItems === array() ) {
				$this->addError( wfMessage( 'smw-datavalue-monolingual-dataitem-missing' )->text() );
				continue;
			}

			$dataItem = reset( $dataItems );

			if ( $dataItem === false ) {
				continue;
			}

			$dataValue = DataValueFactory::getInstance()->newDataItemValue(
				$dataItem,
				$property
			);

			$result = $this->makeValueOutputText(
				$type,
				$dataValue,
				$linker
			);

			if ( $property->getKey() === '_LCODE' ) {
				$languagecode = ' ' . wfMessage( 'smw-datavalue-monolingual-lcode-parenthesis', $result )->text();
			} else {
				$text = $result;
			}
		}

		return $text . $languagecode;
	}

	private function makeValueOutputText( $type, $dataValue, $linker ) {
		switch ( $type ) {
			case 0: return $dataValue->getShortWikiText( $linker );
			case 1: return $dataValue->getShortHTMLText( $linker );
			case 2: return $dataValue->getShortWikiText( $linker );
			case 3: return $dataValue->getShortHTMLText( $linker );
			case 4: return $dataValue->getWikiValue();
		}
	}

	private function newContainerSemanticData( $value ) {

		if ( $this->m_contextPage === null ) {
			$containerSemanticData = ContainerSemanticData::makeAnonymousContainer();
		} else {
			$subobjectName = '_ML' . md5( $value );

			$subject = new DIWikiPage(
				$this->m_contextPage->getDBkey(),
				$this->m_contextPage->getNamespace(),
				$this->m_contextPage->getInterwiki(),
				$subobjectName
			);

			$containerSemanticData = new ContainerSemanticData( $subject );
		}

		return $containerSemanticData;
	}

	private function newLanguageCodeValue( $languageCode ) {

		$languageCodeValue = new LanguageCodeValue();

		if ( $this->m_property !== null ) {
			$languageCodeValue->setProperty( $this->m_property );
		}

		$languageCodeValue->setUserValue( $languageCode );

		return $languageCodeValue;
	}

}
