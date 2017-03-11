<?php

namespace SMW\Query;

use SMW\QueryFactory;
use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\DataValueFactory;
use SMWPropertyValue as PropertyValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QueryCreator implements QueryContext {

	/**
	 * @var QueryFactory
	 */
	private $queryFactory;

	/**
	 * @var array
	 */
	private $configuration = array();

	/**
	 * @see smwgQDefaultNamespaces
	 * @var null|array
	 */
	private $defaultNamespaces = null;

	/**
	 * @see smwgQDefaultLimit
	 * @var integer
	 */
	private $defaultLimit = 0;

	/**
	 * @see smwgQFeatures
	 * @var integer
	 */
	private $queryFeatures = 0;

	/**
	 * @see smwgQConceptFeatures
	 * @var integer
	 */
	private $conceptFeatures = 0;

	/**
	 * @since 2.5
	 *
	 * @param QueryFactory $queryFactory
	 * @param array|null $defaultNamespaces
	 * @param integer $defaultLimit
	 */
	public function __construct( QueryFactory $queryFactory, $defaultNamespaces = null, $defaultLimit = 50 ) {
		$this->queryFactory = $queryFactory;
		$this->defaultNamespaces = $defaultNamespaces;
		$this->defaultLimit = $defaultLimit;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $configuration
	 *
	 * @return self
	 */
	public function setConfiguration( array $configuration ) {
		$this->configuration = $configuration;
		return $this;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $queryFeatures
	 */
	public function setQFeatures( $queryFeatures ) {
		$this->queryFeatures = $queryFeatures;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $conceptFeatures
	 */
	public function setQConceptFeatures( $conceptFeatures ) {
		$this->conceptFeatures = $conceptFeatures;
	}

	/**
	 * Parse a query string given in SMW's query language to create an Query.
	 * Parameters are given as key-value-pairs in the given array. The parameter
	 * $context defines in what context the query is used, which affects certaim
	 * general settings.
	 *
	 * @since 2.5
	 *
	 * @param string $queryString
	 *
	 * @return Query
	 */
	public function create( $queryString ) {

		$context = $this->getConfiguration( 'context', self::INLINE_QUERY );

		$queryParser = $this->queryFactory->newQueryParser(
			$context == self::CONCEPT_DESC ? $this->conceptFeatures : $this->queryFeatures
		);

		$contextPage = $this->getConfiguration( 'contextPage', null );
		$queryMode = $this->getConfiguration( 'queryMode', self::MODE_INSTANCES );

		$queryParser->setContextPage( $contextPage );
		$queryParser->setDefaultNamespaces( $this->defaultNamespaces );

		$query = $this->queryFactory->newQuery(
			$queryParser->getQueryDescription( $queryString ),
			$context
		);

		$query->setQueryToken( $queryParser->getQueryToken() );
		$query->setQueryString( $queryString );
		$query->setContextPage( $contextPage );
		$query->setQueryMode( $queryMode );

		$query->setExtraPrintouts(
			$this->getConfiguration( 'extraPrintouts', array() )
		);

		$query->setMainLabel(
			$this->getConfiguration( 'mainLabel', '' )
		);

		$query->setQuerySource(
			$this->getConfiguration( 'querySource', null )
		);

		// keep parsing or other errors for later output
		$query->addErrors(
			$queryParser->getErrors()
		);

		// set sortkeys, limit, and offset
		$query->setOffset(
			max( 0, trim( $this->getConfiguration( 'offset', 0 ) ) + 0 )
		);

		$query->setLimit(
			max( 0, trim( $this->getConfiguration( 'limit', $this->defaultLimit ) ) + 0 ),
			$queryMode != self::MODE_COUNT
		);

		$sortKeys = $this->getSortKeys(
			$this->getConfiguration( 'sort', array() ),
			$this->getConfiguration( 'order', array() ),
			$this->getConfiguration( 'defaultSort', 'ASC' )
		);

		$query->addErrors(
			$sortKeys['errors']
		);

		$query->setSortKeys(
			$sortKeys['keys']
		);

		return $query;
	}

	/**
	 * @note This method is expected to be private after QueryProcessor::getSortKeys has been removed!
	 *
	 * @since 2.5
	 *
	 * @param array $sortParameters
	 * @param array $orderParameters
	 * @param string $defaultSort
	 *
	 * @return array ( keys => array(), errors => array() )
	 */
	public function getSortKeys( array $sortParameters, array $orderParameters, $defaultSort ) {

		$sortKeys = array();
		$sortErros = array();

		$orders = $this->getNormalizedOrderParameters( $orderParameters );

		foreach ( $sortParameters as $sort ) {
			$sortKey = false;

			// An empty string indicates we mean the page, such as element 0 on the next line.
			// sort=,Some property
			if ( trim( $sort ) === '' ) {
				$sortKey = '';
			} else {

				$propertyValue = DataValueFactory::getInstance()->newDataValueByType( PropertyValue::TYPE_ID );
				$propertyValue->setOption( PropertyValue::OPT_QUERY_CONTEXT, true );

				$propertyValue->setUserValue(
					$this->getNormalizedSortLabel( trim( $sort ) )
				);

				if ( $propertyValue->isValid() ) {
					$sortKey = $propertyValue->getDataItem()->getKey();
				} else {
					$sortErros = array_merge( $sortErros, $propertyValue->getErrors() );
				}
			}

			if ( $sortKey !== false ) {
				$order = empty( $orders ) ? $defaultSort : array_shift( $orders );
				$sortKeys[$sortKey] = $order;
			}
		}

		// If more sort arguments are provided then properties, assume the first one is for the page.
		// TODO: we might want to add errors if there is more then one.
		if ( !array_key_exists( '', $sortKeys ) && !empty( $orders ) ) {
			$sortKeys[''] = array_shift( $orders );
		}

		return array( 'keys' => $sortKeys, 'errors' => $sortErros );
	}

	private function getNormalizedOrderParameters( $orderParameters ) {
		$orders = array();

		foreach ( $orderParameters as $key => $order ) {
			$order = strtolower( trim( $order ) );
			if ( ( $order == 'descending' ) || ( $order == 'reverse' ) || ( $order == 'desc' ) ) {
				$orders[$key] = 'DESC';
			} elseif ( ( $order == 'random' ) || ( $order == 'rand' ) ) {
				$orders[$key] = 'RANDOM';
			} else {
				$orders[$key] = 'ASC';
			}
		}

		return $orders;
	}

	private function getNormalizedSortLabel( $sort ) {
		return Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY ) == mb_convert_case( $sort, MB_CASE_TITLE ) ? '_INST' : $sort;
	}

	private function getConfiguration( $key, $default ) {
		return isset( $this->configuration[$key] ) ? $this->configuration[$key] : $default;
	}

}
