<?php

use SMW\ApplicationFactory;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Localizer;
use SMW\NamespaceUriFinder;
use SMW\Exporter\DataItemByExpElementMatchFinder;
use SMW\Exporter\ElementFactory;
use SMW\Exporter\DataItemToExpResourceEncoder;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Escaper;
use SMW\Exporter\ResourceBuilders\DispatchingResourceBuilder;

/**
 * SMWExporter is a class for converting internal page-based data (SMWSemanticData) into
 * a format for easy serialisation in OWL or RDF.
 *
 * @author Markus Krötzsch
 * @ingroup SMW
 */
class SMWExporter {

	const POOLCACHE_ID = 'exporter.shared';

	/**
	 * @var SMWExporter
	 */
	private static $instance = null;

	/**
	 * @var DataItemToExpResourceEncoder
	 */
	private static $dataItemToExpResourceEncoder = null;

	/**
	 * @var ElementFactory
	 */
	private static $elementFactory = null;

	/**
	 * @var DataItemByExpElementMatchFinder
	 */
	private static $dataItemByExpElementMatchFinder = null;

	/**
	 * @var DispatchingResourceBuilder
	 */
	private static $dispatchingResourceBuilder = null;

	static protected $m_exporturl = false;
	static protected $m_ent_wiki = false;
	static protected $m_ent_property = false;
	static protected $m_ent_category = false;
	static protected $m_ent_wikiurl = false;

	/**
	 * @since 2.0
	 *
	 * @return SMWExporter
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {

			self::$instance = new self();
			self::$instance->initBaseURIs();

			$applicationFactory = ApplicationFactory::getInstance();

			$poolCache = $applicationFactory->getInMemoryPoolCache();
			$poolCache->resetPoolCacheById( self::POOLCACHE_ID );

			// FIXME with 3.x
			// There is no better way of getting around the static use without BC
			self::$elementFactory = new ElementFactory();

			self::$dispatchingResourceBuilder = new DispatchingResourceBuilder();

			self::$dataItemToExpResourceEncoder = new DataItemToExpResourceEncoder(
				$applicationFactory->getStore()
			);

			self::$dataItemToExpResourceEncoder->reset();

			self::$dataItemToExpResourceEncoder->setBCAuxiliaryUse(
				$applicationFactory->getSettings()->get( 'smwgExportBCAuxiliaryUse' )
			);

			self::$dataItemByExpElementMatchFinder = new DataItemByExpElementMatchFinder(
				$applicationFactory->getStore(),
				self::$m_ent_wiki
			);
		}

		return self::$instance;
	}

	/**
	 * @since 2.0
	 */
	public static function clear() {
		self::$instance = null;
		self::$m_exporturl = false;
	}

	/**
	 * @since 2.2
	 */
	public function resetCacheBy( SMWDIWikiPage $diWikiPage ) {
		self::$dataItemToExpResourceEncoder->resetCacheBy( $diWikiPage );
	}

	/**
	 * Make sure that necessary base URIs are initialised properly.
	 */
	static public function initBaseURIs() {
		if ( self::$m_exporturl !== false ) {
			return;
		}
		global $wgContLang, $wgServer, $wgArticlePath;

		global $smwgNamespace; // complete namespace for URIs (with protocol, usually http://)

		$resolver = Title::makeTitle( NS_SPECIAL, 'URIResolver' );

		if ( '' == $smwgNamespace ) {
			$smwgNamespace = $resolver->getFullURL() . '/';
		} elseif ( $smwgNamespace[0] == '.' ) {
			$smwgNamespace = "http://" . substr( $smwgNamespace, 1 ) . $resolver->getLocalURL() . '/';
		}

		// The article name must be the last part of wiki URLs for proper OWL/RDF export:
		self::$m_ent_wikiurl  = $wgServer . str_replace( '$1', '', $wgArticlePath );
		self::$m_ent_wiki     = $smwgNamespace;

		$property = $GLOBALS['smwgExportBCNonCanonicalFormUse'] ? urlencode( str_replace( ' ', '_', $wgContLang->getNsText( SMW_NS_PROPERTY ) ) ) : 'Property';
		$category = $GLOBALS['smwgExportBCNonCanonicalFormUse'] ? urlencode( str_replace( ' ', '_', $wgContLang->getNsText( NS_CATEGORY ) ) ) : 'Category';

		self::$m_ent_property = self::$m_ent_wiki . Escaper::encodeUri( $property . ':' );
		self::$m_ent_category = self::$m_ent_wiki . Escaper::encodeUri( $category . ':' );

		$title = Title::makeTitle( NS_SPECIAL, 'ExportRDF' );
		self::$m_exporturl    = self::$m_ent_wikiurl . $title->getPrefixedURL();

		// Canonical form, the title object always contains a wgContLang reference
		// therefore replace it
		if ( !$GLOBALS['smwgExportBCNonCanonicalFormUse'] ) {
			$localizer = Localizer::getInstance();

			self::$m_ent_property = $localizer->getCanonicalizedUrlByNamespace( NS_SPECIAL, self::$m_ent_property );
			self::$m_ent_category = $localizer->getCanonicalizedUrlByNamespace( NS_SPECIAL, self::$m_ent_category );
			self::$m_ent_wiki = $localizer->getCanonicalizedUrlByNamespace( NS_SPECIAL, self::$m_ent_wiki );
			self::$m_exporturl = $localizer->getCanonicalizedUrlByNamespace( NS_SPECIAL, self::$m_exporturl );
		}
	}

	/**
	 * Create exportable data from a given semantic data record.
	 *
	 * @param $semdata SMWSemanticData
	 * @return SMWExpData
	 */
	static public function makeExportData( SMWSemanticData $semdata ) {
		self::initBaseURIs();

		$subject = $semdata->getSubject();

		// Make sure to use the canonical form, a localized representation
		// should not carry a reference to a subject (e.g invoked as incoming
		// property caused by a different user language)
		if ( $subject->getNamespace() === SMW_NS_PROPERTY && $subject->getSubobjectName() === '' ) {
			$subject = DIProperty::newFromUserLabel( $subject->getDBKey() )->getCanonicalDiWikiPage();
		}

	       // #1690 Couldn't match a CanonicalDiWikiPage which is most likely caused
	       // by an outdated pre-defined property therefore use the original subject
	       if ( $subject->getDBKey() === '' ) {
	           $subject = $semdata->getSubject();
	       }

		// #649 Alwways make sure to have a least one valid sortkey
		if ( !$semdata->getPropertyValues( new DIProperty( '_SKEY' ) ) && $subject->getSortKey() !== '' ) {
			$semdata->addPropertyObjectValue(
				new DIProperty( '_SKEY' ),
				new SMWDIBlob( $subject->getSortKey() )
			);
		}

		$result = self::makeExportDataForSubject( $subject );

		foreach ( $semdata->getProperties() as $property ) {
			self::addPropertyValues( $property, $semdata->getPropertyValues( $property ), $result, $subject );
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
	 * @param $addStubData boolean to indicate if additional data should be added to make a stub entry for this page
	 * @return SMWExpData
	 */
	static public function makeExportDataForSubject( SMWDIWikiPage $diWikiPage, $addStubData = false ) {
		global $wgContLang;

		$wikiPageExpElement = self::getDataItemExpElement( $diWikiPage );
		$result = new SMWExpData( $wikiPageExpElement );

		if ( $diWikiPage->getSubobjectName() !== '' ) {
			$result->addPropertyObjectValue(
				self::getSpecialNsResource( 'rdf', 'type' ),
				self::getSpecialNsResource( 'swivt', 'Subject' )
			);

			$masterPage = new SMWDIWikiPage(
				$diWikiPage->getDBkey(),
				$diWikiPage->getNamespace(),
				$diWikiPage->getInterwiki()
			);

			$result->addPropertyObjectValue(
				self::getSpecialNsResource( 'swivt', 'masterPage' ),
				self::getDataItemExpElement( $masterPage )
			);

			// #649
			// Subobjects contain there individual sortkey's therefore
			// no need to add them twice

			// #520
			$result->addPropertyObjectValue(
				self::getSpecialNsResource( 'swivt', 'wikiNamespace' ),
				new ExpLiteral( strval( $diWikiPage->getNamespace() ), 'http://www.w3.org/2001/XMLSchema#integer' )
			);

		} else {

			// #1410 (use the display title as label when available)
			$displayTitle = DataValueFactory::getInstance()->newDataValueByItem( $diWikiPage )->getDisplayTitle();

			$pageTitle = str_replace( '_', ' ', $diWikiPage->getDBkey() );
			if ( $diWikiPage->getNamespace() !== 0 ) {
				$prefixedSubjectTitle = $wgContLang->getNsText( $diWikiPage->getNamespace()) . ":" . $pageTitle;
			} else {
				$prefixedSubjectTitle = $pageTitle;
			}

			$prefixedSubjectUrl = Escaper::encodeUri( str_replace( ' ', '_', $prefixedSubjectTitle ) );

			switch ( $diWikiPage->getNamespace() ) {
				case NS_CATEGORY: case SMW_NS_CONCEPT:
					$maintype_pe = self::getSpecialNsResource( 'owl', 'Class' );
					$label = $pageTitle;
				break;
				case SMW_NS_PROPERTY:
					$property = new DIProperty( $diWikiPage->getDBKey() );
					$maintype_pe = self::getSpecialNsResource( 'owl', self::getOWLPropertyType( $property->findPropertyTypeID() ) );
					$label = $pageTitle;
				break;
				default:
					$label = $prefixedSubjectTitle;
					$maintype_pe = self::getSpecialNsResource( 'swivt', 'Subject' );
			}

			$result->addPropertyObjectValue( self::getSpecialNsResource( 'rdf', 'type' ), $maintype_pe );

			if ( !$wikiPageExpElement->isBlankNode() ) {
				$ed = new ExpLiteral( $displayTitle !== '' ? $displayTitle : $label );
				$result->addPropertyObjectValue( self::getSpecialNsResource( 'rdfs', 'label' ), $ed );
				$ed = new SMWExpResource( self::$m_exporturl . '/' . $prefixedSubjectUrl );
				$result->addPropertyObjectValue( self::getSpecialNsResource( 'rdfs', 'isDefinedBy' ), $ed );
				$ed = new SMWExpResource( self::getNamespaceUri( 'wikiurl' ) . $prefixedSubjectUrl );
				$result->addPropertyObjectValue( self::getSpecialNsResource( 'swivt', 'page' ), $ed );
				$ed = new ExpLiteral( strval( $diWikiPage->getNamespace() ), 'http://www.w3.org/2001/XMLSchema#integer' );
				$result->addPropertyObjectValue( self::getSpecialNsResource( 'swivt', 'wikiNamespace' ), $ed );

				if ( $addStubData ) {
					// Add a default sort key; for pages that exist in the wiki,
					// this is set during parsing
					$defaultSortkey = new ExpLiteral( $diWikiPage->getSortKey() );
					$result->addPropertyObjectValue( self::getSpecialPropertyResource( '_SKEY' ), $defaultSortkey );
				}

				if ( $diWikiPage->getPageLanguage() ) {
					$result->addPropertyObjectValue(
						self::getSpecialNsResource( 'swivt', 'wikiPageContentLanguage' ),
						new ExpLiteral( strval( $diWikiPage->getPageLanguage() ), 'http://www.w3.org/2001/XMLSchema#string' )
					);
				}

				if ( $diWikiPage->getNamespace() === NS_FILE ) {

					$title = Title::makeTitle( $diWikiPage->getNamespace(), $diWikiPage->getDBkey() );
					$file = wfFindFile( $title );

					if ( $file !== false ) {
						$result->addPropertyObjectValue(
							self::getSpecialNsResource( 'swivt', 'file' ),
							new SMWExpResource( $file->getFullURL() )
						);
					}
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

		$resourceBuilder = self::$dispatchingResourceBuilder->findResourceBuilder( $property );

		if ( $property->isUserDefined() ) {
			foreach ( $dataItems as $dataItem ) {
				$resourceBuilder->addResourceValue( $expData, $property, $dataItem );
			}
		} else { // pre-defined property, only exported if known
			$diSubject = $expData->getSubject()->getDataItem();
			// subject wikipage required for disambiguating special properties:
			if ( is_null( $diSubject ) ||
			     $diSubject->getDIType() != SMWDataItem::TYPE_WIKIPAGE ) {
				return;
			}

			$pe = self::getSpecialPropertyResource( $property->getKey(), $diSubject->getNamespace() );
			if ( is_null( $pe ) ) {
				return; // unknown special property, not exported
			}
			// have helper property ready before entering the for loop, even if not needed:
			$peHelper = self::getResourceElementForProperty( $property, true );

			$filterNamespace = ( $property->getKey() == '_REDI' || $property->getKey() == '_URI' );

			foreach ( $dataItems as $dataItem ) {
				// Basic namespace filtering to ensure that types match for redirects etc.
				/// TODO: currently no full check for avoiding OWL DL illegal redirects is done (OWL property type ignored)
				if ( $filterNamespace && !( $dataItem instanceof SMWDIUri ) &&
				     ( !( $dataItem instanceof SMWDIWikiPage ) ) ) {
					continue;
				}

				$resourceBuilder->addResourceValue( $expData, $property, $dataItem );
			}
		}
	}

	/**
	 * @see DataItemToExpResourceEncoder::mapPropertyToResourceElement
	 */
	static public function getResourceElementForProperty( SMWDIProperty $diProperty, $helperProperty = false, $seekImportVocabulary = true ) {
		return self::$dataItemToExpResourceEncoder->mapPropertyToResourceElement( $diProperty, $helperProperty, $seekImportVocabulary );
	}

	/**
	 * @see DataItemToExpResourceEncoder::mapWikiPageToResourceElement
	 */
	static public function getResourceElementForWikiPage( SMWDIWikiPage $diWikiPage, $markForAuxiliaryUsage = false ) {
		return self::$dataItemToExpResourceEncoder->mapWikiPageToResourceElement( $diWikiPage, $markForAuxiliaryUsage );
	}

	/**
	 * Try to find an SMWDataItem that the given ExpElement might
	 * represent. Returns null if this attempt failed.
	 *
	 * @param ExpElement $expElement
	 * @return SMWDataItem or null
	 */
	public function findDataItemForExpElement( ExpElement $expElement ) {
		return self::$dataItemByExpElementMatchFinder->tryToFindDataItemForExpElement( $expElement );
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
		}

		switch ( $type ) {
			case '_anu':
			return 'AnnotationProperty';
			case '': case '_wpg': case '_wpp': case '_wpc': case '_wpf':
			case '_uri': case '_ema': case '_tel': case '_rec': case '__typ':
			case '__red': case '__spf': case '__spu':
			return 'ObjectProperty';
			default:
			return 'DatatypeProperty';
		}
	}

	/**
	 * Get an ExpNsResource for a special property of SMW, or null if
	 * no resource is assigned to the given property key. The optional
	 * namespace is used to select the proper resource for properties that
	 * must take the type of the annotated object into account for some
	 * reason.
	 *
	 * @param $propertyKey string the Id of the special property
	 * @param $forNamespace integer the namespace of the page which has a value for this property
	 * @return ExpNsResource|null
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
			case '_IMPO':
				return self::getSpecialNsResource( 'swivt', 'specialImportedFrom' );
			default:
				return self::getSpecialNsResource( 'swivt', 'specialProperty' . $propertyKey );
		}
	}


	/**
	 * Create an ExpNsResource for some special element that belongs to
	 * a known vocabulary. An exception is generated when given parameters
	 * that do not fit any known vocabulary.
	 *
	 * @param $namespaceId string (e.g. "rdf")
	 * @param $localName string (e.g. "type")
	 * @return ExpNsResource
	 */
	static public function getSpecialNsResource( $namespaceId, $localName ) {
		$namespace = self::getNamespaceUri( $namespaceId );
		if ( $namespace !== '' ) {
			return new ExpNsResource( $localName, $namespace, $namespaceId );
		} else {
			throw new InvalidArgumentException( "The vocabulary '$namespaceId' is not a known special vocabulary." );
		}
	}

	/**
	 * This function expands standard XML entities used in some generated
	 * URIs. Given a string with such entities, it returns a string with
	 * all entities properly replaced.
	 *
	 * @note The function SMWExporter::getInstance()->getNamespaceUri() is often more
	 * suitable. This XML-specific method might become obsolete.
	 *
	 * @param $uri string of the URI to be expanded
	 * @return string of the expanded URI
	 */
	static public function expandURI( $uri ) {
		self::initBaseURIs();
		$uri = str_replace( array( '&wiki;', '&wikiurl;', '&property;', '&category;', '&owl;', '&rdf;', '&rdfs;', '&swivt;', '&export;' ),
		                    array( self::$m_ent_wiki, self::$m_ent_wikiurl, self::$m_ent_property, self::$m_ent_category, 'http://www.w3.org/2002/07/owl#', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'http://www.w3.org/2000/01/rdf-schema#', 'http://semantic-mediawiki.org/swivt/1.0#',
		                    self::$m_exporturl ),
		                    $uri );
		return $uri;
	}

	/**
	 * @return string
	 */
	public function decodeURI( $uri ) {
		return Escaper::decodeUri( $uri );
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

		if ( ( $uri = NamespaceUriFinder::getUri( $shortName ) ) !== false ) {
			return $uri;
		}

		switch ( $shortName ) {
			case 'wiki':
			return self::$m_ent_wiki;
			case 'wikiurl':
			return self::$m_ent_wikiurl;
			case 'property':
			return self::$m_ent_property;
			case 'category':
			return self::$m_ent_category;
			case 'export':
			return self::$m_exporturl;
			case 'owl':
			return 'http://www.w3.org/2002/07/owl#';
			case 'rdf':
			return 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
			case 'rdfs':
			return 'http://www.w3.org/2000/01/rdf-schema#';
			case 'swivt':
			return 'http://semantic-mediawiki.org/swivt/1.0#';
			case 'xsd':
			return 'http://www.w3.org/2001/XMLSchema#';
			default:
			return '';
		}
	}

	/**
	 * Create an SMWExpData container that encodes the ontology header for an
	 * SMW exported OWL file.
	 *
	 * @param string $ontologyuri specifying the URI of the ontology, possibly
	 * empty
	 *
	 * @return SMWExpData
	 */
	static public function getOntologyExpData( $ontologyuri ) {
		$data = new SMWExpData( new SMWExpResource( $ontologyuri ) );
		$ed = self::getSpecialNsResource( 'owl', 'Ontology' );
		$data->addPropertyObjectValue( self::getSpecialNsResource( 'rdf', 'type' ), $ed );
		$ed = new ExpLiteral( date( DATE_W3C ), 'http://www.w3.org/2001/XMLSchema#dateTime' );
		$data->addPropertyObjectValue( self::getSpecialNsResource( 'swivt', 'creationDate' ), $ed );
		$ed = new SMWExpResource( 'http://semantic-mediawiki.org/swivt/1.0' );
		$data->addPropertyObjectValue( self::getSpecialNsResource( 'owl', 'imports' ), $ed );
		return $data;
	}

	/**
	 * @see ElementFactory::mapDataItemToElement
	 */
	static public function getDataItemExpElement( SMWDataItem $dataItem ) {
		return self::$elementFactory->newByDataItem( $dataItem );
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
	 * @return ExpElement|null
	 */
	static public function getDataItemHelperExpElement( SMWDataItem $dataItem ) {

		if ( $dataItem->getDIType() == SMWDataItem::TYPE_TIME ) {
			return new ExpLiteral( (string)$dataItem->getSortKey(), 'http://www.w3.org/2001/XMLSchema#double', '', $dataItem );
		}

		if ( $dataItem->getDIType() == SMWDataItem::TYPE_GEO ) {
			return new ExpLiteral( (string)$dataItem->getSortKey(), 'http://www.w3.org/2001/XMLSchema#string', '', $dataItem );
		}

		return null;
	}

	/**
	 * Check whether the values of a given type of dataitem have helper
	 * values in the sense of SMWExporter::getInstance()->getDataItemHelperExpElement().
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	static public function hasHelperExpElement( DIProperty $property ) {
		return ( $property->findPropertyTypeID() === '_dat' || $property->findPropertyTypeID() === '_geo' ) || ( !$property->isUserDefined() && !self::hasSpecialPropertyResource( $property ) );
	}

	static protected function hasSpecialPropertyResource( DIProperty $property ) {
		return $property->getKey() === '_SKEY' ||
			$property->getKey() === '_INST' ||
			$property->getKey() === '_MDAT' ||
			$property->getKey() === '_SUBC' ||
			$property->getKey() === '_SUBP' ||
			$property->getKey() === '_TYPE' ||
			$property->getKey() === '_IMPO' ||
			$property->getKey() === '_URI';
	}

}
