<?php

namespace SMW\Exporter;

use SMW\Exporter\Element\ExpResource;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpElement;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Store;
use SMW\DataValueFactory;
use SMW\ApplicationFactory;
use Onoi\Cache\Cache;

use SMWExporter as Exporter;
use SMWDataItem as DataItem;
use RuntimeException;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class CachedDataItemToExpResourceEncoder {

	/**
	 * Identifies auxiliary data (helper values)
	 */
	const AUX_MARKER = 'aux';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var string
	 */
	private $cachePrefix = 'smw:expresourceencoder-cache:';

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param Cache|null $cache
	 */
	public function __construct( Store $store, Cache $cache = null ) {
		$this->store = $store;
		$this->cache = $cache;

		if ( $this->cache === null ) {
			$this->cache = ApplicationFactory::getInstance()->newCacheFactory()->newNullCache();
		}

		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 2.2
	 *
	 * @param string $cachePrefix
	 */
	public function setCachePrefix( $cachePrefix ) {
		$this->cachePrefix = $cachePrefix . ':' . $this->cachePrefix;
	}

	/**
	 * @since 2.2
	 *
	 * @param DIWikiPage $subject
	 */
	public function resetCacheFor( DIWikiPage $subject ) {

		$hash = $this->cachePrefix . $subject->getHash();

		foreach ( array( $hash, $hash . self::AUX_MARKER ) as $key ) {
			$this->cache->delete( $key );
		}
	}

	/**
	 * Create an ExpElement for some internal resource, given by an
	 * DIProperty object.
	 *
	 * This code is only applied to user-defined properties, since the
	 * code for special properties in
	 * Exporter::getSpecialPropertyResource may require information
	 * about the namespace in which some special property is used.
	 *
	 * @note $markForAuxiliaryUsage is to determine whether an auxiliary
	 * property resource is to store a helper value
	 * (see Exporter::getDataItemHelperExpElement) should be generated
	 *
	 * @param DIProperty $property
	 * @param boolean $markForAuxiliaryUsage
	 *
	 * @return ExpResource
	 * @throws RuntimeException
	 */
	public function mapPropertyToResourceElement( DIProperty $property, $markForAuxiliaryUsage = false ) {

		$diWikiPage = $property->getDiWikiPage();

		if ( $diWikiPage !== null ) {
			return $this->mapWikiPageToResourceElement( $diWikiPage, $markForAuxiliaryUsage );
		}

		throw new RuntimeException( 'Only non-inverse, user-defined properties are permitted.' );
	}

	/**
	 * Create an ExpElement for some internal resource, given by an
	 * DIWikiPage object. This is the one place in the code where URIs
	 * of wiki pages and user-defined properties are determined. A modifier
	 * can be given to make variants of a URI, typically done for
	 * auxiliary properties. In this case, the URI is modiied by appending
	 * "-23$modifier" where "-23" is the URI encoding of "#" (a symbol not
	 * occuring in MW titles).
	 *
	 * @param DIWikiPage $diWikiPage
	 * @param boolean $markForAuxiliaryUsage
	 *
	 * @return ExpResource
	 */
	public function mapWikiPageToResourceElement( DIWikiPage $diWikiPage, $markForAuxiliaryUsage = false ) {

		$modifier = $markForAuxiliaryUsage ? self::AUX_MARKER : '';

		$hash = $this->cachePrefix . $diWikiPage->getHash() . $modifier;

		// If a persistent cache is injected use the ExpElement serializer because
		// not all cache layers support object de/serialization
		// ExpElement::newFromSerialization
		if ( $this->cache->contains( $hash ) ) {
			return $this->cache->fetch( $hash );
		}

		if ( $diWikiPage->getSubobjectName() !== '' ) {
			$modifier = $diWikiPage->getSubobjectName();
		}

		$importDataItem = $this->tryToFindImportDataItem( $diWikiPage, $modifier );

		if ( $importDataItem instanceof DataItem ) {
			list( $localName, $namespace, $namespaceId ) = $this->defineElementsForImportDataItem( $importDataItem );
		} else {
			list( $localName, $namespace, $namespaceId ) = $this->defineElementsForDiWikiPage( $diWikiPage, $modifier );
		}

		$resource = new ExpNsResource(
			$localName,
			$namespace,
			$namespaceId,
			$diWikiPage
		);

		$this->cache->save(
			$hash,
			$resource
		);

		return $resource;
	}

	private function defineElementsForImportDataItem( DataItem $dataItem ) {

		$importValue = $this->dataValueFactory->newDataItemValue(
			$dataItem,
			new DIProperty( '_IMPO' )
		);

		return array(
			$importValue->getLocalName(),
			$importValue->getNS(),
			$importValue->getNSID()
		);
	}

	private function defineElementsForDiWikiPage( DIWikiPage $diWikiPage, $modifier ) {

		$localName = '';

		if ( $diWikiPage->getNamespace() === SMW_NS_PROPERTY ) {
			$namespace = Exporter::getInstance()->getNamespaceUri( 'property' );
			$namespaceId = 'property';
			$localName = Escaper::encodeUri( rawurlencode( $diWikiPage->getDBkey() ) );
		}

		if ( ( $localName === '' ) ||
		     ( in_array( $localName{0}, array( '-', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ) ) ) ) {
			$namespace = Exporter::getInstance()->getNamespaceUri( 'wiki' );
			$namespaceId = 'wiki';
			$localName = Escaper::encodePage( $diWikiPage );
		}

		// "-23$modifier" where "-23" is the URI encoding of "#" (a symbol not
	 	// occuring in MW titles).
		if ( $modifier !== '' ) {
			$localName .=  '-23' . Escaper::encodeUri( rawurlencode(  $modifier ) );
		}

		return array(
			$localName,
			$namespace,
			$namespaceId
		);
	}

	private function tryToFindImportDataItem( DIWikiPage $diWikiPage, $modifier ) {

		$importDataItems = null;

		// Only try to find an import vocab for a matchable entity
		if ( $modifier === '' && $this->canUseForVocabularySearch( $diWikiPage ) ) {
			$importDataItems = $this->store->getPropertyValues(
				$diWikiPage,
				new DIProperty( '_IMPO' )
			);
		}

		if ( $importDataItems !== null && $importDataItems !== array() ) {
			$importDataItems = current( $importDataItems );
		}

		return $importDataItems;
	}

	private function canUseForVocabularySearch( $diWikiPage ) {

		if ( $diWikiPage->getNamespace() === NS_CATEGORY ) {
			return true;
		}

		if ( $diWikiPage->getNamespace() !== SMW_NS_PROPERTY || $diWikiPage->getDBKey() === '' ) {
			return false;
		}

		return DIProperty::newFromUserLabel( $diWikiPage->getDBKey() )->isUserDefined();
	}

}
