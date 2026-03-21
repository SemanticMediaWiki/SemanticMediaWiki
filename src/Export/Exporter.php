<?php

namespace SMW\Export;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use RepoGroup;
use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\Uri;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataValueFactory;
use SMW\Exporter\DataItemMatchFinder;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpResource;
use SMW\Exporter\ElementFactory;
use SMW\Exporter\Escaper;
use SMW\Exporter\ExpResourceMapper;
use SMW\Exporter\ResourceBuilders\DispatchingResourceBuilder;
use SMW\Localizer\Localizer;
use SMW\NamespaceUriFinder;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Site;
use SMW\TypesRegistry;

/**
 * Exporter is a class for converting internal page-based data (SemanticData) into
 * a format for easy serialisation in OWL or RDF.
 *
 * @author Markus Krötzsch
 * @ingroup SMW
 */
class Exporter {

	/**
	 * Object properties link individuals to individuals and is defined as an
	 * instance of the built-in OWL class owl:ObjectProperty.
	 */
	const OWL_OBJECT_PROPERTY = 'ObjectProperty';

	/**
	 * Datatype properties link individuals to data values and is defined as an
	 * instance of the built-in OWL class owl:DatatypeProperty.
	 */
	const OWL_DATATYPE_PROPERTY = 'DatatypeProperty';

	/**
	 * These are needed in OWL DL for semantic reasons as a mechanism to attach
	 * meta-data to owl entities (classes, properties and individuals). This data
	 * is ignored by reasoners.
	 */
	const OWL_ANNOTATION_PROPERTY = 'AnnotationProperty';

	/**
	 * In-memory cache reference
	 */
	const POOLCACHE_ID = 'exporter.shared';

	/**
	 * @var Exporter
	 */
	private static $instance = null;

	/**
	 * @var ExpResourceMapper
	 */
	private static $expResourceMapper = null;

	/**
	 * @var ElementFactory
	 */
	private static $elementFactory = null;

	/**
	 * @var DataItemMatchFinder
	 */
	private static $dataItemMatchFinder = null;

	/** @var RepoGroup */
	private static $mwRepoGroup = null;

	/**
	 * @var DispatchingResourceBuilder
	 */
	private static $dispatchingResourceBuilder = null;

	protected static $m_exporturl = false;
	protected static $m_ent_wiki = false;
	protected static $m_ent_property = false;
	protected static $m_ent_category = false;
	protected static $m_ent_wikiurl = false;

	/**
	 * @since 2.0
	 *
	 * @return Exporter
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

			self::$expResourceMapper = new ExpResourceMapper(
				$applicationFactory->getStore()
			);

			self::$expResourceMapper->reset();

			self::$expResourceMapper->setBCAuxiliaryUse(
				$applicationFactory->getSettings()->get( 'smwgExportBCAuxiliaryUse' )
			);

			self::$dataItemMatchFinder = new DataItemMatchFinder(
				$applicationFactory->getStore(),
				self::$m_ent_wiki
			);

			self::$mwRepoGroup = MediaWikiServices::getInstance()->getRepoGroup();
		}

		return self::$instance;
	}

	/**
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function clear(): void {
		self::$instance = null;
		self::$m_exporturl = false;
	}

	/**
	 * @since 2.2
	 *
	 * @param WikiPage $diWikiPage
	 *
	 * @return void
	 */
	public function resetCacheBy( WikiPage $diWikiPage ): void {
		self::$expResourceMapper->invalidateCache( $diWikiPage );
	}

	/**
	 * Make sure that necessary base URIs are initialised properly.
	 *
	 * @return void
	 */
	public static function initBaseURIs(): void {
		if ( self::$m_exporturl !== false ) {
			return;
		}

		$localizer = Localizer::getInstance();
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		global $smwgNamespace; // complete namespace for URIs (with protocol, usually http://)

		$resolver = $titleFactory->makeTitle( NS_SPECIAL, 'URIResolver' );

		if ( $smwgNamespace == '' ) {
			$smwgNamespace = $resolver->getFullURL() . '/';
		} elseif ( $smwgNamespace[0] == '.' ) {
			$smwgNamespace = "http://" . substr( $smwgNamespace, 1 ) . $resolver->getLocalURL() . '/';
		}

		// The article name must be the last part of wiki URLs for proper OWL/RDF export:
		self::$m_ent_wikiurl  = Site::wikiurl();
		self::$m_ent_wiki     = $smwgNamespace;

		$property = 'Property';
		$category = 'Category';

		if ( $GLOBALS['smwgExportBCNonCanonicalFormUse'] ) {
			$property = urlencode( str_replace( ' ', '_', $localizer->getNsText( SMW_NS_PROPERTY ) ) );
			$category = urlencode( str_replace( ' ', '_', $localizer->getNsText( NS_CATEGORY ) ) );
		}

		self::$m_ent_property = self::$m_ent_wiki . Escaper::encodeUri( $property . ':' );
		self::$m_ent_category = self::$m_ent_wiki . Escaper::encodeUri( $category . ':' );

		$title = $titleFactory->makeTitle( NS_SPECIAL, 'ExportRDF' );
		self::$m_exporturl = self::$m_ent_wikiurl . $title->getPrefixedURL();

		// Canonical form, the title object always contains a wgContLang reference
		// therefore replace it
		if ( !$GLOBALS['smwgExportBCNonCanonicalFormUse'] ) {
			self::$m_ent_property = $localizer->getCanonicalizedUrlByNamespace( NS_SPECIAL, self::$m_ent_property );
			self::$m_ent_category = $localizer->getCanonicalizedUrlByNamespace( NS_SPECIAL, self::$m_ent_category );
			self::$m_ent_wiki = $localizer->getCanonicalizedUrlByNamespace( NS_SPECIAL, self::$m_ent_wiki );
			self::$m_exporturl = $localizer->getCanonicalizedUrlByNamespace( NS_SPECIAL, self::$m_exporturl );
		}
	}

	/**
	 * Create exportable data from a given semantic data record.
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return ExpData
	 */
	public function makeExportData( SemanticData $semanticData ) {
		self::initBaseURIs();

		$subject = $semanticData->getSubject();

		// Make sure to use the canonical form, a localized representation
		// should not carry a reference to a subject (e.g invoked as incoming
		// property caused by a different user language)
		if ( $subject->getNamespace() === SMW_NS_PROPERTY && $subject->getSubobjectName() === '' ) {
			$subject = Property::newFromUserLabel( $subject->getDBKey() )->getCanonicalDiWikiPage();
		}

		// #1690 Couldn't match a CanonicalDiWikiPage which is most likely caused
		// by an outdated pre-defined property therefore use the original subject
		if ( $subject->getDBKey() === '' ) {
			$subject = $semanticData->getSubject();
		}

		// #649 Alwways make sure to have a least one valid sortkey
		if ( !$semanticData->getPropertyValues( new Property( '_SKEY' ) ) && $subject->getSortKey() !== '' ) {

			// @see SMWSQLStore3Writers::getSortKey
			if ( $semanticData->getExtensionData( 'sort.extension' ) !== null ) {
				$sortkey = $semanticData->getExtensionData( 'sort.extension' );
			} else {
				$sortkey = $subject->getSortKey();
			}

			// Extend the subobject sortkey in case no @sortkey was given for an
			// entity
			if ( $subject->getSubobjectName() !== '' ) {

				// Add sort data from some dedicated containers (of a record or
				// reference type etc.) otherwise use the sobj name as extension
				// to distinguish each entity
				if ( $semanticData->getExtensionData( 'sort.data' ) !== null ) {
					$sortkey .= '#' . $semanticData->getExtensionData( 'sort.data' );
				} else {
					$sortkey .= '#' . $subject->getSubobjectName();
				}
			}

			// #649 Be consistent about how sortkeys are stored therefore always
			// normalize even for usages like {{DEFAULTSORT: Foo_bar }}
			$sortkey = str_replace( '_', ' ', $sortkey );

			$semanticData->addPropertyObjectValue(
				new Property( '_SKEY' ),
				new Blob( $sortkey )
			);
		}

		$result = $this->makeExportDataForSubject( $subject );

		foreach ( $semanticData->getProperties() as $property ) {
			self::addPropertyValues( $property, $semanticData->getPropertyValues( $property ), $result );
		}

		return $result;
	}

	/**
	 * Make an ExpData object for the given page, and include the basic
	 * properties about this subject that are not directly represented by
	 * SMW property values. The optional parameter $typevalueforproperty
	 * can be used to pass a particular TypesValue object that is used
	 * for determining the OWL type for property pages.
	 *
	 * @todo Take into account whether the wiki page belongs to a builtin property, and ensure URI alignment/type declaration in this case.
	 *
	 * @param WikiPage $subject
	 * @param bool $addStubData boolean to indicate if additional data should be added to make a stub entry for this page
	 *
	 * @return ExpData
	 */
	public function makeExportDataForSubject( WikiPage $subject, $addStubData = false ): ExpData {
		$wikiPageExpElement = $this->newExpElement( $subject );
		$expData = new ExpData( $wikiPageExpElement );

		if ( $subject->getSubobjectName() !== '' ) {
			$expData->addPropertyObjectValue(
				$this->newExpNsResourceById( 'rdf', 'type' ),
				$this->newExpNsResourceById( 'swivt', 'Subject' )
			);

			$masterPage = new WikiPage(
				$subject->getDBkey(),
				$subject->getNamespace(),
				$subject->getInterwiki()
			);

			$expData->addPropertyObjectValue(
				$this->newExpNsResourceById( 'swivt', 'masterPage' ),
				$this->newExpElement( $masterPage )
			);

			// #649
			// Subobjects contain there individual sortkey's therefore
			// no need to add them twice

			// #520
			$expData->addPropertyObjectValue(
				$this->newExpNsResourceById( 'swivt', 'wikiNamespace' ),
				new ExpLiteral( strval( $subject->getNamespace() ), 'http://www.w3.org/2001/XMLSchema#integer' )
			);

		} else {

			// #1410 (use the display title as label when available)
			$displayTitle = DataValueFactory::getInstance()->newDataValueByItem( $subject )->getDisplayTitle();

			$pageTitle = str_replace( '_', ' ', $subject->getDBkey() );
			if ( $subject->getNamespace() !== 0 ) {
				$prefixedSubjectTitle = Localizer::getInstance()->getNsText( $subject->getNamespace() ) . ":" . $pageTitle;
			} else {
				$prefixedSubjectTitle = $pageTitle;
			}

			$prefixedSubjectUrl = Escaper::encodeUri( str_replace( ' ', '_', $prefixedSubjectTitle ) );

			switch ( $subject->getNamespace() ) {
				case NS_CATEGORY:
				case SMW_NS_CONCEPT:
					$maintype_pe = $this->newExpNsResourceById( 'owl', 'Class' );
					$label = $pageTitle;
					break;
				case SMW_NS_PROPERTY:
					$property = new Property( $subject->getDBKey() );
					$maintype_pe = $this->newExpNsResourceById( 'owl', $this->getOWLPropertyType( $property ) );
					$label = $pageTitle;
					break;
				default:
					$label = $prefixedSubjectTitle;
					$maintype_pe = $this->newExpNsResourceById( 'swivt', 'Subject' );
			}

			$expData->addPropertyObjectValue( $this->newExpNsResourceById( 'rdf', 'type' ), $maintype_pe );

			if ( !$wikiPageExpElement->isBlankNode() ) {
				$ed = new ExpLiteral( $displayTitle !== '' ? $displayTitle : $label );
				$expData->addPropertyObjectValue( $this->newExpNsResourceById( 'rdfs', 'label' ), $ed );
				$ed = new ExpResource( self::$m_exporturl . '/' . $prefixedSubjectUrl );
				$expData->addPropertyObjectValue( $this->newExpNsResourceById( 'rdfs', 'isDefinedBy' ), $ed );
				$ed = new ExpResource( self::getNamespaceUri( 'wikiurl' ) . $prefixedSubjectUrl );
				$expData->addPropertyObjectValue( $this->newExpNsResourceById( 'swivt', 'page' ), $ed );
				$ed = new ExpLiteral( strval( $subject->getNamespace() ), 'http://www.w3.org/2001/XMLSchema#integer' );
				$expData->addPropertyObjectValue( $this->newExpNsResourceById( 'swivt', 'wikiNamespace' ), $ed );

				if ( $addStubData ) {
					// Add a default sort key; for pages that exist in the wiki,
					// this is set during parsing
					$property = new Property( '_SKEY' );
					$resourceBuilder = self::$dispatchingResourceBuilder->findResourceBuilder( $property );
					$resourceBuilder->addResourceValue( $expData, $property, $subject );
				}

				if ( $subject->getPageLanguage() ) {
					$expData->addPropertyObjectValue(
						$this->newExpNsResourceById( 'swivt', 'wikiPageContentLanguage' ),
						new ExpLiteral( strval( $subject->getPageLanguage() ), 'http://www.w3.org/2001/XMLSchema#string' )
					);
				}

				if ( $subject->getNamespace() === NS_FILE ) {
					$title = MediaWikiServices::getInstance()->getTitleFactory()
						->makeTitle( $subject->getNamespace(), $subject->getDBkey() );
					$file = self::$mwRepoGroup->findFile( $title );

					if ( $file !== false ) {
						$expData->addPropertyObjectValue(
							$this->newExpNsResourceById( 'swivt', 'file' ),
							new ExpResource( $file->getFullURL() )
						);
					}
				}
			}
		}

		return $expData;
	}

	/**
	 * Extend a given ExpData element by adding export data for the
	 * specified property data itme. This method is called when
	 * constructing export data structures from SemanticData objects.
	 *
	 * @param Property $property
	 * @param DataItem[] $dataItems of DataItem objects for the given property
	 * @param ExpData &$expData to add the data to
	 */
	public static function addPropertyValues( Property $property, array $dataItems, ExpData &$expData ): void {
		$resourceBuilder = self::$dispatchingResourceBuilder->findResourceBuilder( $property );

		if ( $property->isUserDefined() ) {
			foreach ( $dataItems as $dataItem ) {
				$resourceBuilder->addResourceValue( $expData, $property, $dataItem );
			}
		} else { // pre-defined property, only exported if known
			$diSubject = $expData->getSubject()->getDataItem();
			// subject wikipage required for disambiguating special properties:
			if ( $diSubject === null ||
				 $diSubject->getDIType() != DataItem::TYPE_WIKIPAGE ) {
				return;
			}

			$pe = self::getSpecialPropertyResource( $property->getKey(), $diSubject->getNamespace() );
			if ( $pe === null ) {
				return; // unknown special property, not exported
			}
			// have helper property ready before entering the for loop, even if not needed:
			$peHelper = self::getResourceElementForProperty( $property, true );

			$filterNamespace = ( $property->getKey() == '_REDI' || $property->getKey() == '_URI' );

			foreach ( $dataItems as $dataItem ) {
				// Basic namespace filtering to ensure that types match for redirects etc.
				/// TODO: currently no full check for avoiding OWL DL illegal redirects is done (OWL property type ignored)
				if ( $filterNamespace && !( $dataItem instanceof Uri ) &&
					 ( !( $dataItem instanceof WikiPage ) ) ) {
					continue;
				}

				$resourceBuilder->addResourceValue( $expData, $property, $dataItem );
			}
		}
	}

	/**
	 * @see ExpResourceMapper::mapPropertyToResourceElement
	 */
	public static function getResourceElementForProperty( Property $diProperty, $helperProperty = false, $seekImportVocabulary = true ) {
		return self::$expResourceMapper->mapPropertyToResourceElement( $diProperty, $helperProperty, $seekImportVocabulary );
	}

	/**
	 * @see ExpResourceMapper::mapWikiPageToResourceElement
	 */
	public static function getResourceElementForWikiPage( WikiPage $diWikiPage, $markForAuxiliaryUsage = false ) {
		return self::$expResourceMapper->mapWikiPageToResourceElement( $diWikiPage, $markForAuxiliaryUsage );
	}

	/**
	 * Try to find an DataItem that the given ExpElement might
	 * represent. Returns null if this attempt failed.
	 *
	 * @param ExpElement $expElement
	 * @return DataItem or null
	 */
	public function findDataItemForExpElement( ExpElement $expElement ) {
		return self::$dataItemMatchFinder->matchExpElement( $expElement );
	}

	/**
	 * Determine what kind of OWL property some SMW property should be exported as.
	 * The input is an TypesValue object, a typeid string, or empty (use default)
	 *
	 * @todo An improved mechanism for selecting property types here is needed.
	 */
	public function getOWLPropertyType( Property $property ): string {
		return TypesRegistry::getOWLPropertyByType( $property->findPropertyTypeID() );
	}

	/**
	 * Get an ExpNsResource for a special property of SMW, or null if
	 * no resource is assigned to the given property key. The optional
	 * namespace is used to select the proper resource for properties that
	 * must take the type of the annotated object into account for some
	 * reason.
	 *
	 * @param string $propertyKey string the Id of the special property
	 * @param int $forNamespace integer the namespace of the page which has a value for this property
	 * @return ExpNsResource|null
	 */
	public static function getSpecialPropertyResource( $propertyKey, $forNamespace = NS_MAIN ): ?ExpNsResource {
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
	 * @param string $namespaceId string (e.g. "rdf")
	 * @param string $localName string (e.g. "type")
	 *
	 * @return ExpNsResource
	 */
	public function newExpNsResourceById( $namespaceId, $localName ): ExpNsResource {
		$namespace = self::getNamespaceUri( $namespaceId );

		if ( $namespace !== '' ) {
			return new ExpNsResource( $localName, $namespace, $namespaceId );
		}

		throw new InvalidArgumentException( "The vocabulary '$namespaceId' is not a known special vocabulary." );
	}

	/**
	 * @deprecated since 3.2, use non-static newExpNsResourceById instead
	 *
	 * Create an ExpNsResource for some special element that belongs to
	 * a known vocabulary. An exception is generated when given parameters
	 * that do not fit any known vocabulary.
	 *
	 * @param string $namespaceId string (e.g. "rdf")
	 * @param string $localName string (e.g. "type")
	 * @return ExpNsResource
	 */
	public static function getSpecialNsResource( $namespaceId, $localName ): ExpNsResource {
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
	 * @note The function Exporter::getInstance()->getNamespaceUri() is often more
	 * suitable. This XML-specific method might become obsolete.
	 *
	 * @param string $uri string of the URI to be expanded
	 * @return string of the expanded URI
	 */
	public function expandURI( $uri ) {
		self::initBaseURIs();
		$uri = str_replace(
			[
				'&wiki;',
				'&wikiurl;',
				'&property;',
				'&category;',
				'&owl;',
				'&rdf;',
				'&rdfs;',
				'&swivt;',
				'&export;'
			],
			[
				self::$m_ent_wiki,
				self::$m_ent_wikiurl,
				self::$m_ent_property,
				self::$m_ent_category,
				'http://www.w3.org/2002/07/owl#',
				'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
				'http://www.w3.org/2000/01/rdf-schema#',
				'http://semantic-mediawiki.org/swivt/1.0#',
				self::$m_exporturl
			],
			$uri
		);
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
	 * @param string $shortName string id (prefix) of the namespace
	 * @return string of the expanded URI
	 */
	public static function getNamespaceUri( $shortName ) {
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
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return ExpElement
	 */
	public function newExpElement( DataItem $dataItem ) {
		return self::$elementFactory->newFromDataItem( $dataItem );
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
	 * newAuxiliaryExpElement( $dataItem->getSortKeyDataItem() ).
	 * Query conditions like ">" use sortkeys for values, and helper
	 * elements are always preferred in query answering.
	 *
	 * @param DataItem $dataItem
	 * @return ExpElement|null
	 */
	public function newAuxiliaryExpElement( DataItem $dataItem ) {
		if ( $dataItem->getDIType() == DataItem::TYPE_TIME ) {
			return new ExpLiteral( (string)$dataItem->getSortKey(), 'http://www.w3.org/2001/XMLSchema#double', '', $dataItem );
		}

		if ( $dataItem->getDIType() == DataItem::TYPE_GEO ) {
			return new ExpLiteral( (string)$dataItem->getSortKey(), 'http://www.w3.org/2001/XMLSchema#string', '', $dataItem );
		}
	}

	/**
	 * Check whether the values of a given type of dataitem have helper
	 * values in the sense of Exporter::getInstance()->newAuxiliaryExpElement().
	 *
	 * @param Property $property
	 *
	 * @return bool
	 */
	public static function hasHelperExpElement( Property $property ): bool {
		return ( $property->findPropertyTypeID() === '_dat' ||
			$property->findPropertyTypeID() === '_geo' ) ||
			( !$property->isUserDefined() && !self::hasSpecialPropertyResource( $property ) );
	}

	protected static function hasSpecialPropertyResource( Property $property ): bool {
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

/**
 * @deprecated since 7.0.0
 */
class_alias( Exporter::class, 'SMWExporter' );
