<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use Maps\Semantic\ValueDescriptions\AreaDescription;
use SMW\DataTypeRegistry;
use SMW\DIWikiPage;
use SMW\Elastic\QueryEngine\Condition;
use SMW\Elastic\QueryEngine\FieldMapper;
use SMW\Elastic\QueryEngine\QueryBuilder;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDIGeoCoord as DIGeoCoord;
use SMWDInumber as DINumber;
use SMWDITime as DITime;
use SMWDIUri as DIUri;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SomePropertyInterpreter {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var FieldMapper
	 */
	private $fieldMapper;

	/**
	 * @var TermsLookup
	 */
	private $termsLookup;

	/**
	 * @since 3.0
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param SomeProperty $description
	 *
	 * @return array
	 */
	public function interpretDescription( SomeProperty $description, $isConjunction = false, $isChain = false ) {

		// Query types
		//
		// - term: query matches a single term as it is, the value is not
		//   analyzed
		// - match_phrase: query will analyze the input, all the terms must
		//   appear in the field, they must have the same order as the input
		//   value

		// Bool types
		//
		// - must: query must appear in matching documents and will contribute
		//   to the score
		// - filter: query must appear in matching documents, the score
		//   of the query will be ignored
		// - should: query should appear in the matching document

		$this->fieldMapper = $this->queryBuilder->getFieldMapper();
		$this->termsLookup = $this->queryBuilder->getTermsLookup();

		$property = $description->getProperty();
		$pid = $this->fieldMapper->getPID( $this->queryBuilder->getID( $property ) );

		$hierarchy = $this->queryBuilder->findHierarchyMembers(
			$property,
			$description->getHierarchyDepth()
		);

		$desc = $description->getDescription();

		// Copy the context
		if ( isset( $description->isPartOfDisjunction ) ) {
			$desc->isPartOfDisjunction = true;
		}

		$field = 'wpgID';
		$opType = Condition::TYPE_MUST;

		$field = $this->fieldMapper->getField( $property, 'Field' );
		$params = [];

		// [[Foo::Bar]]
		if ( $desc instanceof ValueDescription ) {
			$params = $this->interpretValueDescription( $desc, $property, $pid, $field, $opType );
		}

		// [[Foo::+]]
		if ( $desc instanceof ThingDescription ) {
			$params = $this->interpretThingDescription( $desc, $property, $pid, $field, $opType );
		}

		if ( $params !== [] ) {
			$params = $this->fieldMapper->hierarchy( $params, $pid, $hierarchy );
		}

		// [[-Person:: <q>[[Person.-Has friend.Person::Andy Mars]] [[Age::>>32]]</q> ]]
		if ( $desc instanceof Conjunction ) {
			$params = $this->interpretConjunction( $desc, $property, $pid, $field );
		}

		// Use case: `[[Has page-2:: <q>[[Has page-1::Value 1||Value 2]]
		// [[Has text-1::Value 1||Value 2]]</q> || <q> [[Has page-2::Value 1||Value 2]]</q> ]]`
		if ( $desc instanceof Disjunction ) {
			$params = $this->interpretDisjunction( $desc, $property, $pid, $field, $opType );
		}

		if ( !$params instanceof Condition ) {
			$condition = $this->queryBuilder->newCondition( $params );
		} else {
			$condition = $params;
		}

		$condition->type( $opType );
		$condition->log( [ 'SomeProperty' => $description->getQueryString() ] );

		// [[Foo.Bar::Foobar]], [[Foo.Bar::<q>[[Foo::Bar]] OR [[Fobar::Foo]]</q>]]
		if ( $desc instanceof SomeProperty ) {
			$condition = $this->interpretChain( $desc, $property, $pid, $field );
		}

		if ( $condition === [] ) {
			return [];
		}

		// Build an extra condition to restore strictness by making sure
		// the property exist on those matched entities
		// `[[Has text::!~foo*]]` becomes `[[Has text::!~foo*]] [[Has text::+]`
		if ( $opType === Condition::TYPE_MUST_NOT && !$desc instanceof ThingDescription ) {

			// Use case: `[[Category:Q0905]] [[!Example/Q0905/1]] <q>[[Has page::123]]
			// OR [[Has page::!ABCD]]</q>`
			$params = [ $this->fieldMapper->exists( "$pid.$field" ), $condition ];
			$condition = $this->queryBuilder->newCondition( $params );
			$condition->type( '' );

			if ( $this->queryBuilder->getOption( 'must_not.property.exists' ) ) {
				$description->notConditionField = "$pid.$field";
			}

			// Use case: `[[Has telephone number::!~*123*]]`
			if ( !$isConjunction ) {
				$condition->type( 'must' );
			}
		}

		if ( $isChain === false ) {
			return $condition;
		}

		if ( !isset( $description->sourceChainMemberField ) ) {
			throw new RuntimeException( "Missing `sourceChainMemberField`" );
		}

		$parameters = $this->termsLookup->newParameters(
			[
				'terms_filter.field' => $description->sourceChainMemberField,
				'query.string' => $description->getQueryString(),
				'property.key' => $property->getKey(),
				'params' => $condition->toArray()
			]
		);

		$params = $this->termsLookup->lookup( 'chain', $parameters );
		$this->queryBuilder->addQueryInfo( $parameters->get( 'query.info' ) );

		// Let it fail for a conjunction when the subquery returns empty!
		if ( $params === [] && !isset( $desc->isPartOfDisjunction ) ) {
			// Fail with a non existing condition to avoid a " ...
			// query malformed, must start with start_object ..."
			$params = $this->fieldMapper->exists( "empty.lookup_query" );
		}

		$condition = $this->queryBuilder->newCondition( $params );
		$condition->log( [ 'SomeProperty' => [ 'Chain' => $description->getQueryString() ] ] );

		return $condition;
	}

	private function interpretDisjunction( $description, $property, $pid, $field, &$opType ) {

		$p = [];
		$opType = Condition::TYPE_SHOULD;

		foreach ( $description->getDescriptions() as $desc ) {

			$d = new SomeProperty(
				$property,
				$desc
			);

			$d->sourceChainMemberField = "$pid.wpgID";
			$t = $this->queryBuilder->interpretDescription( $d, true, true );

			if ( $t !== [] ) {
				$p[] = $t->toArray();
			}
		}

		if ( $p === [] ) {
			return [];
		}

		//$this->fieldMapper->bool( 'should', $p );
		$condition = $this->queryBuilder->newCondition( $p );

		return $condition;
	}

	private function interpretConjunction( $description, $property, $pid, $field ) {

		$p = [];
		$logs = [];
		$queryString = $description->getQueryString();
		$logs[] = $queryString;
		$opType = Condition::TYPE_MUST;

		foreach ( $description->getDescriptions() as $desc ) {
			$params = $this->queryBuilder->interpretDescription( $desc, true );

			if ( $params !== [] ) {
				$p[] = $params->toArray();
				$logs = array_merge( $logs, $params->getLogs() );
			}
		}

		if ( $p !== [] ) {
			// We match IDs using the term lookup which is either a resource or
			// a document field (on a txtField etc.)
			$f = strpos( $field, 'wpg' ) !== false ? "$pid.wpgID" : "_id";

			$parameters = $this->termsLookup->newParameters(
				[
					'query.string' => $queryString,
					'field' => $f,
					'params' => $p
				]
			);

			$p = $this->termsLookup->lookup( 'predef', $parameters );
			$this->queryBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
		}

		// Inverse matches are always resource (aka wpgID) related
		if ( $property->isInverse() ) {
			$parameters = $this->termsLookup->newParameters(
				[
					'query.string' => $desc->getQueryString(),
					'property.key' => $property->getKey(),
					'field' => "$pid.wpgID",
					'params' => $this->fieldMapper->field_filter( "$pid.wpgID", $p )
				]
			);

			$p = $this->termsLookup->lookup( 'inverse', $parameters );
			$this->queryBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
		}

		if ( $p === [] ) {
			return [];
		}

		$condition = $this->queryBuilder->newCondition( $p );
		$condition->type( '' );

		$condition->log( [ 'SomeProperty' => [ 'Conjunction' => $logs ] ] );

		return $condition;
	}

	private function interpretChain( $desc, $property, $pid, $field ) {

		$desc->sourceChainMemberField = "$pid.wpgID";
		$p = [];

		// Use case: `[[Category:Sample-1]][[Has page-1.Has page-2:: <q>
		// [[Has text-1::Value 1]] OR <q>[[Has text-2::Value 2]]
		// [[Has page-2::Value 2]]</q></q> ]]`
		if ( $desc->getDescription() instanceof Disjunction ) {

			foreach ( $desc->getDescription()->getDescriptions() as $d ) {
				$d = new SomeProperty(
					$desc->getProperty(),
					$d
				);
				$d->setMembership( $desc->getFingerprint() );
				$d->sourceChainMemberField = "$pid.wpgID";

				if ( isset( $desc->isPartOfDisjunction ) ) {
					$d->isPartOfDisjunction = true;
				}

				$t = $this->interpretDescription( $d, true, true );

				if ( $t !== [] ) {
					$p[] = $t->toArray();
				}
			}

			$p = $this->fieldMapper->bool( 'should', $p );
		} else {
			$p = $this->interpretDescription( $desc, true, true );
		}

		if ( $property->isInverse() ) {
			$parameters = $this->termsLookup->newParameters(
				[
					'query.string' => $desc->getQueryString(),
					'property.key' => $property->getKey(),
					'field' => "$pid.wpgID",
					'params' => $this->fieldMapper->field_filter( "$pid.wpgID", $p->toArray() )
				]
			);

			$p = $this->termsLookup->lookup( 'inverse', $parameters );
			$this->queryBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
		}

		$condition = $this->queryBuilder->newCondition( $p );
		$condition->type( '' );

		return $condition;
	}

	private function interpretThingDescription( $desc, $property, $pid, $field, &$opType ) {

		$isResourceType = false;

		if ( DataTypeRegistry::getInstance()->getDataItemByType( $property->findPropertyValueType() ) === DataItem::TYPE_WIKIPAGE ) {
			$field = 'wpgID';
			$isResourceType = true;
		}

		// [[Has subobject::!+]] is only supported with the ElasticStore
		$opType = isset( $desc->isNegation ) ? Condition::TYPE_MUST_NOT : Condition::TYPE_FILTER;
		$params = $this->fieldMapper->exists( "$pid.$field" );

		// Only allow to match wpg types (aka resources) to be used as
		// invertible query element, this matches the SQLStore behaviour
		if ( $property->isInverse() && $isResourceType ) {
			$parameters = $this->termsLookup->newParameters(
				[
					'query.string' => $desc->getQueryString(),
					'property.key' => $property->getKey(),
					'field' => "$pid.$field",
					'params' => ''
				]
			);

			$params = $this->termsLookup->lookup( 'inverse', $parameters );
			$this->queryBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
		}

		$condition = $this->queryBuilder->newCondition( $params );
		$condition->type( '' );

		return $condition;
	}

	private function interpretValueDescription( $desc, $property, $pid, &$field, &$opType ) {

		$dataItem = $desc->getDataItem();
		$comparator = $desc->getComparator();
		$value = '';
		$opType = Condition::TYPE_MUST;

		$isSubDataType = DataTypeRegistry::getInstance()->isSubDataType(
			$property->findPropertyValueType()
		);

		$comparator = $comparator === SMW_CMP_PRIM_LIKE ? SMW_CMP_LIKE : $comparator;
		$comparator = $comparator === SMW_CMP_PRIM_NLKE ? SMW_CMP_NLKE : $comparator;

		if ( $comparator === SMW_CMP_NLKE || $comparator === SMW_CMP_NEQ ) {
			$opType = Condition::TYPE_MUST_NOT;
		}

		if ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_EQ ) {
			$field = 'wpgID';
			$value = $this->queryBuilder->getID( $dataItem );
		} elseif ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_NEQ ) {
			$field = 'wpgID';
			$value = $this->queryBuilder->getID( $dataItem );
		} elseif ( $dataItem instanceof DIWikiPage ) {
			$value = $dataItem->getSortKey();
		} elseif ( $dataItem instanceof DITime ) {
			$value = $dataItem->getJD();
		} elseif ( $dataItem instanceof DIBoolean ) {
			$value = $dataItem->getBoolean();
		} elseif ( $dataItem instanceof DIGeoCoord ) {
			$value = $dataItem->getSerialization();
		} elseif ( $dataItem instanceof DINumber ) {
			$value = $dataItem->getNumber();
		} elseif ( $dataItem instanceof DIUri ) {
			$value = str_replace( [ '%2A' ], [ '*' ], rawurldecode( $dataItem->getUri() ) );
		} else {
			$value = $dataItem->getSerialization();
		}

		$match = [];

		if ( $comparator === SMW_CMP_GRTR || $comparator === SMW_CMP_GEQ ) {

			// Use not analyzed field
			if ( $dataItem instanceof DIBlob ) {
				$field = "$field.keyword";
			}

			$match = $this->fieldMapper->range( "$pid.$field", $value, $comparator );
		} elseif ( $comparator === SMW_CMP_LESS || $comparator === SMW_CMP_LEQ ) {

			// Use not analyzed field
			if ( $dataItem instanceof DIBlob ) {
				$field = "$field.keyword";
			}

			$match = $this->fieldMapper->range( "$pid.$field", $value, $comparator );
		} elseif ( $dataItem instanceof DIWikiPage && $isSubDataType && $dataItem->getDBKEY() === '' && $comparator === SMW_CMP_NEQ ) {
			// [[Has subobject::!]] select those that are not a subobject
			$match = $this->fieldMapper->term( "subject.subobject.keyword", '' );
			$opType = Condition::TYPE_FILTER;
		} elseif ( $dataItem instanceof DIBlob && ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) ) {

			// #3020
			// Use a term query where possible to allow ES to create a bitset and
			// cache the lookup if possible
			if ( $property->findPropertyValueType() === '_keyw' ) {
				$match = $this->fieldMapper->term( "$pid.$field.keyword", "$value" );
				$opType = $opType === Condition::TYPE_MUST ? Condition::TYPE_FILTER : $opType;
			} elseif ( $this->queryBuilder->getOption( 'text.field.case.insensitive.eq.match' ) ) {
				// [[Has text::Template one]] == [[Has text::template one]]
				$match = $this->fieldMapper->match_phrase( "$pid.$field", "$value" );
			} else {
				$match = $this->fieldMapper->term( "$pid.$field.keyword", "$value" );
				$opType = $opType === Condition::TYPE_MUST ? Condition::TYPE_FILTER : $opType;
			}
		} elseif ( $dataItem instanceof DIUri && ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) ) {

			if ( $this->queryBuilder->getOption( 'uri.field.case.insensitive' ) ) {
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
		} elseif ( $dataItem instanceof DIBlob && $comparator === SMW_CMP_LIKE ) {

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
		} elseif ( $dataItem instanceof DIBlob && $comparator === SMW_CMP_NLKE ) {

			// T:Q0904, Interpreting the meaning of `!~elastic*, +sear*` which is
			// to match non with the term `elastic*` but those that match `sear*`
			// with the conseqence that this is turned from a `must_not` to a `must`
			if ( $this->queryBuilder->getOption( 'query_string.boolean.operators' ) && ( strpos( $value, '+' ) !== false ) ) {
				$opType = Condition::TYPE_MUST;
				$value = "-$value";
			}

			$match = $this->fieldMapper->query_string( "$pid.$field", $value );
		} elseif ( $dataItem instanceof DIUri && $comparator === SMW_CMP_LIKE || $dataItem instanceof DIUri && $comparator === SMW_CMP_NLKE ) {

			$value = str_replace( [ 'http://', 'https://', '=' ], [ '', '', '' ], $value );

			if ( strpos( $value, 'tel:' ) !== false || strpos( $value, 'mailto:' ) !== false ) {
				$value = str_replace( [ 'tel:', 'mailto:' ], [ '', '' ], $value );
				$field = "$field.keyword";
			} elseif ( $this->queryBuilder->getOption( 'uri.field.case.insensitive' ) ) {
				$field = "$field.lowercase";
			}

			$match = $this->fieldMapper->query_string( "$pid.$field", $value );
		} elseif ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_LIKE ) {

			// Q1203
			// [[phrase:fox jump*]] (aka ~"fox jump*") + wildcard; use match with
			// a `multi_match` and type `phrase_prefix`
			$isPhrase = strpos( $value, '"' ) !== false;

			// Match a page title, the issue is accuracy vs. proximity

			// Boolean operators (+/-) are allowed? Use the query_string
			// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#_boolean_operators
			if ( $this->queryBuilder->getOption( 'query_string.boolean.operators' ) && ( strpos( $value, '+' ) !== false || strpos( $value, '-' ) !== false ) ) {
				$match = $this->fieldMapper->query_string( "$pid.$field", $value );
			} elseif ( ( strpos( $value, '*' ) !== false && $value{0} === '*' ) || ( strpos( $value, '~?' ) !== false && $value{0} === '?' ) ) {
				// ES notes "... In order to prevent extremely slow wildcard queries,
				// a wildcard term should not start with one of the wildcards
				// * or ? ..." therefore use `query_string` instead of a
				// `wildcard` term search
				// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html
				$match = $this->fieldMapper->query_string( "$pid.$field", $value );
			} elseif ( strpos( $value, '*' ) !== false && !$isPhrase ) {

				// T:Q0910, Wildcard?
				// - Use the term search `wildcard` with text not being
				// analyzed which means that things like [[Has page::~Foo bar/Bar/*]]
				// are matched strictly without manipulating the query string.
				// - `lowercase` field with a normalizer to achieve case
				// insensitivity
				if ( $this->queryBuilder->getOption( 'page.field.case.insensitive.proximity.match', true ) ) {
					$field = "$field.lowercase";
				} else {
					$field = "$field.keyword";
				}

				$match = $this->fieldMapper->wildcard( "$pid.$field", $value );
				$opType = $opType === Condition::TYPE_MUST ? Condition::TYPE_FILTER : $opType;
			} elseif ( $isPhrase ) {
				$match = $this->fieldMapper->match( "$pid.$field", $value );
			} else {
				$match = $this->fieldMapper->query_string( "$pid.$field", $value );
			}
		} elseif ( $dataItem instanceof DIWikiPage && $comparator === SMW_CMP_NLKE ) {

			// T:Q0905, Interpreting the meaning of `!~elastic*, +sear*` which is
			// to match non with the term `elastic*` but those that match `sear*`
			// with the conseqence that this is turned from a `must_not` to a `must`
			if ( $this->queryBuilder->getOption( 'query_string.boolean.operators' ) && ( strpos( $value, '+' ) !== false ) ) {
				$opType = Condition::TYPE_MUST;
				$value = "-$value";
			}

			$match = $this->fieldMapper->query_string( "$pid.$field", $value );
		} elseif ( $dataItem instanceof DIGeoCoord && $desc instanceof AreaDescription ) {

			// Due to "QueryShardException: Geo fields do not support exact
			// searching, use dedicated geo queries instead" on EQ search,
			// the geo_point is indexed as extra field geoField.point to make
			// use of the `bounding_box` feature in ES while the standard EQ
			// search uses the geoField string representation
			$boundingBox = $desc->getBoundingBox();

			$match = $this->fieldMapper->geo_bounding_box(
				"$pid.$field.point",
				$boundingBox['north'],
				$boundingBox['west'],
				$boundingBox['south'],
				$boundingBox['east']
			);
		} elseif ( $dataItem instanceof DIGeoCoord && $comparator === SMW_CMP_EQ ) {
			$match = $this->fieldMapper->terms( "$pid.$field", $value );
		} elseif ( $comparator === SMW_CMP_LIKE ) {
			$match = $this->fieldMapper->match( "$pid.$field", $value, 'and' );
		} elseif ( $comparator === SMW_CMP_EQ ) {
			$opType = Condition::TYPE_FILTER;
			$match = $this->fieldMapper->term( "$pid.$field", $value );
		} elseif ( $comparator === SMW_CMP_NEQ ) {
			$match = $this->fieldMapper->term( "$pid.$field", $value );
		} else {
			$match = $this->fieldMapper->match( "$pid.$field", $value, 'and' );
		}

		$params = $match;

		if ( $property->isInverse() ) {

			// A simple inverse is enough to fetch the inverse match for a resource
			// [[-Has query::F0103/PageContainsAskWithTemplateUsage]]
			if ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) {

				$parameters = $this->termsLookup->newParameters(
					[
						'query.string' => $desc->getQueryString(),
						'property.key' => $property->getKey(),
						'field' => "$pid.wpgID",
						'params' => $value
					]
				);

				$params = $this->termsLookup->lookup( 'inverse', $parameters );
				$this->queryBuilder->addQueryInfo( $parameters->get('query.info' ) );
			} else {

				// First we need to find entities that fulfill the condition
				// `~*Test*` to allow to match the `-Has subobject` part from
				// [[-Has subobject::~*Test*]]

				// Either use the resource or the document field
				$f = strpos( $field, 'wpg' ) !== false ? "$pid.wpgID" : "_id";

				$parameters = $this->termsLookup->newParameters(
					[
						'query.string' => $desc->getQueryString(),
						'field' => $f,
						'params' => $params
					]
				);

				$p = $this->termsLookup->lookup( 'predef', $parameters );

				$this->queryBuilder->addQueryInfo( $parameters->get('query.info' ) );

				$p = $this->fieldMapper->field_filter( $f, $p );

				$parameters->set( 'property.key', $property->getKey() );
				$parameters->set( 'params', $p );

				$params = $this->termsLookup->lookup( 'inverse', $parameters );
				$this->queryBuilder->addQueryInfo( $parameters->get('query.info' ) );
			}
		}

		$condition = $this->queryBuilder->newCondition( $params );
		$condition->type( '' );

		return $condition;
	}

}
