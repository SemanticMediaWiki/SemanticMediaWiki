<?php

namespace SMW\MediaWiki\Search;

use SMW\MediaWiki\Search\Form\FormsBuilder;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Parser\TermParser;
use SMW\Store;
use SMWQuery as Query;
use SMWQueryProcessor as QueryProcessor;
use Title;
use WebRequest;
use WikiPage;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class QueryBuilder {

	/**
	 * @var WebRequest
	 */
	private $request;

	/**
	 * @var array
	 */
	private $data = [];

	/**
	 * @var array
	 */
	private $queryCache = [];

	/**
	 * @since 3.0
	 *
	 * @param WebRequest|null $request
	 * @param array|null $data
	 */
	public function __construct( WebRequest $request = null, array $data = [] ) {
		$this->request = $request;
		$this->data = $data;

		if ( $this->request === null ) {
			$this->request = $GLOBALS['wgRequest'];
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $term
	 *
	 * @return Query|null
	 */
	public function getQuery( $term ) {

		if ( !is_string( $term ) || trim( $term ) === '' ) {
			return null;
		}

		if ( !array_key_exists( $term, $this->queryCache ) ) {

			$params = QueryProcessor::getProcessedParams( [] );
			$query = QueryProcessor::createQuery( $term, $params );

			$description = $query->getDescription();

			if ( $description === null || is_a( $description, 'SMWThingDescription' ) ) {
				$query = null;
			}

			$this->queryCache[$term] = $query;
		}

		return $this->queryCache[$term];
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 * @param array $searchableNamespaces
	 */
	public function addNamespaceCondition( Query $query = null, $searchableNamespaces = [] ) {

		if ( $query === null ) {
			return;
		}

		$namespaces = [];

		foreach ( $searchableNamespaces as $ns => $name ) {
			if ( $this->request->getCheck( 'ns' . $ns ) ) {
				$namespaces[] = $ns;
			}
		}

		$namespacesDisjunction = new Disjunction(
			array_map( function ( $ns ) {
				return new NamespaceDescription( $ns );
			}, $namespaces )
		);

		$description = new Conjunction( [ $query->getDescription(), $namespacesDisjunction ] );
		$query->setDescription( $description );
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 */
	public function addSort( Query $query = null ) {

		if ( $query === null ) {
			return;
		}

		// @see SortForm
		$sort = $this->request->getVal( 'sort' );

		if ( $sort === 'recent' ) {
			$query->setSortKeys( [ '_MDAT' => 'desc' ] );
		} elseif ( $sort === 'title' ) {
			$query->setSortKeys( [ '' => 'asc' ] );
		} else {
			// Sort by score/relevance if it is supported otherwise the default
			// by title sort will be used instead.
			$query->setOption( Query::SCORE_SORT, 'desc' );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getQueryString( Store $store, $term ) {

		// Special invisible char which is set by the JS component to allow to
		// push a forms submit through the SearchEngine without an actual "search
		// term" to avoid being blocked on an empty request which only contains
		// structured searches.
		$term = rtrim( $term, " " );
		$prefix_map = [];

		if ( $this->data === [] ) {
			$data = SearchProfileForm::getFormDefinitions( $store );
		} else {
			$data = $this->data;
		}

		if ( isset( $data['term_parser']['prefix'] ) && $data['term_parser']['prefix'] ) {
			$prefix_map = (array)$data['term_parser']['prefix'];
		}

		$termParser = new TermParser( $prefix_map );
		$term = $termParser->parse( $term );

		$form = $this->request->getVal( 'smw-form' );

		if ( ( $data = $this->fetchFieldValues( $form, $data ) ) === [] && trim( $term ) ) {
			return $term;
		}

		$queryString = '';
		$lastOr = '';

		foreach ( $data as $key => $values ) {

			if ( !is_array( $values ) ) {
				continue;
			}

			foreach ( $values as $k => $value ) {

				if ( !isset( $value[0] ) || $value[0] === '' ) {
					continue;
				}

				$val = $value[0];
				$op = strtolower( $value[1] ) === 'or' ? ' OR ' : '';

				$queryString .= "[[$key::$val]]$op";
			}
		}

		// Remove last OR to ensure <q></q> has no open OR expression
		if ( substr( $queryString, -3 ) === 'OR ' ) {
			$lastOr = $term !== '' ? 'OR' : '';
			$queryString = substr( $queryString, 0, -3 );
		}

		if ( $queryString === '' ) {
			return $term;
		}

		return "<q>$queryString</q> $lastOr $term";
	}

	/**
	 * @since 3.0
	 *
	 * @param string $form
	 * @param array $data
	 *
	 * @return []
	 */
	public function fetchFieldValues( $form, array $data ) {

		$fieldValues = [];

		if ( !isset( $data['forms'] ) ) {
			return [];
		}

		if ( $form === 'open' ) {
			$properties = $this->request->getArray( 'property' );
			$pvalues = $this->request->getArray( 'pvalue' );
			$op = $this->request->getArray( 'op' );

			foreach ( $properties as $i => $property ) {

				if ( !isset( $fieldValues[$property] ) ) {
					$fieldValues[$property] = [];
				}

				$fieldValues[$property][] = [ $pvalues[$i], $op[$i] ];
			}

			return $fieldValues;
		}

		$fieldsCounter = [];

		foreach ( $data['forms'] as $key => $value ) {

			// @see FormsBuilder
			$k = FormsBuilder::toLowerCase( $key );

			foreach ( $value as $property ) {

				if ( is_array( $property ) ) {
					foreach ( $property as $p => $options ) {
						$property = $p;
					}
				}

				$name = FormsBuilder::toLowerCase( $property );

				if ( !isset( $fieldsCounter[$name] ) ) {
					$fieldsCounter[$name] = 0;
				} else {
					$fieldsCounter[$name]++;
				}

				if ( $form !== $k ) {
					continue;
				}

				$vals = $this->request->getArray(
					FormsBuilder::toLowerCase( $property )
				);

				if ( !isset( $vals[$fieldsCounter[$name]] ) ) {
					continue;
				}

				$val = $vals[$fieldsCounter[$name]];

				// Conditions from custom forms are conjunctive
				$fieldValues[$property][] = [ $val, 'and' ];
			}
		}

		return $fieldValues;
	}



}
