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
class SMWRecordValue extends SMWContainerValue {

	/// cache for datavalues of types belonging to this object
	private $m_typevalues = null;

	protected function parseUserValue( $value ) {
		$this->m_data->clear();
		$this->parseUserValueOrQuery( $value, false );
	}

	protected function parseUserValueOrQuery( $value, $querymode ) {
		if ( $value == '' ) {
			$this->addError( wfMsg( 'smw_novalues' ) );
			return $querymode ? new SMWThingDescription():$this->m_data;
		}

		$subdescriptions = array(); // only used for query mode
		$types = $this->getTypeValues();
		$values = preg_split( '/[\s]*;[\s]*/u', trim( $value ) );
		$vi = 0; // index in value array
		$empty = true;
		for ( $i = 0; $i < max( 5, count( $types ) ); $i++ ) { // iterate over slots
			// special handling for supporting query parsing
			if ( $querymode ) {
				$comparator = SMW_CMP_EQ;
				SMWDataValue::prepareValue( $values[$vi], $comparator );
			}
			// generating the DVs:
			if ( ( count( $values ) > $vi ) &&
			     ( ( $values[$vi] == '' ) || ( $values[$vi] == '?' ) ) ) { // explicit omission
				$vi++;
			} elseif ( array_key_exists( $vi, $values ) && array_key_exists( $i, $types ) ) { // some values left, try next slot
				$dv = SMWDataValueFactory::newTypeObjectValue( $types[$i], $values[$vi] );
				if ( $dv->isValid() ) { // valid DV: keep
					if ( $querymode ) {
						$subdescriptions[] = new SMWRecordFieldDescription( $i, new SMWValueDescription( $dv, $comparator ) );
					} else {
						$property = SMWPropertyValue::makeProperty( '_' . ( $i + 1 ) );
						$this->m_data->addPropertyObjectValue( $property, $dv );
					}
					$vi++;
					$empty = false;
				} elseif ( ( count( $values ) - $vi ) == ( count( $types ) - $i ) ) {
					// too many errors: keep this one to have enough slots left
					$this->m_data->addPropertyObjectValue( SMWPropertyValue::makeProperty( '_' . ( $i + 1 ) ), $dv );
					$this->addError( $dv->getErrors() );
					$vi++;
				}
			}
		}
		if ( $empty ) {
			$this->addError( wfMsg( 'smw_novalues' ) );
		}
		if ( $querymode ) {
			return $empty ? new SMWThingDescription():new SMWRecordDescription( $subdescriptions );
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
					$property = SMWPropertyValue::makeProperty( reset( $value ) );
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
	public function setProperty( SMWPropertyValue $property ) {
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

		$result = new SMWExpData( new SMWExpElement( '', $this ) ); // bnode
		$ed = new SMWExpData( SMWExporter::getSpecialElement( 'swivt', 'Container' ) );
		$result->addPropertyObjectValue( SMWExporter::getSpecialElement( 'rdf', 'type' ), $ed );
		$count = 0;
		foreach ( $this->getDVs() as $value ) {
			$count++;
			if ( ( $value === null ) || ( !$value->isValid() ) ) {
				continue;
			}
			if ( ( $value->getTypeID() == '_wpg' ) || ( $value->getTypeID() == '_uri' ) || ( $value->getTypeID() == '_ema' ) ) {
				$result->addPropertyObjectValue(
				      SMWExporter::getSpecialElement( 'swivt', 'object' . $count ),
				      $value->getExportData() );
			} else {
				$result->addPropertyObjectValue(
				      SMWExporter::getSpecialElement( 'swivt', 'value' . $count ),
				      $value->getExportData() );
			}
		}
		return $result;
	}

////// Additional API for value lists

	/**
	 * Create a list (array with numeric keys) containing the datavalue objects
	 * that this SMWRecordValue object holds. Values that are not present are
	 * set to null. Note that the first index in the array is 0, not 1.
	 */
	public function getDVs() {
		if ( !$this->isValid() ) return array();
		$result = array();
		foreach ( $this->m_data->getProperties() as $prop ) {
			$propname = $prop->getPropertyID();
			$propnum = substr( $propname, 1 );
			if ( ( $propname != false ) && ( is_numeric( $propnum ) ) ) {
				$result[( $propnum - 1 )] = reset( $this->m_data->getPropertyValues( $prop ) );
			}
		}
		return $result;
	}

	/**
	 * Return the array (list) of datatypes that the individual entries of this datatype consist of.
	 * @todo Add some check to account for maximal number of list entries (maybe this should go to a
	 * variant of the SMWTypesValue).
	 */
	public function getTypeValues() {
		if ( $this->m_typevalues !== null ) return $this->m_typevalues; // local cache
		if ( ( $this->m_property === null ) || ( $this->m_property->getWikiPageValue() === null ) ) {
			$this->m_typevalues = array(); // no property known -> no types
		} else { // query for type values
			$typelist = smwfGetStore()->getPropertyValues( $this->m_property->getWikiPageValue(), SMWPropertyValue::makeProperty( '_LIST' ) );
			if ( count( $typelist ) == 1 ) {
				$this->m_typevalues = reset( $typelist )->getTypeValues();
			} else { ///TODO internalionalize
				$this->addError( 'List type not properly specified for this property.' );
				$this->m_typevalues = array();
			}
		}
		return $this->m_typevalues;
	}

////// Internal helper functions

	private function makeOutputText( $type = 0, $linker = null ) {
		if ( !$this->isValid() ) {
			return ( ( $type == 0 ) || ( $type == 1 ) ) ? '' : $this->getErrorText();
		}
		$result = '';
		for ( $i = 0; $i < count( $this->getTypeValues() ); $i++ ) {
			if ( $i == 1 ) {
				$result .= ( $type == 4 ) ? '; ':' (';
			} elseif ( $i > 1 ) {
				$result .= ( $type == 4 ) ? '; ':", ";
			}
			$property = SMWPropertyValue::makeProperty( '_' . ( $i + 1 ) );
			$dv = reset( $this->m_data->getPropertyValues( $property ) );
			$result .= ( $dv !== false ) ? $this->makeValueOutputText( $type, $dv, $linker ): '?';
		}
		if ( ( $i > 1 ) && ( $type != 4 ) ) $result .= ')';
		return $result;
	}

	private function makeValueOutputText( $type, $datavalue, $linker ) {
		switch ( $type ) {
			case 0: return $datavalue->getShortWikiText( $linker );
			case 1: return $datavalue->getShortHTMLText( $linker );
			case 2: return $datavalue->getShortWikiText( $linker );
			case 3: return $datavalue->getShortHTMLText( $linker );
			case 4: return $datavalue->getWikiValue();
		}
	}

}

