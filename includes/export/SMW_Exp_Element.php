<?php
/**
 * SMWExpElement is a class for representing single elements that appear in exported
 * data, such as individual resources, data literals, or blank nodes.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMW
 */

/**
 * A single element for export, e.g. a data literal, instance name, or blank node.
 * Supports various serialisation aids for creating URIs or other strings for export.
 * This abstract base class declares the basic common functionality of export elements.
 *
 * This class can also be used to represent blank nodes: It is assumed that all objects
 * of class SMWExpElement or any of its subclasses do represent blank node if their name
 * is empty or of the form "_id" where "id" is any identifier string. IDs are local to the
 * current context, which such as a list of triples or an SMWExpData container.
 * @ingroup SMW
 */
class SMWExpElement {

	protected $m_dv;
	protected $m_name;

	/**
	 * Constructor. $dv is the SMWDataValue from which this object was created,
	 * if any.
	 */
	public function __construct($name, $dv = NULL) {
		$this->m_name = $name;
		$this->m_dv = $dv;
	}

	/**
	 * Return a string for denoting contents of the element, e.g. the URI or the literal 
	 * values.
	 */
	public function getName() {
		return $this->m_name;
	}

	/**
	 * If available, return the SMWDataValue object from which this SMWExpElement was built.
	 * NULL if unset.
	 */
	public function getDataValue() {
		return $this->m_dv;
	}
}

/**
 * A single resource (individual) for export. Defined by a URI, and possibly also providing
 * abbreviated forms (QNames).
 * @ingroup SMW
 */
class SMWExpResource extends SMWExpElement {

	protected $m_namespace = false;
	protected $m_namespaceid = false;
	protected $m_localname = false;
	protected $m_modifier = ''; // "modifier" string: resources might refer to a wiki page, 
	                               // but many resources may refer to the same page, using different modifiers (e.g.: unit strings)

	/**
	 * Constructor. $dv is the SMWDataValue from which this object was created,
	 * if any. If $namespace and $namespaceid are given, then $name is assumed to
	 * be the local name and they are used to build a QName. Otherwise $name is
	 * assumed to be the full URI.
	 */
	public function __construct($name, $dv = NULL, $namespace=false, $namespaceid=false) {
		if ($namespace !== false) {
			$this->m_namespace = $namespace;
			$this->m_namespaceid = $namespaceid;
			$this->m_localname = $name;
			SMWExpElement::__construct($namespace . $name, $dv);
		} else {
			SMWExpElement::__construct($name, $dv);
		}
	}

	/**
	 * SMW uses URI-Refs (#) to make "variants" of some base URI, e.g. to create multiple
	 * versions of a property to store values with multiple units of measurement. This function
	 * creates such a variant based on a given string label (e.g. unit) and returns a stuitable
	 * SMWExpResource.
	 */
	public function makeVariant($modifier) {
		if ($this->m_namespace != false) {
			$result = new SMWExpResource($this->m_localname . SMWExporter::encodeURI(urlencode(str_replace(' ', '_', '#' . $modifier))),
			                             $this->m_dv, $this->m_namespace, $this->m_namespaceid);
		} else {
			$result = new SMWExpResource($this->m_name . SMWExporter::encodeURI(urlencode(str_replace(' ', '_', '#' . $modifier))), $this->m_dv);
		}
		$result->setModifier($modifier);
		return $result;
	}

	/**
	 * See comment for SMWExpResource::m_modifier and SMWExpResource::makeVariant().
	 */
	public function setModifier($modifier) {
		$this->m_modifier = $modifier;
	}

	/**
	 * See comment for SMWExpResource::m_modifier and SMWExpResource::makeVariant().
	 */
	public function getModifier() {
		return $this->m_modifier;
	}

	/**
	 * Return a qualitifed name for the element, or false if no such name could be found.
	 */
	public function getQName() {
		if ($this->m_namespace != false) {
			return $this->m_namespaceid . ':' . $this->m_localname;
		} else {
			return false;
		}
	}

	/**
	 * If a QName was given, this method returns the namespace identifier used (the part before :).
	 */
	public function getNamespaceID() {
		return $this->m_namespaceid;
	}

	/**
	 * If a QName was given, this method returns the complete namespace URI that the
	 * namespace identifier abbreviates.
	 */
	public function getNamespace() {
		return $this->m_namespace;
	}

	/**
	 * If a QName was given, this method returns its local name (the part after :).
	 */
	public function getLocalName() {
		return $this->m_localname;
	}
}

/**
 * A single datatype literal for export. Defined by a literal value and a datatype URI.
 * Currently no support for language tags.
 * @ingroup SMW
 */
class SMWExpLiteral extends SMWExpElement {

	protected $m_datatype = false;

	/**
	 * Constructor. $dv is the SMWDataValue from which this object was created,
	 * if any. $name here should be the plain string for representing the literal
	 * without datatype or language information. The string $name is a plain UTF8-string
	 * witout any escape sequences whatsoever. Note that it may be required to escape
	 * some symbols in some contexts, especially <, >, & in XML and HTML.
	 */
	public function __construct($name, $dv = NULL, $datatype = false) {
		$this->m_datatype = $datatype;
		SMWExpElement::__construct($name, $dv);
	}

	/**
	 * Return the URI of the datatype used, or false if untyped.
	 */
	public function getDatatype() {
		return $this->m_datatype;
	}

}