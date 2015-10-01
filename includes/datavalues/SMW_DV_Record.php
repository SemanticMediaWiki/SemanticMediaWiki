<?php
/**
 * @ingroup SMWDataValues
 */

/**
 * SMWDataValue implements the handling of small sets of property-value pairs.
 * The declaration of Records in SMW uses the order of values to encode the
 * property that should be used, so the user only needs to enter a list of
 * values. Internally, however, the property-value assignments are not stored
 * with a particular order; they will only be ordered for display, following
 * the declaration. This is why it is not supported to have Records using the
 * same property for more than one value.
 *
 * The class uses SMWDIContainer objects to return its inner state. See the
 * documentation for SMWDIContainer for details on how this "pseudo" data
 * encapsulated many property assignments. Such data is stored internally
 * like a page with various property-value assignments. Indeed, record values
 * can be created from SMWDIWikiPage objects (the missing information will
 * be fetched from the store).
 *
 * @todo Enforce limitation of maximal number of values.
 * @todo Enforce uniqueness of properties in declaration.
 * @todo Complete internationalisation.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWRecordValue extends SMWDataValue {

	/// cache for properties for the fields of this data value
	protected $m_diProperties = null;

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

		return preg_split( '/[\s]*;[\s]*/u', trim( $value ) );
	}

	protected function parseUserValue( $value ) {

		if ( $value === '' ) {
			$this->addError( wfMessage( 'smw_novalues' )->text() );
			return;
		}

		if ( is_null( $this->m_contextPage ) ) {
			$semanticData = SMWContainerSemanticData::makeAnonymousContainer();
		} else {
			$subobjectName = '_' . hash( 'md4', $value, false ); // md4 is probably fastest of PHP's hashes
			$subject = new SMWDIWikiPage( $this->m_contextPage->getDBkey(),
				$this->m_contextPage->getNamespace(), $this->m_contextPage->getInterwiki(),
				$subobjectName );
			$semanticData = new SMWContainerSemanticData( $subject );
		}

		$values = $this->getValuesFromString( $value );
		$valueIndex = 0; // index in value array
		$propertyIndex = 0; // index in property list
		$empty = true;

		foreach ( $this->getPropertyDataItems() as $diProperty ) {
			if ( !array_key_exists( $valueIndex, $values ) ) {
				break; // stop if there are no values left
			}

			$values[$valueIndex] = str_replace( "-3B", ";", $values[$valueIndex] );

			// generating the DVs:
			if ( ( $values[$valueIndex] === '' ) || ( $values[$valueIndex] == '?' ) ) { // explicit omission
				$valueIndex++;
			} else {
				$dataValue = \SMW\DataValueFactory::getInstance()->newPropertyObjectValue( $diProperty, $values[$valueIndex] );

				if ( $dataValue->isValid() ) { // valid DV: keep
					$semanticData->addPropertyObjectValue( $diProperty, $dataValue->getDataItem() );

					$valueIndex++;
					$empty = false;
				} elseif ( ( count( $values ) - $valueIndex ) == ( count( $this->m_diProperties ) - $propertyIndex ) ) {
					$semanticData->addPropertyObjectValue( $diProperty, $dataValue->getDataItem() );
					$this->addError( $dataValue->getErrors() );
					++$valueIndex;
				}
			}
			++$propertyIndex;
		}

		if ( $empty ) {
			$this->addError( wfMessage( 'smw_novalues' )->text() );
		}

		$this->m_dataitem = new SMWDIContainer( $semanticData );
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_CONTAINER ) {
			$this->m_dataitem = $dataItem;
			return true;
		} elseif ( $dataItem->getDIType() == SMWDataItem::TYPE_WIKIPAGE ) {
			$semanticData = new SMWContainerSemanticData( $dataItem );
			$semanticData->copyDataFrom( \SMW\StoreFactory::getStore()->getSemanticData( $dataItem ) );
			$this->m_dataitem = new SMWDIContainer( $semanticData );
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

	/// @todo Allowed values for multi-valued properties are not supported yet.
	protected function checkAllowedValues() {
	}

	/**
	 * Make sure that the content is reset in this case.
	 * @todo This is not a full reset yet (the case that property is changed after a value
	 * was set does not occur in the normal flow of things, hence this has low priority).
	 */
	public function setProperty( SMWDIProperty $property ) {
		parent::setProperty( $property );
		$this->m_diProperties = null;
	}

	/**
	 * @since 2.1
	 *
	 * @param SMWDIProperty[] $properties
	 */
	public function setFieldProperties( array $properties ) {
		foreach ( $properties as $property ) {
			if ( $property instanceof SMWDIProperty ) {
				$this->m_diProperties[] = $property;
			}
		}
	}

////// Additional API for value lists

	/**
	 * @deprecated as of 1.6, use getDataItems instead
	 *
	 * @return array of SMWDataItem
	 */
	public function getDVs() {
		return $this->getDataItems();
	}

	/**
	 * Create a list (array with numeric keys) containing the datavalue
	 * objects that this SMWRecordValue object holds. Values that are not
	 * present are set to null. Note that the first index in the array is
	 * 0, not 1.
	 *
	 * @since 1.6
	 *
	 * @return array of SMWDataItem
	 */
	public function getDataItems() {
		if ( $this->isValid() ) {
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
		} else {
			return array();
		}
	}

	/**
	 * Return the array (list) of properties that the individual entries of
	 * this datatype consist of.
	 *
	 * @since 1.6
	 *
	 * @todo I18N for error message.
	 *
	 * @return array of SMWDIProperty
	 */
	public function getPropertyDataItems() {
		if ( is_null( $this->m_diProperties ) ) {
			$this->m_diProperties = self::findPropertyDataItems( $this->m_property );

			if ( count( $this->m_diProperties ) == 0 ) { // TODO internalionalize
				$this->addError( 'The list of properties to be used for the data fields has not been specified properly.' );
			}
		}

		return $this->m_diProperties;
	}

	/**
	 * Return the array (list) of properties that the individual entries of
	 * this datatype consist of.
	 *
	 * @since 1.6
	 *
	 * @param $diProperty mixed null or SMWDIProperty object for which to retrieve the types
	 *
	 * @return array of SMWDIProperty
	 */
	public static function findPropertyDataItems( $diProperty ) {
		if ( !is_null( $diProperty ) ) {
			$propertyDiWikiPage = $diProperty->getDiWikiPage();

			if ( !is_null( $propertyDiWikiPage ) ) {
				$listDiProperty = new SMWDIProperty( '_LIST' );
				$dataItems = \SMW\StoreFactory::getStore()->getPropertyValues( $propertyDiWikiPage, $listDiProperty );

				if ( count( $dataItems ) == 1 ) {
					$propertyListValue = new SMWPropertyListValue( '__pls' );
					$propertyListValue->setDataItem( reset( $dataItems ) );

					if ( $propertyListValue->isValid() ) {
						return $propertyListValue->getPropertyDataItems();
					}
				}
			}
		}

		return array();
	}

////// Internal helper functions

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
				$dataValue = \SMW\DataValueFactory::getInstance()->newDataItemValue( $dataItem, $propertyDataItem );
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

	protected function makeValueOutputText( $type, $dataValue, $linker ) {
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

}

