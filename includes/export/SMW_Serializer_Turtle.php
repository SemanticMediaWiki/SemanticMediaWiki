<?php

use SMW\InMemoryPoolCache;

/**
 * File holding the SMWTurtleSerializer class that provides basic functions for
 * serialising OWL data in Turtle syntax.
 *
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */

/**
 * Class for serializing exported data (encoded as SMWExpData object) in
 * Turtle syntax.
 *
 * @ingroup SMW
 */
class SMWTurtleSerializer extends SMWSerializer{
	/**
	 * Array of non-trivial sub-SMWExpData elements that cannot be nested while
	 * serializing some SMWExpData. The elements of the array are serialized
	 * later during the same serialization step (so this is not like another
	 * queue for declarations or the like; it just unfolds an SMWExpData
	 * object).
	 *
	 * @var array of SMWExpData
	 */
	protected $subexpdata;

	/**
	 * If true, do not serialize namespace declarations and record them in
	 * $sparql_namespaces instead for later retrieval.
	 * @var boolean
	 */
	protected $sparqlmode;

	/**
	 * Array of retrieved namespaces (abbreviation => URI) for later use.
	 * @var array of string
	 */
	protected $sparql_namespaces;

	public function __construct( $sparqlMode = false ) {
		parent::__construct();
		$this->sparqlmode = $sparqlMode;
	}

	public function clear() {
		parent::clear();
		$this->sparql_namespaces = array();
	}

	/**
	 * @since 2.3
	 */
	public static function reset() {
		InMemoryPoolCache::getInstance()->resetPoolCacheFor( 'turtle.serializer' );
	}

	/**
	 * Get an array of namespace prefixes used in SPARQL mode.
	 * Namespaces are not serialized among triples in SPARQL mode but are
	 * collected separately. This method returns the prefixes and empties
	 * the collected list afterwards.
	 *
	 * @return array shortName => namespace URI
	 */
	public function flushSparqlPrefixes() {
		$result = $this->sparql_namespaces;
		$this->sparql_namespaces = array();
		return $result;
	}

	protected function serializeHeader() {
		if ( $this->sparqlmode ) {
			$this->pre_ns_buffer = '';
			$this->sparql_namespaces = array(
				"rdf" => SMWExporter::getInstance()->expandURI( '&rdf;' ),
				"rdfs" => SMWExporter::getInstance()->expandURI( '&rdfs;' ),
				"owl" => SMWExporter::getInstance()->expandURI( '&owl;' ),
				"swivt" => SMWExporter::getInstance()->expandURI( '&swivt;' ),
				"wiki" => SMWExporter::getInstance()->expandURI( '&wiki;' ),
				"category" => SMWExporter::getInstance()->expandURI( '&category;' ),
				"property" => SMWExporter::getInstance()->expandURI( '&property;' ),
				"xsd" => "http://www.w3.org/2001/XMLSchema#" ,
				"wikiurl" => SMWExporter::getInstance()->expandURI( '&wikiurl;' )
			);
		} else {
			$this->pre_ns_buffer =
			"@prefix rdf: <" . SMWExporter::getInstance()->expandURI( '&rdf;' ) . "> .\n" .
			"@prefix rdfs: <" . SMWExporter::getInstance()->expandURI( '&rdfs;' ) . "> .\n" .
			"@prefix owl: <" . SMWExporter::getInstance()->expandURI( '&owl;' ) . "> .\n" .
			"@prefix swivt: <" . SMWExporter::getInstance()->expandURI( '&swivt;' ) . "> .\n" .
			// A note on "wiki": this namespace is crucial as a fallback when it would be illegal to start e.g. with a number.
			// In this case, one can always use wiki:... followed by "_" and possibly some namespace, since _ is legal as a first character.
			"@prefix wiki: <" . SMWExporter::getInstance()->expandURI( '&wiki;' ) . "> .\n" .
			"@prefix category: <" . SMWExporter::getInstance()->expandURI( '&category;' ) . "> .\n" .
			"@prefix property: <" . SMWExporter::getInstance()->expandURI( '&property;' ) . "> .\n" .
			"@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .\n" . // note that this XSD URI is hardcoded below (its unlikely to change, of course)
			"@prefix wikiurl: <" . SMWExporter::getInstance()->expandURI( '&wikiurl;' ) . "> .\n";
		}
		$this->global_namespaces = array( 'rdf' => true, 'rdfs' => true, 'owl' => true, 'swivt' => true, 'wiki' => true, 'property' => true, 'category' => true );
		$this->post_ns_buffer = "\n";
	}

	protected function serializeFooter() {
		if ( !$this->sparqlmode ) {
			$this->post_ns_buffer .= "\n# Created by Semantic MediaWiki, https://www.semantic-mediawiki.org/\n";
		}
	}

	public function serializeDeclaration( $uri, $typename ) {
		$this->post_ns_buffer .= "<" . SMWExporter::getInstance()->expandURI( $uri ) . "> rdf:type $typename .\n";
	}

	public function serializeExpData( SMWExpData $expData ) {

		$this->subExpData = array( $expData );

		while ( count( $this->subExpData ) > 0 ) {
			$this->serializeNestedExpData( array_pop( $this->subExpData ), '' );
		}

		$this->serializeNamespaces();
	}

	protected function serializeNamespace( $shortname, $uri ) {
		$this->global_namespaces[$shortname] = true;
		if ( $this->sparqlmode ) {
			$this->sparql_namespaces[$shortname] = $uri;
		} else {
			$this->pre_ns_buffer .= "@prefix $shortname: <$uri> .\n";
		}
	}

	/**
	 * Serialize the given SMWExpData object, possibly recursively with
	 * increased indentation.
	 *
	 * @param $data SMWExpData containing the data to be serialised.
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 */
	protected function serializeNestedExpData( SMWExpData $data, $indent ) {
		if ( count( $data->getProperties() ) == 0 ) {
			return; // nothing to export
		}

		// Avoid posting turtle property declarations already known for the
		// subject more than once
		if ( $data->getSubject()->getDataItem() !== null && $data->getSubject()->getDataItem()->getNamespace() === SMW_NS_PROPERTY ) {

			$hash = $data->getHash();
			$poolCache = InMemoryPoolCache::getInstance()->getPoolCacheFor( 'turtle.serializer' );

			if ( $poolCache->contains( $hash ) && $poolCache->fetch( $hash ) ) {
				return;
			}

			$poolCache->save( $hash, true );
		}

		$this->recordDeclarationTypes( $data );

		$bnode = false;
		$this->post_ns_buffer .= $indent;
		if ( !$data->getSubject()->isBlankNode() ) {
			$this->serializeExpResource( $data->getSubject() );
		} else { // blank node
			$bnode = true;
			$this->post_ns_buffer .= "[";
		}

		if ( ( $indent !== '' ) && ( !$bnode ) ) { // called to generate a nested descripion; but Turtle cannot nest non-bnode descriptions, do this later
			$this->subexpdata[] = $data;
			return;
		} elseif ( !$bnode ) {
			$this->post_ns_buffer .= "\n ";
		}

		$firstproperty = true;
		foreach ( $data->getProperties() as $property ) {
			$this->post_ns_buffer .= $firstproperty ? "\t" : " ;\n $indent\t";
			$firstproperty = false;
			$prop_decl_queued = false;
			$class_type_prop = $this->isOWLClassTypeProperty( $property );
			$this->serializeExpResource( $property );
			$firstvalue = true;

			foreach ( $data->getValues( $property ) as $value ) {
				$this->post_ns_buffer .= $firstvalue ? '  ' : ' ,  ';
				$firstvalue = false;

				if ( $value instanceof SMWExpLiteral ) {
					$prop_decl_type = SMW_SERIALIZER_DECL_APROP;
					$this->serializeExpLiteral( $value );
				} elseif ( $value instanceof SMWExpResource ) {
					$prop_decl_type = SMW_SERIALIZER_DECL_OPROP;
					$this->serializeExpResource( $value );
				} elseif ( $value instanceof SMWExpData ) { // resource (maybe blank node), could have subdescriptions
					$prop_decl_type = SMW_SERIALIZER_DECL_OPROP;
					$collection = $value->getCollection();
					if ( $collection !== false ) { // RDF-style collection (list)
						$this->post_ns_buffer .= "( ";
						foreach ( $collection as $subvalue ) {
							$this->serializeNestedExpData( $subvalue, $indent . "\t\t" );
							if ( $class_type_prop ) {
								$this->requireDeclaration( $subvalue->getSubject(), SMW_SERIALIZER_DECL_CLASS );
							}
						}
						$this->post_ns_buffer .= " )";
					} else {
						if ( $class_type_prop ) {
							$this->requireDeclaration( $value->getSubject(), SMW_SERIALIZER_DECL_CLASS );
						}
						if ( count( $value->getProperties() ) > 0 ) { // resource with data: serialise
							$this->post_ns_buffer .= "\n";
							$this->serializeNestedExpData( $value, $indent . "\t\t" );
						} else { // resource without data: may need to be queued
							$this->serializeExpResource( $value->getSubject() );
						}
					}
				}

				if ( !$prop_decl_queued ) {
					$this->requireDeclaration( $property, $prop_decl_type );
					$prop_decl_queued = true;
				}
			}
		}
		$this->post_ns_buffer .= ( $bnode ? " ]" : " ." ) . ( $indent === '' ? "\n\n" : '' );
	}

	protected function serializeExpLiteral( SMWExpLiteral $element ) {
		$this->post_ns_buffer .= self::getTurtleNameForExpElement( $element );
	}

	protected function serializeExpResource( SMWExpResource $element ) {
		if ( $element instanceof SMWExpNsResource ) {
			$this->requireNamespace( $element->getNamespaceID(), $element->getNamespace() );
		}
		$this->post_ns_buffer .= self::getTurtleNameForExpElement( $element );
	}

	/**
	 * Get the Turtle serialization string for the given SMWExpElement. The
	 * method just computes a name, and does not serialize triples, so the
	 * parameter must be an SMWExpResource or SMWExpLiteral, no SMWExpData.
	 *
	 * @param $expElement SMWExpElement being SMWExpLiteral or SMWExpResource
	 * @return string
	 */
	public static function getTurtleNameForExpElement( SMWExpElement $expElement ) {
		if ( $expElement instanceof SMWExpResource ) {
			if ( $expElement->isBlankNode() ) {
				return '[]';
			} elseif ( ( $expElement instanceof SMWExpNsResource ) && ( $expElement->hasAllowedLocalName() ) ) {
				return $expElement->getQName();
			} else {
				return '<' . str_replace( '>', '\>', SMWExporter::getInstance()->expandURI( $expElement->getUri() ) ) . '>';
			}
		} elseif ( $expElement instanceof SMWExpLiteral ) {
			$lexicalForm = '"' . str_replace( array( '\\', "\n", '"' ), array( '\\\\', "\\n", '\"' ), $expElement->getLexicalForm() ) . '"';
			$dt = $expElement->getDatatype();
			if ( ( $dt !== '' ) && ( $dt != 'http://www.w3.org/2001/XMLSchema#string' ) ) {
				$count = 0;
				$newdt = str_replace( 'http://www.w3.org/2001/XMLSchema#', 'xsd:', $dt, $count );
				return ( $count == 1 ) ? "$lexicalForm^^$newdt" : "$lexicalForm^^<$dt>";
			} else {
				return $lexicalForm;
			}
		} else {
			throw new InvalidArgumentException( 'The method can only serialize atomic elements of type SMWExpResource or SMWExpLiteral.' );
		}
	}

}
