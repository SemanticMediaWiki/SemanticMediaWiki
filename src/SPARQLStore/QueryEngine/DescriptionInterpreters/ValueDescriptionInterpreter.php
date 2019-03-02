<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DIWikiPage;
use SMW\Query\Language\Description;
use SMW\Query\Language\ValueDescription;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;
use SMWDIBlob as DIBlob;
use SMWDIUri as DIUri;
use SMWExpElement as ExpElement;
use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ValueDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @var Exporter
	 */
	private $exporter;

	/**
	 * @since 2.1
	 *
	 * @param ConditionBuilder|null $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder = null ) {
		$this->conditionBuilder = $conditionBuilder;
		$this->exporter = Exporter::getInstance();
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof ValueDescription;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {

		$joinVariable = $this->conditionBuilder->getJoinVariable();
		$orderByProperty = $this->conditionBuilder->getOrderByProperty();
		$asNoCase = $this->conditionBuilder->isSetFlag( SMW_SPARQL_QF_NOCASE );

		$dataItem = $description->getDataItem();
		$property = $description->getProperty();

		switch ( $description->getComparator() ) {
			case SMW_CMP_EQ:   $comparator = '=';
			break;
			case SMW_CMP_LESS: $comparator = '<';
			break;
			case SMW_CMP_GRTR: $comparator = '>';
			break;
			case SMW_CMP_LEQ:  $comparator = '<=';
			break;
			case SMW_CMP_GEQ:  $comparator = '>=';
			break;
			case SMW_CMP_NEQ:  $comparator = '!=';
			break;
			case SMW_CMP_PRIM_LIKE;
			case SMW_CMP_LIKE: $comparator = 'regex';
			break;
			case SMW_CMP_PRIM_NLKE;
			case SMW_CMP_NLKE: $comparator = '!regex';
			break;
			default: $comparator = ''; // unkown, unsupported
		}

		if ( $comparator === '' ) {
			return $this->createConditionForEmptyComparator( $joinVariable, $orderByProperty );
		} elseif ( $comparator == '=' && $asNoCase === false ) {
			return $this->createConditionForEqualityComparator( $dataItem, $property, $joinVariable, $orderByProperty );
		} elseif ( $comparator == 'regex' || $comparator == '!regex' ) {
			return $this->createConditionForRegexComparator( $dataItem, $joinVariable, $orderByProperty, $comparator );
		}

		return $this->createFilterConditionForAnyOtherComparator(
			$dataItem,
			$joinVariable,
			$orderByProperty,
			$comparator
		);
	}

	private function createConditionForEmptyComparator( $joinVariable, $orderByProperty ) {
		return $this->conditionBuilder->newTrueCondition( $joinVariable, $orderByProperty );
	}

	private function createConditionForEqualityComparator( $dataItem, $property, $joinVariable, $orderByProperty ) {

		$expElement = $this->exporter->newAuxiliaryExpElement( $dataItem );

		if ( $expElement === null ) {
			$expElement = $this->exporter->newExpElement( $dataItem );
		}

		if ( $expElement === null || !$expElement instanceof ExpElement ) {
			return new FalseCondition();
		}

		$condition = new SingletonCondition( $expElement );

		$redirectByVariable = $this->conditionBuilder->tryToFindRedirectVariableForDataItem(
			$dataItem
		);

		// If it is a standalone value (e.g [[:Foo]] with no property) construct a
		// filter condition otherwise just assign the variable and the succeeding
		// process the ensure the replacement
		if ( $redirectByVariable !== null && $property === null ) {

			$condition = $this->createFilterConditionForAnyOtherComparator(
				$dataItem,
				$joinVariable,
				$orderByProperty,
				'='
			);

			$condition->filter = "?$joinVariable = $redirectByVariable";
		} elseif ( $redirectByVariable !== null ) {
			$condition->matchElement = $redirectByVariable;
		}

		$this->conditionBuilder->addOrderByDataForProperty(
			$condition,
			$joinVariable,
			$orderByProperty,
			$dataItem->getDIType()
		);

		return $condition;
	}

	private function createConditionForRegexComparator( $dataItem, $joinVariable, $orderByProperty, $comparator ) {

		if ( !$dataItem instanceof DIBlob && !$dataItem instanceof DIWikiPage && !$dataItem instanceof DIUri ) {
			return $this->conditionBuilder->newTrueCondition( $joinVariable, $orderByProperty );
		}

		if ( $dataItem instanceof DIBlob ) {
			$search = $dataItem->getString();
		} else {
			$search = $dataItem->getSortKey();
		}

		// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
		$pattern = '^' . str_replace(
			[ 'https://', 'http://', '%2A', '.', '+', '{', '}', '(', ')', '|', '^', '$', '[', ']', '*', '?', "'", '\\\.', '\\', '"', '\\\\\\\"' ],
			[ '*', '*', '*', '\.', '\+', '\{', '\}', '\(', '\)', '\|', '\^', '\$', '\[', '\]', '.*', '.' , "\'", '\\\\\.', '\\\\', '\\\\\"', '\\\\\\\\\\\"' ],
			$search
		) . '$';
		// @codingStandardsIgnoreEnd

		$condition = $this->createFilterConditionToMatchRegexPattern(
			$dataItem,
			$joinVariable,
			$comparator,
			$pattern
		);

		$redirectByVariable = $this->conditionBuilder->tryToFindRedirectVariableForDataItem(
			$dataItem
		);

		if ( $redirectByVariable !== null ) {
			$condition->matchElement = $redirectByVariable;
		}

		$this->conditionBuilder->addOrderByDataForProperty(
			$condition,
			$joinVariable,
			$orderByProperty,
			$dataItem->getDIType()
		);

		return $condition;
	}

	private function createFilterConditionForAnyOtherComparator( $dataItem, $joinVariable, $orderByProperty, $comparator ) {

		$result = new FilterCondition( '', [] );

		$this->conditionBuilder->addOrderByData(
			$result,
			$joinVariable,
			$dataItem->getDIType()
		);

		$orderByVariable = '?' . $result->orderByVariable;

		if ( $dataItem instanceof DIWikiPage ) {
			$expElement = $this->exporter->newExpElement( new DIBlob( $dataItem->getSortKey() ) );
		} else {
			$expElement = $this->exporter->newAuxiliaryExpElement( $dataItem );
			if ( is_null( $expElement ) ) {
				$expElement = $this->exporter->newExpElement( $dataItem );
			}
		}

		$valueName = TurtleSerializer::getTurtleNameForExpElement( $expElement );

		if ( $expElement instanceof ExpNsResource ) {
			$result->namespaces[$expElement->getNamespaceId()] = $expElement->getNamespace();
			$dataItem = $expElement->getDataItem();
		}

		$this->lcase( $dataItem, $orderByVariable, $valueName );

		$result->filter = "$orderByVariable $comparator $valueName";

		return $result;
	}

	private function createFilterConditionToMatchRegexPattern( $dataItem, &$joinVariable, $comparator, $pattern ) {

		$flag = $this->conditionBuilder->isSetFlag( SMW_SPARQL_QF_NOCASE ) ? 'i' : 's';

		if ( $dataItem instanceof DIBlob ) {
			return new FilterCondition( "$comparator( ?$joinVariable, \"$pattern\", \"$flag\")", [] );
		}

		if ( $dataItem instanceof DIUri ) {
			return new FilterCondition( "$comparator( str( ?$joinVariable ), \"$pattern\", \"i\")", [] );
		}

		// Pattern search for a wikipage object can only be done on the sortkey
		// literal and not on it's resource
		$skeyExpElement = Exporter::getInstance()->getSpecialPropertyResource( '_SKEY' );

		$expElement = $this->exporter->newExpElement( $dataItem->getSortKeyDataItem() );
		$condition = new SingletonCondition( $expElement );

		$filterVariable = $this->conditionBuilder->getNextVariable();

		$condition->condition = "?$joinVariable " . $skeyExpElement->getQName(). " ?$filterVariable .\n";
		$condition->matchElement = "?$joinVariable";

		$filterCondition = new FilterCondition( "$comparator( ?$filterVariable, \"$pattern\", \"$flag\")", [] );

		$condition->weakConditions = [ $filterVariable => $filterCondition->getCondition() ];

		return $condition;
	}

	private function lcase( $dataItem, &$orderByVariable, &$valueName ) {

		$isValidDataItem = $dataItem instanceof DIBlob || $dataItem instanceof DIUri || $dataItem instanceof DIWikiPage;

		// https://stackoverflow.com/questions/10660030/how-to-write-sparql-query-that-efficiently-matches-string-literals-while-ignorin
		if ( $this->conditionBuilder->isSetFlag( SMW_SPARQL_QF_NOCASE ) && $isValidDataItem ) {
			$orderByVariable = "lcase(str($orderByVariable) )";
			$valueName = mb_strtolower( $valueName );
		}
	}

}
