<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use Maps\Semantic\ValueDescriptions\AreaDescription;
use SMW\DataTypeRegistry;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\Condition;
use SMW\Elastic\QueryEngine\FieldMapper;
use SMW\Query\Language\ValueDescription;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDIGeoCoord as DIGeoCoord;
use SMWDInumber as DINumber;
use SMWDITime as DITime;
use SMWDIUri as DIUri;
use SMW\Utils\CharExaminer;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SomeValueInterpreter {

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @var FieldMapper
	 */
	private $fieldMapper;

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder ) {
		$this->conditionBuilder = $conditionBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param ValueDescription $description
	 * @param array &$options
	 *
	 * @return Condition
	 * @throws RuntimeException
	 */
	public function interpretDescription( ValueDescription $description, array &$options ) {

		if ( !isset( $options['property'] ) || !$options['property'] instanceof DIProperty  ) {
			throw new RuntimeException( "Missing a property" );
		}

		if ( !isset( $options['pid'] ) ) {
			throw new RuntimeException( "Missing a pid" );
		}

		$this->fieldMapper = $this->conditionBuilder->getFieldMapper();

		$dataItem = $description->getDataItem();
		$comparator = $description->getComparator();

		// Normalize comparator (we don't distinguish them in Elastic)
		if ( $comparator === SMW_CMP_PRIM_LIKE ) {
			$comparator = SMW_CMP_LIKE;
		}

		if ( $comparator === SMW_CMP_PRIM_NLKE ) {
			$comparator = SMW_CMP_NLKE;
		}

		if ( $comparator === SMW_CMP_NLKE || $comparator === SMW_CMP_NEQ ) {
			$options['type'] = Condition::TYPE_MUST_NOT;
		}

		$options['comparator'] = $comparator;

		if ( $dataItem instanceof DIWikiPage ) {
			$params = $this->page( $dataItem, $options );
		} elseif ( $dataItem instanceof DIBlob ) {
			$params = $this->blob( $dataItem, $options );
		} elseif ( $dataItem instanceof DIUri ) {
			$params = $this->uri( $dataItem, $options );
		} elseif ( $dataItem instanceof DIGeoCoord ) {

			if ( $description instanceof AreaDescription ) {
				$options['bounding_box'] = $description->getBoundingBox();
			}

			$params = $this->geo( $dataItem, $options );
		} elseif ( $dataItem instanceof DITime ) {
			$params = $this->plain( $dataItem->getJD(), $options );
		} elseif ( $dataItem instanceof DIBoolean ) {
			$params = $this->plain( $dataItem->getBoolean(), $options );
		} elseif ( $dataItem instanceof DINumber ) {
			$params = $this->plain( $dataItem->getNumber(), $options );
		} else {
			$params = $this->plain( $dataItem->getSerialization(), $options );
		}

		if ( $options['property']->isInverse() ) {
			$options['query.string'] = $description->getQueryString();
			$params = $this->inverse_property( $params, $options );
		}

		$condition = $this->conditionBuilder->newCondition( $params );
		$condition->type( $options['type'] );

		return $condition;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $dataItem
	 *
	 * @return array
	 */
	public function page( DIWikiPage $dataItem, array &$options ) {

		$comparator = $options['comparator'];
		$pid = $options['pid'];
		$field = $options['field'];
		$type = $options['type'];

		$isSubDataType = DataTypeRegistry::getInstance()->isSubDataType(
			$options['property']->findPropertyValueType()
		);

		if ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) {
			$field = 'wpgID';
			$value = $this->conditionBuilder->getID( $dataItem );
		} else {
			$value = $dataItem->getSortKey();
		}

		if ( mb_strlen( $value ) > $this->conditionBuilder->getOption( 'maximum.value.length' ) ) {
			$value = mb_substr( $value, 0, $this->conditionBuilder->getOption( 'maximum.value.length' ) );
		}

		if ( $this->isRange( $comparator ) ) {
			$match = $this->fieldMapper->range( "$pid.$field", $value, $comparator );
		} elseif ( $isSubDataType && $dataItem->getDBKey() === '' && $comparator === SMW_CMP_NEQ ) {
			// [[Has subobject::!]] select those that are not a subobject
			$match = $this->fieldMapper->term( "subject.subobject.keyword", '' );
			$type = Condition::TYPE_FILTER;
		} elseif ( $comparator === SMW_CMP_LIKE ) {

			// Avoid *...* on CJK related terms so that something like
			// [[Has text::in:名古屋]] returns a better match accuracy given that
			// the standard analyzer splits CJK terms into single characters
			if ( $this->conditionBuilder->getOption( 'cjk.best.effort.proximity.match', false ) && CharExaminer::isCJK( $value ) ) {

				if ( $value[0] === '*' ) {
					$value = substr( $value, 1 );
				}

				if ( substr( $value , -1 ) === '*' ) {
					$value = substr( $value, 0, -1 );
				}

				// Use a phrase match to keep the char boundaries and avoid
				// matching single chars
				$value = "\"$value\"";
			}

			// Q1203
			// [[phrase:fox jump*]] (aka ~"fox jump*") + wildcard; use match with
			// a `multi_match` and type `phrase_prefix`
			$isPhrase = strpos( $value, '"' ) !== false;
			$hasWildcard = strpos( $value, '*' ) !== false;

			// Match a page title, the issue is accuracy vs. proximity

			// Boolean operators (+/-) are allowed? Use the query_string
			// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#_boolean_operators
			if ( $this->conditionBuilder->getOption( 'query_string.boolean.operators' ) && ( strpos( $value, '+' ) !== false || strpos( $value, '-' ) !== false ) ) {
				$match = $this->fieldMapper->query_string( "$pid.$field", $value );
			} elseif ( ( $hasWildcard && $value[0] === '*' ) && $this->conditionBuilder->getOption( 'cjk.best.effort.proximity.match', false ) && CharExaminer::isCJK( $value ) ) {

				// Avoid *...* on CJK related terms so that something like
				// [[Has page::in:名古屋]] returns a better match accuracy given that
				// the standard analyzer splits CJK terms into single characters
				if ( $value[0] === '*' ) {
					$value = mb_substr( $value, 1 );
				}

				if ( mb_substr( $value , -1 ) === '*' ) {
					$value = mb_substr( $value, 0, -1 );
				}

				// Use a phrase match to keep the char boundaries and avoid
				// matching single chars
				$match = $this->fieldMapper->match( "$pid.$field", "\"$value\"" );

			} elseif ( ( $hasWildcard && $value[0] === '*' ) || ( strpos( $value, '~?' ) !== false && $value[0] === '?' ) ) {
				// ES notes "... In order to prevent extremely slow wildcard queries,
				// a wildcard term should not start with one of the wildcards
				// * or ? ..." therefore use `query_string` instead of a
				// `wildcard` term search
				// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html
				$match = $this->fieldMapper->query_string( "$pid.$field", $value );
			} elseif ( $hasWildcard && !$isPhrase ) {

				// T:Q0910, Wildcard?
				// - Use the term search `wildcard` with text not being
				// analyzed which means that things like [[Has page::~Foo bar/Bar/*]]
				// are matched strictly without manipulating the query string.
				// - `lowercase` field with a normalizer to achieve case
				// insensitivity
				if ( $this->conditionBuilder->getOption( 'page.field.case.insensitive.proximity.match', true ) ) {
					$field = "$field.lowercase";
				} else {
					$field = "$field.keyword";
				}

				$match = $this->fieldMapper->wildcard( "$pid.$field", $value );
				$type = $type === Condition::TYPE_MUST ? Condition::TYPE_FILTER : $type;
			} elseif ( $isPhrase ) {
				$match = $this->fieldMapper->match( "$pid.$field", $value );
			} else {
				$match = $this->fieldMapper->query_string( "$pid.$field", $value );
			}
		} elseif ( $comparator === SMW_CMP_NLKE ) {

			// T:Q0905, Interpreting the meaning of `!~elastic*, +sear*` which is
			// to match non with the term `elastic*` but those that match `sear*`
			// with the consequence that this is turned from a `must_not` to a `must`
			if ( $this->conditionBuilder->getOption( 'query_string.boolean.operators' ) && ( strpos( $value, '+' ) !== false ) ) {
				$type = Condition::TYPE_MUST;
				$value = "-$value";
			}

			$match = $this->fieldMapper->query_string( "$pid.$field", $value );
		} elseif ( $comparator === SMW_CMP_EQ ) {
			$type = Condition::TYPE_FILTER;
			$match = $this->fieldMapper->term( "$pid.$field", $value );
		} elseif ( $comparator === SMW_CMP_NEQ ) {
			$match = $this->fieldMapper->term( "$pid.$field", $value );
		} else {
			$match = $this->fieldMapper->match( "$pid.$field", $value, 'and' );
		}

		$options['field'] = $field;
		$options['value'] = $value;
		$options['type'] = $type;

		return $match;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIBlob $dataItem
	 * @param array $options
	 *
	 * @return array
	 */
	public function blob( DIBlob $dataItem, array &$options ) {

		$comparator = $options['comparator'];
		$pid = $options['pid'];
		$field = $options['field'];
		$type = $options['type'];

		$value = $dataItem->getSerialization();

		if ( mb_strlen( $value ) > $this->conditionBuilder->getOption( 'maximum.value.length' ) ) {
			$value = mb_substr( $value, 0, $this->conditionBuilder->getOption( 'maximum.value.length' ) );
		}

		if ( $this->isRange( $comparator ) ) {
			// Use a not_analyzed field
			$match = $this->fieldMapper->range( "$pid.$field.keyword", $value, $comparator );
		} elseif ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) {

			// #3020
			// Use a term query where possible to allow ES to create a bitset and
			// cache the lookup if possible
			if ( $options['property']->findPropertyValueType() === '_keyw' ) {
				$match = $this->fieldMapper->term( "$pid.$field.keyword", "$value" );
				$type = $type === Condition::TYPE_MUST ? Condition::TYPE_FILTER : $type;
			} elseif ( $this->conditionBuilder->getOption( 'text.field.case.insensitive.eq.match' ) ) {
				// [[Has text::Template one]] == [[Has text::template one]]
				$match = $this->fieldMapper->match_phrase( "$pid.$field", "$value" );
			} else {
				$match = $this->fieldMapper->term( "$pid.$field.keyword", "$value" );
				$type = $type === Condition::TYPE_MUST ? Condition::TYPE_FILTER : $type;
			}
		} elseif ( $comparator === SMW_CMP_LIKE ) {

			// Q1203
			// [[phrase:fox jump*]] (aka ~"fox jump*")

			// T:Q0102 Choose a `P:xxx.*` over a specific `P:xxx.txtField` field
			// to enforce a `DisjunctionMaxQuery` as in
			// `"(P:8316.txtField:*\\{* | P:8316.txtField.keyword:*\\{*)",`
			$fields = [ "$pid.$field", "$pid.$field.keyword" ];

			if ( $this->fieldMapper->isPhrase( $value ) ) {
				$match = $this->fieldMapper->match( $fields, $value );
			} else {
				$match = $this->fieldMapper->query_string( $fields, $value );
			}
		} elseif ( $comparator === SMW_CMP_NLKE ) {

			// T:Q0904, Interpreting the meaning of `!~elastic*, +sear*` which is
			// to match non with the term `elastic*` but those that match `sear*`
			// with the consequence that this is turned from a `must_not` to a `must`
			if ( $this->conditionBuilder->getOption( 'query_string.boolean.operators' ) && ( strpos( $value, '+' ) !== false ) ) {
				$type = Condition::TYPE_MUST;
				$value = "-$value";
			}

			$match = $this->fieldMapper->query_string( "$pid.$field", $value );
		} else {
			$match = $this->fieldMapper->match( "$pid.$field", $value, 'and' );
		}

		$options['field'] = $field;
		$options['value'] = $value;
		$options['type'] = $type;

		return $match;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIUri $dataItem
	 * @param array $options
	 *
	 * @return array
	 */
	public function uri( DIUri $dataItem, array &$options ) {

		$comparator = $options['comparator'];
		$pid = $options['pid'];
		$field = $options['field'];
		$type = $options['type'];

		$value = str_replace( [ '%2A' ], [ '*' ], rawurldecode( $dataItem->getUri() ) );

		if ( $this->isRange( $comparator ) ) {
			$match = $this->fieldMapper->range( "$pid.$field", $value, $comparator );
		} elseif ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) {

			if ( $this->conditionBuilder->getOption( 'uri.field.case.insensitive' ) ) {
				// As EQ, use the match_phrase to ensure that each part of the
				// string is part of the match.
				// T:Q0908
				$match = $this->fieldMapper->match_phrase( "$pid.$field.lowercase", "$value" );
			} else {
				// Use the keyword field (not analyzed) so that the search
				// matches the exact term
				// T:P0419 (`http://example.org/FoO` !== `http://example.org/Foo`)
				$match = $this->fieldMapper->term( "$pid.$field.keyword", "$value" );
			}
		} elseif ( $comparator === SMW_CMP_LIKE || $comparator === SMW_CMP_NLKE ) {

			$value = str_replace( [ 'http://', 'https://', '=' ], [ '', '', '' ], $value );

			if ( strpos( $value, 'tel:' ) !== false || strpos( $value, 'mailto:' ) !== false ) {
				$value = str_replace( [ 'tel:', 'mailto:' ], [ '', '' ], $value );
				$field = "$field.keyword";
			} elseif ( $this->conditionBuilder->getOption( 'uri.field.case.insensitive' ) ) {
				$field = "$field.lowercase";
			}

			$match = $this->fieldMapper->query_string( "$pid.$field", $value );
		} else {
			$match = $this->fieldMapper->match( "$pid.$field", $value, 'and' );
		}

		$options['field'] = $field;
		$options['value'] = $value;

		return $match;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIGeoCoord $dataItem
	 * @param array $options
	 *
	 * @return array
	 */
	public function geo( DIGeoCoord $dataItem, array &$options ) {

		$comparator = $options['comparator'];
		$pid = $options['pid'];
		$field = $options['field'];
		$type = $options['type'];

		$value = $dataItem->getSerialization();
		$options['value'] = $value;

		if ( $this->isRange( $comparator ) ) {
			$match = $this->fieldMapper->range( "$pid.$field", $value, $comparator );
		} elseif ( isset( $options['bounding_box'] ) ) {

			// Due to "QueryShardException: Geo fields do not support exact
			// searching, use dedicated geo queries instead" on EQ search,
			// the geo_point is indexed as extra field geoField.point to make
			// use of the `bounding_box` feature in ES while the standard EQ
			// search uses the geoField string representation
			$boundingBox = $options['bounding_box'];

			$match = $this->fieldMapper->geo_bounding_box(
				"$pid.$field.point",
				$boundingBox['north'],
				$boundingBox['west'],
				$boundingBox['south'],
				$boundingBox['east']
			);
		} elseif ( $comparator === SMW_CMP_LIKE ) {
			$match = $this->fieldMapper->match( "$pid.$field", $value, 'and' );
		} elseif ( $comparator === SMW_CMP_EQ ) {
			$options['type'] = Condition::TYPE_FILTER;
			$match = $this->fieldMapper->term( "$pid.$field", $value );
		} elseif ( $comparator === SMW_CMP_NEQ ) {
			$match = $this->fieldMapper->term( "$pid.$field", $value );
		} else {
			$match = $this->fieldMapper->match( "$pid.$field", $value, 'and' );
		}

		return $match;
	}

	/**
	 * @since 3.0
	 *
	 * @param mixed $value
	 * @param array $options
	 *
	 * @return array
	 */
	public function plain( $value, array &$options ) {

		if ( mb_strlen( $value ) > $this->conditionBuilder->getOption( 'maximum.value.length' ) ) {
			$value = mb_substr( $value, 0, $this->conditionBuilder->getOption( 'maximum.value.length' ) );
		}

		$comparator = $options['comparator'];
		$pid = $options['pid'];
		$field = $options['field'];

		if ( $this->isRange( $comparator ) ) {
			$match = $this->fieldMapper->range( "$pid.$field", $value, $comparator );
		} elseif ( $comparator === SMW_CMP_LIKE ) {
			$match = $this->fieldMapper->match( "$pid.$field", $value, 'and' );
		} elseif ( $comparator === SMW_CMP_EQ ) {
			$options['type'] = Condition::TYPE_FILTER;
			$match = $this->fieldMapper->term( "$pid.$field", $value );
		} elseif ( $comparator === SMW_CMP_NEQ ) {
			$match = $this->fieldMapper->term( "$pid.$field", $value );
		} else {
			$match = $this->fieldMapper->match( "$pid.$field", $value, 'and' );
		}

		return $match;
	}

	/**
	 * @since 3.0
	 *
	 * @param $params
	 * @param $options
	 *
	 * @return array
	 */
	public function inverse_property( $params, $options ) {

		$termsLookup = $this->conditionBuilder->getTermsLookup();
		$comparator = $options['comparator'];

		$pid = $options['pid'];
		$property = $options['property'];

		// A simple inverse is enough to fetch the inverse match for a resource
		// [[-Has query::F0103/PageContainsAskWithTemplateUsage]]
		if ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) {

			$parameters = $termsLookup->newParameters(
				[
					'query.string' => $options['query.string'],
					'property.key' => $property->getKey(),
					'field' => "$pid.wpgID",
					'params' => $options['value']
				]
			);

			$params = $termsLookup->lookup( 'inverse', $parameters );
			$this->conditionBuilder->addQueryInfo( $parameters->get('query.info' ) );
		} else {

			$field = $options['field'];

			// First we need to find entities that fulfill the condition
			// `~*Test*` to allow to match the `-Has subobject` part from
			// [[-Has subobject::~*Test*]]

			// Either use the resource or the document field
			$f = strpos( $field, 'wpg' ) !== false ? "$pid.wpgID" : "_id";

			$parameters = $termsLookup->newParameters(
				[
					'query.string' => $options['query.string'],
					'field' => $f,
					'params' => $params
				]
			);

			$p = $termsLookup->lookup( 'predef', $parameters );

			$this->conditionBuilder->addQueryInfo( $parameters->get('query.info' ) );

			$p = $this->fieldMapper->field_filter( $f, $p );

			$parameters->set( 'property.key', $property->getKey() );
			$parameters->set( 'params', $p );

			$params = $termsLookup->lookup( 'inverse', $parameters );
			$this->conditionBuilder->addQueryInfo( $parameters->get('query.info' ) );
		}

		return $params;
	}

	private function isRange( $comparator ) {
		return $comparator === SMW_CMP_GRTR || $comparator === SMW_CMP_GEQ || $comparator === SMW_CMP_LESS || $comparator === SMW_CMP_LEQ;
	}

	private function isNot( $comparator ) {
		return $comparator === SMW_CMP_NLKE || $comparator === SMW_CMP_NEQ;
	}

}
