<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * SMWDataValue implements the handling of short lists of values,
 * where the order governs the type of each entry.
 *
 * @todo Enforce limitation of maximal number of values.
 * @todo Complete internationalisation.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWRecordValue extends SMWDataValue {

	/// cache for datavalues of types belonging to this object
	private $m_typevalues = null;

	protected function parseUserValue( $value ) {
		$this->parseUserValueOrQuery( $value, false );
	}

	protected function parseUserValueOrQuery( $value, $querymode ) {
		if ( $value == '' ) {
			$this->addError( wfMsg( 'smw_novalues' ) );
			if ( $querymode ) {
				return new SMWThingDescription();
			} else {
				return;
			}
		}

		if ( $querymode ) {
			$subdescriptions = array();
		} else {
			$semanticData = new SMWContainerSemanticData();
		}

		$types = $this->getTypeValues();
		$values = preg_split( '/[\s]*;[\s]*/u', trim( $value ) );
		$vi = 0; // index in value array
		$empty = true;
		for ( $i = 0; $i < max( 5, count( $types ) ); $i++ ) { // iterate over slots

			if ( $querymode ) { // special handling for supporting query parsing
				$comparator = SMW_CMP_EQ;
				SMWDataValue::prepareValue( $values[$vi], $comparator );
			}

			// generating the DVs:
			if ( ( count( $values ) > $vi ) &&
			     ( ( $values[$vi] == '' ) || ( $values[$vi] == '?' ) ) ) { // explicit omission
				$vi++;
			} elseif ( array_key_exists( $vi, $values ) && array_key_exists( $i, $types ) ) { // some values left, try next slot
				$dataValue = SMWDataValueFactory::newTypeObjectValue( $types[$i], $values[$vi] );
				if ( $dataValue->isValid() ) { // valid DV: keep
					if ( $querymode ) {
						$subdescriptions[] = new SMWRecordFieldDescription( $i, new SMWValueDescription( $dataValue->getDataItem(), $comparator ) );
					} else {
						$property = new SMWDIProperty( '_' . ( $i + 1 ) );
						$semanticData->addPropertyObjectValue( $property, $dataValue->getDataItem() );
					}
					$vi++;
					$empty = false;
				} elseif ( ( count( $values ) - $vi ) == ( count( $types ) - $i ) ) {
					// too many errors: keep this one to have enough slots left
					if ( !$querymode ) {
						$property = new SMWDIProperty( '_' . ( $i + 1 ) );
						$semanticData->addPropertyObjectValue( $property, $dataValue->getDataItem() );
					}
					$this->addError( $dataValue->getErrors() );
					$vi++;
				}
			}
		}

		if ( $empty ) {
			$this->addError( wfMsg( 'smw_novalues' ) );
		}

		if ( $querymode ) {
			return $empty ? new SMWThingDescription() : new SMWRecordDescription( $subdescriptions );
		} else {
			$this->m_dataitem = new SMWDIContainer( $semanticData, $this->m_typeid );
		}
	}

	/**
	 * @see SMWDataValue::setDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	public function setDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_CONTAINER ) {
			$this->m_dataitem = $dataItem;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * This function resembles SMWContainerValue::parseDBkeys() but it already unstubs
	 * the values instead of passing on initialisation strings. This is required since
	 * the datatype of each entry is not determined by the property here (since we are
	 * using generic _1, _2, ... properties that can have any type).
	 */
	protected function parseDBkeys( $args ) {
		$this->m_data->clear();
		$types = $this->getTypeValues();
		if ( count( $args ) > 0 ) {
			foreach ( reset( $args ) as $value ) {
				if ( is_array( $value ) && ( count( $value ) == 2 ) ) {
					$property = new SMWDIProperty( reset( $value ) );
					$pnum = intval( substr( reset( $value ), 1 ) ); // try to find the number of this property
					if ( array_key_exists( $pnum - 1, $types ) ) {
						$dv = SMWDataValueFactory::newTypeObjectValue( $types[$pnum - 1] );
						$dv->setDBkeys( end( $value ) );
						$this->m_data->addPropertyObjectValue( $property, $dv );
					}
				}
			}
		}
	}

	/**
	 * Overwrite SMWDataValue::getQueryDescription() to be able to process
	 * comparators between all values.
	 */
	public function getQueryDescription( $value ) {
		return $this->parseUserValueOrQuery( $value, true );
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
	protected function checkAllowedValues() { }

	/**
	 * Make sure that the content is reset in this case.
	 * @todo This is not a full reset yet (the case that property is changed after a value
	 * was set does not occur in the normal flow of things, hence this has low priority).
	 */
	public function setProperty( SMWDIProperty $property ) {
		parent::setProperty( $property );
		$this->m_typevalues = null;
	}

	/**
	 * @todo Since containers are always exported in a similar fashion, it
	 * would be preferrable to have their export controlled where it happens,
	 * and minimize the below special code.
	 */
	public function getExportData() {
		if ( !$this->isValid() ) return null;

		$result = new SMWExpData( new SMWExpResource( '', $this ) ); // bnode
		$ed = new SMWExpData( SMWExporter::getSpecialNsResource( 'swivt', 'Container' ) );
		$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ), $ed );
		$count = 0;
		foreach ( $this->getDVs() as $value ) {
			$count++;
			if ( ( $value === null ) || ( !$value->isValid() ) ) {
				continue;
			}
			if ( ( $value->getTypeID() == '_wpg' ) || ( $value->getTypeID() == '_uri' ) || ( $value->getTypeID() == '_ema' ) ) {
				$result->addPropertyObjectValue(
				      SMWExporter::getSpecialNsResource( 'swivt', 'object' . $count ),
				      $value->getExportData() );
			} else {
				$result->addPropertyObjectValue(
				      SMWExporter::getSpecialNsResource( 'swivt', 'value' . $count ),
				      $value->getExportData() );
			}
		}
		return $result;
	}

////// Additional API for value lists

	/**
	 * Create a list (array with numeric keys) containing the datavalue
	 * objects that this SMWRecordValue object holds. Values that are not
	 * present are set to null. Note that the first index in the array is
	 * 0, not 1.
	 *
	 * @todo This method should be renamed to getDataItems().
	 * @return array of SMWDataItem
	 */
	public function getDVs() {
		if ( !$this->isValid() ) return array();
		$result = array();
		$semanticData = $this->m_dataitem->getSemanticData();
		foreach ( $semanticData->getProperties() as $prop ) {
			$propname = $prop->getPropertyID();
			$propnum = substr( $propname, 1 );
			if ( ( $propname != false ) && ( is_numeric( $propnum ) ) ) {
				$propertyvalues = $semanticData->getPropertyValues( $prop ); // combining this with next line violates PHP strict standards 
				$result[( $propnum - 1 )] = reset( $propertyvalues );
			}
		}
		return $result;
	}

	/**
	 * Return the array (list) of datatypes that the individual entries of
	 * this datatype consist of.
	 * 
	 * @todo Add some check to account for maximal number of list entries
	 * (maybe this should go to a variant of the SMWTypesValue).
	 * @todo I18N for error message.
	 * @return array of SMWTypesValue
	 */
	public function getTypeValues() {
		if ( $this->m_typevalues === null ) {
			$this->m_typevalues = self::findTypeValues( $this->m_property );
			if ( count( $this->m_typevalues ) == 0 ) { //TODO internalionalize
				$this->addError( 'List type not properly specified for this property.' );
			}
		}

		return $this->m_typevalues;
	}

	/**
	 * Return the array (list) of datatypes that the individual entries of
	 * this datatype consist of.
	 *
	 * @param $diProperty SMWDIProperty object for which to retrieve the types
	 * @return array of SMWTypesValue
	 */
	public static function findTypeValues( $diProperty ) {
		if ( $diProperty !== null ) {
			$propertyDiWikiPage = $diProperty->getDiWikiPage();
		}

		if ( ( $diProperty === null ) || ( $propertyDiWikiPage === null ) ) {
			return array(); // no property known -> no types
		} else { // query for type values
			$listDiProperty = new SMWDIProperty( '_LIST' );
			$dataitems = smwfGetStore()->getPropertyValues( $propertyDiWikiPage, $listDiProperty );
			if ( count( $dataitems ) == 1 ) {
				$typeListValue = new SMWTypeListValue( '__tls' );
				$typeListValue->setDataItem( reset( $dataitems ) );
				return $typeListValue->getTypeValues();
			} else {
				return array();
			}
		}
	}

	/**
	 * Return the array (list) of datatype ID that the individual entries
	 * of this datatype consist of.
	 *
	 * @note The architecture of Records and their types is flawed and needs
	 * improvement. The below code duplicates internals of SMWTypeListValue,
	 * but we do not care about this now.
	 * @param $diProperty SMWDIProperty object for which to retrieve the types
	 * @return array of string
	 */
	public static function findTypeIds( $diProperty ) {
		if ( $diProperty !== null ) {
			$propertyDiWikiPage = $diProperty->getDiWikiPage();
		}

		if ( ( $diProperty === null ) || ( $propertyDiWikiPage === null ) ) {
			return array(); // no property known -> no types
		} else { // query for type values
			$listDiProperty = new SMWDIProperty( '_LIST' );
			$dataitems = smwfGetStore()->getPropertyValues( $propertyDiWikiPage, $listDiProperty );
			if ( count( $dataitems ) == 1 ) {
				return explode( ';', reset( $dataitems )->getString() );
			} else {
				return array();
			}
		}
	}

////// Internal helper functions

	protected function makeOutputText( $type = 0, $linker = null ) {
		if ( !$this->isValid() ) {
			return ( ( $type == 0 ) || ( $type == 1 ) ) ? '' : $this->getErrorText();
		}

		$result = '';
		for ( $i = 0; $i < count( $this->getTypeValues() ); $i++ ) {
			if ( $i == 1 ) {
				$result .= ( $type == 4 ) ? '; ' : ' (';
			} elseif ( $i > 1 ) {
				$result .= ( $type == 4 ) ? '; ' : ', ';
			}
			$property = new SMWDIProperty( '_' . ( $i + 1 ) );
			$propertyvalues = $this->m_dataitem->getSemanticData()->getPropertyValues( $property ); // combining this with next line violates PHP strict standards 
			$dataItem = reset( $propertyvalues );
			if ( $dataItem !== false ) {
				$dataValue = SMWDataValueFactory::newDataItemValue( $dataItem );
				$result .= $this->makeValueOutputText( $type, $dataValue, $linker );
			} else {
				$result .= '?';
			}
		}
		if ( ( $i > 1 ) && ( $type != 4 ) ) $result .= ')';

		return $result;
	}

	protected function makeValueOutputText( $type, $dataValue, $linker ) {
		switch ( $type ) {
			case 0: return $dataValue->getShortWikiText( $linker );
			case 1: return $dataValue->getShortHTMLText( $linker );
			case 2: return $dataValue->getShortWikiText( $linker );
			case 3: return $dataValue->getShortHTMLText( $linker );
			case 4: return $dataValue->getWikiValue();
		}
	}

	public function getDBkeys() {
		return array();// no longer used
	}
}

