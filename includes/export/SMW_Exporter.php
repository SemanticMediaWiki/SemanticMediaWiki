<?php
/**
 * @file
 * @ingroup SMW
 */

/**
 * SMWExporter is a class for converting internal page-based data (SMWSemanticData) into
 * a format for easy serialisation in OWL or RDF.
 *
 * @author Markus Krötzsch
 * @ingroup SMW
 */
class SMWExporter {
	static protected $m_exporturl = false;
	static protected $m_ent_wiki = false;
	static protected $m_ent_property = false;
	static protected $m_ent_wikiurl = false;

	/**
	 * Make sure that necessary base URIs are initialised properly.
	 */
	static public function initBaseURIs() {
		if ( SMWExporter::$m_exporturl !== false ) return;
		global $wgContLang, $wgServer, $wgArticlePath;

		global $smwgNamespace; // complete namespace for URIs (with protocol, usually http://)
		if ( '' == $smwgNamespace ) {
			$resolver = SpecialPage::getTitleFor( 'URIResolver' );
			$smwgNamespace = $resolver->getFullURL() . '/';
		} elseif ( $smwgNamespace[0] == '.' ) {
			$resolver = SpecialPage::getTitleFor( 'URIResolver' );
			$smwgNamespace = "http://" . substr( $smwgNamespace, 1 ) . $resolver->getLocalURL() . '/';
		}

		// The article name must be the last part of wiki URLs for proper OWL/RDF export:
		SMWExporter::$m_ent_wikiurl  = $wgServer . str_replace( '$1', '', $wgArticlePath );
		SMWExporter::$m_ent_wiki     = $smwgNamespace;
		SMWExporter::$m_ent_property = SMWExporter::$m_ent_wiki . SMWExporter::encodeURI( urlencode( str_replace( ' ', '_', $wgContLang->getNsText( SMW_NS_PROPERTY ) . ':' ) ) );
		$title = SpecialPage::getTitleFor( 'ExportRDF' );
		SMWExporter::$m_exporturl    = '&wikiurl;' . $title->getPrefixedURL();
	}

	/**
	 * Create exportable data from a given semantic data record. If given, the string $modifier is used
	 * as a modifier to the URI of the subject (e.g. a unit for properties).
	 */
	static public function makeExportData( /*SMWSemanticData*/ $semdata, $modifier = '' ) {
		SMWExporter::initBaseURIs();
		$subject = $semdata->getSubject();
		$result = $subject->getExportData();

		// first set some general parameters for export
		$category_pe = null;
		$subprop_pe = null;
		switch ( $subject->getNamespace() ) {
			case NS_CATEGORY: case SMW_NS_CONCEPT:
				$category_pe = SMWExporter::getSpecialElement( 'rdfs', 'subClassOf' );
				$equality_pe = SMWExporter::getSpecialElement( 'owl', 'equivalentClass' );
				$maintype_pe = SMWExporter::getSpecialElement( 'owl', 'Class' );
				$label = $subject->getText();
			break;
			case SMW_NS_PROPERTY:
				$category_pe = SMWExporter::getSpecialElement( 'rdf', 'type' );
				$subprop_pe = SMWExporter::getSpecialElement( 'rdfs', 'subPropertyOf' );
				$equality_pe = SMWExporter::getSpecialElement( 'owl', 'equivalentProperty' );
				$types = $semdata->getPropertyValues( SMWPropertyValue::makeProperty( '_TYPE' ) );
				$maintype_pe = SMWExporter::getSpecialElement( 'owl', SMWExporter::getOWLPropertyType( end( $types ) ) );
				$label = $subject->getText();
			break;
			default:
				$category_pe = SMWExporter::getSpecialElement( 'rdf', 'type' );
				$equality_pe = SMWExporter::getSpecialElement( 'owl', 'sameAs' );
				$maintype_pe = SMWExporter::getSpecialElement( 'swivt', 'Subject' );
				$label = $subject->getWikiValue();
		}

		// export standard properties
		if ( $modifier != '' ) {
			$modifier = smwfHTMLtoUTF8( $modifier ); ///TODO: check if this is needed anymore
			$label .= ' (' . $modifier . ')';
		}
		$ed = new SMWExpData( new SMWExpLiteral( $label ) );
		$subj_title = $subject->getTitle();
		$result->addPropertyObjectValue( SMWExporter::getSpecialElement( 'rdfs', 'label' ), $ed );
		$ed = new SMWExpData( new SMWExpResource( '&wikiurl;' . $subj_title->getPrefixedURL() ) );
		$result->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'page' ), $ed );
		$ed = new SMWExpData( new SMWExpResource( SMWExporter::$m_exporturl . '/' . $subj_title->getPrefixedURL() ) );
		$result->addPropertyObjectValue( SMWExporter::getSpecialElement( 'rdfs', 'isDefinedBy' ), $ed );
		$result->addPropertyObjectValue( SMWExporter::getSpecialElement( 'rdf', 'type' ), new SMWExpData( $maintype_pe ) );
		$ed = new SMWExpData( new SMWExpLiteral( $subject->getNamespace(), null, 'http://www.w3.org/2001/XMLSchema#integer' ) );
		$result->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'wikiNamespace' ), $ed );
		if ( $modifier != '' ) { // make variant and possibly add meta data on base properties
			if ( $subject->getNamespace() == SMW_NS_PROPERTY ) {
				$ed = new SMWExpData( new SMWExpLiteral( $modifier, null, 'http://www.w3.org/2001/XMLSchema#string' ) );
				$result->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'modifier' ), $ed );
 				$result->addPropertyObjectValue( SMWExporter::getSpecialElement( 'swivt', 'baseProperty' ), new SMWExpData( $result->getSubject() ) );
			}
			$result->setSubject( $result->getSubject()->makeVariant( $modifier ) );
		}

		// export properties based on stored data
		foreach ( $semdata->getProperties() as $key => $property ) {
			if ( $property->isUserDefined() ) {
				$pe = SMWExporter::getResourceElement( $property );
				foreach ( $semdata->getPropertyValues( $property ) as $dv ) {
					$ed = $dv->getExportData();
					$pem = ( $dv->getUnit() != false ) ? $pe->makeVariant( $dv->getUnit() ):$pe;
					if ( $ed !== null ) {
						$result->addPropertyObjectValue( $pem, $ed );
					}
				}
			} else { // pre-defined property
				$pe = null;
				$cat_only = false; // basic namespace checking for equivalent categories
				switch ( $property->getPropertyID() ) {
					case '_INST': ///TODO: distinguish instanceof and subclassof
						$pe = $category_pe;
					break;
					case '_CONC':
						$pe = $equality_pe;
					break;
					case '_URI':
						$pe = $equality_pe;
					break;
					case '_SUBP':
						$pe = $subprop_pe;
					break;
					case '_MDAT':
						$pe = SMWExporter::getSpecialElement( 'swivt', 'wikiPageModificationDate' );
					break;
					case '_REDI': /// TODO: currently no check for avoiding OWL DL illegal redirects is done
						if ( $subject->getNamespace() == SMW_NS_PROPERTY ) {
							$pe = null; // checking the typing here is too cumbersome, smart stores will smush the properties anyway, and the others will not handle them equivalently
						} else {
							$pe = $equality_pe;
							$cat_only = ( $subject->getNamespace() == NS_CATEGORY );
						}
					break;
				}
				if ( $pe !== null ) {
					foreach ( $semdata->getPropertyValues( $property ) as $dv ) {
						if ( $cat_only ) {
							if ( !( $dv instanceof SMWWikiPageValue ) || ( $dv->etNamespace != NS_CATEGORY ) ) {
								continue;
							}
						}
						$ed = $dv->getExportData();
						if ( $ed !== null ) {
							if ( ( $property->getPropertyID() == '_CONC' ) &&
							     ( $ed->getSubject()->getName() == '' ) ) {
								// equivalent to anonymous class -> simplify description
								foreach ( $ed->getProperties() as $subp ) {
									if ( $subp->getName() != SMWExporter::getSpecialElement( 'rdf', 'type' )->getName() ) {
										foreach ( $ed->getValues( $subp ) as $subval ) {
											$result->addPropertyObjectValue( $subp, $subval );
										}
									}
								}
							} else {
								$result->addPropertyObjectValue( $pe, $ed );
							}
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Create an SMWExpElement for some internal resource, given by a Title of
	 * SMWWikiPageValue object. Returns NULL on error.
	 * $makeqname determines whether the function should strive to create a legal
	 * XML QName for the resource.
	 */
	static public function getResourceElement( $resource ) {
		if ( $resource instanceof Title ) {
			$dv = SMWWikiPageValue::makePageFromTitle( $resource );
		} elseif ( $resource instanceof SMWPropertyValue ) {
			$dv = $resource->getWikiPageValue();
		} elseif ( $resource instanceof SMWWikiPageValue ) {
			$dv = $resource;
		} else {
			return null;
		}
		$idvs = smwfGetStore()->getPropertyValues( $dv, SMWPropertyValue::makeProperty( '_IMPO' ) );
		if ( count( $idvs ) > 0 ) {
			$namespace = current( $idvs )->getNS();
			$namespaceid = current( $idvs )->getNSID();
			$localname = current( $idvs )->getLocalName();
		} else {
			$localname = '';
			if ( $dv->getNamespace() == SMW_NS_PROPERTY ) {
				$namespace = '&property;';
				$namespaceid = 'property';
				$localname = SMWExporter::encodeURI( rawurlencode( $dv->getTitle()->getDBkey() ) );
				if ( in_array( $localname[0], array( '-', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ) ) ) {
					$namespace = '&wiki;';
					$namespaceid = 'wiki';
					$localname = SMWExporter::encodeURI( $dv->getTitle()->getPrefixedURL() );
				}
			} else { // no QName needed, do not attempt to make one
				$namespace = false;
				$namespaceid = false;
				$localname = '&wiki;' . SMWExporter::encodeURI( $dv->getTitle()->getPrefixedURL() );
			}
		}

		return new SMWExpResource( $localname, $dv, $namespace, $namespaceid );
	}

	/**
	 * Determine what kind of OWL property some SMW property should be exported as.
	 * The input is an SMWTypesValue object, a typeid string, or empty (use default)
	 * @todo An improved mechanism for selecting property types here is needed.
	 */
	static public function getOWLPropertyType( $type = '' ) {
		if ( $type instanceof SMWTypesValue ) {
			$type = $type->getDBkey();
		} elseif ( $type == false ) {
			$type = '';
		} // else keep $type
		switch ( $type ) {
			case '_anu': return 'AnnotationProperty';
			case '': case '_wpg': case '_wpp': case '_wpc': case '_wpf':
			case '_uri': case '_ema': case '_tel': case '_rec': case '__typ':
			case '__red': case '__spf': case '__spu':
				return 'ObjectProperty';
			default: return 'DatatypeProperty';
		}
	}

	/**
	 * Create an SMWExportElement for some special element that belongs to a known vocabulary.
	 * The parameter given must be a supported namespace id (e.g. "rdfs") and a local name (e.g. "label").
	 * Returns NULL if $namespace is not known.
	 */
	static public function getSpecialElement( $namespace, $localname ) {
		$namespaces = array(
			'swivt' => '&swivt;',
			'rdfs'  => '&rdfs;',
			'rdf'   => '&rdf;',
			'owl'   => '&owl;',
		);
		if ( array_key_exists( $namespace, $namespaces ) ) {
			return new SMWExpResource( $localname, null, $namespaces[$namespace], $namespace );
		} else {
			return null;
		}
	}

	/**
	 * This function escapes symbols that might be problematic in XML in a uniform
	 * and injective way. It is used to encode URIs.
	 */
	static public function encodeURI( $uri ) {
		$uri = str_replace( '-', '-2D', $uri );
		// $uri = str_replace( '_', '-5F', $uri); //not necessary
		$uri = str_replace( array( ':', '"', '#', '&', "'", '+', '!', '%' ),
		                    array( '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-' ),
		                    $uri );
		return $uri;
	}

	/**
	 * This function unescapes URIs generated with SMWExporter::encodeURI. This
	 * allows services that receive a URI to extract e.g. the according wiki page.
	 */
	static public function decodeURI( $uri ) {
		$uri = str_replace( array( '-3A', '-22', '-23', '-26', '-27', '-2B', '-21', '-' ),
		                    array( ':', '"', '#', '&', "'", '+', '!', '%' ),
		                   $uri );
		$uri = str_replace( '%2D', '-', $uri );
		return $uri;
	}

	/**
	 * This function expands standard XML entities used in some generated
	 * URIs. Given a string with such entities, it returns a string with
	 * all entities properly replaced.
	 */
	static public function expandURI( $uri ) {
		SMWExporter::initBaseURIs();
		$uri = str_replace( array( '&wiki;', '&wikiurl;', '&property;', '&owl;', '&rdf;', '&rdfs;', '&swivt;', '&export;' ),
		                    array( SMWExporter::$m_ent_wiki, SMWExporter::$m_ent_wikiurl, SMWExporter::$m_ent_property, 'http://www.w3.org/2002/07/owl#', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'http://www.w3.org/2000/01/rdf-schema#', 'http://semantic-mediawiki.org/swivt/1.0#',
		                    SMWExporter::$m_exporturl ),
		                    $uri );
		return $uri;
	}

}
