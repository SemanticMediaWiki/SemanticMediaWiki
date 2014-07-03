<?php

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;

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
		if ( self::$m_exporturl !== false ) return;
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
		self::$m_ent_wikiurl  = $wgServer . str_replace( '$1', '', $wgArticlePath );
		self::$m_ent_wiki     = $smwgNamespace;
		self::$m_ent_property = self::$m_ent_wiki . self::encodeURI( urlencode( str_replace( ' ', '_', $wgContLang->getNsText( SMW_NS_PROPERTY ) . ':' ) ) );
		$title = SpecialPage::getTitleFor( 'ExportRDF' );
		self::$m_exporturl    = self::$m_ent_wikiurl . $title->getPrefixedURL();
	}

	/**
	* Extract and prepare for export, properties from the subject of the given SMWSemanticData object.
	 * @param $semdata SMWSemanticData
	 * @param $createNewContainer boolean - Set to true to create a new SMWDIWikiPage to hold the properties
	 * @return SMWExpData
	*/
	static protected function makeExportDataForSemanticData(SMWSemanticData $semdata, $createNewContainer=false) {
		$subject = $semdata->getSubject();
		if ( $subject->getNamespace() == SMW_NS_PROPERTY ) {
			$types = $semdata->getPropertyValues( new SMWDIProperty( '_TYPE' ) );
		} else {
			$types = array();
		}
		$result = self::makeExportDataForSubject( $createNewContainer ? SMWDIWikiPage::newFromTitle( $subject->getTitle() ) : $subject, end( $types ) );
		foreach ( $semdata->getProperties() as $property ) {
			// for subobjects makeExportDataForSubject() computes the sortkey
			if ($subject->getSubObjectName() !== '' && $property->getKey() == '_SKEY') {
			   continue;
			}
			self::addPropertyValues( $property, $semdata->getPropertyValues( $property ), $result );
		}
		return $result;
	}

	/**
	 * Create exportable data from a given semantic data record.
	 *
	 * @param $semdata SMWSemanticData
	 * @return ARRAY of SMWExpData
	 */
	static public function makeExportData( SMWSemanticData $semdata ) {
		self::initBaseURIs();
		$result = array();

    		// Add main page properties attached to the data record's own subject
    		$result[] = self::makeExportDataForSemanticData($semdata, false);

    		// Now get semantic data from any subobjects present and prepare them for export 
		foreach( $semdata->getSubSemanticData() as $subObjectData ) { //an array of SMWSemanticData objects
			// each of these will get its own SMWDIWikiPage to hold the subobject properties
			$result[] = self::makeExportDataForSemanticData( $subObjectData, true );
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
	 * @todo Take into account whether the wiki page belongs to a builtin property, and ensure URI alignment/type declaration in this case.
	 *
	 * @param $diWikiPage SMWDIWikiPage
	 * @param $typesvalueforproperty mixed either an SMWTypesValue or null
	 * @param $addStubData boolean to indicate if additional data should be added to make a stub entry for this page
	 * @return SMWExpData
	 */
	static public function makeExportDataForSubject( SMWDIWikiPage $diWikiPage, $typesvalueforproperty = null, $addStubData = false ) {
		global $wgContLang;
		$wikiPageExpElement = self::getDataItemExpElement( $diWikiPage );
		$result = new SMWExpData( $wikiPageExpElement );

		if ( $diWikiPage->getSubobjectName() !== '' ) {
			$result->addPropertyObjectValue( self::getSpecialNsResource( 'rdf', 'type' ), self::getSpecialNsResource( 'swivt', 'Subject' ) );
			$masterPage = new SMWDIWikiPage( $diWikiPage->getDBkey(), $diWikiPage->getNamespace(), $diWikiPage->getInterwiki() );
			$masterExpElement = self::getDataItemExpElement( $masterPage );
			$result->addPropertyObjectValue( self::getSpecialNsResource( 'swivt', 'masterPage' ), $masterExpElement );

			// Subobjects exported to RDF need a sort key if the Sparql server is the primary property store. Otherwise they are not retrieved during #ask queries.
			// The sort key produced here looks like "host page# xxxx", i.e. with a space in the middle.;
			$subObjectLabel = $diWikiPage->getDBkey() . '#' . $diWikiPage->getSubobjectName();
			// The sort key references the datatype
			$sortkey = self::getDataItemExpElement( new SMWDIBlob( str_replace( '_', ' ', $subObjectLabel )));
			$result->addPropertyObjectValue( self::getSpecialPropertyResource( '_SKEY' ), $sortkey );
		} else {
			$pageTitle = str_replace( '_', ' ', $diWikiPage->getDBkey() );
			if ( $diWikiPage->getNamespace() !== 0 ) {
				$prefixedSubjectTitle = $wgContLang->getNsText( $diWikiPage->getNamespace()) . ":" . $pageTitle;
			} else {
				$prefixedSubjectTitle = $pageTitle;
			}
			$prefixedSubjectUrl = wfUrlencode( str_replace( ' ', '_', $prefixedSubjectTitle ) );

			switch ( $diWikiPage->getNamespace() ) {
				case NS_CATEGORY: case SMW_NS_CONCEPT:
					$maintype_pe = self::getSpecialNsResource( 'owl', 'Class' );
					$label = $pageTitle;
				break;
				case SMW_NS_PROPERTY:
					if ( $typesvalueforproperty == null ) {
						$types = \SMW\StoreFactory::getStore()->getPropertyValues( $diWikiPage, new SMWDIProperty( '_TYPE' ) );
						$typesvalueforproperty = end( $types );
					}
					$maintype_pe = self::getSpecialNsResource( 'owl', self::getOWLPropertyType( $typesvalueforproperty ) );
					$label = $pageTitle;
				break;
				default:
					$label = $prefixedSubjectTitle;
					$maintype_pe = self::getSpecialNsResource( 'swivt', 'Subject' );
			}

			$result->addPropertyObjectValue( self::getSpecialNsResource( 'rdf', 'type' ), $maintype_pe );

			if ( !$wikiPageExpElement->isBlankNode() ) {
				$ed = new SMWExpLiteral( $label );
				$result->addPropertyObjectValue( self::getSpecialNsResource( 'rdfs', 'label' ), $ed );
				$ed = new SMWExpResource( self::getNamespaceUri( 'wikiurl' ) . $prefixedSubjectUrl );
				$result->addPropertyObjectValue( self::getSpecialNsResource( 'swivt', 'page' ), $ed );
				$ed = new SMWExpResource( self::$m_exporturl . '/' . $prefixedSubjectUrl );
				$result->addPropertyObjectValue( self::getSpecialNsResource( 'rdfs', 'isDefinedBy' ), $ed );
				$ed = new SMWExpLiteral( strval( $diWikiPage->getNamespace() ), 'http://www.w3.org/2001/XMLSchema#integer' );
				$result->addPropertyObjectValue( self::getSpecialNsResource( 'swivt', 'wikiNamespace' ), $ed );
				if ( $addStubData ) {
					// Add a default sort key; for pages that exist in the wiki,
					// this is set during parsing
					$defaultSortkey = self::getDataItemExpElement( new SMWDIBlob( str_replace( '_', ' ', $diWikiPage->getDBkey() )));
					$result->addPropertyObjectValue( self::getSpecialPropertyResource( '_SKEY' ), $defaultSortkey );
				}
			}
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
			$pe = self::getResourceElementForProperty( $property );
			$peHelper = self::getResourceElementForProperty( $property, true );

			foreach ( $dataItems as $dataItem ) {
				$ed = self::getDataItemExpElement( $dataItem );
				if ( !is_null( $ed ) ) {
					$expData->addPropertyObjectValue( $pe, $ed );
				}

				$edHelper = self::getDataItemHelperExpElement( $dataItem );
				if ( !is_null( $edHelper ) ) {
					$expData->addPropertyObjectValue( $peHelper, $edHelper );
				}
			}
		} else { // pre-defined property, only exported if known
			$diSubject = $expData->getSubject()->getDataItem();
			// subject wikipage required for disambiguating special properties:
			if ( is_null( $diSubject ) ||
			     $diSubject->getDIType() != SMWDataItem::TYPE_WIKIPAGE ) {
				return;
			}

			$pe = self::getSpecialPropertyResource( $property->getKey(), $diSubject->getNamespace() );
			if ( is_null( $pe ) ) return; // unknown special property, not exported
			// have helper property ready before entering the for loop, even if not needed:
			$peHelper = self::getResourceElementForProperty( $property, true );

			$filterNamespace = ( $property->getKey() == '_REDI' || $property->getKey() == '_URI' );

			foreach ( $dataItems as $dataItem ) {
				// Basic namespace filtering to ensure that types match for redirects etc.
				/// TODO: currently no full check for avoiding OWL DL illegal redirects is done (OWL property type ignored)
				if ( $filterNamespace && !( $dataItem instanceof SMWDIUri ) &&
				     ( !( $dataItem instanceof SMWDIWikiPage ) ||
				        ( $dataItem->getNamespace() != $diSubject->getNamespace() ) ) ) {
					continue;
				}

				$ed = self::getDataItemExpElement( $dataItem );

				if ( !is_null( $ed ) ) {
					if ( $property->getKey() == '_CONC' &&
					     $ed->getSubject()->getUri() === '' ) {
						// equivalent to anonymous class -> simplify description
						foreach ( $ed->getProperties() as $subp ) {
							if ( $subp->getUri() != self::getSpecialNsResource( 'rdf', 'type' )->getUri() ) {
								foreach ( $ed->getValues( $subp ) as $subval ) {
									$expData->addPropertyObjectValue( $subp, $subval );
								}
							}
						}
					} elseif ( $property->getKey() == '_REDI' ) {
						$expData->addPropertyObjectValue( $pe, $ed );
						$peUri = self::getSpecialPropertyResource( '_URI' );
						$expData->addPropertyObjectValue( $peUri, $ed );
					} else {
						$expData->addPropertyObjectValue( $pe, $ed );
					}
				}

				$edHelper = self::getDataItemHelperExpElement( $dataItem );
				if ( !is_null( $edHelper ) ) {
					$expData->addPropertyObjectValue( $peHelper, $edHelper );
				}
			}
		}
	}

	/**
	 * Create an SMWExpElement for some internal resource, given by an
	 * SMWDIProperty object.
	 * This code is only applied to user-defined properties, since the
	 * code for special properties in
	 * SMWExporter::getSpecialPropertyResource() may require information
	 * about the namespace in which some special property is used.
	 *
	 * @param $diProperty SMWDIProperty
	 * @param $helperProperty boolean determines if an auxiliary property resource to store a helper value (see SMWExporter::getDataItemHelperExpElement()) should be generated
	 * @return SMWExpResource
	 */
	static public function getResourceElementForProperty( SMWDIProperty $diProperty, $helperProperty = false ) {
		$diWikiPage = $diProperty->getDiWikiPage();
		if ( is_null( $diWikiPage ) ) {
			throw new Exception( 'SMWExporter::getResourceElementForProperty() can only be used for non-inverse, user-defined properties.' );
		} elseif ( $helperProperty ) {
			return self::getResourceElementForWikiPage( $diWikiPage, 'aux' );
		} else {
			return self::getResourceElementForWikiPage( $diWikiPage );
		}
	}

	/**
	 * Create an SMWExpElement for some internal resource, given by an
	 * SMWDIWikiPage object. This is the one place in the code where URIs
	 * of wiki pages and user-defined properties are determined. A modifier
	 * can be given to make variants of a URI, typically done for
	 * auxiliary properties. In this case, the URI is modiied by appending
	 * "-23$modifier" where "-23" is the URI encoding of "#" (a symbol not
	 * occuring in MW titles).
	 *
	 * @param $diWikiPage SMWDIWikiPage or SMWDIProperty
	 * @param $modifier string, using only Latin letters and numbers
	 * @return SMWExpResource
	 */
	static public function getResourceElementForWikiPage( SMWDIWikiPage $diWikiPage, $modifier = '' ) {
		global $wgContLang;

		if ( $diWikiPage->getNamespace() == NS_MEDIA ) { // special handling for linking media files directly (object only)
			$title = Title::makeTitle( $diWikiPage->getNamespace(), $diWikiPage->getDBkey() ) ;
			$file = wfFindFile( $title );
			if ( $file !== false ) {
				return new SMWExpResource( $file->getFullURL() );
			} // else: Medialink to non-existing file :-/ fall through
		}

		if ( $diWikiPage->getSubobjectName() !== '' ) {
			$modifier = $diWikiPage->getSubobjectName();
		}

		if ( $modifier === '' ) {
			$importProperty = new SMWDIProperty( '_IMPO' );
			$importDis = \SMW\StoreFactory::getStore()->getPropertyValues( $diWikiPage, $importProperty );
			$importURI = ( count( $importDis ) > 0 );
		} else {
			$importURI = false;
		}

		if ( $importURI ) {
			$importValue = DataValueFactory::getInstance()->newDataItemValue( current( $importDis ), $importProperty );
			$namespace = $importValue->getNS();
			$namespaceId = $importValue->getNSID();
			$localName = $importValue->getLocalName();
		} else {
			$localName = '';
			if ( $diWikiPage->getNamespace() == SMW_NS_PROPERTY ) {
				$namespace = self::getNamespaceUri( 'property' );
				$namespaceId = 'property';
				$localName = self::encodeURI( rawurlencode( $diWikiPage->getDBkey() ) );
			}
			if ( ( $localName === '' ) ||
			     ( in_array( $localName{0}, array( '-', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ) ) ) ) {
				$namespace = self::getNamespaceUri( 'wiki' );
				$namespaceId = 'wiki';
				if ( $diWikiPage->getNamespace() !== 0 ) {
					$localName = str_replace( ' ', '_', $wgContLang->getNSText( $diWikiPage->getNamespace() ) ) . ':' . $diWikiPage->getDBkey();
				} else {
					$localName = $diWikiPage->getDBkey();
				}
				$localName = self::encodeURI( wfUrlencode( $localName ) );
			}
			if ( $modifier !== '' ) {
				$localName .=  '-23' . $modifier;
			}
		}

		return new SMWExpNsResource( $localName, $namespace, $namespaceId, $diWikiPage );
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
				$dbKeyRaw = rawurldecode( self::decodeURI( $localName ) );
				# We split the decoded URI to isolate the subobject name
				$parts = explode( '#', $dbKeyRaw, 2 ); 
				$dbKey = $parts[0]; # always
				if ( count( $parts ) == 2 ) {
					$subobjectname = $parts[1];
				} else {
					$subobjectname = '';
				}

				# Explode key again to look for namespace prefixes
				$parts = explode( ':', $dbKey, 2 );
				if ( count( $parts ) == 1 ) {
					$dataItem = new SMWDIWikiPage( $dbKey, NS_MAIN, '', $subobjectname );
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
						$dataItem = new SMWDIWikiPage( $parts[1], $namespaceId, '', $subobjectname );
					} else {
						$title = Title::newFromDBkey( $dbKey );

						if ( !is_null( $title ) ) {
							$dataItem = new SMWDIWikiPage( $title->getDBkey(), $title->getNamespace(), $title->getInterwiki(), $subobjectname );
						}
					}
				}
			} // else: not in wiki namespace -- TODO: this could be an imported URI
		} else {
			// TODO (currently not needed, but will be useful for displaying external SPARQL results)
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
			$type = DataTypeRegistry::getInstance()->findTypeId( str_replace( '_', ' ', $type->getDBkey() ) );
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
				return self::getSpecialNsResource( 'rdf', 'type' );
			case '_SUBC':
				return self::getSpecialNsResource( 'rdfs', 'subClassOf' );
			case '_CONC':
				return self::getSpecialNsResource( 'owl', 'equivalentClass' );
			case '_URI':
				if ( $forNamespace == NS_CATEGORY || $forNamespace == SMW_NS_CONCEPT ) {
					return self::getSpecialNsResource( 'owl', 'equivalentClass' );
				} elseif ( $forNamespace == SMW_NS_PROPERTY ) {
					return self::getSpecialNsResource( 'owl', 'equivalentProperty' );
				} else {
					return self::getSpecialNsResource( 'owl', 'sameAs' );
				}
			case '_REDI':
				return self::getSpecialNsResource( 'swivt', 'redirectsTo' );
			case '_SUBP':
				if ( $forNamespace == SMW_NS_PROPERTY ) {
					return self::getSpecialNsResource( 'rdfs', 'subPropertyOf' );
				} else {
					return null;
				}
			case '_MDAT':
				return self::getSpecialNsResource( 'swivt', 'wikiPageModificationDate' );
			case '_CDAT':
				return self::getSpecialNsResource( 'swivt', 'wikiPageCreationDate' );
			case '_LEDT':
				return self::getSpecialNsResource( 'swivt', 'wikiPageLastEditor' );
			case '_NEWP':
				return self::getSpecialNsResource( 'swivt', 'wikiPageIsNew' );
			case '_SKEY':
				return self::getSpecialNsResource( 'swivt', 'wikiPageSortKey' );
			case '_TYPE':
				return self::getSpecialNsResource( 'swivt', 'type' );
			default:
				return self::getSpecialNsResource( 'swivt', 'specialProperty' . $propertyKey );
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
		if ( $namespace !== '' ) {
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
		$ed = self::getSpecialNsResource( 'owl', 'Ontology' );
		$data->addPropertyObjectValue( self::getSpecialNsResource( 'rdf', 'type' ), $ed );
		$ed = new SMWExpLiteral( date( DATE_W3C ), 'http://www.w3.org/2001/XMLSchema#dateTime' );
		$data->addPropertyObjectValue( self::getSpecialNsResource( 'swivt', 'creationDate' ), $ed );
		$ed = new SMWExpResource( 'http://semantic-mediawiki.org/swivt/1.0' );
		$data->addPropertyObjectValue( self::getSpecialNsResource( 'owl', 'imports' ), $ed );
		return $data;
	}

	/**
	 * Create an SWMExpElement that encodes the data of the given
	 * dataitem object. This method is meant to be used when exporting a
	 * dataitem as a subject or object. To get the URI of a property, use
	 * SMWExporter::getResourceElementForProperty() or
	 * SMWExporter::getSpecialPropertyResource().
	 *
	 * @param $dataItem SMWDataItem
	 * @return SMWExpElement
	 */
	static public function getDataItemExpElement( SMWDataItem $dataItem ) {
		switch ( $dataItem->getDIType() ) {
			case SMWDataItem::TYPE_NUMBER:
				$lit = new SMWExpLiteral( strval( $dataItem->getNumber() ), 'http://www.w3.org/2001/XMLSchema#double', $dataItem );
				return $lit;
			case SMWDataItem::TYPE_BLOB:
				$lit = new SMWExpLiteral( smwfHTMLtoUTF8( $dataItem->getString() ), 'http://www.w3.org/2001/XMLSchema#string', $dataItem );
				return $lit;
			case SMWDataItem::TYPE_BOOLEAN:
				$xsdvalue =  $dataItem->getBoolean() ? 'true' : 'false';
				$lit = new SMWExpLiteral( $xsdvalue, 'http://www.w3.org/2001/XMLSchema#boolean', $dataItem );
				return $lit;
			case SMWDataItem::TYPE_URI:
				$res = new SMWExpResource( $dataItem->getURI(), $dataItem );
				return $res;
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
			case SMWDataItem::TYPE_GEO:
				/// TODO
				return null;
			case SMWDataItem::TYPE_CONTAINER:
				return self::makeExportData( $dataItem->getSemanticData() );
			case SMWDataItem::TYPE_WIKIPAGE:
				return self::getResourceElementForWikiPage( $dataItem );
			case SMWDataItem::TYPE_CONCEPT:
				/// TODO
				return null;
			case SMWDataItem::TYPE_PROPERTY:
				return self::getResourceElementForProperty( $dataItem );
		}
	}

	/**
	 * Create an SWMExpElement that encodes auxiliary data for representing
	 * values of the specified dataitem object in a simplified fashion.
	 * This is done for types of dataitems that are not supported very well
	 * in current systems, or that do not match a standard datatype in RDF.
	 * For example, time points (DITime) are encoded as numbers. The number
	 * can replace the actual time for all query and ordering purposes (the
	 * order in either case is linear and maps to the real number line).
	 * Only data retrieval should better use the real values to avoid that
	 * rounding errors lead to unfaithful recovery of data. Note that the
	 * helper values do not maintain any association with their original
	 * values -- they are a fully redundant alternative representation, not
	 * an additional piece of information for the main values. Even if
	 * decoding is difficult, they must be in one-to-one correspondence to
	 * the original value.
	 *
	 * For dataitems that do not have such a simplification, the method
	 * returns null.
	 *
	 * @note If a helper element is used, then it must be the same as
	 * getDataItemHelperExpElement( $dataItem->getSortKeyDataItem() ).
	 * Query conditions like ">" use sortkeys for values, and helper
	 * elements are always preferred in query answering.
	 *
	 * @param $dataItem SMWDataItem
	 * @return SMWExpElement or null
	 */
	static public function getDataItemHelperExpElement( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_TIME ) {
			$lit = new SMWExpLiteral( (string)$dataItem->getSortKey(), 'http://www.w3.org/2001/XMLSchema#double', $dataItem );
			return $lit;
		} else {
			return null;
		}
	}

	/**
	 * Check whether the values of a given type of dataitem have helper
	 * values in the sense of SMWExporter::getDataItemHelperExpElement().
	 *
	 * @param $dataItemType integer type ID of dataitem (see SMWDataItem)
	 * @return boolean
	 */
	static public function hasHelperExpElement( $dataItemType ) {
		return ( $dataItemType == SMWDataItem::TYPE_TIME );
	}

}
