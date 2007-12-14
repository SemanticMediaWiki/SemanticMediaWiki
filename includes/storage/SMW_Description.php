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
define('SMW_PRINT_CCAT', 3);  // check whether current element is in given category


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
	 * @param $title optional Title object that specifies the request (usually some property)
	 * @param $datavalue optional SMWDataValue container that sets parameters for printing data values (e.g. the unit)
	 */
	public function SMWPrintRequest($mode, $label, Title $title = NULL, $outputformat = '') {
		$this->m_mode = $mode;
		$this->m_label = $label;
		$this->m_title = $title;
		$this->m_outputformat = $outputformat;
		if ( ($mode == SMW_PRINT_CCAT) && ($outputformat === '') ) {
			$this->m_outputformat = 'x'; // changed default for Boolean case
		}
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
			case SMW_PRINT_PROP: case SMW_PRINT_CCAT:
				return $linker->makeLinkObj($this->m_title, htmlspecialchars($this->m_label));
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
				case SMW_PRINT_PROP: case SMW_PRINT_CCAT:
					return '[[:' . $this->m_title->getPrefixedText() . '|' . $this->m_label . ']]';
				case SMW_PRINT_THIS: default: return $this->m_label;
			}
		}
	}

	public function getText($outputmode, $linker = NULL) {
		switch ($outputmode) {
			case SMW_OUTPUT_WIKI: return $this->getWikiText($linker);
			case SMW_OUTPUT_HTML: default: return $this->getHTMLText($linker);
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

	/**
	 * Serialise this object like print requests given in #ask.
	 */
	public function getSerialisation() {
		/// TODO: do not use "= label" if label is the default anyway
		switch ($this->m_mode) {
			case SMW_PRINT_CATS:
				global $wgContLang;
				$catlabel = $wgContLang->getNSText(NS_CATEGORY);
				$result = '?' . $catlabel;
				if ($this->m_label != $catlabel) {
					$result .= '=' . $this->m_label;
				}
				return $result;
			case SMW_PRINT_PROP: case SMW_PRINT_CCAT:
				if ($this->m_mode == SMW_PRINT_CCAT) {
					$result = '?' . $this->m_title->getPrefixedText();
					if ( $this->m_outputformat != 'x' ) {
						$result .= '#' . $this->m_outputformat;
					}
				} else {
					$result = '?' . $this->m_title->getText();
					if ( $this->m_outputformat != '' ) {
						$result .= '#' . $this->m_outputformat;
					}
				}
				if ( $this->m_title->getText() != $this->m_label ) {
					$result .= '=' . $this->m_label;
				}
				return $result;
			case SMW_PRINT_THIS: default: return ''; // no current serialisation
		}
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
			$result = new SMWThingDescription();
			$result->setPrintRequests($this->getPrintRequests());
			return $result;
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
 * Description of a single class, i.e. a wiki category, or of a disjunction
 * of such classes. Corresponds to (disjunctions of) atomic concepts in OWL and 
 * to (unions of) classes in RDF.
 */
class SMWClassDescription extends SMWDescription {
	protected $m_titles;

	public function SMWClassDescription($content) {
		if ($content instanceof Title) {
			$this->m_titles = array($content);
		} elseif (is_array($content)) {
			$this->m_titles = $content;
		}
	}

	public function addDescription(SMWClassDescription $description) {
		$this->m_titles = array_merge($this->m_titles, $description->getCategories());
	}

	public function getCategories() {
		return $this->m_titles;
	}

	public function getQueryString() {
		$first = true;
		foreach ($this->m_titles as $cat) {
			if ($first) {
				$result = '[[' . $cat->getPrefixedText();
				$first = false;
			} else {
				$result .= '||' . $cat->getText();
			}
		}
		return $result . ']]';
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		global $smwgQSubcategoryDepth;
		if ($smwgQSubcategoryDepth > 0) {
			return 1; // disj. of cats should not cause much effort if we compute cat-hierarchies anyway!
		} else {
			return count($this->m_titles);
		}
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		if ($maxsize >= $this->getSize()) {
			$maxsize = $maxsize - $this->getSize();
			return $this;
		} elseif ( $maxsize <= 0 ) {
			$log[] = $this->getQueryString();
			$result = new SMWThingDescription();
		} else {
			$result = new SMWClassDescription(array_slice($this->m_titles, 0, $maxsize));
			$rest = new SMWClassDescription(array_slice($this->m_titles, $maxsize));
			$log[] = $rest->getQueryString();
			$maxsize = 0;
		}
		$result->setPrintRequests($this->getPrintRequests());
		return $result;
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
 * Technically this usually corresponds to nominal predicates or to unary 
 * concrete domain predicates in OWL which are parametrised by one constant 
 * from the concrete domain.
 * In RDF, concrete domain predicates that define ranges (like "greater or 
 * equal to") are not directly available.
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
		if ($this->m_comparator == SMW_CMP_EQ) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getSize() {
		return 1;
	}

}


/**
 * Description of an ordered list of SMWDescription objects, used as
 * values for some n-ary property. NULL values are to be used for 
 * unspecifed values. Corresponds to the built-in support for n-ary 
 * properties, i.e. can be viewed as a macro in OWL and RDF.
 */
class SMWValueList extends SMWDescription {
	protected $m_descriptions;
	protected $m_size;

	public function SMWValueList($descriptions = array()) {
		$this->m_descriptions = array_values($descriptions);
		$this->m_size = count($descriptions);
	}

	public function getCount() {
		return $this->m_size;
	}

	public function getDescriptions() {
		return $this->m_descriptions;
	}

	public function setDescription($index, $description) {
		$this->m_descriptions[$index] = $description;
		if ($index >= $this->m_size) { // fill other places with NULL
			for ($i=$this->m_size; $i<$index; $i++) {
				$this->m_descriptions[$i] = NULL;
			}
			$this->m_size = $index+1;
		}
	}

	public function getDescription($index) {
		if ($index < $this->m_size) {
			return $this->m_descriptions[$index];
		} else {
			return NULL;
		}
	}

	public function getQueryString() {
		$result = '';
		$first = true;
		$nonempty = false;
		for ($i=0; $i<$this->m_size; $i++) {
			if ($first) {
				$first = false;
			} else {
				$result .= ';';
			}
			if ($this->m_descriptions[$i] !== NULL) {
				$nonempty = true;
				$result .= $this->m_descriptions[$i]->getQueryString();
			}
		}
		if (!$nonempty) {
			return '+';
		} else {
			return $result;
		}
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		$size = 1;
		foreach ($this->m_descriptions as $desc) {
			if ($desc !== NULL) {
				$size += $desc->getSize();
			}
		}
		return $size;
	}

	public function getDepth() {
		$depth = 0;
		foreach ($this->m_descriptions as $desc) {
			if ($desc !== NULL) {
				$depth = max($depth, $desc->getDepth());
			}
		}
		return $depth;
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		if ($maxsize <= 0) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$maxsize--;
		$prunelog = array();
		$newdepth = $maxdepth;
		$result = new SMWValueList();
		$result->setPrintRequests($this->getPrintRequests());
		for ($i=0; $i<$this->m_size; $i++) {
			if ($this->m_descriptions[$i] !== NULL) {
				$restdepth = $maxdepth;
				$result->setDescription($i, $this->m_descriptions[$i]->prune($maxsize, $restdepth, $prunelog));
				$newdepth = min($newdepth, $restdepth);
			} else {
				$result->setDescription($i, NULL);
			}
		}
		$log = array_merge($log, $prunelog);
		$maxdepth = $newdepth;
		return $result;
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
			// move print descriptions downwards
			///TODO: This may not be a good solution, since it does modify $description and since it does not react to future cahges
			$this->m_printreqs = array_merge($this->m_printreqs, $description->getPrintRequests());
			$description->setPrintRequests(array());
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
			$result = new SMWThingDescription();
			$result->setPrintRequests($this->getPrintRequests());
			return $result;
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
	protected $m_classdesc = NULL; // contains a single class description if any such disjunct was given;
	                               // disjunctive classes are aggregated therein
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
			$this->m_catdesc = NULL;
		}
		if (!$this->m_true) {
			if ($description instanceof SMWClassDescription) {
				if ($this->m_classdesc === NULL) { // first class description
					$this->m_classdesc = $description;
					$this->m_descriptions[] = $description;
				} else {
					$this->m_classdesc->addDescription($description);
				}
			} else {
				$this->m_descriptions[] = $description;
			}
		}
		// move print descriptions downwards
		///TODO: This may not be a good solution, since it does modify $description and since it does not react to future cahges
		$this->m_printreqs = array_merge($this->m_printreqs, $description->getPrintRequests());
		$description->setPrintRequests(array());
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
			$result = new SMWThingDescription();
			$result->setPrintRequests($this->getPrintRequests());
			return $result;
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
		return '[[' . $this->m_property->getText() . '::' . $this->m_description->getQueryString() . ']]';
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

