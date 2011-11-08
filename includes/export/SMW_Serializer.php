<?php

/**
 * File holding the SMWSerializer class that provides basic functions for
 * serialising data in OWL and RDF syntaxes. 
 *
 * @file SMW_Serializer.php
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */

define( 'SMW_SERIALIZER_DECL_CLASS', 1 );
define( 'SMW_SERIALIZER_DECL_OPROP', 2 );
define( 'SMW_SERIALIZER_DECL_APROP', 4 );

/**
 * Abstract class for serializing exported data (encoded as SMWExpData object)
 * in a concrete syntactic format such as Turtle or RDF/XML. The serializer
 * adds object serialisations to an internal string that can be retrieved for
 * pushing it to an output. This abstract class does not define this string as
 * implementations may want to use their own scheme (e.g. using two buffers as
 * in the case of SMWRDFXMLSerializer). The function flushContent() returns the
 * string serialized so far so as to enable incremental serialization.
 * 
 * RDF and OWL have two types of dependencies that are managed by this class:
 * namespaces (and similar abbreviation schemes) and element declarations.
 * The former need to be defined before being used, while the latter can occur
 * at some later point in the serialization. Declarations are relevant to the
 * OWL data model, being one of Class, DatatypeProperty, and ObjectProperty
 * (only the latter two are mutually exclusive). This class determines the
 * required declaration from the context in which an element is used. 
 *
 * @ingroup SMW
 */
abstract class SMWSerializer {
	/**
	 * The current working string is obtained by concatenating the strings
	 * $pre_ns_buffer and $post_ns_buffer. The split between the two is such
	 * that one can append additional namespace declarations to $pre_ns_buffer
	 * so that they affect all current elements. The buffers are flushed during
	 * output in order to achieve "streaming" RDF export for larger files.
	 * @var string
	 */
	protected $pre_ns_buffer;
	/**
	 * See documentation for $pre_ns_buffer.
	 * @var string
	 */
	protected $post_ns_buffer;
	/**
	 * Array for recording required declarations; format:
	 * resourcename => decl-flag, where decl-flag is a sum of flags
	 * SMW_SERIALIZER_DECL_CLASS, SMW_SERIALIZER_DECL_OPROP, 
	 * SMW_SERIALIZER_DECL_APROP.
	 * @var array of integer
	 */
	protected $decl_todo;
	/**
	 * Array for recording previous declarations; format:
	 * resourcename => decl-flag, where decl-flag is a sum of flags
	 * SMW_SERIALIZER_DECL_CLASS, SMW_SERIALIZER_DECL_OPROP, 
	 * SMW_SERIALIZER_DECL_APROP.
	 * @var array of integer
	 */
	protected $decl_done;
	/**
	 * Array of additional namespaces (abbreviation => URI), flushed on
	 * closing the current namespace tag. Since we export in a streamed
	 * way, it is not always possible to embed additional namespaces into
	 * a syntactic block (e.g. an RDF/XML tag) which might have been sent to
	 * the client already. But we wait with printing the current block so that
	 * extra namespaces from this array can still be printed (note that one
	 * never know which extra namespaces you encounter during export).
	 * @var array of string
	 */
	protected $extra_namespaces;
	/**
	 * Array of namespaces that have been declared globally already. Contains
	 * entries of format 'namespace abbreviation' => true, assuming that the
	 * same abbreviation always refers to the same URI.
	 * @var array of string
	 */
	protected $global_namespaces;
	
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->clear();
	}
	
	/**
	 * Clear internal states to start a new serialization.
	 */
	public function clear() {
		$this->pre_ns_buffer = '';
		$this->post_ns_buffer = '';
		$this->decl_todo = array();
		$this->decl_done = array();
		$this->global_namespaces = array();
		$this->extra_namespaces = array();
	}

	/**
	 * Start a new serialization, resetting all internal data and serializing
	 * necessary header elements.
	 */
	public function startSerialization() {
		$this->clear();
		$this->serializeHeader();
	}

	/**
	 * Complete the serialization so that calling flushContent() will return
	 * the final part of the output, leading to a complete serialization with
	 * all necessary declarations. No further serialization functions must be
	 * called after this. 
	 */
	public function finishSerialization() {
		$this->serializeDeclarations();
		$this->serializeFooter();
	}

	/**
	 * Serialize the header (i.e. write it to the internal buffer). May
	 * include standard syntax to start output but also declare some common
	 * namespaces globally. 
	 */
	abstract protected function serializeHeader();

	/**
	 * Serialise the footer (i.e. write it to the internal buffer).
	 */
	abstract protected function serializeFooter();
	
	/**
	 * Serialize any declarations that have been found to be missing while
	 * serializing other elements.
	 */
	public function serializeDeclarations() {
		foreach ( $this->decl_todo as $name => $flag ) {
			$types = array();
			if ( $flag & SMW_SERIALIZER_DECL_CLASS ) $types[] = 'owl:Class';
			if ( $flag & SMW_SERIALIZER_DECL_OPROP ) $types[] = 'owl:ObjectProperty';
			if ( $flag & SMW_SERIALIZER_DECL_APROP ) $types[] = 'owl:DatatypeProperty';
			foreach ( $types as $typename ) {
				$this->serializeDeclaration( $name, $typename );
			}
			$curdone = array_key_exists( $name, $this->decl_done ) ? $this->decl_done[$name] : 0;
			$this->decl_done[$name] = $curdone | $flag;
		}
		$this->decl_todo = array(); // reset all
	}

	/**
	 * Serialize a single declaration for the given $uri (expanded) and type
	 * (given as a QName).
	 * @param $uri string URI of the thing to declare
	 * @param $typename string one of owl:Class, owl:ObjectProperty, and
	 * owl:datatypeProperty
	 */
	abstract public function serializeDeclaration( $uri, $typename );

	/**
	 * Serialise the given SMWExpData object. The method must not assume that
	 * the exported data refers to wiki pages or other SMW data, and it must
	 * ensure that all required auxiliary declarations for obtaining proper OWL
	 * are included in any case (this can be done using requireDeclaration()).
	 *
	 * @param $data SMWExpData containing the data to be serialised.
	 */
	abstract public function serializeExpData( SMWExpData $data );

	/**
	 * Get the string that has been serialized so far. This function also
	 * resets the internal buffers for serilized strings and namespaces
	 * (what is flushed is gone).
	 */
	public function flushContent() {
		if ( ( $this->pre_ns_buffer === '' ) && ( $this->post_ns_buffer === '' ) ) return '';
		$this->serializeNamespaces();
		$result = $this->pre_ns_buffer . $this->post_ns_buffer;
		$this->pre_ns_buffer = '';
		$this->post_ns_buffer = '';
		return $result;
	}

	/**
	 * Include collected namespace information into the serialization.
	 */
	protected function serializeNamespaces() {
		foreach ( $this->extra_namespaces as $nsshort => $nsuri ) {
			$this->serializeNamespace( $nsshort, $nsuri );
		}
		$this->extra_namespaces = array();
	}

	/**
	 * Serialize a single namespace.
	 * Namespaces that were serialized in such a way that they remain
	 * available for all following output should be added to
	 * $global_namespaces. 
	 * @param $shortname string abbreviation/prefix to declare 
	 * @param $uri string URI prefix that the namespace encodes
	 */ 
	abstract protected function serializeNamespace( $shortname, $uri );

	/**
	 * Require an additional namespace to be declared in the serialization.
	 * The function checks whether the required namespace is available globally
	 * and add it to the list of required namespaces otherwise.
	 */
	protected function requireNamespace( $nsshort, $nsuri ) {
		if ( !array_key_exists( $nsshort, $this->global_namespaces ) ) {
			$this->extra_namespaces[$nsshort] = $nsuri;
		}
	}

	/**
	 * State that a certain declaration is needed. The method checks if the 
	 * declaration is already available, and records a todo otherwise.
	 */
	protected function requireDeclaration( SMWExpResource $resource, $decltype ) {
		// Do not declare predefined OWL language constructs:
		if ( $resource instanceof SMWExpNsResource ) {
			$nsId = $resource->getNamespaceId();
			if ( ( $nsId == 'owl' ) || ( $nsId == 'rdf' ) || ( $nsId == 'rdfs' ) ) {
				return;
			}
		}
		// Do not declare blank nodes:
		if ( $resource->isBlankNode() ) return;

		$name = $resource->getUri();
		if ( array_key_exists( $name, $this->decl_done ) && ( $this->decl_done[$name] & $decltype ) ) {
			return;
		}
		if ( !array_key_exists( $name, $this->decl_todo ) ) {
			$this->decl_todo[$name] = $decltype;			
		} else {
			$this->decl_todo[$name] = $this->decl_todo[$name] | $decltype;
		}
	}

	/**
	 * Update the declaration "todo" and "done" lists for the case that the
	 * given data has been serialized with the type information it provides.
	 *  
	 * @param $expData specifying the type data upon which declarations are based
	 */
	protected function recordDeclarationTypes( SMWExpData $expData ) {
		foreach ( $expData->getSpecialValues( 'rdf', 'type') as $typeresource ) {
			if ( $typeresource instanceof SMWExpNsResource ) {
				switch ( $typeresource->getQName() ) {
					case 'owl:Class': $typeflag = SMW_SERIALIZER_DECL_CLASS; break; 
					case 'owl:ObjectProperty': $typeflag = SMW_SERIALIZER_DECL_OPROP; break; 
					case 'owl:DatatypeProperty': $typeflag = SMW_SERIALIZER_DECL_APROP; break; 
					default: $typeflag = 0;
				}
				if ( $typeflag != 0 ) {
					$this->declarationDone( $expData->getSubject(), $typeflag );
				}
			}  
		}
	}

	/**
	 * Update the declaration "todo" and "done" lists to reflect the fact that
	 * the given element has been declared to has the given type.
	 * 
	 * @param $element SMWExpResource specifying the element to update 
	 * @param $typeflag integer specifying the type (e.g. SMW_SERIALIZER_DECL_CLASS)
	 */
	protected function declarationDone( SMWExpResource $element, $typeflag ) {
		$name = $element->getUri();
		$curdone = array_key_exists( $name, $this->decl_done ) ? $this->decl_done[$name] : 0;
		$this->decl_done[$name] = $curdone | $typeflag;
		if ( array_key_exists( $name, $this->decl_todo ) ) {
			$this->decl_todo[$name] = $this->decl_todo[$name] & ( ~$typeflag );
			if ( $this->decl_todo[$name] == 0 ) {
				unset( $this->decl_todo[$name] );
			}
		}
	}

	/**
	 * Check if the given property is one of the special properties of the OWL
	 * language that require their values to be classes or RDF lists of
	 * classes. In these cases, it is necessary to declare this in the exported
	 * data. 
	 *  
	 * @note The list of properties checked here is not complete for the OWL
	 * language but covers what is used in SMW.
	 * @note OWL 2 allows URIs to refer to both classes and individual elements
	 * in different contexts. We only need declarations for classes that are
	 * used as such, hence it is enough to check the property. Moreover, we do
	 * not use OWL Datatypes in SMW, so rdf:type, rdfs:domain, etc. always
	 * refer to classes.
	 * @param SMWExpNsResource $property
	 */
	protected function isOWLClassTypeProperty( SMWExpNsResource $property ) {
		$locname = $property->getLocalName();
		if ( $property->getNamespaceID() == 'rdf' ) {
			return ( $locname == 'type' ); 
		} elseif ( $property->getNamespaceID() == 'owl' ) {
			return ( $locname == 'intersectionOf' ) || ( $locname == 'unionOf' ) ||
			       ( $locname == 'equivalentClass' ) || 
			       ( $locname == 'complementOf' ) || ( $locname == 'someValuesFrom' ) ||
			       ( $locname == 'allValuesFrom' ) || ( $locname == 'onClass' );
		} elseif ( $property->getNamespaceID() == 'rdfs' ) {
			return ( $locname == 'subClassOf' ) || ( $locname == 'range' ) || ( $locname == 'domain' );
		} else {
			return false;
		}
	}

}
