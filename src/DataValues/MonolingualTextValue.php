<?php

namespace SMW\DataValues;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Localizer;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIContainer as DIContainer;

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
	 * @var DataValueFactory
	 */
	private $dataValueFactory = null;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '_mlt_rec' );
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @see RecordValue::setFieldProperties
	 *
	 * @param SMWDIProperty[] $properties
	 */
	public function setFieldProperties( array $properties ) {
		// Keep the interface while the properties for this type
		// are fixed.
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
			( $languageCode === '' && $this->isEnabledFeature( SMW_DV_MLTV_LCODE ) ) ) {
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

			$value = $text;

			if ( $property->getKey() === '_LCODE' ) {
				$value = $languageCode;
			}

			$dataValue = $this->dataValueFactory->newDataValueByProperty(
				$property,
				$value,
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
		return $this->getValueParser()->parse( $userValue );
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
	public function getShortWikiText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::WIKI_SHORT, $linker );
	}

	/**
	 * @see DataValue::getShortHTMLText
	 */
	public function getShortHTMLText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::HTML_SHORT, $linker );
	}

	/**
	 * @see DataValue::getLongWikiText
	 */
	public function getLongWikiText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::WIKI_LONG, $linker );
	}

	/**
	 * @see DataValue::getLongHTMLText
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::HTML_LONG, $linker );
	}

	/**
	 * @see DataValue::getWikiValue
	 */
	public function getWikiValue() {
		return $this->getDataValueFormatter()->format( DataValueFormatter::VALUE );
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

		if ( $dataItem === false || ( $dataItem->getString() !== Localizer::asBCP47FormattedLanguageCode( $languageCode ) ) ) {
			return null;
		}

		$dataItems = $semanticData->getPropertyValues( new DIProperty( '_TEXT' ) );
		$dataItem = reset( $dataItems );

		if ( $dataItem === false ) {
			return null;
		}

		$dataValue = $this->dataValueFactory->newDataValueByItem(
			$dataItem,
			new DIProperty( '_TEXT' )
		);

		return $dataValue;
	}

	private function newContainerSemanticData( $value ) {

		if ( $this->m_contextPage === null ) {
			$containerSemanticData = ContainerSemanticData::makeAnonymousContainer();
			$containerSemanticData->skipAnonymousCheck();
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

	private function getValueParser() {

		if ( $this->monolingualTextValueParser === null ) {
			$this->monolingualTextValueParser = ValueParserFactory::getInstance()->newMonolingualTextValueParser();
		}

		return $this->monolingualTextValueParser;
	}

}
