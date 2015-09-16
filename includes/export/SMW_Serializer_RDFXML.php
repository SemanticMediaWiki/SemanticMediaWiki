<?php

/**
 * File holding the SMWRDFXMLSerializer class that provides basic functions for
 * serialising OWL data in RDF/XML syntax.
 *
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */

/**
 * Class for serializing exported data (encoded as SMWExpData object) in
 * RDF/XML.
 *
 * @ingroup SMW
 */
class SMWRDFXMLSerializer extends SMWSerializer{
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

	public function clear() {
		parent::clear();
		$this->namespaces_are_global = false;
		$this->namespace_block_started = false;
	}

	protected function serializeHeader() {
		$this->namespaces_are_global = true;
		$this->namespace_block_started = true;
		$this->pre_ns_buffer =
			"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			"<!DOCTYPE rdf:RDF[\n" .
			"\t<!ENTITY rdf " . $this->makeValueEntityString( SMWExporter::getInstance()->expandURI( '&rdf;' ) ) . ">\n" .
			"\t<!ENTITY rdfs " . $this->makeValueEntityString( SMWExporter::getInstance()->expandURI( '&rdfs;' ) ) . ">\n" .
			"\t<!ENTITY owl " . $this->makeValueEntityString( SMWExporter::getInstance()->expandURI( '&owl;' ) ) . ">\n" .
			"\t<!ENTITY swivt " . $this->makeValueEntityString( SMWExporter::getInstance()->expandURI( '&swivt;' ) ) . ">\n" .
			// A note on "wiki": this namespace is crucial as a fallback when it would be illegal to start e.g. with a number.
			// In this case, one can always use wiki:... followed by "_" and possibly some namespace, since _ is legal as a first character.
			"\t<!ENTITY wiki "  . $this->makeValueEntityString( SMWExporter::getInstance()->expandURI( '&wiki;' ) ) . ">\n" .
			"\t<!ENTITY category " . $this->makeValueEntityString( SMWExporter::getInstance()->expandURI( '&category;' ) ) . ">\n" .
			"\t<!ENTITY property " . $this->makeValueEntityString( SMWExporter::getInstance()->expandURI( '&property;' ) ) . ">\n" .
			"\t<!ENTITY wikiurl " . $this->makeValueEntityString( SMWExporter::getInstance()->expandURI( '&wikiurl;' ) ) . ">\n" .
			"]>\n\n" .
			"<rdf:RDF\n" .
			"\txmlns:rdf=\"&rdf;\"\n" .
			"\txmlns:rdfs=\"&rdfs;\"\n" .
			"\txmlns:owl =\"&owl;\"\n" .
			"\txmlns:swivt=\"&swivt;\"\n" .
			"\txmlns:wiki=\"&wiki;\"\n" .
			"\txmlns:category=\"&category;\"\n" .
			"\txmlns:property=\"&property;\"";
		$this->global_namespaces = array( 'rdf' => true, 'rdfs' => true, 'owl' => true, 'swivt' => true, 'wiki' => true, 'property' => true, 'category' => true );
		$this->post_ns_buffer .= ">\n\n";
	}

	protected function serializeFooter() {
		$this->post_ns_buffer .= "\t<!-- Created by Semantic MediaWiki, https://semantic-mediawiki.org/ -->\n";
		$this->post_ns_buffer .= '</rdf:RDF>';
	}

	public function serializeDeclaration( $uri, $typename ) {
		$this->post_ns_buffer .= "\t<$typename rdf:about=\"$uri\" />\n";
	}

	public function serializeExpData( SMWExpData $expData ) {
		$this->serializeNestedExpData( $expData, '' );
		$this->serializeNamespaces();
		if ( !$this->namespaces_are_global ) {
			$this->pre_ns_buffer .= $this->post_ns_buffer;
			$this->post_ns_buffer = '';
			$this->namespace_block_started = false;
		}
	}

	public function flushContent() {
		$result = parent::flushContent();
		$this->namespaces_are_global = false; // must not be done before calling the parent method (which may declare namespaces)
		$this->namespace_block_started = false;
		return $result;
	}

	protected function serializeNamespace( $shortname, $uri ) {
		if ( $this->namespaces_are_global ) {
			$this->global_namespaces[$shortname] = true;
			$this->pre_ns_buffer .= "\n\t";
		} else {
			$this->pre_ns_buffer .= ' ';
		}
		$this->pre_ns_buffer .= "xmlns:$shortname=\"$uri\"";
	}

	/**
	 * Serialize the given SMWExpData object, possibly recursively with
	 * increased indentation.
	 *
	 * @param $expData SMWExpData containing the data to be serialised.
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 */
	protected function serializeNestedExpData( SMWExpData $expData, $indent ) {
		$this->recordDeclarationTypes( $expData );

		$type = $expData->extractMainType()->getQName();
		if ( !$this->namespace_block_started ) { // start new ns block
			$this->pre_ns_buffer .= "\t$indent<$type";
			$this->namespace_block_started = true;
		} else { // continue running block
			$this->post_ns_buffer .= "\t$indent<$type";
		}

		if ( ( $expData->getSubject() instanceof SMWExpResource ) &&
		      !$expData->getSubject()->isBlankNode() ) {
			 $this->post_ns_buffer .= ' rdf:about="' . $expData->getSubject()->getUri() . '"';
		} // else: blank node, no "rdf:about"

		if ( count( $expData->getProperties() ) == 0 ) { // nothing else to export
			$this->post_ns_buffer .= " />\n";
		} else { // process data
			$this->post_ns_buffer .= ">\n";

			foreach ( $expData->getProperties() as $property ) {
				$prop_decl_queued = false;
				$isClassTypeProp = $this->isOWLClassTypeProperty( $property );

				foreach ( $expData->getValues( $property ) as $valueElement ) {
					$this->requireNamespace( $property->getNamespaceID(), $property->getNamespace() );

					if ( $valueElement instanceof SMWExpLiteral ) {
						$prop_decl_type = SMW_SERIALIZER_DECL_APROP;
						$this->serializeExpLiteral( $property, $valueElement, "\t\t$indent" );
					} elseif ( $valueElement instanceof SMWExpResource ) {
						$prop_decl_type = SMW_SERIALIZER_DECL_OPROP;
						$this->serializeExpResource( $property, $valueElement, "\t\t$indent", $isClassTypeProp );
					} elseif ( $valueElement instanceof SMWExpData ) {
						$prop_decl_type = SMW_SERIALIZER_DECL_OPROP;

						$collection = $valueElement->getCollection();
						if ( $collection !== false ) { // RDF-style collection (list)
							$this->serializeExpCollection( $property, $collection, "\t\t$indent", $isClassTypeProp );
						} elseif ( count( $valueElement->getProperties() ) > 0 ) { // resource with data
							$this->post_ns_buffer .= "\t\t$indent<" . $property->getQName() . ">\n";
							$this->serializeNestedExpData( $valueElement, "\t\t$indent" );
							$this->post_ns_buffer .= "\t\t$indent</" . $property->getQName() . ">\n";
						} else { // resource without data
							$this->serializeExpResource( $property, $valueElement->getSubject(), "\t\t$indent", $isClassTypeProp );
						}
					} // else: no other types of export elements

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
	 * Add to the output a serialization of a property assignment where an
	 * SMWExpLiteral is the object. It is assumed that a suitable subject
	 * block has already been openend.
	 *
	 * @param $expResourceProperty SMWExpNsResource the property to use
	 * @param $expLiteral SMWExpLiteral the data value to use
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 */
	protected function serializeExpLiteral( SMWExpNsResource $expResourceProperty, SMWExpLiteral $expLiteral, $indent ) {
		$this->post_ns_buffer .= $indent . '<' . $expResourceProperty->getQName();
		if ( $expLiteral->getDatatype() !== '' ) {
			$this->post_ns_buffer .= ' rdf:datatype="' . $expLiteral->getDatatype() . '"';
		}
		$this->post_ns_buffer .= '>' . $this->makeAttributeValueString( $expLiteral->getLexicalForm() ) .
			'</' . $expResourceProperty->getQName() . ">\n";
	}

	/**
	 * Add to the output a serialization of a property assignment where an
	 * SMWExpResource is the object. It is assumed that a suitable subject
	 * block has already been openend.
	 *
	 * @param $expResourceProperty SMWExpNsResource the property to use
	 * @param $expResource SMWExpResource the data value to use
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 * @param $isClassTypeProp boolean whether the resource must be declared as a class
	 */
	protected function serializeExpResource( SMWExpNsResource $expResourceProperty, SMWExpResource $expResource, $indent, $isClassTypeProp ) {
		$this->post_ns_buffer .= $indent . '<' . $expResourceProperty->getQName();
		if ( !$expResource->isBlankNode() ) {
			if ( ( $expResource instanceof SMWExpNsResource ) && ( $expResource->getNamespaceID() == 'wiki' ) ) {
				// very common case, reduce bandwidth
				$this->post_ns_buffer .= ' rdf:resource="&wiki;' . $expResource->getLocalName() . '"';
			} else {
				$uriValue = $this->makeAttributeValueString( $expResource->getUri() );
				$this->post_ns_buffer .= ' rdf:resource="' . $uriValue . '"';
			}
		}
		$this->post_ns_buffer .= "/>\n";
		if ( $isClassTypeProp ) {
			$this->requireDeclaration( $expResource, SMW_SERIALIZER_DECL_CLASS );
		}
	}

	/**
	 * Add a serialization of the given SMWExpResource to the output,
	 * assuming that an opening property tag is alerady there.
	 *
	 * @param $expResourceProperty SMWExpNsResource the property to use
	 * @param $expResource array of (SMWExpResource or SMWExpData)
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 * @param $isClassTypeProp boolean whether the resource must be declared as a class
	 *
	 * @bug The $isClassTypeProp parameter is not properly taken into account.
	 * @bug Individual resources are not serialised properly.
	 */
	protected function serializeExpCollection( SMWExpNsResource $expResourceProperty, array $collection, $indent, $isClassTypeProp ) {
		$this->post_ns_buffer .= $indent . '<' . $expResourceProperty->getQName() . " rdf:parseType=\"Collection\">\n";
		foreach ( $collection as $expElement ) {
			if ( $expElement instanceof SMWExpData ) {
				$this->serializeNestedExpData( $expElement, $indent );
			} else {
				// FIXME: the below is not the right thing to do here
				//$this->serializeExpResource( $expResourceProperty, $expElement, $indent );
			}
			if ( $isClassTypeProp ) {
				// FIXME: $expResource is undefined
				//$this->requireDeclaration( $expResource, SMW_SERIALIZER_DECL_CLASS );
			}
		}
		$this->post_ns_buffer .= "$indent</" . $expResourceProperty->getQName() . ">\n";
	}

	/**
	 * Escape a string in the special form that is required for values in
	 * DTD entity declarations in XML. Namely, this require the percent sign
	 * to be replaced.
	 *
	 * @param $string string to be escaped
	 * @return string
	 */
	protected function makeValueEntityString( $string ) {
		return "'" . str_replace( '%', '&#37;', $string ) . "'";
	}

	/**
	 * Escape a string as required for using it in XML attribute values.
	 *
	 * @param $string string to be escaped
	 * @return string
	 */
	protected function makeAttributeValueString( $string ) {
		return str_replace( array( '&', '>', '<' ), array( '&amp;', '&gt;', '&lt;' ), $string );
	}

}
