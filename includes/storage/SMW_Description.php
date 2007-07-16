<?php
/**
 * This file contains basic classes for representing (query) descriptions in
 * the SMW API.
 *
 * @author Markus Krötzsch
 */

// comparators for datavalues:
define('SMW_CMP_EQ',1); // matches only datavalues that are equal to the given value
define('SMW_CMP_LEQ',2); // matches only datavalues that are less or equal than the given value
define('SMW_CMP_GEQ',3); // matches only datavalues that are greater or equal to the given value
define('SMW_CMP_NEQ',4); // matches only datavalues that are unequal to the given value

// print request
define('SMW_PRINT_CATS', 0);  // print all direct cateories of the current element
define('SMW_PRINT_PROP', 1);  // print all property values of a certain attribute of the current element
define('SMW_PRINT_THIS', 2);  // print the current element


/**
 * Container class for request for printout, as used in queries to
 * obtain additional information for the retrieved results.
 */
class SMWPrintRequest {
	protected $m_mode; // type of print request
	protected $m_label; // string for labelling results, contains no markup
	protected $m_title; // title object to which print request refers (if any)
	protected $m_typeid = false; // id of the datatype of the printed objects, if applicable
	protected $m_outputformat; // output format string for formatting results, if applicable

	/**
	 * Create a print request.
	 * @param $mode a constant defining what to printout
	 * @param $label the string label to describe this printout
	 * @param $title optional Title object that specifies the request (usually a relation or attribute)
	 * @param $datavalue optional SMWDataValue container that sets parameters for printing data values (e.g. the unit)
	 */
	public function SMWPrintRequest($mode, $label, Title $title = NULL, $outputformat = '') {
		$this->m_mode = $mode;
		$this->m_label = $label;
		$this->m_title = $title;
		$this->m_outputformat = $outputformat;
	}
	
	public function getMode() {
		return $this->m_mode;
	}

	public function getLabel() {
		return $this->m_label;
	}

	/**
	 * Obtain an HTML-formatted representation of the label.
	 * The $linker is a Linker object used for generating hyperlinks.
	 * If it is NULL, no links will be created.
	 */
	public function getHTMLText($linker = NULL) {
		if ( ($linker === NULL) || ($this->m_label == '') ) {
			return htmlspecialchars($this->m_label);
		}
		switch ($this->m_mode) {
			case SMW_PRINT_CATS: return htmlspecialchars($this->m_label); // TODO: link to Special:Categories
			case SMW_PRINT_PROP: return $linker->makeLinkObj($this->m_title, htmlspecialchars($this->m_label));
			case SMW_PRINT_THIS: default: return htmlspecialchars($this->m_label);
		}
		
	}

	/**
	 * Obtain a Wiki-formatted representation of the label.
	 */
	public function getWikiText($linked = false) {
		if ( ($linked === NULL) || ($linked === false) || ($this->m_label == '') ) {
			return $this->m_label;
		} else {
			switch ($this->m_mode) {
				case SMW_PRINT_CATS: return $this->m_label; // TODO: link to Special:Categories
				case SMW_PRINT_PROP:
					return '[[' . $this->m_title->getPrefixedText() . '|' . $this->m_label . ']]';
				case SMW_PRINT_THIS: default: return $this->m_label;
			}
		}
		
	}

	public function getTitle() {
		return $this->m_title;
	}

	public function getOutputFormat() {
		return $this->m_outputformat;
	}

	public function getTypeID() {
		if ($this->m_typeid === false) {
			if ($this->m_mode == SMW_PRINT_PROP) {
				$this->m_typeid = SMWDataValueFactory::getPropertyObjectTypeID($this->m_title);
			} else {
				$this->m_typeid = '_wpg'; // return objects might be titles, but anyway
			}
		}
		return $this->m_typeid;
	}

	/**
	 * Return a hash string that is used to eliminate duplicate
	 * print requests.
	 */
	public function getHash() {
		$hash = $this->m_mode . ':';
		if ($this->m_title !== NULL) {
			$hash .= $this->m_title->getPrefixedText() . ':';
		}
		$hash .= $this->m_outputformat . ':';
		return $hash;
	}
}

/**
 * Abstract base class for all descriptions.
 */
abstract class SMWDescription {
	protected $m_printreqs = array();
	// add code for managing printouts, including iteration

	/**
	 * Get the (possibly empty) array of all print requests that
	 * exist for the entities that fit this description.
	 */
	public function getPrintRequests() {
		return $this->m_printreqs;
	}

	/**
	 * Set the array of print requests completely.
	 */
	public function setPrintRequests($printrequests) {
		$this->m_printreqs = $printrequests;
	}

	public function addPrintRequest(SMWPrintRequest $printrequest) {
		$this->m_printreqs[$printrequest->getHash()] = $printrequest;
	}

	/**
	 * Add a new print request, but at the beginning of the list of requests
	 * (thus it will be printed first).
	 */
	public function prependPrintRequest(SMWPrintRequest $printrequest) {
		$this->m_printreqs = array_merge(array($printrequest->getHash() => $printrequest), $this->m_printreqs);
	}

	/**
	 * Return a string expressing this query.
	 */
	abstract public function getQueryString();

	/**
	 * Return true if the description is required to encompass at most a single
	 * result, independently of the knowledge base.
	 */
	abstract public function isSingleton();

	/**
	 * Compute the size of the decription. Default is 1.
	 */
	public function getSize() {
		return 1;
	}

	/**
	 * Compute the depth of the decription. Default is 0.
	 */
	public function getDepth() {
		return 0;
	}

	/**
	 * Recursively restrict query to a maximal size and depth as given.
	 * Returns a possibly changed description that should be used as a replacement.
	 * Reduce values of parameters to account for the returned descriptions size.
	 * Default implementation for non-nested descriptions of size 1.
	 * The parameter $log contains a list of all pruned conditions, updated when some
	 * description was reduced.
	 * NOTE: objects must not do changes on $this during pruning, since $this can be
	 * reused in multiple places of one or many queries. Make new objects to reflect
	 * changes.
	 */
	public function prune(&$maxsize, &$maxdepth, &$log) {
		if ( ($maxsize < $this->getSize()) || ($maxdepth < $this->getDepth()) ) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		} else {
			$maxsize = $maxsize - $this->getSize();
			$maxdepth = $maxdepth - $this->getDepth();
			return $this;
		}
	}

}

/**
 * A dummy description that describes any object. Corresponds to
 * owl:thing, the class of all abstract objects. Note that it is
 * not used for datavalues of attributes in order to support type 
 * hinting in the API: descriptions of data are always 
 * SMWValueDescription objects.
 */
class SMWThingDescription extends SMWDescription {
	public function getQueryString() {
		return '+';
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		return 0; // no real condition, no size or depth
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		return $this;
	}
}

/**
 * Description of a single class, i.e. a wiki category.
 * Corresponds to atomic concepts in OWL and to classes in RDF.
 */
class SMWClassDescription extends SMWDescription {
	protected $m_title;

	public function SMWClassDescription(Title $category) {
		$this->m_title = $category;
	}

	public function getCategory() {
		return $this->m_title;
	}

	public function getQueryString() {
		if ($this->m_title !== NULL) {
			return '[[' . $this->m_title->getPrefixedText() . ']]';
		} else {
			return '';
		}
	}

	public function isSingleton() {
		return false;
	}

}

/**
 * Description of all pages within a given wiki namespace,
 * given by a numerical constant.
 * Corresponds to a class restriction with a special class
 * that characterises the given namespace (or at least that
 * is how one could map this to OWL etc.).
 */
class SMWNamespaceDescription extends SMWDescription {
	protected $m_namespace;

	public function SMWNamespaceDescription($namespace) {
		$this->m_namespace = $namespace;
	}

	public function getNamespace() {
		return $this->m_namespace;
	}

	public function getQueryString() {
		global $wgContLang;
		return '[[' . $wgContLang->getNSText($this->m_namespace) . ':+]]';
	}

	public function isSingleton() {
		return false;
	}

}

/**
 * Description of one data value, or of a range of data values.
 *
 * Technically this usually corresponds to unary concrete domain predicates
 * in OWL which are parametrised by one constant from the concrete domain.
 * In rare cases where SMW attributes represent object properties, this can
 * also be similar to a nominal class. In RDF, concrete domain predicates that
 * define ranges (like "greater or equal to") are not directly available.
 */
class SMWValueDescription extends SMWDescription {
	protected $m_datavalue;
	protected $m_comparator;

	public function SMWValueDescription(SMWDataValue $datavalue, $comparator = SMW_CMP_EQ) {
		$this->m_datavalue = $datavalue;
		$this->m_comparator = $comparator;
	}

	public function getDataValue() {
		return $this->m_datavalue;
	}

	public function getComparator() {
		return $this->m_comparator;
	}

	public function getQueryString() {
		if ($this->m_datavalue !== NULL) {
			switch ($this->m_comparator) {
				case SMW_CMP_LEQ:
					$comparator = '<';
				break;
				case SMW_CMP_GEQ:
					$comparator = '>';
				break;
				case SMW_CMP_NEQ: 
					$comparator = '!'; // not supported yet?
				break;
				default: case SMW_CMP_EQ: 
					$comparator = '';
				break;
			}
			return $comparator . $this->m_datavalue->getWikiValue();
		} else {
			return '+';
		}
	}

	public function isSingleton() {
		return false;
	}
	
	public function getSize() {
		return 1;
	}

}

/**
 * Description of a collection of many descriptions, all of which
 * must be satisfied (AND, conjunction).
 *
 * Corresponds to conjunction in OWL and SPARQL. Not available in RDFS.
 */
class SMWConjunction extends SMWDescription {
	protected $m_descriptions;

	public function SMWConjunction($descriptions = array()) {
		$this->m_descriptions = $descriptions;
	}

	public function getDescriptions() {
		return $this->m_descriptions;
	}

	public function addDescription(SMWDescription $description) {
		if (! ($description instanceof SMWThingDescription) ) {
			$this->m_descriptions[] = $description;
		}
	}

	public function getQueryString() {
		$result = '';
		foreach ($this->m_descriptions as $desc) {
			$result .= $desc->getQueryString() . ' ';
		}
		if ($result == '') {
			return '+';
		} else {
			return ' &lt;q&gt;' . $result . '&lt;/q&gt;';
		}
	}

	public function isSingleton() {
		foreach ($this->m_descriptions as $d) {
			if ($d->isSingleton()) {
				return true;
			}
		}
		return false;
	}

	public function getSize() {
		$size = 0;
		foreach ($this->m_descriptions as $desc) {
			$size += $desc->getSize();
		}
		return $size;
	}

	public function getDepth() {
		$depth = 0;
		foreach ($this->m_descriptions as $desc) {
			$depth = max($depth, $desc->getDepth());
		}
		return $depth;
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		if ($maxsize <= 0) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$prunelog = array();
		$newdepth = $maxdepth;
		$result = new SMWConjunction();
		$result->setPrintRequests($this->getPrintRequests());
		foreach ($this->m_descriptions as $desc) {
			$restdepth = $maxdepth;
			$result->addDescription($desc->prune($maxsize, $restdepth, $prunelog));
			$newdepth = min($newdepth, $restdepth);
		}
		if (count($result->getDescriptions()) > 0) {
			$log = array_merge($log, $prunelog);
			$maxdepth = $newdepth;
			return $result;
		} else {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
	}
}

/**
 * Description of a collection of many descriptions, at least one of which
 * must be satisfied (OR, disjunction).
 *
 * Corresponds to disjunction in OWL and SPARQL. Not available in RDFS.
 */
class SMWDisjunction extends SMWDescription {
	protected $m_descriptions;
	protected $m_true = false; // used if disjunction is trivially true already

	public function SMWDisjunction($descriptions = array()) {
		foreach ($descriptions as $desc) {
			$this->addDescription($desc);
		}
	}

	public function getDescriptions() {
		return $this->m_descriptions;
	}

	public function addDescription(SMWDescription $description) {
		if ($description instanceof SMWThingDescription) {
			$this->m_true = true;
			$this->m_descriptions = array(); // no conditions any more
		}
		if (!$this->m_true) {
			$this->m_descriptions[] = $description;
		}
	}

	public function getQueryString() {
		if ($this->m_true) {
			return '+';
		}
		$result = '';
		// TODO: many disjunctions have more suitable || abbreviations
		$first = true;
		foreach ($this->m_descriptions as $desc) {
			if ($first) {
				$first = false;
			} else {
				$result .= ' || ';
			}
			$result .= $desc->getQueryString();
		}
		return ' &lt;q&gt;' . $result . '&lt;/q&gt;';
	}

	public function isSingleton() {
		// NOTE: this neglects the case where several disjuncts describe the same object.
		// I think I cannot really make myself care about this issue ... -- mak
		if (count($this->m_descriptions) != 1) {
			return false;
		} else {
			return $this->m_descriptions[0]->isSingleton();
		}
	}

	public function getSize() {
		$size = 0;
		foreach ($this->m_descriptions as $desc) {
			$size += $desc->getSize();
		}
		return $size;
	}

	public function getDepth() {
		$depth = 0;
		foreach ($this->m_descriptions as $desc) {
			$depth = max($depth, $desc->getDepth());
		}
		return $depth;
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		if ($maxsize <= 0) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$prunelog = array();
		$newdepth = $maxdepth;
		$result = new SMWDisjunction();
		$result->setPrintRequests($this->getPrintRequests());
		foreach ($this->m_descriptions as $desc) {
			$restdepth = $maxdepth;
			$result->addDescription($desc->prune($maxsize, $restdepth, $prunelog));
			$newdepth = min($newdepth, $restdepth);
		}
		if (count($result->getDescriptions()) > 0) {
			$log = array_merge($log, $prunelog);
			$maxdepth = $newdepth;
			return $result;
		} else {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
	}
}

/**
 * Description of a set of instances that have an attribute with some value that
 * fits another (sub)description.
 *
 * Corresponds to existential quatification ("some" restriction) on concrete properties
 * in OWL. In conjunctive queries (OWL) and SPARQL (RDF), it is represented by using 
 * variables in the object part of such properties.
 */
class SMWSomeProperty extends SMWDescription {
	protected $m_description;
	protected $m_property;

	public function SMWSomeProperty(Title $property, SMWDescription $description) {
		$this->m_property = $property;
		$this->m_description = $description;
	}

	public function getProperty() {
		return $this->m_property;
	}

	public function getDescription() {
		return $this->m_description;
	}

	public function getQueryString() {
		return '[[' . $this->m_property->getText() . ':=' . $this->m_description->getQueryString() . ']]';
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		return 1+$this->getDescription()->getSize();
	}

	public function getDepth() {
		return 1+$this->getDescription()->getDepth();
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		if (($maxsize <= 0)||($maxdepth <= 0)) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$maxsize--;
		$maxdepth--;
		$result = new SMWSomeProperty($this->m_property, $this->m_description->prune($maxsize,$maxdepth,$log));
		$result->setPrintRequests($this->getPrintRequests());
		return $result;
	}
}




/**
 * Description of a class that contains exactly one explicitly given 
 * object.
 *
 * Corresponds to nominal concepts in OWL, and can be emulated for querying 
 * by using individuals directly in conjunctive queries (OWL) or SPARQL (RDF).
 */
class SMWNominalDescription extends SMWDescription {
	protected $m_title;

	public function SMWNominalDescription(Title $individual) {
		trigger_error("SMWNominalDescription is deprecated.", E_USER_NOTICE);
		$this->m_title = $individual;
	}

	public function getIndividual() {
		return $this->m_title;
	}

	public function getQueryString() {
		if ($this->m_title !== NULL) {
			return '[[:' . $this->m_title->getPrefixedText() . ']]';
		} else {
			return '';
		}
	}

	public function isSingleton() {
		return true;
	}
}

/**
 * Description of a set of instances that have a relation to at least one
 * element that fits another (sub)description.
 *
 * Corresponds to existential quatification ("some" restriction) on abstract properties in 
 * OWL. In conjunctive queries (OWL) and SPARQL (RDF), it is represented by using 
 * variables in the object part of such properties.
 */
class SMWSomeRelation extends SMWDescription {
	protected $m_description;
	protected $m_relation;

	public function SMWSomeRelation(Title $relation, SMWDescription $description) {
		trigger_error("SMWSomeRelation is deprecated.", E_USER_NOTICE);
		$this->m_relation = $relation;
		$this->m_description = $description;
	}

	public function getRelation() {
		return $this->m_relation;
	}

	public function getDescription() {
		return $this->m_description;
	}

	public function getQueryString() {
		if ($this->m_description instanceof SMWThingDescription) {
			return '[[' . $this->m_relation->getText() . '::+]]';
		} else {
			return '[[' . $this->m_relation->getText() . ':: &lt;q&gt;' . $this->m_description->getQueryString() . '&lt;/q&gt;]]';
		}
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		return 1+$this->getDescription()->getSize();
	}

	public function getDepth() {
		return 1+$this->getDescription()->getDepth();
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		if (($maxsize <= 0)||($maxdepth <= 0)) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$maxsize--;
		$maxdepth--;
		$result = new SMWSomeRelation($this->getRelation(), $this->m_description->prune($maxsize,$maxdepth,$log));
		$result->setPrintRequests($this->getPrintRequests());
		return $result;
	}
}

/**
 * Description of a set of instances that have an attribute with some value that
 * fits another (sub)description.
 *
 * Corresponds to existential quatification ("some" restriction) on concrete properties
 * in OWL. In conjunctive queries (OWL) and SPARQL (RDF), it is represented by using 
 * variables in the object part of such properties.
 */
class SMWSomeAttribute extends SMWDescription {
	protected $m_description;
	protected $m_attribute;

	public function SMWSomeAttribute(Title $attribute, SMWDescription $description) {
		trigger_error("SMWSomeAttribute is deprecated.", E_USER_NOTICE);
		$this->m_attribute = $attribute;
		$this->m_description = $description;
	}

	public function getAttribute() {
		return $this->m_attribute;
	}

	public function getDescription() {
		return $this->m_description;
	}

	public function getQueryString() {
		return '[[' . $this->m_attribute->getText() . ':=' . $this->m_description->getQueryString() . ']]';
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		return 1+$this->getDescription()->getSize();
	}

	public function getDepth() {
		return 1+$this->getDescription()->getDepth();
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		if (($maxsize <= 0)||($maxdepth <= 0)) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$maxsize--;
		$maxdepth--;
		$result = new SMWSomeAttribute($this->getAttribute(), $this->m_description->prune($maxsize,$maxdepth,$log));
		$result->setPrintRequests($this->getPrintRequests());
		return $result;
	}
}


