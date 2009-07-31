<?php
/**
 * This file contains basic classes for representing (query) descriptions in
 * the SMW API.
 * @file
 * @ingroup SMWQuery
 * @author Markus Krötzsch
 */

/**
 * Container class for request for printout, as used in queries to
 * obtain additional information for the retrieved results.
 * @ingroup SMWQuery
 */
class SMWPrintRequest {
	/// Query mode to print all direct categories of the current element.
	const PRINT_CATS = 0;
	/// Query mode to print all property values of a certain attribute of the current element.
	const PRINT_PROP = 1;
	/// Query mode to print the current element (page in result set).
	const PRINT_THIS = 2;
	/// Query mode to print whether current element is in given category (Boolean printout).
	const PRINT_CCAT = 3;

	protected $m_mode; // type of print request
	protected $m_label; // string for labelling results, contains no markup
	protected $m_data; // data entries specifyin gwhat was requested (mixed type)
	protected $m_typeid = false; // id of the datatype of the printed objects, if applicable
	protected $m_outputformat; // output format string for formatting results, if applicable
	protected $m_hash = false; // cache your hash (currently useful since SMWQueryResult accesses the hash many times, might be dropped at some point)

	/**
	 * Create a print request.
	 * @param $mode a constant defining what to printout
	 * @param $label the string label to describe this printout
	 * @param $data optional data for specifying some request, might be a property object, title, or something else; interpretation depends on $mode
	 * @param $outputformat optional string for specifying an output format, e.g. an output unit
	 */
	public function __construct($mode, $label, $data = NULL, $outputformat = false) {
		$this->m_mode = $mode;
		$this->m_label = $label;
		$this->m_data = $data;
		$this->m_outputformat = $outputformat;
		if ( ($mode == SMWPrintRequest::PRINT_CCAT) && ($outputformat == false) ) {
			$this->m_outputformat = 'x'; // changed default for Boolean case
		}
		if ($this->m_data instanceof SMWDataValue) {
			$this->m_data->setCaption($label);
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
			case SMWPrintRequest::PRINT_CATS:
				return htmlspecialchars($this->m_label); // TODO: link to Special:Categories
			case SMWPrintRequest::PRINT_CCAT:
				return $linker->makeLinkObj($this->m_data, htmlspecialchars($this->m_label));
			case SMWPrintRequest::PRINT_PROP:
				return $this->m_data->getShortHTMLText($linker);
			case SMWPrintRequest::PRINT_THIS: default: return htmlspecialchars($this->m_label);
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
				case SMWPrintRequest::PRINT_CATS:
					return $this->m_label; // TODO: link to Special:Categories
				case SMWPrintRequest::PRINT_PROP:
					return $this->m_data->getShortWikiText($linked);
				case SMWPrintRequest::PRINT_CCAT:
				return '[[:' . $this->m_data->getPrefixedText() . '|' . $this->m_label . ']]';
				case SMWPrintRequest::PRINT_THIS: default: return $this->m_label;
			}
		}
	}

	/**
	 * Convenience method for accessing the text in either HTML or Wiki format.
	 */
	public function getText($outputmode, $linker = NULL) {
		switch ($outputmode) {
			case SMW_OUTPUT_WIKI: return $this->getWikiText($linker);
			case SMW_OUTPUT_HTML: case SMW_OUTPUT_FILE: default: return $this->getHTMLText($linker);
		}
	}

	/**
	 * Return additional data related to the print request. The result might be
	 * an object of class SMWPropertyValue or Title, or simply NULL if no data
	 * is required for the given type of printout.
	 */
	public function getData() {
		return $this->m_data;
	}

	public function getOutputFormat() {
		return $this->m_outputformat;
	}

	/**
	 * If this print request refers to some property, return the type id of this property.
	 * Otherwise return FALSE.
	 */
	public function getTypeID() {
		if ($this->m_typeid === false) {
			if ($this->m_mode == SMWPrintRequest::PRINT_PROP) {
				$this->m_typeid = $this->m_data->getPropertyTypeID();
			} else {
				$this->m_typeid = '_wpg'; // return objects might be titles, but anyway
			}
		}
		return $this->m_typeid;
	}

	/**
	 * Return a hash string that is used to eliminate duplicate
	 * print requests. The hash also includes the chosen label,
	 * so it is possible to print the same date with different
	 * labels.
	 */
	public function getHash() {
		if ($this->m_hash === false) {
			$this->m_hash = $this->m_mode . ':' . $this->m_label . ':';
			if ($this->m_data instanceof Title) {
				$this->m_hash .= $this->m_data->getPrefixedText() . ':';
			} elseif ($this->m_data instanceof SMWDataValue) {
				$this->m_hash .= $this->m_data->getHash() . ':';
			}
			$this->m_hash .= $this->m_outputformat . ':';
		}
		return $this->m_hash;
	}

	/**
	 * Serialise this object like print requests given in \#ask.
	 */
	public function getSerialisation() {
		switch ($this->m_mode) {
			case SMWPrintRequest::PRINT_CATS:
				global $wgContLang;
				$catlabel = $wgContLang->getNSText(NS_CATEGORY);
				$result = '?' . $catlabel;
				if ($this->m_label != $catlabel) {
					$result .= '=' . $this->m_label;
				}
				return $result;
			case SMWPrintRequest::PRINT_PROP: case SMWPrintRequest::PRINT_CCAT:
				if ($this->m_mode == SMWPrintRequest::PRINT_CCAT) {
					$printname = $this->m_data->getPrefixedText();
					$result = '?' . $printname;
					if ( $this->m_outputformat != 'x' ) {
						$result .= '#' . $this->m_outputformat;
					}
				} else {
					$printname = $this->m_data->getWikiValue();
					$result = '?' . $printname;
					if ( $this->m_outputformat != '' ) {
						$result .= '#' . $this->m_outputformat;
					}
				}
				if ( $printname != $this->m_label ) {
					$result .= '=' . $this->m_label;
				}
				return $result;
			case SMWPrintRequest::PRINT_THIS: default: return ''; // no current serialisation
		}
	}

	/**
	 * @deprecated Use SMWPrintRequest::getData(). This method will vanish in SMW 1.5.
	 */
	public function getTitle() {
		return ($this->m_data instanceof Title)?$this->m_data:NULL;
	}
}

/**
 * Abstract base class for all descriptions.
 * @ingroup SMWQuery
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
	 * Some descriptions have different syntax in property value positions. The
	 * parameter $asvalue specifies whether the serialisation should take that into
	 * account.
	 * Example: The SMWValueDescription [[Paris]] returns the single result "Paris"
	 * but can also be used as value in [[has location::Paris]] which is preferred
	 * over the canonical [[has location::\<q\>[[Paris]]\</q\>]].
	 */
	abstract public function getQueryString($asvalue = false);

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
	 * Report on query features used in description. Return values are (sums of)
	 * query feature constants such as SMW_PROPERTY_QUERY.
	 */
	public function getQueryFeatures() {
		return 0;
	}

	/**
	 * Recursively restrict query to a maximal size and depth as given.
	 * Returns a possibly changed description that should be used as a replacement.
	 * Reduce values of parameters to account for the returned descriptions size.
	 * Default implementation for non-nested descriptions of size 1.
	 * The parameter $log contains a list of all pruned conditions, updated when some
	 * description was reduced.
	 * @note Objects must not do changes on $this during pruning, since $this can be
	 * reused in multiple places of one or many queries. Make new objects to reflect
	 * changes!
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
 * @ingroup SMWQuery
 */
class SMWThingDescription extends SMWDescription {
	public function getQueryString($asvalue = false) {
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
 * Description of a single class as given by a wiki category, or of a disjunction
 * of such classes. Corresponds to (disjunctions of) atomic classes in OWL and
 * to (unions of) classes in RDF.
 * @ingroup SMWQuery
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

	public function getQueryString($asvalue = false) {
		$first = true;
		foreach ($this->m_titles as $cat) {
			if ($first) {
				$result = '[[' . $cat->getPrefixedText();
				$first = false;
			} else {
				$result .= '||' . $cat->getText();
			}
		}
		$result .= ']]';
		if ($asvalue) {
			return ' &lt;q&gt;' . $result . '&lt;/q&gt; ';
		} else {
			return $result;
		}
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

	public function getQueryFeatures() {
		if (count($this->m_titles) > 1) {
			return SMW_CATEGORY_QUERY | SMW_DISJUNCTION_QUERY;
		} else {
			return SMW_CATEGORY_QUERY;
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
 * Description of a single class as described by a concept page in the wiki. Corresponds to
 * classes in (the EL fragment of) OWL DL, and to some extent to tree-shaped queries in SPARQL.
 * @ingroup SMWQuery
 */
class SMWConceptDescription extends SMWDescription {
	protected $m_concept;

	public function __construct(Title $concept) {
		$this->m_concept = $concept;
	}

	public function getConcept() {
		return $this->m_concept;
	}

	public function getQueryString($asvalue = false) {
		$result = '[[' . $this->m_concept->getPrefixedText() . ']]';
		if ($asvalue) {
			return ' &lt;q&gt;' . $result . '&lt;/q&gt; ';
		} else {
			return $result;
		}
	}

	public function isSingleton() {
		return false;
	}

	public function getQueryFeatures() {
		return SMW_CONCEPT_QUERY;
	}

	///NOTE: getSize and getDepth /could/ query the store to find the real size
	/// of the concept. But it is not clear if this is desirable anyway, given that
	/// caching structures may be established for retrieving concepts more quickly.
	/// Inspecting those would require future requests to the store, and be very
	/// store specific.
}


/**
 * Description of all pages within a given wiki namespace,
 * given by a numerical constant.
 * Corresponds to a class restriction with a special class
 * that characterises the given namespace (or at least that
 * is how one could map this to OWL etc.).
 * @ingroup SMWQuery
 */
class SMWNamespaceDescription extends SMWDescription {
	protected $m_namespace;

	public function SMWNamespaceDescription($namespace) {
		$this->m_namespace = $namespace;
	}

	public function getNamespace() {
		return $this->m_namespace;
	}

	public function getQueryString($asvalue = false) {
		global $wgContLang;
		if ($asvalue) {
			return ' &lt;q&gt;[[' . $wgContLang->getNSText($this->m_namespace) . ':+]]&lt;/q&gt; ';;
		} else {
			return '[[' . $wgContLang->getNSText($this->m_namespace) . ':+]]';
		}
	}

	public function isSingleton() {
		return false;
	}

	public function getQueryFeatures() {
		return SMW_NAMESPACE_QUERY;
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
 * @ingroup SMWQuery
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

	public function getQueryString($asvalue = false) {
		if ($this->m_datavalue !== NULL) {
			switch ($this->m_comparator) {
				case SMW_CMP_LEQ:  $comparator = '<'; break;
				case SMW_CMP_GEQ:  $comparator = '>'; break;
				case SMW_CMP_NEQ:  $comparator = '!'; break;
				case SMW_CMP_LIKE: $comparator = '~'; break;
				default: case SMW_CMP_EQ:
					$comparator = '';
				break;
			}
			if ($asvalue) {
				return $comparator . $this->m_datavalue->getWikiValue();
			} else { // this only is possible for values of Type:Page
				return '[[' . $comparator . $this->m_datavalue->getWikiValue() . ']]';
			}
		} else {
			return $asvalue?'+':''; //the else case may result in an error here (query without proper condition)
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
 * @ingroup SMWQuery
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

	public function getQueryString($asvalue = false) {
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
 * @ingroup SMWQuery
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
			if ($description instanceof SMWConjunction) { // absorb sub-conjunctions
				foreach ($description->getDescriptions() as $subdesc) {
					$this->m_descriptions[] = $subdesc;
				}
			} else {
				$this->m_descriptions[] = $description;
			}
			// move print descriptions downwards
			///TODO: This may not be a good solution, since it does modify $description and since it does not react to future changes
			$this->m_printreqs = array_merge($this->m_printreqs, $description->getPrintRequests());
			$description->setPrintRequests(array());
		}
	}

	public function getQueryString($asvalue = false) {
		$result = '';
		foreach ($this->m_descriptions as $desc) {
			$result .= ($result?' ':'') . $desc->getQueryString(false);
		}
		if ($result == '') {
			return $asvalue?'+':'';
		} elseif ($asvalue) { // <q> not needed for stand-alone conjunctions (AND binds stronger than OR)
			return ' &lt;q&gt;' . $result . '&lt;/q&gt; ';
		} else {
			return $result;
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

	public function getQueryFeatures() {
		$result = SMW_CONJUNCTION_QUERY;
		foreach ($this->m_descriptions as $desc) {
			$result = $result | $desc->getQueryFeatures();
		}
		return $result;
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		if ($maxsize <= 0) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$prunelog = array();
		$newdepth = $maxdepth;
		$result = new SMWConjunction();
		foreach ($this->m_descriptions as $desc) {
			$restdepth = $maxdepth;
			$result->addDescription($desc->prune($maxsize, $restdepth, $prunelog));
			$newdepth = min($newdepth, $restdepth);
		}
		if (count($result->getDescriptions()) > 0) {
			$log = array_merge($log, $prunelog);
			$maxdepth = $newdepth;
			if (count($result->getDescriptions()) == 1) { // simplify unary conjunctions!
				$result = array_shift($result->getDescriptions());
			}
			$result->setPrintRequests($this->getPrintRequests());
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
 * @ingroup SMWQuery
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
			if ($description instanceof SMWClassDescription) { // combine class descriptions
				if ($this->m_classdesc === NULL) { // first class description
					$this->m_classdesc = $description;
					$this->m_descriptions[] = $description;
				} else {
					$this->m_classdesc->addDescription($description);
				}
			} elseif ($description instanceof SMWDisjunction) { // absorb sub-disjunctions
				foreach ($description->getDescriptions() as $subdesc) {
					$this->m_descriptions[] = $subdesc;
				}
			//} elseif ($description instanceof SMWSomeProperty) {
			   ///TODO: use subdisjunct. for multiple SMWSomeProperty descs with same property
			} else {
				$this->m_descriptions[] = $description;
			}
		}
		// move print descriptions downwards
		///TODO: This may not be a good solution, since it does modify $description and since it does not react to future cahges
		$this->m_printreqs = array_merge($this->m_printreqs, $description->getPrintRequests());
		$description->setPrintRequests(array());
	}

	public function getQueryString($asvalue = false) {
		if ($this->m_true) {
			return '+';
		}
		$result = '';
		$sep = $asvalue?'||':' OR ';
		foreach ($this->m_descriptions as $desc) {
			$subdesc = $desc->getQueryString($asvalue);
			if ($desc instanceof SMWSomeProperty) { // enclose in <q> for parsing
				if ($asvalue) {
					$subdesc = ' &lt;q&gt;[[' . $subdesc . ']]&lt;/q&gt; ';
				} else {
					$subdesc = ' &lt;q&gt;' . $subdesc . '&lt;/q&gt; ';
				}
			}
			$result .= ($result?$sep:'') . $subdesc;
		}
		if ($asvalue) {
			return $result;
		} else {
			return ' &lt;q&gt;' . $result . '&lt;/q&gt; ';
		}
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

	public function getQueryFeatures() {
		$result = SMW_DISJUNCTION_QUERY;
		foreach ($this->m_descriptions as $desc) {
			$result = $result | $desc->getQueryFeatures();
		}
		return $result;
	}

	public function prune(&$maxsize, &$maxdepth, &$log) {
		if ($maxsize <= 0) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		$prunelog = array();
		$newdepth = $maxdepth;
		$result = new SMWDisjunction();
		foreach ($this->m_descriptions as $desc) {
			$restdepth = $maxdepth;
			$result->addDescription($desc->prune($maxsize, $restdepth, $prunelog));
			$newdepth = min($newdepth, $restdepth);
		}
		if (count($result->getDescriptions()) > 0) {
			$log = array_merge($log, $prunelog);
			$maxdepth = $newdepth;
			if (count($result->getDescriptions()) == 1) { // simplify unary disjunctions!
				$result = array_shift($result->getDescriptions());
			}
			$result->setPrintRequests($this->getPrintRequests());
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
 * @ingroup SMWQuery
 */
class SMWSomeProperty extends SMWDescription {
	protected $m_description;
	protected $m_property;

	public function SMWSomeProperty(SMWPropertyValue $property, SMWDescription $description) {
		$this->m_property = $property;
		$this->m_description = $description;
	}

	public function getProperty() {
		return $this->m_property;
	}

	public function getDescription() {
		return $this->m_description;
	}

	public function getQueryString($asvalue = false) {
		$subdesc = $this->m_description->getQueryString(true);
		$sep = ($this->m_description instanceof SMWSomeProperty)?'.':'::'; // use property chain syntax
		if ($asvalue) {
			return $this->m_property->getWikiValue() . $sep . $subdesc;
		} else {
			return '[[' . $this->m_property->getWikiValue() . $sep . $subdesc . ']]';
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

	public function getQueryFeatures() {
		return SMW_PROPERTY_QUERY | $this->m_description->getQueryFeatures();
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
