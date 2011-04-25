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
		SMWExporter::$m_exporturl    = SMWExporter::$m_ent_wikiurl . $title->getPrefixedURL();
	}

	/**
	 * Create exportable data from a given semantic data record.
	 *
	 * @param $semdata SMWSemanticData
	 * @return SMWExpData
	 */
	static public function makeExportData( SMWSemanticData $semdata ) {
		SMWExporter::initBaseURIs();
		$subject = $semdata->getSubject();
		if ( $subject->getNamespace() == SMW_NS_PROPERTY ) {
			$types = $semdata->getPropertyValues( new SMWDIProperty( '_TYPE' ) );
		} else {
			$types = array();
		}
		$result = SMWExporter::makeExportDataForSubject( $subject, end( $types ) );
		foreach ( $semdata->getProperties() as $property ) {
			SMWExporter::addPropertyValues( $property, $semdata->getPropertyValues( $property ), $result );
		}
		return $result;
	}
	
	/**
	 * Make an SMWExpData object for the given page, and include the basic
	 * properties about this subject that are not directly represented by
	 * SMW property values. The optional parameter $typevalueforproperty
	 * can be used to pass a particular SMWTypesValue object that is used
	 * for determining the OWL type for property pages.
	 *
	 * @param $diWikiPage SMWDIWikiPage
	 * @param $typesvalueforproperty mixed either an SMWTypesValue or null
	 * @param $addStubData boolean to indicate if additional data should be added to make a stub entry for this page
	 * @return SMWExpData
	 */
	static public function makeExportDataForSubject( SMWDIWikiPage $diWikiPage, $typesvalueforproperty = null, $addStubData = false ) {		
		global $wgContLang;
		$result = new SMWExpData( self::getDataItemExpElement( $diWikiPage ) );
		$pageTitle = str_replace( '_', ' ', $diWikiPage->getDBkey() );
		if ( $diWikiPage->getNamespace() !== 0 ) {
			$prefixedSubjectTitle = $wgContLang->getNsText( $diWikiPage->getNamespace()) . ":" . $pageTitle;
		} else {
			$prefixedSubjectTitle = $pageTitle;
		}
		$prefixedSubjectUrl = wfUrlencode( str_replace( ' ', '_', $prefixedSubjectTitle ) );
		switch ( $diWikiPage->getNamespace() ) {
			case NS_CATEGORY: case SMW_NS_CONCEPT:
				$maintype_pe = SMWExporter::getSpecialNsResource( 'owl', 'Class' );
				$label = $pageTitle;
			break;
			case SMW_NS_PROPERTY:
				if ( $typesvalueforproperty == null ) {
					$types = smwfGetStore()->getPropertyValues( $diWikiPage, new SMWDIProperty( '_TYPE' ) );
					$typesvalueforproperty = end( $types );
				}
				$maintype_pe = SMWExporter::getSpecialNsResource( 'owl', SMWExporter::getOWLPropertyType( $typesvalueforproperty ) );
				$label = $pageTitle;
			break;
			default:
				$label = $prefixedSubjectTitle;
				$maintype_pe = SMWExporter::getSpecialNsResource( 'swivt', 'Subject' );
		}
		$ed = new SMWExpLiteral( $label );
		$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdfs', 'label' ), $ed );
		$ed = new SMWExpResource( self::getNamespaceUri( 'wikiurl' ) . $prefixedSubjectUrl );
		$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'page' ), $ed );
		$ed = new SMWExpResource( SMWExporter::$m_exporturl . '/' . $prefixedSubjectUrl );
		$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdfs', 'isDefinedBy' ), $ed );
		$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ), $maintype_pe );
		$ed = new SMWExpLiteral( $diWikiPage->getNamespace(), 'http://www.w3.org/2001/XMLSchema#integer' );
		$result->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'wikiNamespace' ), $ed );
		if ( $addStubData ) {
			$defaultSortkey = new SMWExpLiteral( str_replace( '_', ' ', $diWikiPage->getDBkey() ) );
			$result->addPropertyObjectValue( self::getSpecialPropertyResource( '_SKEY' ), $defaultSortkey );
		}
		return $result;
	}
	
	/**
	 * Extend a given SMWExpData element by adding export data for the
	 * specified property data itme. This method is called when
	 * constructing export data structures from SMWSemanticData objects.
	 *
	 * @param $property SMWDIProperty
	 * @param $dataItems array of SMWDataItem objects for the given property
	 * @param $data SMWExpData to add the data to
	 */
	static public function addPropertyValues( SMWDIProperty $property, array $dataItems, SMWExpData &$expData ) {
		if ( $property->isUserDefined() ) {
			$pe = SMWExporter::getResourceElement( $property );
			foreach ( $dataItems as $dataItem ) {
				$ed = self::getDataItemExpElement( $dataItem );
				if ( $ed !== null ) {
					$expData->addPropertyObjectValue( $pe, $ed );
				}
			}
		} else { // pre-defined property, only exported if known
			$diSubject = $expData->getSubject()->getDataItem();
			if ( ( $diSubject == null ) || ( $diSubject->getDIType() != SMWDataItem::TYPE_WIKIPAGE ) ) {
				return; // subject datavalue (wikipage) required for treating special properties properly
			}

			$pe = self::getSpecialPropertyResource( $property->getKey(), $diSubject->getNamespace() );
			if ( $pe === null ) return; // unknown special property, not exported 
			if ( $property->getKey() == '_REDI' || $property->getKey() == '_URI' ) {
				$filterNamespace = true;
				if ( $property->getKey() == '_REDI' ) {
					$pe = array( $pe, self::getSpecialPropertyResource( '_URI' ) );
				}
			} else {
				$filterNamespace = false;
			}

			foreach ( $dataItems as $dataItem ) {
				// Basic namespace filtering to ensure that types match for redirects etc.
				/// TODO: currently no full check for avoiding OWL DL illegal redirects is done (OWL property type ignored)
				if ( $filterNamespace && !( $dataItem instanceof SMWDIUri ) &&
				     ( !( $dataItem instanceof SMWDIWikiPage ) ||
				        ( $dataItem->getNamespace() != $diSubject->getNamespace() ) ) ) {
					continue;
				}
				$ed = self::getDataItemExpElement( $dataItem );
				if ( $ed !== null ) {
					if ( ( $property->getKey() == '_CONC' ) && ( $ed->getSubject()->getUri() == '' ) ) {
						// equivalent to anonymous class -> simplify description
						foreach ( $ed->getProperties() as $subp ) {
							if ( $subp->getUri() != SMWExporter::getSpecialNsResource( 'rdf', 'type' )->getUri() ) {
								foreach ( $ed->getValues( $subp ) as $subval ) {
									$expData->addPropertyObjectValue( $subp, $subval );
								}
							}
						}
					} elseif ( is_array( $pe ) ) {
						foreach ( $pe as $extraPropertyElement ) {
							$expData->addPropertyObjectValue( $extraPropertyElement, $ed );
						}
					} else {
						$expData->addPropertyObjectValue( $pe, $ed );
					}
				}
			}
		}
	}

	/**
	 * Create an SMWExpElement for some internal resource, given by an
	 * SMWDIWikiPage or SMWDIProperty object.
	 * This is the one place in the code where URIs of wiki pages and
	 * properties are defined.
	 *
	 * @param $resource SMWDataItem must be SMWDIWikiPage or SMWDIProperty
	 * @return SMWExpResource
	 */
	static public function getResourceElement( SMWDataItem $resource ) {
		global $wgContLang;
		if ( $resource instanceof SMWDIWikiPage ) {
			$diWikiPage = $resource;
		} elseif ( $resource instanceof SMWDIProperty ) {
			$diWikiPage = $resource->getDiWikiPage();
			if ( $diWikiPage === null ) { /// TODO Maybe treat special properties here, too
				return null;
			}
		} else {
			throw new InvalidArgumentException( 'SMWExporter::getResourceElement() expects an object of type SMWDIWikiPage or SMWDIProperty' );
		}
		$importDis = smwfGetStore()->getPropertyValues( $diWikiPage, new SMWDIProperty( '_IMPO' ) );
		if ( count( $importDis ) > 0 ) {
			$importValue = SMWDataValueFactory::newDataItemValue( current( $importDis ) );
			$namespace = $importValue->getNS();
			$namespaceid = $importValue->getNSID();
			$localname = $importValue->getLocalName();
		} else {
			$localname = '';
			if ( $diWikiPage->getNamespace() == SMW_NS_PROPERTY ) {
				$namespace = self::getNamespaceUri( 'property' );
				$namespaceid = 'property';
				$localname = SMWExporter::encodeURI( rawurlencode( $diWikiPage->getDBkey() ) );
			}
			if ( ( $localname == '' ) ||
			     ( in_array( $localname[0], array( '-', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ) ) ) ) {
				$namespace = self::getNamespaceUri( 'wiki' );
				$namespaceid = 'wiki';
				if ( $diWikiPage->getNamespace() !== 0 ) {
					$localname = str_replace( ' ', '_', $wgContLang->getNSText( $diWikiPage->getNamespace() ) ) . ":" . $diWikiPage->getDBkey();
				} else {
					$localname = $diWikiPage->getDBkey();
				}
				$localname = SMWExporter::encodeURI( wfUrlencode( $localname ) );
			}
		}

		return new SMWExpNsResource( $localname, $namespace, $namespaceid, $diWikiPage );
	}

	/**
	 * Try to find an SMWDataItem that the given SMWExpElement might
	 * represent. Returns null if this attempt failed.
	 *
	 * @param SMWExpElement $expElement
	 * @return SMWDataItem or null
	 */
	static public function findDataItemForExpElement( SMWExpElement $expElement ) {
		global $wgContLang;

		$dataItem = null;
		if ( $expElement instanceof SMWExpResource ) {
			$uri = $expElement->getUri();
			$wikiNamespace = self::getNamespaceUri( 'wiki' );
			if ( strpos( $uri, $wikiNamespace ) === 0 ) {
				$localName = substr( $uri, strlen( $wikiNamespace ) );
				$dbKey = urldecode( self::decodeURI( $localName ) );
				$parts = explode( ':', $dbKey, 2 );
				if ( count( $parts ) == 1 ) {
					$dataItem = new SMWDIWikiPage( $dbKey, NS_MAIN, '' );
				} else {
					// try the by far most common cases directly before using Title
					$namespaceName = str_replace( '_', ' ', $parts[0] );
					$namespaceId = -1;
					foreach ( array( SMW_NS_PROPERTY, NS_CATEGORY, NS_USER, NS_HELP ) as $nsId ) {
						if ( $namespaceName == $wgContLang->getNsText( $nsId ) ) {
							$namespaceId = $nsId;
							break;
						}
					}
					if ( $namespaceId != -1 ) {
						$dataItem = new SMWDIWikiPage( $parts[1], $namespaceId, '' );
					} else {
						$title = Title::newFromDBkey( $dbKey );
						if ( $title !== null ) {
							$dataItem = SMWDIWikiPage::newFromTitle( $title );
						}
					}
				}
			}
		} else {
			// TODO
		}
		return $dataItem;
	}

	/**
	 * Determine what kind of OWL property some SMW property should be exported as.
	 * The input is an SMWTypesValue object, a typeid string, or empty (use default)
	 * @todo An improved mechanism for selecting property types here is needed.
	 */
	static public function getOWLPropertyType( $type = '' ) {
		if ( $type instanceof SMWDIWikiPage ) {
			$type = SMWDataValueFactory::findTypeID( str_replace( '_', ' ', $type->getDBkey() ) );
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
	 * Get an SMWExpNsResource for a special property of SMW, or null if
	 * no resource is assigned to the given property key. The optional
	 * namespace is used to select the proper resource for properties that
	 * must take the type of the annotated object into account for some
	 * reason.
	 *
	 * @param $propertyKey string the Id of the special property
	 * @param $forNamespace integer the namespace of the page which has a value for this property
	 * @return SMWExpNsResource or null
	 */
	static public function getSpecialPropertyResource( $propertyKey, $forNamespace = NS_MAIN ) {
		switch ( $propertyKey ) {
			case '_INST': 
				return SMWExporter::getSpecialNsResource( 'rdf', 'type' );
			case '_SUBC':
				return SMWExporter::getSpecialNsResource( 'rdfs', 'subClassOf' );
			case '_CONC': // we actually simplify this below, but need a non-null value now
				return SMWExporter::getSpecialNsResource( 'owl', 'equivalentClass' );
			case '_URI':
				if ( $forNamespace == NS_CATEGORY || $forNamespace == SMW_NS_CONCEPT ) {
					return SMWExporter::getSpecialNsResource( 'owl', 'equivalentClass' );
				} elseif ( $forNamespace == SMW_NS_PROPERTY ) {
					return SMWExporter::getSpecialNsResource( 'owl', 'equivalentProperty' );
				} else {
					return SMWExporter::getSpecialNsResource( 'owl', 'sameAs' );
				}
			case '_REDI':
				return SMWExporter::getSpecialNsResource( 'swivt', 'redirectsTo' );
			case '_SUBP':
				if ( $forNamespace == SMW_NS_PROPERTY ) {
					return SMWExporter::getSpecialNsResource( 'rdfs', 'subPropertyOf' );
				} else {
					return null;
				}
			case '_MDAT':
				return SMWExporter::getSpecialNsResource( 'swivt', 'wikiPageModificationDate' );
			case '_SKEY':
				return SMWExporter::getSpecialNsResource( 'swivt', 'wikiPageSortKey' );
			case '_TYPE': /// TODO: property type currently not exported
				return null;
			default: return null;
		}
	}


	/**
	 * Create an SMWExpNsResource for some special element that belongs to
	 * a known vocabulary. An exception is generated when given parameters
	 * that do not fit any known vocabulary.
	 *
	 * @param $namespaceId string (e.g. "rdf")
	 * @param $localName string (e.g. "type")
	 * @return SMWExpNsResource
	 */
	static public function getSpecialNsResource( $namespaceId, $localName ) {
		$namespace = self::getNamespaceUri( $namespaceId );
		if ( $namespace != '' ) {
			return new SMWExpNsResource( $localName, $namespace, $namespaceId );
		} else {
			throw new InvalidArgumentException( "The vocabulary '$namespaceId' is not a known special vocabulary." );
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
	 *
	 * @note The function SMWExporter::getNamespaceUri() is often more
	 * suitable. This XML-specific method might become obsolete.
	 *
	 * @param $uri string of the URI to be expanded
	 * @return string of the expanded URI
	 */
	static public function expandURI( $uri ) {
		self::initBaseURIs();
		$uri = str_replace( array( '&wiki;', '&wikiurl;', '&property;', '&owl;', '&rdf;', '&rdfs;', '&swivt;', '&export;' ),
		                    array( self::$m_ent_wiki, self::$m_ent_wikiurl, self::$m_ent_property, 'http://www.w3.org/2002/07/owl#', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'http://www.w3.org/2000/01/rdf-schema#', 'http://semantic-mediawiki.org/swivt/1.0#',
		                    self::$m_exporturl ),
		                    $uri );
		return $uri;
	}

	/**
	 * Get the URI of a standard namespace prefix used in SMW, or the empty
	 * string if the prefix is not known.
	 *
	 * @param $shortName string id (prefix) of the namespace
	 * @return string of the expanded URI
	 */
	static public function getNamespaceUri( $shortName ) {
		self::initBaseURIs();
		switch ( $shortName ) {
			case 'wiki':     return self::$m_ent_wiki;
			case 'wikiurl':  return self::$m_ent_wikiurl;
			case 'property': return self::$m_ent_property;
			case 'export':   return self::$m_exporturl;
			case 'owl':      return 'http://www.w3.org/2002/07/owl#';
			case 'rdf':      return 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
			case 'rdfs':     return 'http://www.w3.org/2000/01/rdf-schema#';
			case 'swivt':    return 'http://semantic-mediawiki.org/swivt/1.0#';
			case 'xsd':      return 'http://www.w3.org/2001/XMLSchema#';
			default: return '';
		}
	}

	/**
	 * Create an SMWExpData container that encodes the ontology header for an
	 * SMW exported OWL file.
	 * 
	 * @param string $ontologyuri specifying the URI of the ontology, possibly
	 * empty
	 */
	static public function getOntologyExpData( $ontologyuri ) {
		$data = new SMWExpData( new SMWExpResource( $ontologyuri ) );
		$ed = SMWExporter::getSpecialNsResource( 'owl', 'Ontology' );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'rdf', 'type' ), $ed );
		$ed = new SMWExpLiteral( date( DATE_W3C ), 'http://www.w3.org/2001/XMLSchema#dateTime' );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'swivt', 'creationDate' ), $ed );
		$ed = new SMWExpResource( 'http://semantic-mediawiki.org/swivt/1.0' );
		$data->addPropertyObjectValue( SMWExporter::getSpecialNsResource( 'owl', 'imports' ), $ed );
		return $data;
	}

	/**
	 * Create an SWMExpElement that encodes the data of the given
	 * dataitem object.
	 * 
	 * @param $dataItem SMWDataItem
	 * @return SMWExpElement
	 */
	static public function getDataItemExpElement( SMWDataItem $dataItem ) {
		switch ( $dataItem->getDIType() ) {
			case SMWDataItem::TYPE_NUMBER:	
				$lit = new SMWExpLiteral( $dataItem->getNumber(), 'http://www.w3.org/2001/XMLSchema#double', $dataItem );
				return $lit;
			break;
			case SMWDataItem::TYPE_STRING: case SMWDataItem::TYPE_BLOB:
				$lit = new SMWExpLiteral( smwfHTMLtoUTF8( $dataItem->getString() ), 'http://www.w3.org/2001/XMLSchema#string', $dataItem );
				return $lit;
			break;
			case SMWDataItem::TYPE_BOOLEAN:
				$xsdvalue =  $dataItem->getBoolean() ? 'true' : 'false';
				$lit = new SMWExpLiteral( $xsdvalue, 'http://www.w3.org/2001/XMLSchema#boolean', $dataItem );
				return $lit;
			break;
			case SMWDataItem::TYPE_URI:
				/// TODO This escaping seems very odd. The serialisation should handle such things.
				$res = new SMWExpResource( str_replace( '&', '&amp;', $dataItem->getURI() ), $dataItem );
				return $res;
			break;
			case SMWDataItem::TYPE_TIME:
				$gregorianTime = $dataItem->getForCalendarModel( SMWDITime::CM_GREGORIAN );
				if ( $gregorianTime->getYear() > 0 ) {
					$xsdvalue = str_pad( $gregorianTime->getYear(), 4, "0", STR_PAD_LEFT );
				} else {
					$xsdvalue = '-' . str_pad( 1 - $gregorianTime->getYear(), 4, "0", STR_PAD_LEFT );
				}
				$xsdtype = 'http://www.w3.org/2001/XMLSchema#gYear';
				if ( $gregorianTime->getPrecision() >= SMWDITime::PREC_YM ) {
					$xsdtype = 'http://www.w3.org/2001/XMLSchema#gYearMonth';
					$xsdvalue .= '-' . str_pad( $gregorianTime->getMonth(), 2, "0", STR_PAD_LEFT );
					if ( $gregorianTime->getPrecision() >= SMWDITime::PREC_YMD ) {
						$xsdtype = 'http://www.w3.org/2001/XMLSchema#date';
						$xsdvalue .= '-' . str_pad( $gregorianTime->getDay(), 2, "0", STR_PAD_LEFT );
						if ( $gregorianTime->getPrecision() == SMWDITime::PREC_YMDT ) {
							$xsdtype = 'http://www.w3.org/2001/XMLSchema#dateTime';
							$xsdvalue .= 'T' .
							     sprintf( "%02d", $gregorianTime->getHour() ) . ':' .
							     sprintf( "%02d", $gregorianTime->getMinute()) . ':' .
							     sprintf( "%02d", $gregorianTime->getSecond() );
						}
					}
				}
				$xsdvalue .= 'Z';
				$lit = new SMWExpLiteral( $xsdvalue, $xsdtype, $gregorianTime );
				return $lit;
			break;
			case SMWDataItem::TYPE_GEO:
				/// TODO
				return null;
			break;
			case SMWDataItem::TYPE_CONTAINER:
				/// TODO
				return null;
			break;
			case SMWDataItem::TYPE_WIKIPAGE:
				if ( $dataItem->getNamespace() == NS_MEDIA ) { // special handling for linking media files directly (object only)
					$title = Title::makeTitle( $dataItem->getNamespace(), $dataItem->getDBkey() ) ;
					$file = wfFindFile( $title );
					if ( $file !== false ) {
						return new SMWExpResource( $file->getFullURL() );
					} else { // Medialink to non-existing file :-/
						return SMWExporter::getResourceElement( $dataItem );
					}
				} else {
					return SMWExporter::getResourceElement( $dataItem );
				}
			break;
			case SMWDataItem::TYPE_CONCEPT:
				/// TODO
				return null;
			break;
			case SMWDataItem::TYPE_PROPERTY:
				return SMWExporter::getResourceElement( $dataItem->getDiWikiPage() );
			break;
		}
	}

}
