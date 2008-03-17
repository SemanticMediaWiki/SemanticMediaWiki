<?php

/**
 * SMWExpData is a class representing semantic data that is ready for easy
 * serialisation in OWL or RDF.
 *
 * @author Markus Krötzsch
 */

/**
 * SMWExpData is a data container for export-ready semantic content. It is
 * organised as a tree-shaped data structure with one root subject and zero
 * or more children connected with labelled edges to the root. Children are
 * again SMWExpData objects, and edges are annotated with SMWExpElements
 * specifying properties.
 *
 * @note AUTOLOADED
 */
class SMWExpData {
	protected $m_subject;
	protected $m_children = array(); // property text keys => array of children SMWExpData objects
	protected $m_edges = array(); // property text keys => property SMWExpElements

	/**
	 * Constructor. $subject is the SMWExpElement for the 
	 * subject about which this SMWExpData is.
	 */
	public function __construct(SMWExpElement $subject) {
		$this->m_subject = $subject;
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
	public function setSubject(SMWExpElement $subject) {
		$this->m_subject = $subject;
	}

	/**
	 * Store a value for an property identified by its title object. No duplicate elimination as this
	 * is usually done in SMWSemanticData already (which is typically used to generate this object)
	 */
	public function addPropertyObjectValue(SMWExpElement $property, SMWExpData $child) {
		if (!array_key_exists($property->getName(), $this->m_edges)) {
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
	public function getValues(/*SMWExpElement*/ $property) {
		if (array_key_exists($property->getName(), $this->m_children)) {
			return $this->m_children[$property->getName()];
		} else {
			return array();
		}
	}

	/**
	 * Return the list of SMWExpData values associated to some property that is
	 * specifed by a standard namespace id and local name.
	 */
	public function getSpecialValues($namespace, $localname) {
		$pe = SMWExporter::getSpecialElement($namespace, $localname);
		if ($pe !== NULL) {
			return $this->getValues($pe);
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
		$pe = SMWExporter::getSpecialElement('rdf', 'type');
		if (array_key_exists($pe->getName(), $this->m_children)) {
			$result = array_shift($this->m_children[$pe->getName()]);
			if (count($this->m_children[$pe->getName()]) == 0) {
				unset($this->m_edges[$pe->getName()]);
			}
			return $result->getSubject();
		} else {
			return SMWExporter::getSpecialElement('rdf', 'Resource');
		}
	}

	/**
	 * Return an array of ternary arrays (subject predicate object) of SMWExpElements
	 * that represents the flattened version of the given data.
	 */
	public function getTripleList() {
		global $smwgBnodeCount;
		if (!isset($smwgBnodeCount)) {
			$smwgBnodeCount = 0;
		}
		$result = array();
		foreach ($this->m_edges as $key => $edge) {
			foreach ($this->m_children[$key] as $child) {
				$name = $child->getSubject()->getName();
				if ( ($name == '') || ($name[0] == '_') ) { // bnode, distribute ID
					$child = clone $child;
					$subject = new SMWExpElement('_' . $smwgBnodeCount++,$child->getSubject()->getDataValue());
					$child->setSubject($subject);
				}
				$result[] = array($this->m_subject, $edge, $child->getSubject());
				$result = array_merge($result, $child->getTripleList()); //recursively generate all children of childs.
			}
		}
		return $result;
	}

}
