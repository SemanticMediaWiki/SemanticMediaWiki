<?php
/**
 * SMWExpElement is a class for representing single elements that appear in
 * exported data, such as individual resources, data literals, or blank nodes.
 *
 * @author Markus Krötzsch
 * @file
 * @ingroup SMW
 */

/**
 * A single element for export, e.g. a data literal, instance name, or blank
 * node. This abstract base class declares the basic common functionality of
 * export elements (which is not much, really).
 * @note This class should not be instantiated directly.
 *
 * @ingroup SMW
 */
abstract class SMWExpElement {

	/**
	 * The SMWDataItem that this export element is associated with, if
	 * any. Might be unset if not given yet.
	 * @var SMWDataItem
	 */
	protected $m_dataItem;

	/**
	 * Constructor.
	 *
	 * @param $dataItem SMWDataItem or null
	 */
	public function __construct( $dataItem = null ) {
		if ( $dataItem !== null ) {
			$this->m_dataItem = $dataItem;
		}
	}

	/**
	 * Get a SMWDataItem object that represents the contents of this export
	 * element in SMW, or null if no such data item could be found.
	 *
	 * @return SMWDataItem or null
	 */
	public function getDataItem() {
		return isset( $this->m_dataItem ) ? $this->m_dataItem : null;
	}
}

/**
 * A single resource (individual) for export, as defined by a URI.
 * This class can also be used to represent blank nodes: It is assumed that all
 * objects of class SMWExpElement or any of its subclasses represent a blank
 * node if their name is empty or of the form "_id" where "id" is any
 * identifier string. IDs are local to the current context, such as a list of
 * triples or an SMWExpData container. 
 *
 * @ingroup SMW
 */
class SMWExpResource extends SMWExpElement {

	/**
	 * Constructor. The given URI must not contain serialization-specific
	 * abbreviations or escapings, such as XML entities.
	 *
	 * @param $uri string of the full URI
	 * @param $dataItem SMWDataItem or null
	 */
	public function __construct( $uri, $dataItem = null ) {
		parent::__construct( $dataItem );
		$this->m_uri = $uri;
	}
	
	/**
	 * Return true if this resource represents a blank node.
	 *
	 * @return boolean
	 */
	public function isBlankNode() {
		return ( $this->m_uri == '' ) || ( $this->m_uri{0} == '_' );
	}

	/**
	 * Get the URI of this resource. The result is a UTF-8 encoded URI (or
	 * IRI) without any escaping.
	 *
	 * @return string
	 */
	public function getUri() {
		return $this->m_uri;
	}

}


/**
 * A single resource (individual) for export, defined by a URI for which there
 * also is a namespace abbreviation.
 *
 * @ingroup SMW
 */
class SMWExpNsResource extends SMWExpResource {

	/**
	 * Namespace URI prefix of the abbreviated URI
	 * @var string
	 */
	protected $m_namespace;
	/**
	 * Namespace abbreviation of the abbreviated URI
	 * @var string
	 */
	protected $m_namespaceid;
	/**
	 * Local part of the abbreviated URI
	 * @var string
	 */
	protected $m_localname;

	/**
	 * Constructor. The given URI must not contain serialization-specific
	 * abbreviations or escapings, such as XML entities.
	 *
	 * @param $localname string local part of the abbreviated URI
	 * @param $namespace string namespace URI prefix of the abbreviated URI
	 * @param $namespaceid string namespace abbreviation of the abbreviated URI
	 * @param $dataItem SMWDataItem or null
	 */
	public function __construct( $localname, $namespace, $namespaceid, $dataItem = null ) {
		parent::__construct( $namespace . $localname, $dataItem );
		$this->m_namespace = $namespace;
		$this->m_namespaceid = $namespaceid;
		$this->m_localname = $localname;
	}

	/**
	 * Return a qualitifed name for the element.
	 *
	 * @return string
	 */
	public function getQName() {
		return $this->m_namespaceid . ':' . $this->m_localname;
	}

	/**
	 * Get the namespace identifier used (the part before :).
	 *
	 * @return string
	 */
	public function getNamespaceId() {
		return $this->m_namespaceid;
	}

	/**
	 * Get the namespace URI that is used in the abbreviation.
	 *
	 * @return string
	 */
	public function getNamespace() {
		return $this->m_namespace;
	}

	/**
	 * Get the local name (the part after :).
	 *
	 * @return string
	 */
	public function getLocalName() {
		return $this->m_localname;
	}

	/**
	 * Check if the local name is qualifies as a local name in XML and
	 * Turtle. The function returns true if this is surely the case, and
	 * false if it may not be the case. However, we do not check the whole
	 * range of allowed Unicode entities for performance reasons.
	 *
	 * @return boolean
	 */
	public function hasAllowedLocalName() {
		return preg_match( '/^[A-Za-z_][-A-Za-z_0-9]*$/u', $this->m_localname );
	}

}

/**
 * A single datatype literal for export. Defined by a literal value and a
 * datatype URI.
 * 
 * @todo Currently no support for language tags.
 *
 * @ingroup SMW
 */
class SMWExpLiteral extends SMWExpElement {

	/**
	 * Datatype URI for the literal.
	 * @var string
	 */
	protected $m_datatype;
	/**
	 * Lexical form of the literal.
	 * @var string
	 */
	protected $m_lexicalForm;

	/**
	 * Constructor. The given lexical form should be the plain string for
	 * representing the literal without datatype or language information.
	 * It must not use any escaping or abbrevition mechanisms.
	 *
	 * @param $lexicalForm string lexical form
	 * @param $datatype string datatype URI or empty for untyped literals
	 * @param $dataItem SMWDataItem or null
	 */
	public function __construct( $lexicalForm, $datatype = '', $dataItem = null ) {
		parent::__construct( $dataItem );
		$this->m_lexicalForm = $lexicalForm;
		$this->m_datatype = $datatype;
	}

	/**
	 * Return the URI of the datatype used, or the empty string if untyped.
	 *
	 * @return string
	 */
	public function getDatatype() {
		return $this->m_datatype;
	}

	/**
	 * Return the lexical form of the literal. The result does not use
	 * any escapings and might still need to be escaped in some contexts.
	 * The lexical form is not validated or canonicalized.
	 *
	 * @return string
	 */
	public function getLexicalForm() {
		return $this->m_lexicalForm;
	}

}