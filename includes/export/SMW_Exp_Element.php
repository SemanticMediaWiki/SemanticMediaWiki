<?php
/**
 * SMWExpElement is a class for representing single elements that appear in
 * exported data, such as individual resources, data literals, or blank nodes.
 *
 * @author Markus Krötzsch
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
	 *
	 * @var SMWDataItem|null
	 */
	protected $dataItem;

	/**
	 * Constructor.
	 *
	 * @param $dataItem SMWDataItem|null
	 */
	public function __construct( SMWDataItem $dataItem = null ) {
		$this->dataItem = $dataItem;
	}

	/**
	 * Get a SMWDataItem object that represents the contents of this export
	 * element in SMW, or null if no such data item could be found.
	 *
	 * @return SMWDataItem|null
	 */
	public function getDataItem() {
		return $this->dataItem;
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
	 * @var string
	 */
	protected $uri;

	/**
	 * Constructor. The given URI must not contain serialization-specific
	 * abbreviations or escapings, such as XML entities.
	 *
	 * @param string $uri The full URI
	 * @param SMWDataItem|null $dataItem
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $uri, SMWDataItem $dataItem = null ) {
		if ( !is_string( $uri ) ) {
			throw new InvalidArgumentException( '$uri needs to be a string' );
		}

		parent::__construct( $dataItem );

		$this->uri = $uri;
	}
	
	/**
	 * Return true if this resource represents a blank node.
	 *
	 * @return boolean
	 */
	public function isBlankNode() {
		return $this->uri === '' || $this->uri{0} == '_';
	}

	/**
	 * Get the URI of this resource. The result is a UTF-8 encoded URI (or
	 * IRI) without any escaping.
	 *
	 * @return string
	 */
	public function getUri() {
		return $this->uri;
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
	protected $namespace;

	/**
	 * Namespace abbreviation of the abbreviated URI
	 * @var string
	 */
	protected $namespaceId;

	/**
	 * Local part of the abbreviated URI
	 * @var string
	 */
	protected $localName;

	/**
	 * Constructor. The given URI must not contain serialization-specific
	 * abbreviations or escapings, such as XML entities.
	 *
	 * @param string $localName Local part of the abbreviated URI
	 * @param string $namespace Namespace URI prefix of the abbreviated URI
	 * @param string $namespaceId Namespace abbreviation of the abbreviated URI
	 * @param SMWDataItem|null $dataItem
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $localName, $namespace, $namespaceId, SMWDataItem $dataItem = null ) {
		if ( !is_string( $localName ) ) {
			throw new InvalidArgumentException( '$localName needs to be a string' );
		}

		if ( !is_string( $namespace ) ) {
			throw new InvalidArgumentException( '$namespace needs to be a string' );
		}

		if ( !is_string( $namespaceId ) ) {
			throw new InvalidArgumentException( '$namespaceId needs to be a string' );
		}

		parent::__construct( $namespace . $localName, $dataItem );

		$this->namespace = $namespace;
		$this->namespaceId = $namespaceId;
		$this->localName = $localName;
	}

	/**
	 * Return a qualified name for the element.
	 *
	 * @return string
	 */
	public function getQName() {
		return $this->namespaceId . ':' . $this->localName;
	}

	/**
	 * Get the namespace identifier used (the part before :).
	 *
	 * @return string
	 */
	public function getNamespaceId() {
		return $this->namespaceId;
	}

	/**
	 * Get the namespace URI that is used in the abbreviation.
	 *
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	/**
	 * Get the local name (the part after :).
	 *
	 * @return string
	 */
	public function getLocalName() {
		return $this->localName;
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
		return preg_match( '/^[A-Za-z_][-A-Za-z_0-9]*$/u', $this->localName );
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
	protected $datatype;
	
	/**
	 * Lexical form of the literal.
	 * @var string
	 */
	protected $lexicalForm;

	/**
	 * Constructor. The given lexical form should be the plain string for
	 * representing the literal without datatype or language information.
	 * It must not use any escaping or abbreviation mechanisms.
	 *
	 * @param string $lexicalForm lexical form
	 * @param string $datatype Data type URI or empty for untyped literals
	 * @param SMWDataItem|null $dataItem
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $lexicalForm, $datatype = '', SMWDataItem $dataItem = null ) {
		if ( !is_string( $lexicalForm ) ) {
			throw new InvalidArgumentException( '$lexicalForm needs to be a string' );
		}

		if ( !is_string( $datatype ) ) {
			throw new InvalidArgumentException( '$datatype needs to be a string' );
		}

		parent::__construct( $dataItem );

		$this->lexicalForm = $lexicalForm;
		$this->datatype = $datatype;
	}

	/**
	 * Return the URI of the datatype used, or the empty string if untyped.
	 *
	 * @return string
	 */
	public function getDatatype() {
		return $this->datatype;
	}

	/**
	 * Return the lexical form of the literal. The result does not use
	 * any escapings and might still need to be escaped in some contexts.
	 * The lexical form is not validated or canonicalized.
	 *
	 * @return string
	 */
	public function getLexicalForm() {
		return $this->lexicalForm;
	}

}