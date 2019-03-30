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
 * Returns the result content (DI objects) for a single PrintRequest, representing
 * as cell of the intersection between a subject row and a print column.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class ResultFieldMatchFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PrintRequest
	 */
	private $printRequest;

	/**
	 * @var QueryToken
	 */
	private $queryToken;

	/**
	 * @var boolean|array
	 */
	private static $catCacheObj = false;

	/**
	 * @var boolean|array
	 */
	private static $catCache = false;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param PrintRequest $printRequest
	 */
	public function __construct( Store $store, PrintRequest $printRequest ) {
		$this->printRequest = $printRequest;
		$this->store = $store;
	}

	/**
	 * @since 2.5
	 *
	 * @param QueryToken|null $queryToken
	 */
	public function setQueryToken( QueryToken $queryToken = null ) {

		if ( $queryToken === null ) {
			return;
		}

		$this->queryToken = $queryToken;

		$this->queryToken->setOutputFormat(
			$this->printRequest->getOutputFormat()
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param DataItem $dataItem
	 *
	 * @param DataItem[]|[]
	 */
	public function findAndMatch( DataItem $dataItem ) {

		$content = [];

		// Request the current element (page in result set).
		// The limit is ignored here.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_THIS ) ) {
			return [ $dataItem ];
		}

		// Request all direct categories of the current element
		// Always recompute cache here to ensure output format is respected.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_CATS ) ) {
			self::$catCache = $this->store->getPropertyValues(
				$dataItem,
				new DIProperty( '_INST' ),
				$this->getRequestOptions( false )
			);

			self::$catCacheObj = $dataItem->getHash();

			$limit = $this->printRequest->getParameter( 'limit' );

			return ( $limit === false ) ? ( self::$catCache ) : array_slice( self::$catCache, 0, $limit );
		}

		// Request to whether current element is in given category (Boolean printout).
		// The limit is ignored here.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_CCAT ) ) {
			if ( self::$catCacheObj !== $dataItem->getHash() ) {
				self::$catCache = $this->store->getPropertyValues(
					$dataItem,
					new DIProperty( '_INST' )
				);
				self::$catCacheObj = $dataItem->getHash();
			}

			$found = false;
			$prkey = $this->printRequest->getData()->getDBkey();

			foreach ( self::$catCache as $cat ) {
				if ( $cat->getDBkey() == $prkey ) {
					$found = true;
					break;
				}
			}

			return [ new DIBoolean( $found ) ];
		}

		// Request all property values of a certain attribute of the current element.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_PROP ) || $this->printRequest->isMode( PrintRequest::PRINT_CHAIN ) ) {
			return $this->getResultsForProperty( $dataItem );
		}

		return $content;
	}

	/**
	 * Make a request option object based on the given parameters, and
	 * return NULL if no such object is required. The parameter defines
	 * if the limit should be taken into account, which is not always desired
	 * (especially if results are to be cached for future use).
	 *
	 * @param boolean $useLimit
	 *
	 * @return RequestOptions|null
	 */
	public function getRequestOptions( $useLimit = true ) {
		$limit = $useLimit ? $this->printRequest->getParameter( 'limit' ) : false;
		$order = trim( $this->printRequest->getParameter( 'order' ) );
		$options = null;

		// Important: use "!=" for order, since trim() above does never return "false", use "!==" for limit since "0" is meaningful here.
		if ( ( $limit !== false ) || ( $order != false ) ) {
			$options = new RequestOptions();

			if ( $limit !== false ) {
				$options->limit = trim( $limit );
			}

			// Expecting a natural sort behaviour (n-asc, n-desc)?
			if ( strpos( $order, 'n-' ) !== false ) {
				$order = str_replace( 'n-', '', $order );
				$options->natural = true;
			}

			if ( ( $order == 'descending' ) || ( $order == 'reverse' ) || ( $order == 'desc' ) ) {
				$options->sort = true;
				$options->ascending = false;
			} elseif ( ( $order == 'ascending' ) || ( $order == 'asc' ) ) {
				$options->sort = true;
				$options->ascending = true;
			}
		}

		return $options;
	}

	private function getResultsForProperty( $dataItem ) {

		$content = $this->getResultContent(
			$dataItem
		);

		if ( !$this->isMultiValueWithParameter( 'index' ) && !$this->isMultiValueWithParameter( 'lang' ) ) {
			return $content;
		}

		// Print one component of a multi-valued string.
		//
		// Known limitation: the printrequest still is of type _rec, so if
		// printers check for this then they will not recognize that it returns
		// some more concrete type.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_CHAIN ) ) {
			$propertyValue = $this->printRequest->getData()->getLastPropertyChainValue();
		} else {
			$propertyValue = $this->printRequest->getData();
		}

		$index = $this->printRequest->getParameter( 'index' );
		$lang = $this->printRequest->getParameter( 'lang' );
		$newcontent = [];

		// Replace content with specific content from a Container/MultiValue
		foreach ( $content as $diContainer ) {

			/* AbstractMultiValue */
			$multiValue = DataValueFactory::getInstance()->newDataValueByItem(
				$diContainer,
				$propertyValue->getDataItem()
			);

			$multiValue->setOption( $multiValue::OPT_QUERY_CONTEXT, true );

			if ( $multiValue instanceof MonolingualTextValue && $lang !== false && ( $textValue = $multiValue->getTextValueByLanguageCode( $lang ) ) !== null ) {

				// Return the text representation without a language reference
				// (tag) since the value has been filtered hence only matches
				// that language
				$newcontent[] = $this->applyContentManipulation( $textValue->getDataItem() );

				// Set the index so ResultArray::getNextDataValue can
				// find the correct PropertyDataItem (_TEXT;_LCODE) position
				// to match the DI
				$this->printRequest->setParameter( 'index', 1 );
			} elseif ( $lang === false && $index !== false && ( $dataItemByRecord = $multiValue->getDataItemByIndex( $index ) ) !== null ) {
				$newcontent[] = $this->applyContentManipulation( $dataItemByRecord );
			}
		}

		$content = $newcontent;
		unset( $newcontent );

		return $content;
	}

	private function isMultiValueWithParameter( $parameter ) {
		return strpos( $this->printRequest->getTypeID(), '_rec' ) !== false && $this->printRequest->getParameter( $parameter ) !== false;
	}

	private function getResultContent( DataItem $dataItem ) {

		$dataValue = $this->printRequest->getData();
		$dataItems = [ $dataItem ];

		if ( !$dataValue->isValid() ) {
			return [];
		}

		// If it is a chain then try to find a connected DIWikiPage subject that
		// matches the property on the chained PrintRequest.
		// For example, Number.Date.SomeThing will not return any meaningful results
		// because Number will return a DINumber object and not a DIWikiPage.
		// If on the other hand Has page.Number (with Number being the Last and
		// `Has page` is of type Page) then the iteration will lookup on results
		// for `Has page` and try to match a Number annotation on the results
		// retrieved from `Has page`.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_CHAIN ) ) {

			// Output of the previous iteration is the input for the next iteration
			foreach ( $dataValue->getPropertyChainValues() as $pv ) {
				$dataItems = $this->doFetchPropertyValues( $dataItems, $pv );

				// If the results return empty then it means that for this element
				// the chain has no matchable items hence we stop
				if ( $dataItems === [] ) {
					return [];
				}
			}

			$dataValue = $dataValue->getLastPropertyChainValue();
		}

		return $this->doFetchPropertyValues( $dataItems, $dataValue );
	}

	private function doFetchPropertyValues( $dataItems, $dataValue ) {

		$propertyValues = [];

		foreach ( $dataItems as $dataItem ) {

			if ( !$dataItem instanceof DIWikiPage ) {
				continue;
			}

			$pv = $this->store->getPropertyValues(
				$dataItem,
				$dataValue->getDataItem(),
				$this->getRequestOptions()
			);

			if ( $pv instanceof \Iterator ) {
				$pv = iterator_to_array( $pv );
			}

			$propertyValues = array_merge( $propertyValues, $pv );
			unset( $pv );
		}

		array_walk( $propertyValues, function( &$dataItem ) {
			$dataItem = $this->applyContentManipulation( $dataItem );
		} );

		return $propertyValues;
	}

	private function applyContentManipulation( $dataItem ) {

		if ( !$dataItem instanceof DIBlob ) {
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

}
