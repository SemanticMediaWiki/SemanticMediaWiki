<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * Abstract datavalue class to implement a generic container for
 * complex values (internal objects) that do not have a single
 * value but a set of nested property-value pairs.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
abstract class SMWContainerValue extends SMWDataValue {

	protected $m_data;

	public function __construct( $typeid ) {
		parent::__construct( $typeid );
		$this->m_data = new SMWSemanticData( null );
	}

	/**
	 * We use the internal SMWSemanticData object to store some of this objects
	 * data. Clone it to make sure that data can be modified independelty from
	 * the original object's content.
	 */
	public function __clone() {
		$this->m_data = clone $this->m_data; // note that this is always set
	}

	/**
	 * Containers have one DB key, so the value of this function should be an array with one
	 * element. This one DB key should consist of an array of arbitrary length where each
	 * entry encodes one property-value pair. The pairs are encoded as arrays of size two
	 * that correspond to the input arguments of SMWSemanticData::addPropertyStubValue():
	 * a property DB key (string), and a value DB key array (array).
	 */
	protected function parseDBkeys( $args ) {
		$this->m_data->clear();
		if ( count( $args ) > 0 ) {
			foreach ( reset( $args ) as $value ) {
				if ( is_array( $value ) && ( count( $value ) == 2 ) ) {
					$this->m_data->addPropertyStubValue( reset( $value ), end( $value ) );
				}
			}
		}
	}

	/**
	 * Serialize data in the format described for parseDBkeys(). However, it is usually
	 * expected that callers are aware of containers (this is the main purpose of this
	 * abstract class) so they can use specific methods for accessing the data in a more
	 * convenient form that contains the (probably available) information about property
	 * and data *objects* (not just their plain strings).
	 */
	public function getDBkeys() {
		$data = array();
		foreach ( $this->m_data->getProperties() as $property ) {
			foreach ( $this->m_data->getPropertyValues( $property ) as $dv ) {
				$data[] = array( $property->getDBkey(), $dv->getDBkeys() );
			}
		}
		return array( $data );
	}

	public function getSignature() {
		return 'c';
	}

	public function getValueIndex() {
		return -1;
	}

	public function getLabelIndex() {
		return -1;
	}

	public function getHash() {
		if ( $this->isValid() ) {
			return $this->m_data->getHash();
		} else {
			return implode( "\t", $this->getErrors() );
		}
	}

	// Methods for parsing, serialisation, and display are not defined in this abstract class:
		// public function getShortWikiText($linked = null);
		// public function getShortHTMLText($linker = null);
		// public function getLongWikiText($linked = null);
		// public function getLongHTMLText($linker = null);
		// protected function parseUserValue($value);
		// public function getWikiValue();

	/**
	 * Return the stored data as a SMWSemanticData object. This is more conveniently to access than
	 * what getDBkeys() gives, but intended only for reading. It may not be safe to write to the returned
	 * object.
	 */
	public function getData() {
		return $this->m_data;
	}

}
