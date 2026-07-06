<?php

namespace SMW\Query\Result;

use Iterator;
use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataTypeRegistry;
use SMW\Parser\InTextAnnotationParser;
use SMW\Query\PrintRequest;
use SMW\Query\QueryToken;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\PrefetchCache;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ItemFetcher {

	/**
	 * @var PrefetchCache
	 */
	private $prefetchCache;

	private ?PrintRequest $printRequest = null;

	private ?QueryToken $queryToken = null;

	private bool $prefetch = true;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly Store $store,
		private readonly array $dataItems = [],
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @param int $features
	 */
	public function setPrefetchFlag( $features ): void {
		$this->prefetch = ( (int)$features & SMW_QUERYRESULT_PREFETCH ) != 0;
	}

	/**
	 * @since 3.1
	 *
	 * @param PrintRequest $printRequest
	 */
	public function setPrintRequest( PrintRequest $printRequest ): void {
		$this->printRequest = $printRequest;
	}

	/**
	 * @since 3.1
	 *
	 * @param QueryToken|null $queryToken
	 */
	public function setQueryToken( ?QueryToken $queryToken = null ): void {
		$this->queryToken = $queryToken;
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem|null|false $dataItem
	 */
	public function highlightTokens( $dataItem ): DataItem|false|null|Blob {
		if ( !$dataItem instanceof Blob || !$this->printRequest instanceof PrintRequest ) {
			return $dataItem;
		}

		$type = $this->printRequest->getTypeID();

		// Avoid `_cod`, `_eid` or similar types that use the Blob as storage
		// object
		if (
			$type !== '_txt' &&
			!DataTypeRegistry::getInstance()->isRecordType( $type ) ) {
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

		return new Blob( $string );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $dataItems
	 * @param Property $property
	 * @param RequestOptions $requestOptions
	 *
	 * @return array
	 */
	public function fetch( array $dataItems, Property $property, RequestOptions $requestOptions ): array {
		if ( !$this->prefetch ) {
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

				// Allow the first chain element to use the entire result list
				// to filter members as early as possible from the chain
				if ( $requestOptions->isFirstChain ?? false ) {
					$list = $this->dataItems;
				}
			}

			$this->prefetchCache->prefetch( $list, $property, $requestOptions );
		}

		foreach ( $dataItems as $subject ) {

			if ( !$subject instanceof WikiPage ) {
				continue;
			}

			$pv = $this->prefetchCache->getPropertyValues(
				$subject,
				$property,
				$requestOptions
			);

			if ( $pv === [] ) {
				continue;
			}

			$propertyValues = array_merge( $propertyValues, $pv );
			unset( $pv );
		}

		array_walk( $propertyValues, function ( &$dataItem ): void {
			$dataItem = $this->highlightTokens( $dataItem );
		} );

		return $propertyValues;
	}

	/**
	 * @return mixed[]
	 */
	private function legacyFetch( array $dataItems, Property $property, RequestOptions $requestOptions ): array {
		$propertyValues = [];
		$requestOptions->setOption( RequestOptions::CONDITION_CONSTRAINT_RESULT, false );
		$requestOptions->setCaller( __METHOD__ );

		foreach ( $dataItems as $dataItem ) {

			if ( !$dataItem instanceof WikiPage ) {
				continue;
			}

			$pv = $this->store->getPropertyValues(
				$dataItem,
				$property,
				$requestOptions
			);

			if ( $pv instanceof Iterator ) {
				$pv = iterator_to_array( $pv );
			}

			$propertyValues = array_merge( $propertyValues, $pv );
			unset( $pv );
		}

		array_walk( $propertyValues, function ( &$dataItem ): void {
			$dataItem = $this->highlightTokens( $dataItem );
		} );

		return $propertyValues;
	}

}
