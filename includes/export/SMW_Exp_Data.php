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
	public function __construct(/*SMWExpElement*/ $subject) {
		$this->m_subject = $subject;
	}

	/**
	 * Return subject to which the stored semantic annotation refer to.
	 */
	public function getSubject() {
		return $this->m_subject;
	}

	/**
	 * Store a value for an property identified by its title object. No duplicate elimination as this
	 * is usually done in SMWSemanticData already (which is typically used to generate this object)
	 */
	public function addPropertyObjectValue(/*SMWExpElement*/ $property, /*SMWExpData*/ $child) {
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
	 * Return an array of ternary arrays (subject predicate object) of SMWExpElements
	 * that represents the flattened version of the given data.
	 */
	public function getTripleList() {
		$result = array();
		foreach ($this->m_edges as $key => $edge) {
			foreach ($this->m_children[$key] as $child) {
				$result[] = array($this->m_subject, $edge, $child->getSubject());
				///TODO also recursively generate all children of childs.
			}
		}
		return $result;
	}

}
