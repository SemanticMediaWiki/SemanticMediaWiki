<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use SMW\Localizer\Localizer;
use SMWInfolink as Infolink;
use WebRequest;

/**
 * @private
 *
 * The following parameter are used to communicate with the backend:
 *
 * - q: query
 * - c: filtered category
 * - pv: filtered property and values
 * - vc: describes the selected "value condition" whether to be OR, AND, or NOT
 * - clear: describes to be cleared filters
 * - cstate: defines the "card state" whether to be "c" collapsed or "e" expanded
 * - csum: defines a checksum to verify whether the query string was modified
 *   during a request
 *
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class ParametersProcessor {

	// RequestParameters

	/**
	 * @var Profile
	 */
	private $profile;

	/**
	 * @var string
	 */
	private $queryString = '';

	/**
	 * @var string
	 */
	private $format = '';

	/**
	 * @var
	 */
	private $parameters = [];

	/**
	 * @var
	 */
	private $filterConditions = [];

	/**
	 * @var
	 */
	private $propertyFilters = [];

	/**
	 * @var
	 */
	private $valueFilters = [];

	/**
	 * @since 3.2
	 *
	 * @param Profile $profile
	 */
	public function __construct( Profile $profile ) {
		$this->profile = $profile;
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function getParameters(): array {
		return $this->parameters;
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getFormat(): string {
		return $this->format;
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function getFilterConditions(): array {
		return $this->filterConditions;
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function getValueFilters(): array {
		return $this->valueFilters;
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function getPropertyFilters(): array {
		return $this->propertyFilters;
	}

	/**
	 * @since 3.2
	 *
	 * @param WebRequest $request
	 */
	public function checkRequest( WebRequest $request ) {
		// Was not filtered and the query checksum is different which means
		// the query string was modified
		if (
			$request->getVal( 'filtered', '' ) !== '1' &&
			$request->getInt( 'csum', '' ) !== crc32( $request->getVal( 'q', '' ) ) ) {

			// Remove the filters that may have remained from a previous
			// request
			$request->setVal( 'c', '' );
			$request->setVal( 'pv', '' );
			$request->setVal( 'vc', '' );
			$request->setVal( 'cstate', '' );
			$request->setVal( 'clear', '' );
		}

		if ( $request->getCheck( 'reset' ) ) {
			$request->setVal( 'c', '' );
			$request->setVal( 'pv', '' );
			$request->setVal( 'vc', '' );
			$request->setVal( 'cstate', '' );
			$request->setVal( 'clear', '' );
			$request->unsetVal( 'reset' );
		}
	}

	/**
	 * @since 3.2
	 *
	 * @param WebRequest $request
	 * @param array|null $params
	 */
	public function process( WebRequest $request, $params ) {
		$this->parameters = [];

		$query = $request->getVal( 'q' );
		$limit = $request->getInt( 'size' );
		$this->format = $request->getVal( 'format', 'broadtable' );

		if ( $limit == 0 ) {
			$limit = $this->profile->get( 'result.default_limit' );
		}

		$this->filterConditions = [];
		$this->valueFilters = [];

		if ( !$request->getCheck( 'q' ) ) {
			$params = $this->fromQueryParameter( $params );

			if ( $params !== '' ) {
				$request->setVal( 'q', $params );
			}

			$query = Infolink::decodeParameters( $params, true );
		} else {
			$query = Infolink::decodeParameters( $request->getVal( 'q' ), false );
		}

		$this->parameters = $this->makeParameters( $query, $request );

		$this->parameters[] = 'limit=' . $limit;
		$this->parameters[] = 'offset=' . $request->getVal( 'offset', 0 );

		if ( in_array( $request->getVal( 'order', 'asc' ), [ 'asc', 'desc' ] ) ) {
			$this->parameters[] = 'order=' . $request->getVal( 'order', 'asc' );
		} elseif ( $request->getVal( 'order', 'asc' ) === 'recent' ) {
			$this->parameters[] = 'order=desc';
			$this->parameters[] = 'sort=Modification date';
		}

		$this->parameters[] = "format=$this->format";

		if ( $this->format === 'table' ) {
			$this->format = 'broadtable';
		}

		if ( $this->format === 'broadtable' ) {
			$this->parameters[] = "class=datatable";
		}
	}

	private function fromQueryParameter( $query ) {
		$params = '';

		// Allow Category:Foo, Property:Bar, Concept:Foobar
		if ( strpos( $query, ':' ) !== false ) {
			[ $ns, $v ] = explode( ':', $query, 2 );

			if ( Localizer::getInstance()->getNsIndex( $ns ) === NS_CATEGORY ) {
				$params = str_replace( '_', ' ', "[[Category:$v]]" );
			}

			if ( Localizer::getInstance()->getNsIndex( $ns ) === SMW_NS_PROPERTY ) {
				$params = str_replace( '_', ' ', "[[$v::+]]" );
			}

			if ( Localizer::getInstance()->getNsIndex( $ns ) === SMW_NS_CONCEPT ) {
				$params = str_replace( '_', ' ', "[[Concept:$v]]" );
			}
		} elseif ( strpos( $query, '/' ) !== false ) {
			// PropertyFoo/ValueBar
			[ $p, $v ] = explode( '/', $query, 2 );
			$params = "[[" . str_replace( '_', ' ', $p ) . "::$v]]";
		}

		return $params;
	}

	private function makeParameters( $query, $request ) {
		$this->queryString = $query[0] ?? '';

		$parameters = [];
		$printRequests = [];

		// Properties, property values, ranges
		if ( $request->getVal( 'pv' ) !== '' ) {

			$clear = $request->getArray( 'clear' );
			$pv = $request->getArray( 'pv' );

			if ( isset( $clear['p'] ) && isset( $pv[$clear['p']] ) ) {
				unset( $pv[$clear['p']] );
				$request->setVal( 'pv', $pv );
			} elseif ( isset( $clear['p.all'] ) ) {
				$pv = [];
				$request->setVal( 'pv', $pv );
				$request->unsetVal( 'clear' );
				$request->unsetVal( 'vc' );
			}

			$printRequests += $this->propertyFilterConditions( $pv, $clear );

			$this->valueFilterConditions(
				$pv,
				$request->getArray( 'vc' ),
				$clear
			);
		}

		// Categories
		if ( $request->getVal( 'c' ) !== '' ) {

			$clear = $request->getArray( 'clear' );
			$c = $request->getArray( 'c' );

			if ( isset( $clear['c'] ) && isset( $c[$clear['c']] ) ) {
				unset( $c[$clear['c']] );
				$request->setVal( 'c', $c );
			} elseif ( isset( $clear['c.all'] ) ) {
				$c = [];
				$request->setVal( 'c', $c );
				$request->unsetVal( 'clear' );
			}

			$printRequests += $this->categoryFilterConditions( $c, $clear );
		}

		// Extra search fields
		if ( $request->getVal( 'fields' ) !== '' ) {
			$printRequests += $this->fieldConditions( $request->getArray( 'fields' ) );
		}

		if ( $this->filterConditions !== [] ) {
			$this->queryString = '<q>' . $this->queryString . '</q>' . implode( '', $this->filterConditions );
		}

		if ( $this->queryString !== '' ) {
			$parameters[] = $this->queryString;
		}

		// If no printrequest was added, use the initial condition to find
		// something to be used for the display
		if ( $printRequests === [] && isset( $query[0] ) ) {
			$printRequests = $this->addDefaultPrintRequests( $query[0] );
		}

		$parameters = array_merge( $parameters, array_keys( $printRequests ) );

		return $parameters;
	}

	private function fieldConditions( $fields ) {
		if ( !is_array( $fields ) || $fields === [] ) {
			return [];
		}

		$fieldList = $this->profile->get( 'search.extra_fields.field_list', [] );

		if ( $fieldList === [] ) {
			return [];
		}

		$conditions = [];
		$printRequests = [];

		foreach ( $fields as $key => $value ) {

			if ( $value === '' || !isset( $fieldList[$key]['property'] ) ) {
				continue;
			}

			$property = $fieldList[$key]['property'];
			$conditions[] = "[[$property::$value]]";

			$printRequests["?$property"] = true;
		}

		if ( $conditions !== [] ) {
			$this->queryString .= '<q>' . implode( '', $conditions ) . '</q>';
		}

		return $printRequests;
	}

	private function propertyFilterConditions( $values, $clear ) {
		$filters = array_keys( (array)$values );
		$this->propertyFilters = $filters;

		$conditions = [];
		$printRequests = [];

		foreach ( $filters as $property ) {

			if ( isset( $clear['p'] ) && $clear['p'] === $property ) {
				continue;
			}

			if ( strpos( $this->queryString, "[[$property::+]]" ) === false ) {
				$conditions[] = "[[$property::+]]";
			}

			$printRequests["?$property"] = true;
		}

		if ( $conditions !== [] ) {
			$this->filterConditions[] = '<q>' . implode( '', $conditions ) . '</q>';
		}

		return $printRequests;
	}

	private function categoryFilterConditions( $values, $clear ) {
		$filters = (array)$values;
		$conditions = [];
		$printRequests = [];

		foreach ( $filters as $filter ) {

			if ( isset( $clear['c'] ) && $clear['c'] === $filter ) {
				continue;
			}

			$conditions[] = "[[Category:$filter]]";
		}

		if ( $conditions !== [] ) {
			$this->filterConditions[] = '<q>' . implode( '', $conditions ) . '</q>';
			$printRequests["?Category"] = true;
		}

		return $printRequests;
	}

	private function valueFilterConditions( $values, $cond, $clear ) {
		if ( is_string( $values ) ) {
			$filters = array_filter( explode( '|', $values ) );
		} else {
			$filters = (array)$values;
		}

		$conditions = [];

		if ( isset( $clear['p'] ) && isset( $filters[$clear['p']] ) ) {
			unset( $filters[$clear['p']] );
		}

		foreach ( $filters as $prop => $filter ) {
			$not = strtoupper( $cond[$prop] ?? 'OR' ) === 'NOT' ? '!' : '';

			if ( !is_array( $filter ) ) {
				continue;
			}

			// In case the filter is cleared for a particular property, remove
			// the entire value chain for this property
			if ( isset( $clear[$prop] ) ) {
				continue;
			}

			if ( !isset( $conditions[$prop] ) ) {
				$conditions[$prop] = [];
			}

			foreach ( $filter as $value ) {

				if ( $value === '' || ( isset( $clear['v'] ) && $clear['v'] === $value ) ) {
					continue;
				}

				// `|` cannot normally occur as part of a value declaration in
				// MediaWiki and it is used here as decode marker for a range
				if ( strpos( $value, '|' ) !== false ) {

					[ $min, $max ] = explode( '|', $value, 2 );

					// Switching the places in case it says NOT

					if ( $min === 'INF' && $not === '' ) {
						$conditions[$prop][] = "[[$prop::≤$max]]";
					} elseif ( $min === 'INF' && $not === '!' ) {
						$conditions[$prop][] = "[[$prop::≤$min]]";
					} elseif ( $max === 'INF' && $not === '' ) {
						$conditions[$prop][] = "[[$prop::≥$min]]";
					} elseif ( $max === 'INF' && $not === '!' ) {
						$conditions[$prop][] = "[[$prop::≥$max]]";
					} elseif ( $not === '!' ) {
						$conditions[$prop][] = "<q>[[$prop::≤$min]] OR [[$prop::≥$max]]</q>";
					} elseif ( $min === $max ) {
					// $conditions[$prop][] = "<q>[[$prop::$max]]</q>";
					} else {
						$conditions[$prop][] = "[[$prop::≥$min]][[$prop::≤$max]]";
					}
				} else {
					$conditions[$prop][] = "[[$prop::{$not}{$value}]]";
				}
			}

			if ( $conditions[$prop] === [] ) {
				unset( $conditions[$prop] );
			}
		}

		foreach ( $conditions as $prop => $condition ) {
			$expr = strtoupper( $cond[$prop] ?? 'OR' );

			if ( $expr === 'NOT' ) {
				$expr = '';
			}

			if ( is_array( $condition ) ) {
				$condition = implode( " $expr ", $condition );
			}

			$this->filterConditions[] = '<q>' . $condition . '</q>';
			$this->valueFilters[$prop] = '<q>' . $condition . '</q>';
		}
	}

	private function addDefaultPrintRequests( string $query ) {
		preg_match_all( '/\[\[(.*?)\]\]/i', $query, $matches );
		$printRequests = [];

		foreach ( $matches[1] as $match ) {

			if ( strpos( $match, '::' ) !== false ) {
				[ $prop, $value ] = explode( '::', $match );
				$printRequests["?$prop"] = true;
			} elseif ( strpos( $match, ':' ) !== false ) {
				[ $ns, $value ] = explode( ':', $match );

				if ( Localizer::getInstance()->getNsIndex( $ns ) === NS_CATEGORY ) {
					$printRequests["?Category"] = true;
				}
			}
		}

		return $printRequests;
	}

}
