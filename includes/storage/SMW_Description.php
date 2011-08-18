<?php
/**
 * This file contains basic classes for representing (query) descriptions in
 * the SMW API.
 *
 * @file
 * @ingroup SMWQuery
 *
 * @author Markus Krötzsch
 */


/**
 * Abstract base class for all descriptions.
 * @ingroup SMWQuery
 */
abstract class SMWDescription {

	/**
	 * @var array of SMWPrintRequest
	 */
	protected $m_printreqs = array();

	/**
	 * Get the (possibly empty) array of all print requests that
	 * exist for the entities that fit this description.
	 *
	 * @return array of SMWPrintRequest
	 */
	public function getPrintRequests() {
		return $this->m_printreqs;
	}

	/**
	 * Set the array of print requests completely.
	 *
	 * @param array of SMWPrintRequest $printrequests
	 */
	public function setPrintRequests( array $printrequests ) {
		$this->m_printreqs = $printrequests;
	}

	/**
	 * Add a single SMWPrintRequest.
	 *
	 * @param SMWPrintRequest $printrequest
	 */
	public function addPrintRequest( SMWPrintRequest $printrequest ) {
		$this->m_printreqs[] = $printrequest;
	}

	/**
	 * Add a new print request, but at the beginning of the list of requests
	 * (thus it will be printed first).
	 *
	 * @param SMWPrintRequest
	 */
	public function prependPrintRequest( SMWPrintRequest $printrequest ) {
		array_unshift( $this->m_printreqs, $printrequest );
	}

	/**
	 * Return a string expressing this query.
	 * Some descriptions have different syntax in property value positions. The
	 * parameter $asvalue specifies whether the serialisation should take that into
	 * account.
	 *
	 * Example: The SMWValueDescription [[Paris]] returns the single result "Paris"
	 * but can also be used as value in [[has location::Paris]] which is preferred
	 * over the canonical [[has location::\<q\>[[Paris]]\</q\>]].
	 *
	 * @param boolean $asvalue
	 * 
	 * @return string
	 */
	abstract public function getQueryString( $asvalue = false );

	/**
	 * Return true if the description is required to encompass at most a single
	 * result, independently of the knowledge base.
	 *
	 * @return boolean
	 */
	abstract public function isSingleton();

	/**
	 * Compute the size of the decription. Default is 1.
	 *
	 * @return integer
	 */
	public function getSize() {
		return 1;
	}

	/**
	 * Compute the depth of the decription. Default is 0.
	 *
	 * @return integer
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
	 * Determine the datatype of the values that are described by this object.
	 * Most descriptins can only describe wiki pages, so this is the default,
	 * but some descriptions may refer to other datatypes, and overwrite this
	 * function accordingly.
	 *
	 * @return string
	 */
// 	public function getTypeID() {
// 		return '_wpg';
// 	}

	/**
	 * Recursively restrict query to a maximal size and depth as given.
	 * Returns a possibly changed description that should be used as a replacement.
	 * Reduce values of parameters to account for the returned descriptions size.
	 * Default implementation for non-nested descriptions of size 1.
	 * The parameter $log contains a list of all pruned conditions, updated when some
	 * description was reduced.
	 *
	 * @note Objects must not do changes on $this during pruning, since $this can be
	 * reused in multiple places of one or many queries. Make new objects to reflect
	 * changes!
	 */
	public function prune( &$maxsize, &$maxdepth, &$log ) {
		if ( ( $maxsize < $this->getSize() ) || ( $maxdepth < $this->getDepth() ) ) {
			$log[] = $this->getQueryString();
			
			$result = new SMWThingDescription();
			$result->setPrintRequests( $this->getPrintRequests() );
			
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

	public function getQueryString( $asvalue = false ) {
		return $asvalue ? '+' : '';
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		return 0; // no real condition, no size or depth
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		return $this;
	}

	/**
	 * Return an empty type id since we cannot know the datatype of values that
	 * are described by this description. This type should not be relevant in
	 * any place, since description types are currently only necessary for
	 * processing an SMWSomeProperty object where the property does not specify
	 * the type.
	 */
// 	public function getTypeID() {
// 		return '';
// 	}
}

/**
 * Description of a single class as given by a wiki category, or of a
 * disjunction of such classes. Corresponds to (disjunctions of) atomic classes
 * in OWL and to (unions of) classes in RDF.
 * @ingroup SMWQuery
 */
class SMWClassDescription extends SMWDescription {
	
	/**
	 * @var array of SMWDIWikiPage
	 */
	protected $m_diWikiPages;

	/**
	 * Constructor.
	 * 
	 * @param mixed $content SMWDIWikiPage or array of SMWDIWikiPage
	 * 
	 * @throws Exception
	 */
	public function __construct( $content ) {
		if ( $content instanceof SMWDIWikiPage ) {
			$this->m_diWikiPages = array( $content );
		} elseif ( is_array( $content ) ) {
			$this->m_diWikiPages = $content;
		} else {
			throw new Exception( "SMWClassDescription::__construct(): parameter must be an SMWDIWikiPage object or an array of such objects." );
		}
	}

	/**
	 * @param SMWClassDescription $description
	 */
	public function addDescription( SMWClassDescription $description ) {
		$this->m_diWikiPages = array_merge( $this->m_diWikiPages, $description->getCategories() );
	}

	/**
	 * @return array of SMWDIWikiPage
	 */
	public function getCategories() {
		return $this->m_diWikiPages;
	}

	public function getQueryString( $asvalue = false ) {
		$first = true;
		foreach ( $this->m_diWikiPages as $wikiPage ) {
			$wikiValue = SMWDataValueFactory::newDataItemValue( $wikiPage, null );
			if ( $first ) {
				$result = '[[' . $wikiValue->getPrefixedText();
				$first = false;
			} else {
				$result .= '||' . $wikiValue->getText();
			}
		}
		
		$result .= ']]';
		
		if ( $asvalue ) {
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
		if ( $smwgQSubcategoryDepth > 0 ) {
			return 1; // disj. of cats should not cause much effort if we compute cat-hierarchies anyway!
		} else {
			return count( $this->m_diWikiPages );
		}
	}

	public function getQueryFeatures() {
		if ( count( $this->m_diWikiPages ) > 1 ) {
			return SMW_CATEGORY_QUERY | SMW_DISJUNCTION_QUERY;
		} else {
			return SMW_CATEGORY_QUERY;
		}
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		if ( $maxsize >= $this->getSize() ) {
			$maxsize = $maxsize - $this->getSize();
			return $this;
		} elseif ( $maxsize <= 0 ) {
			$log[] = $this->getQueryString();
			$result = new SMWThingDescription();
		} else {
			$result = new SMWClassDescription( array_slice( $this->m_diWikiPages, 0, $maxsize ) );
			$rest = new SMWClassDescription( array_slice( $this->m_diWikiPages, $maxsize ) );
			
			$log[] = $rest->getQueryString();
			$maxsize = 0;
		}
		
		$result->setPrintRequests( $this->getPrintRequests() );
		return $result;
	}

}


/**
 * Description of a single class as described by a concept page in the wiki.
 * Corresponds to classes in (the EL fragment of) OWL DL, and to some extent to
 * tree-shaped queries in SPARQL.
 * @ingroup SMWQuery
 */
class SMWConceptDescription extends SMWDescription {
	
	/**
	 * @var SMWDIWikiPage
	 */
	protected $m_concept;

	/**
	 * Constructor.
	 * 
	 * @param SMWDIWikiPage $concept
	 */
	public function __construct( SMWDIWikiPage $concept ) {
		$this->m_concept = $concept;
	}

	/**
	 * @return SMWDIWikiPage
	 */
	public function getConcept() {
		return $this->m_concept;
	}

	public function getQueryString( $asvalue = false ) {
		$pageValue = SMWDataValueFactory::newDataItemValue( $this->m_concept, null );
		$result = '[[' . $pageValue->getPrefixedText() . ']]';
		if ( $asvalue ) {
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
 * Description of all pages within a given wiki namespace, given by a numerical
 * constant. Corresponds to a class restriction with a special class that
 * characterises the given namespace (or at least that is how one could map
 * this to OWL etc.).
 * @ingroup SMWQuery
 */
class SMWNamespaceDescription extends SMWDescription {
	
	/**
	 * @var integer
	 */
	protected $m_namespace;

	/**
	 * Constructor.
	 * 
	 * @param integer $namespace The namespace index
	 */
	public function __construct( $namespace ) {
		$this->m_namespace = $namespace;
	}

	/**
	 * @return integer
	 */
	public function getNamespace() {
		return $this->m_namespace;
	}

	public function getQueryString( $asvalue = false ) {
		global $wgContLang;
		if ( $asvalue ) {
			return ' &lt;q&gt;[[' . $wgContLang->getNSText( $this->m_namespace ) . ':+]]&lt;/q&gt; ';
		} else {
			return '[[' . $wgContLang->getNSText( $this->m_namespace ) . ':+]]';
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
	
	protected $m_dataItem;
	protected $m_comparator;
	protected $m_property;

	public function __construct( SMWDataItem $dataItem, $property, $comparator = SMW_CMP_EQ ) {
		$this->m_dataItem = $dataItem;
		$this->m_comparator = $comparator;
		$this->m_property = $property;
	}

	/// @deprecated Use getDataItem() and SMWDataValueFactory::newDataItemValue() if needed. Vanishes before SMW 1.7
	public function getDataValue() {
		return $this->m_dataItem;
	}

	public function getDataItem() {
		return $this->m_dataItem;
	}

	public function getComparator() {
		return $this->m_comparator;
	}

	public function getQueryString( $asvalue = false ) {
		$comparator = SMWQueryLanguage::getStringForComparator( $this->m_comparator );
		$dataValue = SMWDataValueFactory::newDataItemValue( $this->m_dataItem, $this->m_property );
		if ( $asvalue ) {
			return $comparator . $dataValue->getWikiValue();
		} else { // this only is possible for values of Type:Page
			return '[[' . $comparator . $dataValue->getWikiValue() . ']]';
		}
	}

	public function isSingleton() {
		if ( $this->m_comparator == SMW_CMP_EQ ) {
			return true;
		} else {
			return false;
		}
	}

	public function getSize() {
		return 1;
	}

// 	public function getTypeID() {
// 		return $this->m_dataItem->getTypeID();
// 	}

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

	public function __construct( $descriptions = array() ) {
		$this->m_descriptions = $descriptions;
	}

	public function getDescriptions() {
		return $this->m_descriptions;
	}

	public function addDescription( SMWDescription $description ) {
		if ( ! ( $description instanceof SMWThingDescription ) ) {
			if ( $description instanceof SMWConjunction ) { // absorb sub-conjunctions
				foreach ( $description->getDescriptions() as $subdesc ) {
					$this->m_descriptions[] = $subdesc;
				}
			} else {
				$this->m_descriptions[] = $description;
			}
			
			// move print descriptions downwards
			///TODO: This may not be a good solution, since it does modify $description and since it does not react to future changes
			$this->m_printreqs = array_merge( $this->m_printreqs, $description->getPrintRequests() );
			$description->setPrintRequests( array() );
		}
	}

	public function getQueryString( $asvalue = false ) {
		$result = '';
		
		foreach ( $this->m_descriptions as $desc ) {
			$result .= ( $result ? ' ' : '' ) . $desc->getQueryString( false );
		}
		
		if ( $result == '' ) {
			return $asvalue ? '+' : '';
		} else { // <q> not needed for stand-alone conjunctions (AND binds stronger than OR)
			return $asvalue ? " &lt;q&gt;{$result}&lt;/q&gt; " : $result;
		}
	}

	public function isSingleton() {
		foreach ( $this->m_descriptions as $d ) {
			if ( $d->isSingleton() ) {
				return true;
			}
		}
		return false;
	}

	public function getSize() {
		$size = 0;

		foreach ( $this->m_descriptions as $desc ) {
			$size += $desc->getSize();
		}

		return $size;
	}

	public function getDepth() {
		$depth = 0;

		foreach ( $this->m_descriptions as $desc ) {
			$depth = max( $depth, $desc->getDepth() );
		}

		return $depth;
	}

// 	public function getTypeID() {
// 		if ( count( $this->m_descriptions ) > 0 ) { // all subdescriptions should have the same type!
// 			return reset( $this->m_descriptions )->getTypeID();
// 		} else {
// 			return ''; // unknown
// 		}
// 	}

	public function getQueryFeatures() {
		$result = SMW_CONJUNCTION_QUERY;

		foreach ( $this->m_descriptions as $desc ) {
			$result = $result | $desc->getQueryFeatures();
		}

		return $result;
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		if ( $maxsize <= 0 ) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}
		
		$prunelog = array();
		$newdepth = $maxdepth;
		$result = new SMWConjunction();
		
		foreach ( $this->m_descriptions as $desc ) {
			$restdepth = $maxdepth;
			$result->addDescription( $desc->prune( $maxsize, $restdepth, $prunelog ) );
			$newdepth = min( $newdepth, $restdepth );
		}
		
		if ( count( $result->getDescriptions() ) > 0 ) {
			$log = array_merge( $log, $prunelog );
			$maxdepth = $newdepth;
			
			if ( count( $result->getDescriptions() ) == 1 ) { // simplify unary conjunctions!
				$descriptions = $result->getDescriptions();
				$result = array_shift( $descriptions );
			}
			
			$result->setPrintRequests( $this->getPrintRequests() );
			
			return $result;
		} else {
			$log[] = $this->getQueryString();
			
			$result = new SMWThingDescription();
			$result->setPrintRequests( $this->getPrintRequests() );
			
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
	protected $m_classdesc = null; // contains a single class description if any such disjunct was given;
	                               // disjunctive classes are aggregated therein
	protected $m_true = false; // used if disjunction is trivially true already

	public function __construct( $descriptions = array() ) {
		foreach ( $descriptions as $desc ) {
			$this->addDescription( $desc );
		}
	}

	public function getDescriptions() {
		return $this->m_descriptions;
	}

	public function addDescription( SMWDescription $description ) {
		if ( $description instanceof SMWThingDescription ) {
			$this->m_true = true;
			$this->m_descriptions = array(); // no conditions any more
			$this->m_classdesc = null;
		}
		
		if ( !$this->m_true ) {
			if ( $description instanceof SMWClassDescription ) { // combine class descriptions
				if ( $this->m_classdesc === null ) { // first class description
					$this->m_classdesc = $description;
					$this->m_descriptions[] = $description;
				} else {
					$this->m_classdesc->addDescription( $description );
				}
			} elseif ( $description instanceof SMWDisjunction ) { // absorb sub-disjunctions
				foreach ( $description->getDescriptions() as $subdesc ) {
					$this->m_descriptions[] = $subdesc;
				}
			// } elseif ($description instanceof SMWSomeProperty) {
			   ///TODO: use subdisjunct. for multiple SMWSomeProperty descs with same property
			} else {
				$this->m_descriptions[] = $description;
			}
		}
		
		// move print descriptions downwards
		///TODO: This may not be a good solution, since it does modify $description and since it does not react to future cahges
		$this->m_printreqs = array_merge( $this->m_printreqs, $description->getPrintRequests() );
		$description->setPrintRequests( array() );
	}

	public function getQueryString( $asvalue = false ) {
		if ( $this->m_true ) {
			return '+';
		}
		
		$result = '';
		$sep = $asvalue ? '||':' OR ';
		
		foreach ( $this->m_descriptions as $desc ) {
			$subdesc = $desc->getQueryString( $asvalue );
			
			if ( $desc instanceof SMWSomeProperty ) { // enclose in <q> for parsing
				if ( $asvalue ) {
					$subdesc = ' &lt;q&gt;[[' . $subdesc . ']]&lt;/q&gt; ';
				} else {
					$subdesc = ' &lt;q&gt;' . $subdesc . '&lt;/q&gt; ';
				}
			}
			
			$result .= ( $result ? $sep:'' ) . $subdesc;
		}
		if ( $asvalue ) {
			return $result;
		} else {
			return ' &lt;q&gt;' . $result . '&lt;/q&gt; ';
		}
	}

	public function isSingleton() {
		/// NOTE: this neglects the unimportant case where several disjuncts describe the same object.
		if ( count( $this->m_descriptions ) != 1 ) {
			return false;
		} else {
			return $this->m_descriptions[0]->isSingleton();
		}
	}

	public function getSize() {
		$size = 0;

		foreach ( $this->m_descriptions as $desc ) {
			$size += $desc->getSize();
		}

		return $size;
	}

	public function getDepth() {
		$depth = 0;

		foreach ( $this->m_descriptions as $desc ) {
			$depth = max( $depth, $desc->getDepth() );
		}

		return $depth;
	}

// 	public function getTypeID() {
// 		if ( count( $this->m_descriptions ) > 0 ) { // all subdescriptions should have the same type!
// 			return reset( $this->m_descriptions )->getTypeID();
// 		} else {
// 			return ''; // unknown
// 		}
// 	}

	public function getQueryFeatures() {
		$result = SMW_DISJUNCTION_QUERY;

		foreach ( $this->m_descriptions as $desc ) {
			$result = $result | $desc->getQueryFeatures();
		}

		return $result;
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		if ( $maxsize <= 0 ) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}

		$prunelog = array();
		$newdepth = $maxdepth;
		$result = new SMWDisjunction();

		foreach ( $this->m_descriptions as $desc ) {
			$restdepth = $maxdepth;
			$result->addDescription( $desc->prune( $maxsize, $restdepth, $prunelog ) );
			$newdepth = min( $newdepth, $restdepth );
		}

		if ( count( $result->getDescriptions() ) > 0 ) {
			$log = array_merge( $log, $prunelog );
			$maxdepth = $newdepth;
			
			if ( count( $result->getDescriptions() ) == 1 ) { // simplify unary disjunctions!
				$descriptions = $result->getDescriptions();
				$result = array_shift( $descriptions );
			}

			$result->setPrintRequests( $this->getPrintRequests() );

			return $result;
		} else {
			$log[] = $this->getQueryString();

			$result = new SMWThingDescription();
			$result->setPrintRequests( $this->getPrintRequests() );

			return $result;
		}
	}
}

/**
 * Description of a set of instances that have an attribute with some value
 * that fits another (sub)description.
 *
 * Corresponds to existential quatification ("SomeValuesFrom" restriction) on
 * properties in OWL. In conjunctive queries (OWL) and SPARQL (RDF), it is
 * represented by using variables in the object part of such properties.
 * @ingroup SMWQuery
 */
class SMWSomeProperty extends SMWDescription {

	protected $m_description;
	protected $m_property;

	public function __construct( SMWDIProperty $property, SMWDescription $description ) {
		$this->m_property = $property;
		$this->m_description = $description;
	}

	public function getProperty() {
		return $this->m_property;
	}

	public function getDescription() {
		return $this->m_description;
	}

	public function getQueryString( $asvalue = false ) {
		$subdesc = $this->m_description;
		$propertyChainString = $this->m_property->getLabel();
		$propertyname = $propertyChainString;

		while ( ( $propertyname != '' ) && ( $subdesc instanceof SMWSomeProperty ) ) { // try to use property chain syntax
			$propertyname = $subdesc->getProperty()->getLabel();
			if ( $propertyname != '' ) {
				$propertyChainString .= '.' . $propertyname;
				$subdesc = $subdesc->getDescription();
			}
		}

		if ( $asvalue ) {
			return '&lt;q&gt;[[' . $propertyChainString . '::' . $subdesc->getQueryString( true ) . ']]&lt;/q&gt;';
		} else {
			return '[[' . $propertyChainString . '::' . $subdesc->getQueryString( true ) . ']]';
		}
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		return 1 + $this->getDescription()->getSize();
	}

	public function getDepth() {
		return 1 + $this->getDescription()->getDepth();
	}

	public function getQueryFeatures() {
		return SMW_PROPERTY_QUERY | $this->m_description->getQueryFeatures();
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		if ( ( $maxsize <= 0 ) || ( $maxdepth <= 0 ) ) {
			$log[] = $this->getQueryString();
			return new SMWThingDescription();
		}

		$maxsize--;
		$maxdepth--;

		$result = new SMWSomeProperty( $this->m_property, $this->m_description->prune( $maxsize, $maxdepth, $log ) );
		$result->setPrintRequests( $this->getPrintRequests() );

		return $result;
	}
	
}