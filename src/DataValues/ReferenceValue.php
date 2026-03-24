<?php

namespace SMW\DataValues;

use SMW\DataItems\Container;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataModel\SemanticData;
use SMW\DataValueFactory;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\Localizer\Message;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * ReferenceValue allows to define additional DV to describe the state of a
 * SourceValue in terms of provenance or referential evidence. ReferenceValue is
 * stored as separate entity to the original subject in order to encapsulate the
 * SourceValue from the remaining annotations with reference to a subject.
 *
 * Defining which fields are required can vary and therefore is left to the user
 * to specify such requirements using the `'Has fields' property.
 *
 * For example, declaring `[[Has fields::SomeValue;Date;SomeUrl;...]]` on a
 * `SomeProperty` property page is to define:
 *
 * - a property called `SomeValue` with its own specification
 * - a date property with the Date type
 * - a property called `SomeUrl` with its own specification
 * - ... any other property the users expects to require when making a value
 *   annotation of this type
 *
 * An annotation like `[[SomeProperty::Foo;12-12-1212;http://example.org]]` is
 * expected to be a concatenated string and to be separated by ';' to indicate
 * the next value string and will corespondent to the index of the `Has fields`
 * declaration.
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ReferenceValue extends AbstractMultiValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '_ref_rec';

	/**
	 * @var Property[]|null
	 */
	private $properties = null;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function setFieldProperties( array $properties ): void {
		foreach ( $properties as $property ) {
			if ( $property instanceof Property ) {
				$this->properties[] = $property;
			}
		}
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getValuesFromString( $value ): array {
		// #664 / T17732
		$value = str_replace( "\;", "-3B", $value );

		// Bug 21926 / T23926
		// Values that use html entities are encoded with a semicolon
		$value = htmlspecialchars_decode( $value, ENT_QUOTES );
		$values = preg_split( '/[\s]*;[\s]*/u', trim( $value ) );

		return str_replace( "-3B", ";", $values );
	}

	/**
	 * @see DataValue::getShortWikiText
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getShortWikiText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::WIKI_SHORT, $linker );
	}

	/**
	 * @see DataValue::getShortHTMLText
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getShortHTMLText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::HTML_SHORT, $linker );
	}

	/**
	 * @see DataValue::getLongWikiText
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getLongWikiText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::WIKI_LONG, $linker );
	}

	/**
	 * @see DataValue::getLongHTMLText
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::HTML_LONG, $linker );
	}

	/**
	 * @see DataValue::getWikiValue
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getWikiValue() {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::VALUE );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getPropertyDataItems() {
		if ( $this->properties === null ) {
			$this->properties = $this->getFieldProperties( $this->getProperty() );

			if ( count( $this->properties ) == 0 ) {
				$this->addErrorMsg( [ 'smw-datavalue-reference-invalid-fields-definition' ], Message::PARSE );
			}
		}

		return $this->properties;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getDataItems(): array {
		return parent::getDataItems();
	}

	/**
	 * @note called by DataValue::setUserValue
	 * @see DataValue::parseUserValue
	 *
	 * {@inheritDoc}
	 */
	protected function parseUserValue( $value ): void {
		if ( $value === '' ) {
			$this->addErrorMsg( [ 'smw_novalues' ] );
			return;
		}

		$containerSemanticData = $this->newContainerSemanticData( $value );
		$sortKeys = [];

		$values = $this->getValuesFromString( $value );
		$index = 0; // index in value array

		$propertyIndex = 0; // index in property list
		$empty = true;

		foreach ( $this->getPropertyDataItems() as $property ) {

			if ( !array_key_exists( $index, $values ) || $this->getErrors() !== [] ) {
				break; // stop if there are no values left
			}

			// generating the DVs:
			if ( ( $values[$index] === '' ) || ( $values[$index] == '?' ) ) { // explicit omission
				$index++;
			} else {
				$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
					$property,
					$values[$index],
					false,
					$containerSemanticData->getSubject()
				);

				if ( $dataValue->isValid() ) { // valid DV: keep
					$dataItem = $dataValue->getDataItem();

					$containerSemanticData->addPropertyObjectValue(
						$property,
						$dataItem
					);

					// Chronological order determined first
					if ( $dataItem instanceof Time ) {
						array_unshift( $sortKeys, $dataItem->getSortKey() );
					} else {
						$sortKeys[] = $dataItem->getSortKey();
					}

					$index++;
					$empty = false;
				} elseif ( $index == 0 || ( count( $values ) - $index ) == ( count( $this->properties ) - $propertyIndex ) ) {
					$containerSemanticData->addPropertyObjectValue( $property, $dataValue->getDataItem() );
					$this->addError( $dataValue->getErrors() );
					++$index;
				}
			}

			++$propertyIndex;
		}

		if ( $empty && $this->getErrors() === [] ) {
			$this->addErrorMsg( [ 'smw_novalues' ] );
		}

		// Remember the data to extend the sortkey
		$containerSemanticData->setExtensionData( 'sort.data', implode( ';', $sortKeys ) );

		$this->m_dataitem = new Container( $containerSemanticData );
	}

	/**
	 * @see DataValue::loadDataItem
	 */
	protected function loadDataItem( DataItem $dataItem ): bool {
		if ( $dataItem->getDIType() === DataItem::TYPE_CONTAINER ) {
			$this->m_dataitem = $dataItem;
			return true;
		} elseif ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE ) {

			$semanticData = null;
			$subobjectName = $dataItem->getSubobjectName();

			if ( $this->hasCallable( SemanticData::class ) ) {
				$semanticData = $this->getCallable( SemanticData::class )();
			}

			if (
				$semanticData instanceof SemanticData &&
				$semanticData->hasSubSemanticData( $subobjectName ) ) {
				$this->m_dataitem = new Container(
					$semanticData->findSubSemanticData( $subobjectName )
				);
			} else {
				$semanticData = new ContainerSemanticData( $dataItem );
				$semanticData->copyDataFrom( ApplicationFactory::getInstance()->getStore()->getSemanticData( $dataItem ) );
				$this->m_dataitem = new Container( $semanticData );
			}

			return true;
		}

		return false;
	}

	private function newContainerSemanticData( $value ): ContainerSemanticData {
		if ( $this->m_contextPage === null ) {
			$containerSemanticData = ContainerSemanticData::makeAnonymousContainer();
			$containerSemanticData->skipAnonymousCheck();
		} else {
			$subobjectName = '_REF' . md5( $value );

			$subject = new WikiPage(
				$this->m_contextPage->getDBkey(),
				$this->m_contextPage->getNamespace(),
				$this->m_contextPage->getInterwiki(),
				$subobjectName
			);

			$containerSemanticData = new ContainerSemanticData( $subject );
		}

		return $containerSemanticData;
	}

}
