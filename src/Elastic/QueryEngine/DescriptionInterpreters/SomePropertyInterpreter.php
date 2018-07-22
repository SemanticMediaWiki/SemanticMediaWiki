<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use Maps\Semantic\ValueDescriptions\AreaDescription;
use SMW\DataTypeRegistry;
use SMW\DIWikiPage;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\Condition;
use SMW\Elastic\QueryEngine\FieldMapper;
use SMW\Elastic\QueryEngine\QueryBuilder;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\NamespaceDescription;
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
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

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
	 * @param ConditionBuilder $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder ) {
		$this->conditionBuilder = $conditionBuilder;
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

		$this->fieldMapper = $this->conditionBuilder->getFieldMapper();
		$this->termsLookup = $this->conditionBuilder->getTermsLookup();

		$property = $description->getProperty();
		$pid = $this->fieldMapper->getPID( $this->conditionBuilder->getID( $property ) );

		$hierarchy = $this->conditionBuilder->findHierarchyMembers(
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

		if ( $desc instanceof ClassDescription ) {
			$params = $this->interpretClassDescription( $desc, $property, $pid, $field );
		}

		if ( $desc instanceof NamespaceDescription ) {
			$params = $this->interpretNamespaceDescription( $desc, $property, $pid, $field );
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
			$condition = $this->conditionBuilder->newCondition( $params );
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
			$condition = $this->conditionBuilder->newCondition( $params );
			$condition->type( '' );

			if ( $this->conditionBuilder->getOption( 'must_not.property.exists' ) ) {
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
		$this->conditionBuilder->addQueryInfo( $parameters->get( 'query.info' ) );

		// Let it fail for a conjunction when the subquery returns empty!
		if ( $params === [] && !isset( $desc->isPartOfDisjunction ) ) {
			// Fail with a non existing condition to avoid a " ...
			// query malformed, must start with start_object ..."
			$params = $this->fieldMapper->exists( "empty.lookup_query" );
		}

		$condition = $this->conditionBuilder->newCondition( $params );
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
			$t = $this->conditionBuilder->interpretDescription( $d, true, true );

			if ( $t !== [] ) {
				$p[] = $t->toArray();
			}
		}

		if ( $p === [] ) {
			return [];
		}

		//$this->fieldMapper->bool( 'should', $p );
		$condition = $this->conditionBuilder->newCondition( $p );

		return $condition;
	}

	private function interpretClassDescription( $description, $property, $pid, $field ) {

		$queryString = $description->getQueryString();
		$condition = $this->conditionBuilder->interpretDescription( $description );

		$parameters = $this->termsLookup->newParameters(
			[
				'query.string' => $queryString,
				'field' => "$pid.wpgID",
				'params' => $condition
			]
		);

		$params = $this->termsLookup->lookup( 'predef', $parameters );
		$this->conditionBuilder->addQueryInfo( $parameters->get( 'query.info' ) );

		if ( $params === [] ) {
			return [];
		}

		$condition = $this->conditionBuilder->newCondition( $params );
		$condition->type( Condition::TYPE_MUST );
		$condition->log( [ 'SomeProperty' => [ 'ClassDescription' => $queryString ] ] );

		return $condition;
	}

	private function interpretNamespaceDescription( $description, $property, $pid, $field ) {

		$queryString = $description->getQueryString();
		$condition = $this->conditionBuilder->interpretDescription( $description );

		$parameters = $this->termsLookup->newParameters(
			[
				'query.string' => $queryString,
				'field' => "$pid.wpgID",
				'params' => $condition
			]
		);

		$params = $this->termsLookup->lookup( 'predef', $parameters );
		$this->conditionBuilder->addQueryInfo( $parameters->get( 'query.info' ) );

		if ( $params === [] ) {
			return [];
		}

		$condition = $this->conditionBuilder->newCondition( $params );
		$condition->type( Condition::TYPE_MUST );
		$condition->log( [ 'SomeProperty' => [ 'NamespaceDescription' => $queryString ] ] );

		return $condition;
	}

	private function interpretConjunction( $description, $property, $pid, $field ) {

		$p = [];
		$logs = [];
		$queryString = $description->getQueryString();
		$logs[] = $queryString;
		$opType = Condition::TYPE_MUST;

		foreach ( $description->getDescriptions() as $desc ) {
			$params = $this->conditionBuilder->interpretDescription( $desc, true );

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
			$this->conditionBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
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
			$this->conditionBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
		}

		if ( $p === [] ) {
			return [];
		}

		$condition = $this->conditionBuilder->newCondition( $p );
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
			$this->conditionBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
		}

		$condition = $this->conditionBuilder->newCondition( $p );
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
			$this->conditionBuilder->addQueryInfo( $parameters->get( 'query.info' ) );
		}

		$condition = $this->conditionBuilder->newCondition( $params );
		$condition->type( '' );

		return $condition;
	}

	private function interpretValueDescription( $desc, $property, $pid, &$field, &$type ) {

		$options = [
			'type' => $type,
			'field' => $field,
			'pid' => $pid,
			'property' => $property
		];

		$condition = $this->conditionBuilder->interpretSomeValue( $desc, $options );

		$field = $options['field'];
		$type = $options['type'];

		return $condition;
	}

}
