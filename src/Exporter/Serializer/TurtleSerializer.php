<?php

namespace SMW\Exporter\Serializer;

use SMW\InMemoryPoolCache;
use SMWExporter as Exporter;
use SMWExpData as ExpData;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpResource;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpElement;
use InvalidArgumentException;

/**
 * Class for serializing exported data (encoded as ExpData object) in
 * Turtle syntax.
 *
 * @license GNU GPL v2+
 * @since 1.5.5
 *
 * @author Markus Krötzsch
 */
class TurtleSerializer extends Serializer {

	/**
	 * Array of non-trivial sub-ExpData elements that cannot be nested while
	 * serializing some ExpData. The elements of the array are serialized
	 * later during the same serialization step (so this is not like another
	 * queue for declarations or the like; it just unfolds an ExpData
	 * object).
	 *
	 * @var array of ExpData
	 */
	protected $subexpdata;

	/**
	 * If true, do not serialize namespace declarations and record them in
	 * $sparql_namespaces instead for later retrieval.
	 *
	 * @var boolean
	 */
	protected $sparqlmode;

	/**
	 * Array of retrieved namespaces (abbreviation => URI) for later use.
	 *
	 * @var array of string
	 */
	protected $sparql_namespaces;

	/**
	 * @since 1.5.5
	 *
	 * @param bool $sparqlMode
	 */
	public function __construct( $sparqlMode = false ) {
		parent::__construct();
		$this->sparqlmode = $sparqlMode;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear() {
		parent::clear();
		$this->sparql_namespaces = [];
	}

	/**
	 * @since 2.3
	 */
	public static function reset() {
		InMemoryPoolCache::getInstance()->resetPoolCacheById( 'turtle.serializer' );
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
		$this->sparql_namespaces = [];
		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function serializeHeader() {
		$exporter = Exporter::getInstance();

		if ( $this->sparqlmode ) {
			$this->pre_ns_buffer = '';
			$this->sparql_namespaces = [
				"rdf" => $exporter->expandURI( '&rdf;' ),
				"rdfs" => $exporter->expandURI( '&rdfs;' ),
				"owl" => $exporter->expandURI( '&owl;' ),
				"swivt" => $exporter->expandURI( '&swivt;' ),
				"wiki" => $exporter->expandURI( '&wiki;' ),
				"category" => $exporter->expandURI( '&category;' ),
				"property" => $exporter->expandURI( '&property;' ),
				"xsd" => "http://www.w3.org/2001/XMLSchema#" ,
				"wikiurl" => $exporter->expandURI( '&wikiurl;' )
			];
		} else {
			$this->pre_ns_buffer =
			"@prefix rdf: <" . $exporter->expandURI( '&rdf;' ) . "> .\n" .
			"@prefix rdfs: <" . $exporter->expandURI( '&rdfs;' ) . "> .\n" .
			"@prefix owl: <" . $exporter->expandURI( '&owl;' ) . "> .\n" .
			"@prefix swivt: <" . $exporter->expandURI( '&swivt;' ) . "> .\n" .

			// A note on "wiki": this namespace is crucial as a fallback when it
			// would be illegal to start e.g. with a number. In this case, one can
			// always use wiki:... followed by "_" and possibly some namespace,
			// since _ is legal as a first character.
			"@prefix wiki: <" . $exporter->expandURI( '&wiki;' ) . "> .\n" .
			"@prefix category: <" . $exporter->expandURI( '&category;' ) . "> .\n" .
			"@prefix property: <" . $exporter->expandURI( '&property;' ) . "> .\n" .

			 // note that this XSD URI is hardcoded below (its unlikely to change, of course)
			"@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .\n" .
			"@prefix wikiurl: <" . $exporter->expandURI( '&wikiurl;' ) . "> .\n";
		}

		$this->global_namespaces = [
			'rdf' => true,
			'rdfs' => true,
			'owl' => true,
			'swivt' => true,
			'wiki' => true,
			'property' => true,
			'category' => true
		];

		$this->post_ns_buffer = "\n";
	}

	/**
	 * {@inheritDoc}
	 */
	protected function serializeFooter() {
		if ( !$this->sparqlmode ) {
			$this->post_ns_buffer .= "\n# Created by Semantic MediaWiki, https://www.semantic-mediawiki.org/\n";
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function serializeDeclaration( $uri, $typename ) {
		$this->post_ns_buffer .= "<" . Exporter::getInstance()->expandURI( $uri ) . "> rdf:type $typename .\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function serializeExpData( ExpData $expData ) {

		$this->subExpData = [ $expData ];

		while ( count( $this->subExpData ) > 0 ) {
			$this->serializeNestedExpData( array_pop( $this->subExpData ), '' );
		}

		$this->serializeNamespaces();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function serializeNamespace( $shortname, $uri ) {
		$this->global_namespaces[$shortname] = true;
		if ( $this->sparqlmode ) {
			$this->sparql_namespaces[$shortname] = $uri;
		} else {
			$this->pre_ns_buffer .= "@prefix $shortname: <$uri> .\n";
		}
	}

	/**
	 * Serialize the given ExpData object, possibly recursively with
	 * increased indentation.
	 *
	 * @param $data ExpData containing the data to be serialised.
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 */
	protected function serializeNestedExpData( ExpData $data, $indent ) {
		if ( count( $data->getProperties() ) == 0 ) {
			return; // nothing to export
		}

		$subject = $data->getSubject();

		// Avoid posting turtle property declarations already known for the
		// subject more than once
		if (
			$subject->getDataItem() !== null &&
			$subject->getDataItem()->getNamespace() === SMW_NS_PROPERTY ) {

			$hash = $data->getHash();
			$poolCache = InMemoryPoolCache::getInstance()->getPoolCacheById( 'turtle.serializer' );

			if ( $poolCache->contains( $hash ) && $poolCache->fetch( $hash ) ) {
				return;
			}

			$poolCache->save( $hash, true );
		}

		$this->recordDeclarationTypes( $data );

		$bnode = false;
		$this->post_ns_buffer .= $indent;

		if ( !$subject->isBlankNode() ) {
			$this->serializeExpResource( $subject );
		} else { // blank node
			$bnode = true;
			$this->post_ns_buffer .= "[";
		}

		// Called to generate a nested descripion; but Turtle cannot nest non-bnode
		// descriptions, do this later
		if ( ( $indent !== '' ) && ( !$bnode ) ) {
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

				if ( $value instanceof ExpLiteral ) {
					$prop_decl_type = SMW_SERIALIZER_DECL_APROP;
					$this->serializeExpLiteral( $value );
				} elseif ( $value instanceof ExpResource ) {
					$prop_decl_type = SMW_SERIALIZER_DECL_OPROP;
					$this->serializeExpResource( $value );
				} elseif ( $value instanceof ExpData ) { // resource (maybe blank node), could have subdescriptions
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

	protected function serializeExpLiteral( ExpLiteral $element ) {
		$this->post_ns_buffer .= self::getTurtleNameForExpElement( $element );
	}

	protected function serializeExpResource( ExpResource $element ) {

		if ( $element instanceof ExpNsResource ) {
			$this->requireNamespace( $element->getNamespaceID(), $element->getNamespace() );
		}

		$this->post_ns_buffer .= self::getTurtleNameForExpElement( $element );
	}

	/**
	 * Get the Turtle serialization string for the given ExpElement. The
	 * method just computes a name, and does not serialize triples, so the
	 * parameter must be an ExpResource or ExpLiteral, no ExpData.
	 *
	 * @param $expElement ExpElement being ExpLiteral or ExpResource
	 *
	 * @return string
	 */
	public static function getTurtleNameForExpElement( ExpElement $expElement ) {
		if ( $expElement instanceof ExpResource ) {
			if ( $expElement->isBlankNode() ) {
				return '[]';
			} elseif ( ( $expElement instanceof ExpNsResource ) && ( $expElement->hasAllowedLocalName() ) ) {
				return $expElement->getQName();
			} else {
				return '<' . str_replace( '>', '\>', Exporter::getInstance()->expandURI( $expElement->getUri() ) ) . '>';
			}
		} elseif ( $expElement instanceof ExpLiteral ) {
			$dataType = $expElement->getDatatype();
			$lexicalForm = self::getCorrectLexicalForm( $expElement );

			if ( ( $dataType !== '' ) && ( $dataType != 'http://www.w3.org/2001/XMLSchema#string' ) ) {
				$count = 0;
				$newdt = str_replace( 'http://www.w3.org/2001/XMLSchema#', 'xsd:', $dataType, $count );
				return ( $count == 1 ) ? "$lexicalForm^^$newdt" : "$lexicalForm^^<$dataType>";
			} else {
				return $lexicalForm;
			}
		}

		throw new InvalidArgumentException( 'The method can only serialize atomic elements of type ExpResource or ExpLiteral.' );
	}

	private static function getCorrectLexicalForm( $expElement ) {

		$lexicalForm = str_replace( [ '\\', "\n", '"' ], [ '\\\\', "\\n", '\"' ], $expElement->getLexicalForm() );

		if ( $expElement->getLang() !== '' && ( $expElement->getDatatype() === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#langString' ) ) {
			$lexicalForm = '"' . $lexicalForm . '@' . $expElement->getLang() . '"';
		} elseif ( $expElement->getLang() !== '' ) {
			$lexicalForm = '"' . $lexicalForm . '"' . '@' . $expElement->getLang();
		} else {
			$lexicalForm = '"' . $lexicalForm . '"';
		}

		return $lexicalForm;
	}

}
