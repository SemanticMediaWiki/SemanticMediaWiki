<?php

namespace SMW\Query\Result;

use SMW\DataValueFactory;
use SMW\DataValues\MonolingualTextValue;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Parser\InTextAnnotationParser;
use SMW\Query\PrintRequest;
use SMW\Query\QueryToken;
use SMW\RequestOptions;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ItemFetcher {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PrefetchCache
	 */
	private $prefetchCache;

	/**
	 * @var PrintRequest
	 */
	private $printRequest;

	/**
	 * @var QueryToken
	 */
	private $queryToken;

	/**
	 * @var DIWikiPage[]
	 */
	private $dataItems = [];

	/**
	 * @var boolean
	 */
	private $prefetch = true;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store, array $dataItems = [] ) {
		$this->store = $store;
		$this->dataItems = $dataItems;
	}

	/**
	 * @since 3.1
	 *
	 * @param int $features
	 */
	public function setPrefetchFlag( $features ) {
		$this->prefetch = ( (int)$features & SMW_QUERYRESULT_PREFETCH ) != 0;
	}

	/**
	 * @since 3.1
	 *
	 * @param PrintRequest $printRequest
	 */
	public function setPrintRequest( PrintRequest $printRequest ) {
		$this->printRequest = $printRequest;
	}

	/**
	 * @since 3.1
	 *
	 * @param QueryToken|null $queryToken
	 */
	public function setQueryToken( QueryToken $queryToken = null ) {
		$this->queryToken = $queryToken;
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem|null|false $dataItem
	 */
	public function highlightTokens( $dataItem ) {

		if ( !$dataItem instanceof DIBlob || !$this->printRequest instanceof PrintRequest ) {
			return $dataItem;
		}

		$type = $this->printRequest->getTypeID();

		// Avoid `_cod`, `_eid` or similar types that use the DIBlob as storage
		// object
		if ( $type !== '_txt' && strpos( $type, '_rec' ) === false ) {
			return $dataItem;
		}

		$outputFormat = $this->printRequest->getOutputFormat();

		// #2325
		// Output format marked with -raw are allowed to retain a possible [[ :: ]]
		// annotation
		// '-ia' is deprecated use `-raw`
		if ( strpos( $outputFormat, '-raw' ) !== false || strpos( $outputFormat, '-ia' ) !== false ) {
			return $dataItem;
		}

		// #1314
		$string = InTextAnnotationParser::removeAnnotation(
			$dataItem->getString()
		);

		// #2253
		if ( $this->queryToken !== null ) {
			$string = $this->queryToken->highlight( $string );
		}

		return new DIBlob( $string );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $dataItems
	 * @param DIProperty $property
	 * @param RequestOptions $requestOptions
	 *
	 * @return array
	 */
	public function fetch( array $dataItems, DIProperty $property, RequestOptions $requestOptions ) {

		if ( $this->prefetch === false ) {
			return $this->legacyFetch( $dataItems, $property, $requestOptions );
		}

		if ( $this->prefetchCache === null ) {
			$this->prefetchCache = $this->store->service( 'PrefetchCache' );
		}

		$propertyValues = [];
		$prop = $property->getKey();

		// In prefetch mode avoid restriting the result due to use of WHERE IN
		$requestOptions->exclude_limit = true;

		// If its a chain we need to reload since the DataItem's use are traversed
		// from each chain element
		if ( !$this->prefetchCache->isCached( $property ) || $requestOptions->isChain ) {
			$list = $this->dataItems;

			if ( $requestOptions->isChain ) {
				$list = $dataItems;
			}

			$this->prefetchCache->prefetch( $list, $property, $requestOptions );
		}

		foreach ( $dataItems as $subject ) {

			if ( !$subject instanceof DIWikiPage ) {
				continue;
			}

			$pv = $this->prefetchCache->getPropertyValues(
				$subject,
				$property,
				$requestOptions
			);

			if ( $pv instanceof \Traversable ) {
				$pv = iterator_to_array( $pv );
			}

			if ( $pv === [] ) {
				continue;
			}

			$propertyValues = array_merge( $propertyValues, $pv );
			unset( $pv );
		}

		array_walk( $propertyValues, function( &$dataItem ) {
			$dataItem = $this->highlightTokens( $dataItem );
		} );

		return $propertyValues;
	}

	private function legacyFetch( $dataItems, $property, $requestOptions ) {

		$propertyValues = [];
		$requestOptions->setOption( RequestOptions::CONDITION_CONSTRAINT_RESULT, false );
		$requestOptions->setCaller( __METHOD__ );

		foreach ( $dataItems as $dataItem ) {

			if ( !$dataItem instanceof DIWikiPage ) {
				continue;
			}

			$pv = $this->store->getPropertyValues(
				$dataItem,
				$property,
				$requestOptions
			);

			if ( $pv instanceof \Iterator ) {
				$pv = iterator_to_array( $pv );
			}

			$propertyValues = array_merge( $propertyValues, $pv );
			unset( $pv );
		}

		array_walk( $propertyValues, function( &$dataItem ) {
			$dataItem = $this->highlightTokens( $dataItem );
		} );

		return $propertyValues;
	}

}
