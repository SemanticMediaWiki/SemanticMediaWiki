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
class SMWListValue extends SMWContainerValue {

	/// cache for datavalues of types belonging to this object
	private $m_typevalues = NULL;

	/// Should this DV operate on query syntax (special mode for parsing queries in a compatible fashion)
	private $m_querysyntax = false;
	/// Array of comparators as might be found in query strings (based on inputs like >, <, etc.)
	private $m_comparators;

	protected function parseUserValue($value) {
		$this->m_data->clear();
		$this->m_comparators = array(); // only for query mode
		if ($value == '') { /// TODO internalionalize
			$this->addError('No values specified.');
			return;
		}

		$types = $this->getTypeValues();
		$values = preg_split('/[\s]*;[\s]*/u', trim($value));
		$vi = 0; // index in value array
		$empty = true;
		for ($i = 0; $i < max(5,count($types)); $i++) { // iterate over slots
			// special handling for supporting query parsing
			if ($this->m_querysyntax) {
				$comparator = SMW_CMP_EQ;
				SMWQueryParser::prepareValue($values[$vi], $comparator);
			}
			// generating the DVs:
			if ( (count($values) > $vi) &&
			     ( ($values[$vi] == '') || ($values[$vi] == '?') ) ) { // explicit omission
				$vi++;
			} elseif (array_key_exists($vi,$values) && array_key_exists($i,$types)) { // some values left, try next slot
				$dv = SMWDataValueFactory::newTypeObjectValue($types[$i], $values[$vi]);
				if ($dv->isValid()) { // valid DV: keep
					$this->m_data->addPropertyObjectValue(SMWPropertyValue::makeProperty('_' . ($i+1)), $dv);
					$vi++;
					$empty = false;
					if ($this->m_querysyntax) { // keep comparator for later querying
						$this->m_comparators[$i] = $comparator;
					}
				} elseif ( (count($values)-$vi) == (count($types)-$i) ) {
					// too many errors: keep this one to have enough slots left
					$this->m_data->addPropertyObjectValue(SMWPropertyValue::makeProperty('_' . ($i+1)), $dv);
					$this->addError($dv->getErrors());
					$vi++;
				}
			}
		}
		if ($empty) { /// TODO internalionalize
			$this->addError('No values specified.');
		}
	}

	/**
	 * This function resembles SMWContainerValue::parseDBkeys() but it already unstubs
	 * the values instead of passing on initialisation strings. This is required since
	 * the datatype of each entry is not determined by the property here (since we are
	 * using generic _1, _2, ... properties that can have any type).
	 */
	protected function parseDBkeys($args) {
		$this->m_data->clear();
		$types = $this->getTypeValues();
		if (count($args)>0) {
			foreach (reset($args) as $value) {
				if (is_array($value) && (count($value)==2)) {
					$property = SMWPropertyValue::makeProperty(reset($value));
					$pnum = intval(substr(reset($value),1)); // try to find the number of this property
					if (array_key_exists($pnum-1,$types)) {
						$dv = SMWDataValueFactory::newTypeObjectValue( $types[$pnum-1] );
						$dv->setDBkeys(end($value));
						$this->m_data->addPropertyObjectValue($property, $dv);
					}
				}
			}
		}
	}

	public function getShortWikiText($linked = NULL) {
		if ($this->m_caption !== false) {
			return $this->m_caption;
		}
		return $this->makeOutputText(0, $linked);
	}

	public function getShortHTMLText($linker = NULL) {
		if ($this->m_caption !== false) {
			return $this->m_caption;
		}
		return $this->makeOutputText(1, $linker);
	}

	public function getLongWikiText($linked = NULL) {
		return $this->makeOutputText(2, $linked);
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->makeOutputText(3, $linker);
	}

	public function getWikiValue() {
		return $this->makeOutputText(4);
	}

	private function makeOutputText($type = 0, $linker = NULL) {
		if (!$this->isValid()) {
			return ( ($type == 0)||($type == 1) )? '' : $this->getErrorText();
		}
		$result = '';
		for ($i = 0; $i < count($this->getTypeValues()); $i++) {
			if ($i == 1) {
				$result .= ($type == 4)?'; ':' (';
			} elseif ($i > 1) {
				$result .= ($type == 4)?'; ':", ";
			}
			$property = SMWPropertyValue::makeProperty('_' . ($i+1));
			$dv = reset($this->m_data->getPropertyValues($property));
			$result .= ($dv !== false)? $this->makeValueOutputText($type, $dv, $linker): '?';
		}
		if ( ($i>1) && ($type != 4) ) $result .= ')';
		return $result;
	}

	private function makeValueOutputText($type, $datavalue, $linker) {
		switch ($type) {
			case 0: return $datavalue->getShortWikiText($linker);
			case 1: return $datavalue->getShortHTMLText($linker);
			case 2: return $datavalue->getShortWikiText($linker);
			case 3: return $datavalue->getShortHTMLText($linker);
			case 4: return $datavalue->getWikiValue();
		}
	}

	/// @todo Allowed values for multi-valued properties are not supported yet.
	protected function checkAllowedValues() {}

	/**
	 * Make sure that the content is reset in this case.
	 * @todo This is not a full reset yet (the case that property is changed after a value
	 * was set does not occur in the normal flow of things, hence thishas low priority).
	 */
	public function setProperty(SMWPropertyValue $property) {
		parent::setProperty($property);
		$this->m_typevalues = NULL;
	}

	public function getExportData() {
		if (!$this->isValid()) return NULL;

		$result = new SMWExpData(new SMWExpElement('', $this)); // bnode
		$ed = new SMWExpData(SMWExporter::getSpecialElement('swivt','Container'));
		$result->addPropertyObjectValue(SMWExporter::getSpecialElement('rdf','type'), $ed);
		$count = 0;
		foreach ($this->m_values as $value) {
			$count++;
			if ( ($value === NULL) || (!$value->isValid()) ) {
				continue;
			}
			if (($value->getTypeID() == '_wpg') || ($value->getTypeID() == '_uri') || ($value->getTypeID() == '_ema')) {
				$result->addPropertyObjectValue(
				      SMWExporter::getSpecialElement('swivt','object' . $count),
				      $value->getExportData());
			} else {
				$result->addPropertyObjectValue(
				      SMWExporter::getSpecialElement('swivt','value' . $count),
				      $value->getExportData());
			}
		}
		return $result;
	}

////// Custom functions for n-ary attributes

	/**
	 * Change to query syntax mode.
	 */
	public function acceptQuerySyntax() {
		$this->m_querysyntax = true;
	}

	/**
	 * Return the array (list) of datatypes that the individual entries of this datatype consist of.
	 * @todo Add some check to account for maximal number of list entries (maybe this should go to a
	 * variant of the SMWTypesValue).
	 */
	public function getTypeValues() {
		if ($this->m_typevalues !== NULL) return $this->m_typevalues; // local cache
		if ( ($this->m_property === NULL) || ($this->m_property->getWikiPageValue() === NULL) ) {
			$this->m_typevalues = array(); // no property known -> no types
		} else { // query for type values
			$typelist = smwfGetStore()->getPropertyValues($this->m_property->getWikiPageValue(), SMWPropertyValue::makeProperty('_LIST'));
			if (count($typelist) == 1) {
				$this->m_typevalues = reset($typelist)->getTypeValues();
			} else { ///TODO internalionalize
				$this->addError('List type not properly specified for this property.');
				$this->m_typevalues = array();
			}
		}
		return $this->m_typevalues;
	}

}

