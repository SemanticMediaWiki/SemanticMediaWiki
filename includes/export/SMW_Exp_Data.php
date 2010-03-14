<?php
/**
 * SMWExpData is a class representing semantic data that is ready for easy
 * serialisation in OWL or RDF.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMW
 */

/**
 * SMWExpData is a data container for export-ready semantic content. It is
 * organised as a tree-shaped data structure with one root subject and zero
 * or more children connected with labelled edges to the root. Children are
 * again SMWExpData objects, and edges are annotated with SMWExpElements
 * specifying properties.
 *
 * @ingroup SMW
 */
class SMWExpData {
	protected $m_subject;
	protected $m_children = array(); // property text keys => array of children SMWExpData objects
	protected $m_edges = array(); // property text keys => property SMWExpElements

	/**
	 * Constructor. $subject is the SMWExpElement for the 
	 * subject about which this SMWExpData is.
	 */
	public function __construct( SMWExpElement $subject ) {
		$this->m_subject = $subject;
	}

	/**
	 * Turn an array of SMWElements into an RDF collection.
	 */
	public static function makeCollection( $elements ) {
		if ( count( $elements ) == 0 ) {
			return new SMWExpData( SMWExporter::getSpecialElement( 'rdf', 'nil' ) );
		} else {
			$rdftype  = SMWExporter::getSpecialElement( 'rdf', 'type' );
			$rdffirst = SMWExporter::getSpecialElement( 'rdf', 'first' );
			$rdfrest  = SMWExporter::getSpecialElement( 'rdf', 'rest' );
			$result = new SMWExpData( new SMWExpElement( '' ) ); // bnode
			$result->addPropertyObjectValue( $rdftype, new SMWExpData( SMWExporter::getSpecialElement( 'rdf', 'List' ) ) );
			$result->addPropertyObjectValue( $rdffirst, array_shift( $elements ) );
			$result->addPropertyObjectValue( $rdfrest, SMWExpData::makeCollection( $elements ) );
			return $result;
		}
	}

	/**
	 * Return subject to which the stored semantic annotation refer to.
	 */
	public function getSubject() {
		return $this->m_subject;
	}

	/**
	 * Set the subject element.
	 */
	public function setSubject( SMWExpElement $subject ) {
		$this->m_subject = $subject;
	}

	/**
	 * Store a value for a property identified by its title object. No duplicate elimination as this
	 * is usually done in SMWSemanticData already (which is typically used to generate this object)
	 */
	public function addPropertyObjectValue( SMWExpElement $property, SMWExpData $child ) {
		if ( !array_key_exists( $property->getName(), $this->m_edges ) ) {
			$this->m_children[$property->getName()] = array();
			$this->m_edges[$property->getName()] = $property;
		}
		$this->m_children[$property->getName()][] = $child;
	}

	/**
	 * Return the list of SMWExpElements for all properties for which some values exist.
	 */
	public function getProperties() {
		return $this->m_edges;
	}

	/**
	 * Return the list of SMWExpData values associated to some property (element)
	 */
	public function getValues( /*SMWExpElement*/ $property ) {
		if ( array_key_exists( $property->getName(), $this->m_children ) ) {
			return $this->m_children[$property->getName()];
		} else {
			return array();
		}
	}

	/**
	 * Return the list of SMWExpData values associated to some property that is
	 * specifed by a standard namespace id and local name.
	 */
	public function getSpecialValues( $namespace, $localname ) {
		$pe = SMWExporter::getSpecialElement( $namespace, $localname );
		if ( $pe !== null ) {
			return $this->getValues( $pe );
		} else {
			return array();
		}
	}

	/**
	 * This function finds the main type (class) element of the subject based on the 
	 * current property assignments. It returns this type element (SMWExpElement) and 
	 * removes the according type assignement from the data.
	 */
	public function extractMainType() {
		$pe = SMWExporter::getSpecialElement( 'rdf', 'type' );
		if ( array_key_exists( $pe->getName(), $this->m_children ) ) {
			$result = array_shift( $this->m_children[$pe->getName()] );
			if ( count( $this->m_children[$pe->getName()] ) == 0 ) {
				unset( $this->m_edges[$pe->getName()] );
				unset( $this->m_children[$pe->getName()] );
			}
			return $result->getSubject();
		} else {
			return SMWExporter::getSpecialElement( 'rdf', 'Resource' );
		}
	}

	/**
	 * Check if this element can be serialised using parserType="Collection" and
	 * if yes return an array of SMWExpElements corresponding to the collection 
	 * elements in the specified order. Otherwise return false.
	 */
	public function getCollection() {
		$rdftype  = SMWExporter::getSpecialElement( 'rdf', 'type' );
		$rdffirst = SMWExporter::getSpecialElement( 'rdf', 'first' );
		$rdfrest  = SMWExporter::getSpecialElement( 'rdf', 'rest' );
		$rdfnil   = SMWExporter::getSpecialElement( 'rdf', 'nil' );
		$name = $this->getSubject()->getName();
		// first check if we are basically an rdf List:
		if ( ( ( $name == '' ) || ( $name { 0 } == '_' ) ) && // bnode
		     ( array_key_exists( $rdftype->getName(), $this->m_children ) ) &&
		     ( count( $this->m_children[$rdftype->getName()] ) == 1 ) &&
		     ( array_key_exists( $rdffirst->getName(), $this->m_children ) ) &&
		     ( count( $this->m_children[$rdffirst->getName()] ) == 1 ) &&
		     ( array_key_exists( $rdfrest->getName(), $this->m_children ) ) &&
		     !( end( $this->m_children[$rdffirst->getName()] ) instanceof SMWExpLiteral ) &&
		     // (parseType collection in RDF not possible with literals :-/)
		     ( count( $this->m_children[$rdfrest->getName()] ) == 1 ) &&
		     ( count( $this->m_children ) == 3 ) ) {
			$typedata = end( $this->m_children[$rdftype->getName()] );
			$rdflist = SMWExporter::getSpecialElement( 'rdf', 'List' );
			if ( $typedata->getSubject()->getName() == $rdflist->getName() ) {
				$first = end( $this->m_children[$rdffirst->getName()] );
				$rest  = end( $this->m_children[$rdfrest->getName()] );
				$restlist = $rest->getCollection();
				if ( $restlist === false ) {
					return false;
				} else {
					array_unshift( $restlist, $first );
					return $restlist;
				}
			} else {
				return $false;
			}
		} elseif ( ( !array_key_exists( $rdftype->getName(), $this->m_children ) ) &&
		           ( $name == $rdfnil->getName() ) ) {
			return array();
		} else {
			return false;
		}
	}

	/**
	 * Return an array of ternary arrays (subject predicate object) of SMWExpElements
	 * that represents the flattened version of the given data.
	 */
	public function getTripleList() {
		global $smwgBnodeCount;
		if ( !isset( $smwgBnodeCount ) ) {
			$smwgBnodeCount = 0;
		}
		$result = array();
		foreach ( $this->m_edges as $key => $edge ) {
			foreach ( $this->m_children[$key] as $child ) {
				$name = $child->getSubject()->getName();
				if ( ( $name == '' ) || ( $name[0] == '_' ) ) { // bnode, distribute ID
					$child = clone $child;
					$subject = new SMWExpElement( '_' . $smwgBnodeCount++, $child->getSubject()->getDataValue() );
					$child->setSubject( $subject );
				}
				$result[] = array( $this->m_subject, $edge, $child->getSubject() );
				$result = array_merge( $result, $child->getTripleList() ); // recursively generate all children of childs.
			}
		}
		return $result;
	}

}
