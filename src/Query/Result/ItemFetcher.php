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
	 * @var prefetchCache
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
	private $prefetch = false;

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
		return $this->legacyFetch( $dataItems, $property, $requestOptions );
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
