<?php

/**
 * File holding the SMWTurtleSerializer class that provides basic functions for
 * serialising OWL data in Turtle syntax. 
 *
 * @file SMW_Serializer.php
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
	 */
	protected $subexpdata;

	protected function serializeHeader() {
		$this->pre_ns_buffer =
			"@prefix rdf: <" . SMWExporter::expandURI( '&rdf;' ) . "> .\n" .
			"@prefix rdfs: <" . SMWExporter::expandURI( '&rdfs;' ) . "> .\n" .
			"@prefix owl: <" . SMWExporter::expandURI( '&owl;' ) . "> .\n" .
			"@prefix swivt: <" . SMWExporter::expandURI( '&swivt;' ) . "> .\n" .
			// A note on "wiki": this namespace is crucial as a fallback when it would be illegal to start e.g. with a number.
			// In this case, one can always use wiki:... followed by "_" and possibly some namespace, since _ is legal as a first character.
			"@prefix wiki: <" . SMWExporter::expandURI( '&wiki;' ) . "> .\n" .
			"@prefix property: <" . SMWExporter::expandURI( '&property;' ) . "> .\n" .
			"@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .\n" . // note that this XSD URI is hardcoded below (its unlikely to change, of course) 
			"@prefix wikiurl: <" . SMWExporter::expandURI( '&wikiurl;' ) . "> .\n";
		$this->global_namespaces = array( 'rdf' => true, 'rdfs' => true, 'owl' => true, 'swivt' => true, 'wiki' => true, 'property' => true );
		$this->post_ns_buffer = "\n";
	}

	protected function serializeFooter() {
		$this->post_ns_buffer .= "\n# Created by Semantic MediaWiki, http://semantic-mediawiki.org/\n";
	}
	
	public function serializeDeclaration( $uri, $typename ) {
		$this->post_ns_buffer .= "<" . SMWExporter::expandURI( $uri ) . "> rdf:type $typename .\n";
	}

	public function serializeExpData( SMWExpData $data ) {
		$this->subexpdata = array( $data );
		while ( count( $this->subexpdata ) > 0 ) {
			$this->serializeNestedExpData( array_pop( $this->subexpdata ), '' );
		}
		$this->serializeNamespaces();

	}
	
	protected function serializeNamespace( $shortname, $uri ) {
		$this->global_namespaces[$shortname] = true;
		$this->pre_ns_buffer .= "@prefix $shortname: <$uri> .\n";
	}

	/**
	 * Serialize the given SMWExpData object, possibly recursively with
	 * increased indentation.
	 *
	 * @param $data SMWExpData containing the data to be serialised.
	 * @param $indent string specifying a prefix for indentation (usually a sequence of tabs)
	 */
	protected function serializeNestedExpData( SMWExpData $data, $indent ) {
		if ( count( $data->getProperties() ) == 0 ) return; // nothing to export
		$this->recordDeclarationTypes( $data );

		$bnode = false;
		$this->post_ns_buffer .= $indent;
		if ( $data->getSubject() instanceof SMWExpLiteral ) {
			$this->serializeExpLiteral( $data->getSubject() );
		} elseif ( ( $data->getSubject() instanceof SMWExpResource ) && ( !$data->getSubject()->isBlankNode() ) ) {
			$this->serializeExpResource( $data->getSubject() );
		} else { // blank node
			$bnode = true;
			$this->post_ns_buffer .= "[";
		}
		
		if ( ( $indent != '' ) && ( !$bnode ) ) { // called to generate a nested descripion; but Turtle cannot nest non-bnode descriptions, do this later
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
				
				$this->requireNamespace( $property->getNamespaceID(), $property->getNamespace() );
				$object = $value->getSubject();

				if ( $object instanceof SMWExpLiteral ) {
					$prop_decl_type = SMW_SERIALIZER_DECL_APROP;
					$this->serializeExpLiteral( $object );
				} else { // resource (maybe blank node), could have subdescriptions
					$prop_decl_type = SMW_SERIALIZER_DECL_OPROP;
					$collection = $value->getCollection();
					if ( $collection !== false ) { // RDF-style collection (list)
						$this->post_ns_buffer .= "( ";
						foreach ( $collection as $subvalue ) {
							$this->serializeNestedExpData( $subvalue, $indent . "\t\t" );
							if ( $class_type_prop ) {
								$this->requireDeclaration( $subvalue, SMW_SERIALIZER_DECL_CLASS );
							}
						}
						$this->post_ns_buffer .= " )";
					} else {
						if ( $class_type_prop ) {
							$this->requireDeclaration( $object, SMW_SERIALIZER_DECL_CLASS );
						}
						if ( count( $value->getProperties() ) > 0 ) { // resource with data: serialise
							$this->post_ns_buffer .= "\n";
							$this->serializeNestedExpData( $value, $indent . "\t\t" );
						} else { // resource without data: may need to be queued
							$this->serializeExpResource( $object );
						}
					}
				}

				if ( !$prop_decl_queued ) {
					$this->requireDeclaration( $property, $prop_decl_type );
					$prop_decl_queued = true;
				}
			}
		}
		$this->post_ns_buffer .= ( $bnode ? " ]" : " ." ) . ( $indent == '' ? "\n\n" : '' );
	}
	
	protected function serializeExpLiteral( SMWExpLiteral $element ) {
		$this->post_ns_buffer .= '"' . str_replace( array( '\\', "\n", '"' ), array( '\\\\', "\\n", '\"' ), $element->getName() ) . '"';
		$dt = $element->getDatatype();
		if ( ( $dt != '' ) && ( $dt != 'http://www.w3.org/2001/XMLSchema#string' ) ) {
			$count = 0;
			$newdt = str_replace( 'http://www.w3.org/2001/XMLSchema#', 'xsd:',  $dt, $count );
			if ( $count == 1 ) {
				$this->post_ns_buffer .= '^^' . $newdt;
			} else {
				$this->post_ns_buffer .= '^^<' . $dt . '>';
			}
		}
	}
	
	protected function serializeExpResource( SMWExpResource $element ) {
		if ( $element->isBlankNode() ) {
			$this->post_ns_buffer .= '[]';
		} else {
			if ( $element->getQName() !== false ) {
				$this->post_ns_buffer .= $element->getQName();
			} else {
				$this->post_ns_buffer .= '<' . str_replace( '>', '\>', SMWExporter::expandURI( $element->getName() ) ) . '>';
			}
		}
	}

}
