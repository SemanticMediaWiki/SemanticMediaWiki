<?php

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DataValues\AbstractMultiValue;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDataItem as DataItem;
use SMWDIContainer as DIContainer;

/**
 * SMWDataValue implements the handling of small sets of property-value pairs.
 * The declaration of Records in SMW uses the order of values to encode the
 * property that should be used, so the user only needs to enter a list of
 * values. Internally, however, the property-value assignments are not stored
 * with a particular order; they will only be ordered for display, following
 * the declaration. This is why it is not supported to have Records using the
 * same property for more than one value.
 *
 * The class uses DIContainer objects to return its inner state. See the
 * documentation for DIContainer for details on how this "pseudo" data
 * encapsulated many property assignments. Such data is stored internally
 * like a page with various property-value assignments. Indeed, record values
 * can be created from DIWikiPage objects (the missing information will
 * be fetched from the store).
 *
 * @todo Enforce limitation of maximal number of values.
 * @todo Enforce uniqueness of properties in declaration.
 * @todo Complete internationalisation.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWRecordValue extends AbstractMultiValue {

	/// cache for properties for the fields of this data value
	protected $m_diProperties = null;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '_rec' );
	}

	/**
	 * @since 2.3
	 *
	 * @return DIProperty[]|null
	 */
	public function getProperties() {
		return $this->m_diProperties;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $value
	 *
	 * @return array
	 */
	public function getValuesFromString( $value ) {
		// #664 / T17732
		$value = str_replace( "\;", "-3B", $value );

		// Bug 21926 / T23926
		// Values that use html entities are encoded with a semicolon
		$value = htmlspecialchars_decode( $value, ENT_QUOTES );
		$values = preg_split( '/[\s]*;[\s]*/u', trim( $value ) );

		return str_replace( "-3B", ";", $values );
	}

	protected function parseUserValue( $value ) {

		if ( $value === '' ) {
			$this->addErrorMsg( [ 'smw_novalues' ] );
			return;
		}

		$containerSemanticData = $this->newContainerSemanticData( $value );
		$sortKeys = [];

		$values = $this->getValuesFromString( $value );
		$valueIndex = 0; // index in value array
		$propertyIndex = 0; // index in property list
		$empty = true;

		foreach ( $this->getPropertyDataItems() as $diProperty ) {

			if ( !array_key_exists( $valueIndex, $values ) || $this->getErrors() !== [] ) {
				break; // stop if there are no values left
			}

			// generating the DVs:
			if ( ( $values[$valueIndex] === '' ) || ( $values[$valueIndex] == '?' ) ) { // explicit omission
				$valueIndex++;
			} else {

				$val = $values[$valueIndex];

				// If an annotation starts with `#` and remains unmodified
				// then the wiki parser would interpret it as list element
				// and format it accordingly eventhough it is not suppose to
				// be an ul/ol list item.
				if ( $val !== '' && $val{0} === '#' ) {
					$val = str_replace( "#", '&#x23;', $val );
				}

				$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
					$diProperty,
					$val,
					false,
					$containerSemanticData->getSubject()
				);

				if ( $dataValue->isValid() ) { // valid DV: keep
					$containerSemanticData->addPropertyObjectValue( $diProperty, $dataValue->getDataItem() );
					$sortKeys[] = $dataValue->getDataItem()->getSortKey();

					$valueIndex++;
					$empty = false;
				} elseif ( ( count( $values ) - $valueIndex ) == ( count( $this->m_diProperties ) - $propertyIndex ) ) {
					$containerSemanticData->addPropertyObjectValue( $diProperty, $dataValue->getDataItem() );
					$this->addError( $dataValue->getErrors() );
					++$valueIndex;
				}
			}
			++$propertyIndex;
		}

		if ( $empty && $this->getErrors() === []  ) {
			$this->addErrorMsg( [ 'smw_novalues' ] );
		}

		// Remember the data to extend the sortkey
		$containerSemanticData->setExtensionData( 'sort.data', implode( ';', $sortKeys ) );

		$this->m_dataitem = new DIContainer( $containerSemanticData );
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem DataItem
	 * @return boolean
	 */
	protected function loadDataItem( DataItem $dataItem ) {
		if ( $dataItem->getDIType() == DataItem::TYPE_CONTAINER ) {
			$this->m_dataitem = $dataItem;
			return true;
		} elseif ( $dataItem->getDIType() == DataItem::TYPE_WIKIPAGE ) {
			$semanticData = new ContainerSemanticData( $dataItem );
			$semanticData->copyDataFrom( ApplicationFactory::getInstance()->getStore()->getSemanticData( $dataItem ) );
			$this->m_dataitem = new DIContainer( $semanticData );
			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linked = null ) {
		if ( $this->m_caption !== false ) {
			return $this->m_caption;
		}
		return $this->makeOutputText( 0, $linked );
	}

	public function getShortHTMLText( $linker = null ) {
		if ( $this->m_caption !== false ) {
			return $this->m_caption;
		}
		return $this->makeOutputText( 1, $linker );
	}

	public function getLongWikiText( $linked = null ) {
		return $this->makeOutputText( 2, $linked );
	}

	public function getLongHTMLText( $linker = null ) {
		return $this->makeOutputText( 3, $linker );
	}

	public function getWikiValue() {
		return $this->makeOutputText( 4 );
	}

	/**
	 * Make sure that the content is reset in this case.
	 * @todo This is not a full reset yet (the case that property is changed after a value
	 * was set does not occur in the normal flow of things, hence this has low priority).
	 */
	public function setProperty( DIProperty $property ) {
		parent::setProperty( $property );
		$this->m_diProperties = null;
	}

	/**
	 * @since 2.1
	 *
	 * @param DIProperty[] $properties
	 */
	public function setFieldProperties( array $properties ) {
		foreach ( $properties as $property ) {
			if ( $property instanceof DIProperty ) {
				$this->m_diProperties[] = $property;
			}
		}
	}

	/**
	 * @since 1.6
	 *
	 * {@inheritDoc}
	 */
	public function getDataItems() {
		return parent::getDataItems();
	}

	/**
	 * Return the array (list) of properties that the individual entries of
	 * this datatype consist of.
	 *
	 * @since 1.6
	 *
	 * @todo I18N for error message.
	 *
	 * @return array of DIProperty
	 */
	public function getPropertyDataItems() {

		if ( $this->m_diProperties !== null ) {
			return $this->m_diProperties;
		}

		$this->m_diProperties = $this->getFieldProperties( $this->m_property );

		if ( $this->m_diProperties  === [] ) { // TODO internalionalize
			$this->addError( 'The list of properties to be used for the data fields has not been specified properly.' );
		}

		return $this->m_diProperties;
	}

	protected function makeOutputText( $type = 0, $linker = null ) {
		if ( !$this->isValid() ) {
			return ( ( $type == 0 ) || ( $type == 1 ) ) ? '' : $this->getErrorText();
		}

		$result = '';
		$i = 0;
		foreach ( $this->getPropertyDataItems() as $propertyDataItem ) {
			if ( $i == 1 ) {
				$result .= ( $type == 4 ) ? '; ' : ' (';
			} elseif ( $i > 1 ) {
				$result .= ( $type == 4 ) ? '; ' : ', ';
			}
			++$i;
			$propertyValues = $this->m_dataitem->getSemanticData()->getPropertyValues( $propertyDataItem ); // combining this with next line violates PHP strict standards
			$dataItem = reset( $propertyValues );
			if ( $dataItem !== false ) {
				$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $dataItem, $propertyDataItem );
				$result .= $this->makeValueOutputText( $type, $dataValue, $linker );
			} else {
				$result .= '?';
			}
		}
		if ( ( $i > 1 ) && ( $type != 4 ) ) {
			$result .= ')';
		}

		return $result;
	}

	protected function makeValueOutputText( $type, SMWDataValue $dataValue, $linker ) {
		switch ( $type ) {
			case 0:
			return $dataValue->getShortWikiText( $linker );
			case 1:
			return $dataValue->getShortHTMLText( $linker );
			case 2:
			return $dataValue->getShortWikiText( $linker );
			case 3:
			return $dataValue->getShortHTMLText( $linker );
			case 4:
			return str_replace( ";", "\;", $dataValue->getWikiValue() );
		}
	}

	private function newContainerSemanticData( $value ) {

		if ( $this->m_contextPage === null ) {
			$containerSemanticData = ContainerSemanticData::makeAnonymousContainer();
			$containerSemanticData->skipAnonymousCheck();
		} else {
			$subobjectName = '_' . hash( 'md4', $value, false ); // md4 is probably fastest of PHP's hashes

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

}
