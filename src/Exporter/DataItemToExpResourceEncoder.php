<?php

namespace SMW\Exporter;

use RuntimeException;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpResource;
use SMW\InMemoryPoolCache;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWExporter as Exporter;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class DataItemToExpResourceEncoder {

	/**
	 * Identifies auxiliary data (helper values)
	 */
	const AUX_MARKER = 'aux';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var InMemoryPoolCache
	 */
	private $inMemoryPoolCache;

	/**
	 * @note Legacy setting expected to vanish with 3.0
	 *
	 * @var boolean
	 */
	private $bcAuxiliaryUse = true;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->inMemoryPoolCache = InMemoryPoolCache::getInstance();
	}

	/**
	 * @since 2.3
	 */
	public function reset() {
		$this->inMemoryPoolCache->resetPoolCacheFor( 'exporter.dataitem.resource.encoder' );
	}

	/**
	 * @since 2.2
	 *
	 * @param boolean $bcAuxiliaryUse
	 */
	public function setBCAuxiliaryUse( $bcAuxiliaryUse ) {
		$this->bcAuxiliaryUse = (bool)$bcAuxiliaryUse;
	}

	/**
	 * @since 2.2
	 *
	 * @param DIWikiPage $subject
	 */
	public function resetCacheFor( DIWikiPage $subject ) {

		$hash = $subject->getHash();

		$poolCache = $this->inMemoryPoolCache->getPoolCacheFor( 'exporter.dataitem.resource.encoder' );

		foreach ( array( $hash, $hash . self::AUX_MARKER ) as $key ) {
			$poolCache->delete( $key );
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
	 * @note $useAuxiliaryModifier is to determine whether an auxiliary
	 * property resource is to store a helper value
	 * (see Exporter::getDataItemHelperExpElement) should be generated
	 *
	 * @param DIProperty $property
	 * @param boolean $useAuxiliaryModifier
	 *
	 * @return ExpResource
	 * @throws RuntimeException
	 */
	public function mapPropertyToResourceElement( DIProperty $property, $useAuxiliaryModifier = false ) {

		// We want the a canonical representation to ensure that resources
		// are language independent
		$diWikiPage = $property->getCanonicalDiWikiPage();

		if ( $diWikiPage === null ) {
			throw new RuntimeException( 'Only non-inverse, user-defined properties are permitted.' );
		}

		// No need for any aux properties besides those listed here
		if ( !$this->bcAuxiliaryUse && $property->findPropertyTypeID() !== '_dat' && $property->findPropertyTypeID() !== '_geo' ) {
			$useAuxiliaryModifier = false;
		}

		return $this->mapWikiPageToResourceElement( $diWikiPage, $useAuxiliaryModifier );
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
	 * @param boolean $useAuxiliaryModifier
	 *
	 * @return ExpResource
	 */
	public function mapWikiPageToResourceElement( DIWikiPage $diWikiPage, $useAuxiliaryModifier = false ) {

		$modifier = $useAuxiliaryModifier ? self::AUX_MARKER : '';

		$hash = $diWikiPage->getHash() . $modifier;

		$poolCache = $this->inMemoryPoolCache->getPoolCacheFor( 'exporter.dataitem.resource.encoder' );

		if ( $poolCache->contains( $hash ) ) {
			return $poolCache->fetch( $hash );
		}

		if ( $diWikiPage->getSubobjectName() !== '' ) {
			$modifier = $diWikiPage->getSubobjectName();
		}

		$resource = $this->newExpNsResource(
			$diWikiPage,
			$modifier
		);

		$poolCache->save(
			$hash,
			$resource
		);

		return $resource;
	}

	private function newExpNsResource( $diWikiPage, $modifier ) {

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

		$resource->wasMatchedToImportVocab = $importDataItem instanceof DataItem;
		$dbKey = $diWikiPage->getDBkey();

		if ( $diWikiPage->getNamespace() === SMW_NS_PROPERTY && $dbKey !== '' && $dbKey{0} !== '-' ) {
			$resource->isUserDefined = DIProperty::newFromUserLabel( $diWikiPage->getDBkey() )->isUserDefined();
		}

		return $resource;
	}

	private function defineElementsForImportDataItem( DataItem $dataItem ) {

		$importValue = $this->dataValueFactory->newDataValueByItem(
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

		if ( $diWikiPage->getNamespace() === NS_CATEGORY ) {
			$namespace = Exporter::getInstance()->getNamespaceUri( 'category' );
			$namespaceId = 'category';
			$localName = Escaper::encodeUri( rawurlencode( $diWikiPage->getDBkey() ) );
		}

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
		if ( $diWikiPage->getNamespace() === NS_CATEGORY || $diWikiPage->getNamespace() === SMW_NS_PROPERTY ) {
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

}
