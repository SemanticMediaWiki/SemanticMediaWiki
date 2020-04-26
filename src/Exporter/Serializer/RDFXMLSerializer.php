<?php

namespace SMW\Exporter\Serializer;

use SMWExporter as Exporter;
use SMWExpData as ExpData;
use SMW\Exporter\Element\ExpResource;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;

/**
 * Class for serializing exported data (encoded as ExpData object) in
 * RDF/XML.
 *
 * @license GNU GPL v2+
 * @since 1.5.5
 *
 * @author Markus Krötzsch
 */
class RDFXMLSerializer extends Serializer {

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
	 * {@inheritDoc}
	 */
	public function clear() {
		parent::clear();
		$this->namespaces_are_global = false;
		$this->namespace_block_started = false;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function serializeHeader() {
		$exporter = Exporter::getInstance();

		$this->namespaces_are_global = true;
		$this->namespace_block_started = true;
		$this->pre_ns_buffer =
			"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			"<!DOCTYPE rdf:RDF[\n" .
			"\t<!ENTITY rdf " . $this->makeValueEntityString( $exporter->expandURI( '&rdf;' ) ) . ">\n" .
			"\t<!ENTITY rdfs " . $this->makeValueEntityString( $exporter->expandURI( '&rdfs;' ) ) . ">\n" .
			"\t<!ENTITY owl " . $this->makeValueEntityString( $exporter->expandURI( '&owl;' ) ) . ">\n" .
			"\t<!ENTITY swivt " . $this->makeValueEntityString( $exporter->expandURI( '&swivt;' ) ) . ">\n" .

			// A note on "wiki": this namespace is crucial as a fallback when it
			// would be illegal to start e.g. with a number. In this case, one can
			// always use wiki:... followed by "_" and possibly some namespace,
			// since _ is legal as a first character.
			"\t<!ENTITY wiki " . $this->makeValueEntityString( $exporter->expandURI( '&wiki;' ) ) . ">\n" .
			"\t<!ENTITY category " . $this->makeValueEntityString( $exporter->expandURI( '&category;' ) ) . ">\n" .
			"\t<!ENTITY property " . $this->makeValueEntityString( $exporter->expandURI( '&property;' ) ) . ">\n" .
			"\t<!ENTITY wikiurl " . $this->makeValueEntityString( $exporter->expandURI( '&wikiurl;' ) ) . ">\n" .
			"]>\n\n" .
			"<rdf:RDF\n" .
			"\txmlns:rdf=\"&rdf;\"\n" .
			"\txmlns:rdfs=\"&rdfs;\"\n" .
			"\txmlns:owl =\"&owl;\"\n" .
			"\txmlns:swivt=\"&swivt;\"\n" .
			"\txmlns:wiki=\"&wiki;\"\n" .
			"\txmlns:category=\"&category;\"\n" .
			"\txmlns:property=\"&property;\"";

		$this->global_namespaces = [
			'rdf' => true,
			'rdfs' => true,
			'owl' => true,
			'swivt' => true,
			'wiki' => true,
			'property' => true,
			'category' => true
		];

		$this->post_ns_buffer .= ">\n\n";
	}

	/**
	 * {@inheritDoc}
	 */
	protected function serializeFooter() {
		$this->post_ns_buffer .= "\t<!-- Created by Semantic MediaWiki, https://www.semantic-mediawiki.org/ -->\n";
		$this->post_ns_buffer .= '</rdf:RDF>';
	}

	/**
	 * {@inheritDoc}
	 */
	public function serializeDeclaration( $uri, $typename ) {
		$this->post_ns_buffer .= "\t<$typename rdf:about=\"$uri\" />\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function serializeExpData( ExpData $expData ) {

		$this->serializeNestedExpData( $expData, '' );
		$this->serializeNamespaces();

		if ( !$this->namespaces_are_global ) {
			$this->pre_ns_buffer .= $this->post_ns_buffer;
			$this->post_ns_buffer = '';
			$this->namespace_block_started = false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function flushContent() : string {
		$result = parent::flushContent();

		// must not be done before calling the parent method (which may declare
		// namespaces)
		$this->namespaces_are_global = false;
		$this->namespace_block_started = false;

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
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
	 * Serialize the given ExpData object, possibly recursively with
	 * increased indentation.
	 *
	 * @param $expData ExpData containing the data to be serialised.
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 */
	protected function serializeNestedExpData( ExpData $expData, $indent ) {
		$this->recordDeclarationTypes( $expData );

		$type = $expData->extractMainType()->getQName();

		if ( !$this->namespace_block_started ) { // start new ns block
			$this->pre_ns_buffer .= "\t$indent<$type";
			$this->namespace_block_started = true;
		} else { // continue running block
			$this->post_ns_buffer .= "\t$indent<$type";
		}

		// else: blank node, no "rdf:about"
		if (
			$expData->getSubject() instanceof ExpResource &&
		    !$expData->getSubject()->isBlankNode() ) {
			$this->post_ns_buffer .= ' rdf:about="' . $expData->getSubject()->getUri() . '"';
		}

		// nothing else to export
		if ( count( $expData->getProperties() ) == 0 ) {
			return $this->post_ns_buffer .= " />\n";
		}

		$this->post_ns_buffer .= ">\n";

		foreach ( $expData->getProperties() as $property ) {
			$prop_decl_queued = false;
			$isClassTypeProp = $this->isOWLClassTypeProperty( $property );

			foreach ( $expData->getValues( $property ) as $valueElement ) {
				$this->requireNamespace( $property->getNamespaceID(), $property->getNamespace() );

				if ( $valueElement instanceof ExpLiteral ) {
					$prop_decl_type = SMW_SERIALIZER_DECL_APROP;
					$this->serializeExpLiteral( $property, $valueElement, "\t\t$indent" );
				} elseif ( $valueElement instanceof ExpResource ) {
					$prop_decl_type = SMW_SERIALIZER_DECL_OPROP;
					$this->serializeExpResource( $property, $valueElement, "\t\t$indent", $isClassTypeProp );
				} elseif ( $valueElement instanceof ExpData ) {
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

	/**
	 * Add to the output a serialization of a property assignment where an
	 * ExpLiteral is the object. It is assumed that a suitable subject
	 * block has already been openend.
	 *
	 * @param $expResourceProperty ExpNsResource the property to use
	 * @param $expLiteral ExpLiteral the data value to use
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 */
	protected function serializeExpLiteral( ExpNsResource $expResourceProperty, ExpLiteral $expLiteral, $indent ) {

		$this->post_ns_buffer .= $indent . '<' . $expResourceProperty->getQName();

		// https://www.w3.org/TR/rdf-syntax-grammar/#section-Syntax-languages
		// "... to indicate that the included content is in the given language.
		// Typed literals which includes XML literals are not affected by this
		// attribute. The most specific in-scope language present (if any) is
		// applied to property element string literal ..."
		if ( $expLiteral->getDatatype() !== '' && $expLiteral->getLang() !== '' ) {
			$this->post_ns_buffer .= ' xml:lang="' . $expLiteral->getLang() . '"';
		} elseif ( $expLiteral->getDatatype() !== '' ) {
			$this->post_ns_buffer .= ' rdf:datatype="' . $expLiteral->getDatatype() . '"';
		}

		$this->post_ns_buffer .= '>' . $this->makeAttributeValueString( $expLiteral->getLexicalForm() );
		$this->post_ns_buffer .= '</' . $expResourceProperty->getQName() . ">\n";
	}

	/**
	 * Add to the output a serialization of a property assignment where an
	 * ExpResource is the object. It is assumed that a suitable subject
	 * block has already been openend.
	 *
	 * @param $expResourceProperty ExpNsResource the property to use
	 * @param $expResource ExpResource the data value to use
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 * @param $isClassTypeProp boolean whether the resource must be declared as a class
	 */
	protected function serializeExpResource( ExpNsResource $expResourceProperty, ExpResource $expResource, $indent, $isClassTypeProp ) {
		$this->post_ns_buffer .= $indent . '<' . $expResourceProperty->getQName();

		if ( !$expResource->isBlankNode() ) {
			if ( ( $expResource instanceof ExpNsResource ) && ( $expResource->getNamespaceID() == 'wiki' ) ) {
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
	 * Add a serialization of the given ExpResource to the output,
	 * assuming that an opening property tag is alerady there.
	 *
	 * @param $expResourceProperty ExpNsResource the property to use
	 * @param $expResource array of (ExpResource or ExpData)
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 * @param $isClassTypeProp boolean whether the resource must be declared as a class
	 *
	 * @bug The $isClassTypeProp parameter is not properly taken into account.
	 * @bug Individual resources are not serialised properly.
	 */
	protected function serializeExpCollection( ExpNsResource $expResourceProperty, array $collection, $indent, $isClassTypeProp ) {

		$this->post_ns_buffer .= $indent . '<' . $expResourceProperty->getQName() . " rdf:parseType=\"Collection\">\n";

		foreach ( $collection as $expElement ) {
			if ( $expElement instanceof ExpData ) {
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
	 *
	 * @return string
	 */
	protected function makeValueEntityString( $string ) {
		return "'" . str_replace( '%', '&#37;', $string ) . "'";
	}

	/**
	 * Escape a string as required for using it in XML attribute values.
	 *
	 * @param $string string to be escaped
	 *
	 * @return string
	 */
	protected function makeAttributeValueString( $string ) {
		return str_replace( [ '&', '>', '<' ], [ '&amp;', '&gt;', '&lt;' ], $string );
	}

}
