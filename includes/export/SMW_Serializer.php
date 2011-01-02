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
 * Class for serializing exported data (encoded as SMWExpData object) in a
 * concrete syntactic format such as Turtle or RDF/XML. The serializer
 * adds object serialisations to an internal string that can be retrieved for
 * pushing it to an output. RDF and OWL have two types of dependencies that are
 * managed: namespaces (and similar abbreviations) and element declarations.
 * The former need to be defined before being used, while the latter can occur
 * at some later point in the serialization. Declarations are relevant to the
 * OWL data model, being one of Class, DatatypeProperty, and ObjectProperty
 * (only the latter two are mutually exclusive). This class determines the
 * required declaration from the context in which an element is used. 
 *
 * @ingroup SMW
 */
class SMWSerializer {
	/**
	 * Array for recording required declarations; format:
	 * resourcename => decl-flag, where decl-flag is a sum of flags
	 * SMW_SERIALIZER_DECL_CLASS, SMW_SERIALIZER_DECL_OPROP, 
	 * SMW_SERIALIZER_DECL_APROP.
	 */
	protected $decl_todo;
	/**
	 * Array for recording previous declarations; format:
	 * resourcename => decl-flag, where decl-flag is a sum of flags
	 * SMW_SERIALIZER_DECL_CLASS, SMW_SERIALIZER_DECL_OPROP, 
	 * SMW_SERIALIZER_DECL_APROP.
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
	 */
	protected $extra_namespaces;
	/**
	 * Array of namespaces that have been declared globally already. Contains
	 * entries of format 'namespace abbreviation' => true, assuming that the
	 * same abbreviation always refers to the same URI.
	 */
	protected $global_namespaces;
	/**
	 * The current working string is obtained by concatenating the strings
	 * $pre_ns_buffer and $post_ns_buffer. The split between the two is such
	 * that one can append additional namespace declarations to $pre_ns_buffer
	 * so that they affect all current elements. The buffers are flushed during
	 * output in order to achieve "streaming" RDF export for larger files.
	 */
	protected $pre_ns_buffer;
	/**
	 * See documentation for $pre_ns_buffer.
	 */
	protected $post_ns_buffer;
	/**
	 * True if the $pre_ns_buffer contains the beginning of a namespace
	 * declaration block to which further declarations for the current
	 * context can be appended. 
	 */
	protected $namespace_block_started;
	/**
	 * True if the namespaces that are added at the current serialization stage
	 * become global, i.e. remain available for all later contexts. This is the
	 * case in RDF/XML only as long as the header has not been streamed to the
	 * client (reflected herein by calling flushContent()). Later, namespaces
	 * can only be added locally to individual elements, thus requiring them to
	 * be re-added multiple times if used in many elements.
	 */
	protected $namespaces_are_global;
	
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
		$this->element_queue = array();
		$this->element_done = array();
		$this->decl_todo = array();
		$this->decl_done = array();
		$this->pre_ns_buffer = '';
		$this->post_ns_buffer = '';
		$this->namespaces_are_global = false;
		$this->extra_namespaces = array();
	}

	/* Functions for exporting RDF */

	public function serializeHeader() {
		$this->clear();
		$this->namespaces_are_global = true;
		$this->namespace_block_started = true;
		$this->pre_ns_buffer =
			"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			"<!DOCTYPE rdf:RDF[\n" .
			"\t<!ENTITY rdf " . $this->makeValueEntityString( SMWExporter::expandURI( '&rdf;' ) ) . ">\n" .
			"\t<!ENTITY rdfs " . $this->makeValueEntityString( SMWExporter::expandURI( '&rdfs;' ) ) . ">\n" .
			"\t<!ENTITY owl " . $this->makeValueEntityString( SMWExporter::expandURI( '&owl;' ) ) . ">\n" .
			"\t<!ENTITY swivt " . $this->makeValueEntityString( SMWExporter::expandURI( '&swivt;' ) ) . ">\n" .
			// A note on "wiki": this namespace is crucial as a fallback when it would be illegal to start e.g. with a number.
			// In this case, one can always use wiki:... followed by "_" and possibly some namespace, since _ is legal as a first character.
			"\t<!ENTITY wiki "  . $this->makeValueEntityString( SMWExporter::expandURI( '&wiki;' ) ) . ">\n" .
			"\t<!ENTITY property " . $this->makeValueEntityString( SMWExporter::expandURI( '&property;' ) ) . ">\n" .
			"\t<!ENTITY wikiurl " . $this->makeValueEntityString( SMWExporter::expandURI( '&wikiurl;' ) ) . ">\n" .
			"]>\n\n" .
			"<rdf:RDF\n" .
			"\txmlns:rdf=\"&rdf;\"\n" .
			"\txmlns:rdfs=\"&rdfs;\"\n" .
			"\txmlns:owl =\"&owl;\"\n" .
			"\txmlns:swivt=\"&swivt;\"\n" .
			"\txmlns:wiki=\"&wiki;\"\n" .
			"\txmlns:property=\"&property;\"";
		$this->global_namespaces = array( 'rdf' => true, 'rdfs' => true, 'owl' => true, 'swivt' => true, 'wiki' => true, 'property' => true );
		$this->post_ns_buffer .= ">\n\n";
	}

	/**
	 * Serialise the footer.
	 */
	public function serializeFooter() {
		$this->serializeDeclarations();
		$this->post_ns_buffer .= "\t<!-- Created by Semantic MediaWiki, http://semantic-mediawiki.org/ -->\n";
		$this->post_ns_buffer .= '</rdf:RDF>';
	}
	
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
			foreach ( $types as $type ) {
				$this->post_ns_buffer .= "\t<$type rdf:about=\"$name\" />\n";
			}
			$curdone = array_key_exists( $name, $this->decl_done ) ? $this->decl_done[$name] : 0;
			$this->decl_done[$name] = $curdone | $flag;
		}
		$this->decl_todo = array(); // reset all
	}

	/**
	 * Serialise the given SMWExpData object. The method does not assume that
	 * the exported data refers to wiki pages or other SMW data, and it makes
	 * sure that all required auxiliary declarations for obtaining proper OWL
	 * are included anyway.
	 *
	 * @param $data SMWExpData containing the data to be serialised.
	 */
	public function serializeExpData( SMWExpData $data ) {
		$this->serializeNestedExpData( $data, '' );
		$this->serializeNamespaces();
		if ( !$this->namespaces_are_global ) {
			$this->pre_ns_buffer .= $this->post_ns_buffer;
			$this->post_ns_buffer = '';
			$this->namespace_block_started = false;
		}
	}
		
	/**
	 * Serialise the given SMWExpData object, possibly recursively with
	 * increased indentation.
	 *
	 * @param $data SMWExpData containing the data to be serialised.
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 */
	protected function serializeNestedExpData( SMWExpData $data, $indent ) {
		$this->recordDeclarationTypes( $data );

		$type = $data->extractMainType()->getQName();
		if ( !$this->namespace_block_started ) { // start new ns block
			$this->pre_ns_buffer .= "\t$indent<$type";
			$this->namespace_block_started = true;
		} else { // continue running block
			$this->post_ns_buffer .= "\t$indent<$type";
		}

		if ( ( $data->getSubject() instanceof SMWExpLiteral ) ||
		     ( $data->getSubject() instanceof SMWExpResource ) ) {
			 $this->post_ns_buffer .= ' rdf:about="' . $data->getSubject()->getName() . '"';
		} // else: blank node, no "rdf:about"

		if ( count( $data->getProperties() ) == 0 ) { // nothing else to export
			$this->post_ns_buffer .= " />\n";
		} else { // process data
			$this->post_ns_buffer .= ">\n";

			foreach ( $data->getProperties() as $property ) {
				$prop_decl_queued = false;
				$prop_decl_type = 0;
				$class_type_prop = $this->isOWLClassTypeProperty( $property );

				foreach ( $data->getValues( $property ) as $value ) {
					$this->post_ns_buffer .= "\t\t$indent<" . $property->getQName();
					$this->requireNamespace( $property->getNamespaceID(), $property->getNamespace() );
					$object = $value->getSubject();

					if ( $object instanceof SMWExpLiteral ) {
						$prop_decl_type = SMW_SERIALIZER_DECL_APROP;
						if ( $object->getDatatype() != '' ) {
							$this->post_ns_buffer .= ' rdf:datatype="' . $object->getDatatype() . '"';
						}
						$this->post_ns_buffer .= '>' .
							str_replace( array( '&', '>', '<' ), array( '&amp;', '&gt;', '&lt;' ), $object->getName() ) .
							'</' . $property->getQName() . ">\n";
					} else { // resource (maybe blank node), could have subdescriptions
						$prop_decl_type = SMW_SERIALIZER_DECL_OPROP;
						$collection = $value->getCollection();
						if ( $collection !== false ) { // RDF-style collection (list)
							$this->post_ns_buffer .= " rdf:parseType=\"Collection\">\n";
							foreach ( $collection as $subvalue ) {
								$this->serializeNestedExpData( $subvalue, $indent . "\t\t" );
								if ( $class_type_prop ) {
									$this->requireDeclaration( $subvalue, SMW_SERIALIZER_DECL_CLASS );
								}
							}
							$this->post_ns_buffer .= "\t\t$indent</" . $property->getQName() . ">\n";
						} else {
							if ( $class_type_prop ) {
								$this->requireDeclaration( $object, SMW_SERIALIZER_DECL_CLASS );
							}
							if ( count( $value->getProperties() ) > 0 ) { // resource with data: serialise
								$this->post_ns_buffer .= ">\n";
								$this->serializeNestedExpData( $value, $indent . "\t\t" );
								$this->post_ns_buffer .= "\t\t$indent</" . $property->getQName() . ">\n";
							} else { // resource without data: may need to be queued
								if ( !$object->isBlankNode() ) {
									$this->post_ns_buffer .= ' rdf:resource="' . $object->getName() . '"';
								}
								$this->post_ns_buffer .= "/>\n";
							}
						}
					}
					if ( !$prop_decl_queued ) {
						$this->requireDeclaration( $property, $prop_decl_type );
						$prop_decl_queued = true;
					}
				}
			}
			$this->post_ns_buffer .= "\t$indent</" . $type . ">\n";
		}
	}

	/**
	 * Get the string that has been serialized so far. This function also
	 * resets the internal buffers for serilized strings and namespaces
	 * (what is flushed is gone).
	 */
	public function flushContent() {
		if ( ( $this->pre_ns_buffer == '' ) && ( $this->post_ns_buffer == '' ) ) return '';
		$this->serializeNamespaces();
		$this->namespaces_are_global = false;
		$result = $this->pre_ns_buffer . $this->post_ns_buffer;
		$this->pre_ns_buffer = '';
		$this->post_ns_buffer = '';
		$this->namespace_block_started = false;
		return $result;
	}

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
	 * Include collected namespace information into the serialization.
	 */
	protected function serializeNamespaces() {
		foreach ( $this->extra_namespaces as $nsshort => $nsuri ) {
			if ( $this->namespaces_are_global ) {
				$this->global_namespaces[$nsshort] = true;
				$this->pre_ns_buffer .= "\n\t";
			} else {
				$this->pre_ns_buffer .= ' ';
			}
			$this->pre_ns_buffer .= "xmlns:$nsshort=\"$nsuri\"";
		}
		$this->extra_namespaces = array();
	}

	/**
	 * State that a certain declaration is needed. The method checks if the 
	 * declaration is already available, and records a todo otherwise.
	 */
	protected function requireDeclaration( SMWExpResource $resource, $decltype ) {
		$namespaceid = $resource->getNamespaceID();
		// Do not declare predefined OWL language constructs:
		if ( ( $namespaceid == 'owl' ) || ( $namespaceid == 'rdf' ) || ( $namespaceid == 'rdfs' ) ) return;
		// Do not declare blank nodes:
		if ( $resource->isBlankNode() ) return;

		$name = $resource->getName();
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
	 * @param $data specifying the type data upon which declarations are based
	 */
	protected function recordDeclarationTypes( SMWExpData $data ) {
		foreach ( $data->getSpecialValues( 'rdf', 'type') as $typedata ) {
			$typeresource = $typedata->getSubject();
			if ( $typeresource instanceof SMWExpResource ) {
				switch ( $typeresource->getQName() ) {
					case 'owl:Class': $typeflag = SMW_SERIALIZER_DECL_CLASS; break; 
					case 'owl:ObjectProperty': $typeflag = SMW_SERIALIZER_DECL_OPROP; break; 
					case 'owl:DatatypeProperty': $typeflag = SMW_SERIALIZER_DECL_APROP; break; 
					default: $typeflag = 0;
				}
				if ( $typeflag != 0 ) {
					$this->declarationDone( $data->getSubject(), $typeflag );
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
		$name = $element->getName();
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
	 * @param SMWExpResource $property
	 */
	protected function isOWLClassTypeProperty( SMWExpResource $property ) {
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
	
	/**
	 * Escape a string in the special form that is required for values in 
	 * DTD entity declarations in XML. Namely, this require the percent sign
	 * to be replaced.
	 * @param string $string to be escaped 
	 */
	protected function makeValueEntityString( $string ) {
		return "'" . str_replace( '%','&#37;',$string ) . "'";
	}

}
