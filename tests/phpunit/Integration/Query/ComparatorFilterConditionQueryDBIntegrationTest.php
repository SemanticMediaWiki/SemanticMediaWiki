<?php

namespace SMW\Tests\Integration\Query;

use SMW\DIProperty;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMwConjunction as Conjunction;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;
use SMWDITime as DITime;
use SMWQuery as Query;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 *
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ComparatorFilterConditionQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	private $subjectsToBeCleared = [];
	private $semanticDataFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	/**
	 * @dataProvider numericConjunctionFilterProvider
	 */
	public function testNumericConjunctionConstraints( $range, $parameters, $expected ) {
		$this->queryPagesThatUseConjunctionConstraintsForPropertyValues( $range, $parameters, $expected );
	}

	/**
	 * @dataProvider textConjunctionFilterProvider
	 */
	public function testTextConjunctionConstraints( $range, $parameters, $expected ) {
		$this->queryPagesThatUseConjunctionConstraintsForPropertyValues( $range, $parameters, $expected );
	}

	/**
	 * @dataProvider dateConjunctionFilterProvider
	 */
	public function testDateConjunctionConstraints( $range, $parameters, $expected ) {

		if ( is_a( $this->getStore(), '\SMW\SPARQLStore\SPARQLStore' )
			&& is_a( $this->getStore()->getConnection( 'sparql' ), '\SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector' ) ) {
			$this->markTestSkipped( "Date filter constraints do not work properly in Virtuoso" );
		}

		$this->queryPagesThatUseConjunctionConstraintsForPropertyValues( $range, $parameters, $expected );
	}

	public function queryPagesThatUseConjunctionConstraintsForPropertyValues( $range, $parameters, $expected ) {

		$expectedSubjects = [];
		$property = $parameters['property'];

		foreach ( $range as $key => $value ) {

			$semanticData = $this->semanticDataFactory
				->newEmptySemanticData( __METHOD__ . strval( $key ) );

			$semanticData->addPropertyObjectValue( $property, $value );

			$this->subjectsToBeCleared[] = $semanticData->getSubject();
			$this->getStore()->updateData( $semanticData );

			if ( in_array( $key, $expected['subjects'] ) ) {
				$expectedSubjects[] = $semanticData->getSubject();
			}
		}

		$description = new Conjunction( [
			new SomeProperty(
				$property,
				new ValueDescription( $parameters['lower'], null, $parameters['lowerComp'] ) ),
			new SomeProperty(
				$property,
				new ValueDescription( $parameters['upper'], null, $parameters['upperComp'] ) ),
		] );

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->assertEquals(
			$expected['count'],
			$queryResult->getCount()
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$queryResult
		);
	}

	public function numericConjunctionFilterProvider() {

		$property = new DIProperty( 'SomeNumericPropertyToFilter' );
		$property->setPropertyTypeId( '_num' );

		#0 Numeric Greater Equal, Less Equal
		$provider[] = [
			[
				1 => new DINumber( 1 ),
				6 => new DINumber( 6 ),
				10 => new DINumber( 10 )
			],
			[
				'lower' => new DINumber( 1 ),
				'upper' => new DINumber( 9 ),
				'lowerComp' => SMW_CMP_GEQ,
				'upperComp' => SMW_CMP_LEQ,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ 1, 6 ]
			]
		];

		#1 Numeric Greater, Equal
		$provider[] = [
			[
				1 => new DINumber( 1 ),
				2 => new DINumber( 2 ),
				6 => new DINumber( 6 ),
				10 => new DINumber( 10 )
			],
			[
				'lower' => new DINumber( 1 ),
				'upper' => new DINumber( 10 ),
				'lowerComp' => SMW_CMP_GRTR,
				'upperComp' => SMW_CMP_LESS,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ 2, 6 ]
			]
		];

		#2 Numeric Greater, Less
		$provider[] = [
			[
				1 => new DINumber( 1 ),
				2 => new DINumber( 2 )
			],
			[
				'lower' => new DINumber( 1 ),
				'upper' => new DINumber( 2 ),
				'lowerComp' => SMW_CMP_GRTR,
				'upperComp' => SMW_CMP_LESS,
				'property'  => $property,
			],
			[
				'count'    => 0,
				'subjects' => []
			]
		];

		#3 Numeric Greater, Not Like
		$provider[] = [
			[
				1 => new DINumber( 1 ),
				2 => new DINumber( 2 ),
				3 => new DINumber( 3 )
			],
			[
				'lower' => new DINumber( 1 ),
				'upper' => new DINumber( 3 ),
				'lowerComp' => SMW_CMP_GRTR,
				'upperComp' => SMW_CMP_NEQ,
				'property'  => $property,
			],
			[
				'count'    => 1,
				'subjects' => [ 2 ]
			]
		];

		return $provider;
	}

	public function textConjunctionFilterProvider() {

		$property = new DIProperty( 'SomeBlobPropertyToFilter' );
		$property->setPropertyTypeId( '_txt' );

		#4 Text, Greater Equal, Less Equal
		$provider[] = [
			[
				'AA' => new DIBlob( 'AA' ),
				'BB' => new DIBlob( 'BB' ),
				'CC' => new DIBlob( 'CC' )
			],
			[
				'lower' => new DIBlob( 'AA' ),
				'upper' => new DIBlob( 'BB' ),
				'lowerComp' => SMW_CMP_GEQ,
				'upperComp' => SMW_CMP_LEQ,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ 'AA', 'BB' ]
			]
		];

		#5 Text, Like, Like
		$provider[] = [
			[
				'A'   => new DIBlob( 'A' ),
				'AA'  => new DIBlob( 'AA' ),
				'BBA' => new DIBlob( 'BBA' ),
				'AAC' => new DIBlob( 'AAC' )
			],
			[
				'lower' => new DIBlob( 'A*' ),
				'upper' => new DIBlob( 'AA*' ),
				'lowerComp' => SMW_CMP_LIKE,
				'upperComp' => SMW_CMP_LIKE,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ 'AA', 'AAC' ]
			]
		];

		#6 Text, Like, Not Like
		$provider[] = [
			[
				'AABA' => new DIBlob( 'AABA' ),
				'AACA' => new DIBlob( 'AACA' ),
				'AAAB' => new DIBlob( 'AAAB' ),
			],
			[
				'lower' => new DIBlob( 'AA?A' ),
				'upper' => new DIBlob( 'AA?B' ),
				'lowerComp' => SMW_CMP_LIKE,
				'upperComp' => SMW_CMP_NLKE,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ 'AABA', 'AACA' ]
			]
		];

		return $provider;
	}

	public function dateConjunctionFilterProvider() {

		$property = new DIProperty( 'SomeDatePropertyToFilter' );
		$property->setPropertyTypeId( '_dat' );

		#7 Date, Greater Equal, Less Equal
		$provider[] = [
			[
				'197001' => new DITime( 1, 1970, 01, 01, 1, 1 ),
				'197002' => new DITime( 1, 1970, 02, 01, 1, 1 ),
				'197003' => new DITime( 1, 1970, 03, 01, 1, 1 ),
			],
			[
				'lower' => new DITime( 1, 1970, 01, 01 ),
				'upper' => new DITime( 1, 1970, 03, 01 ),
				'lowerComp' => SMW_CMP_GEQ,
				'upperComp' => SMW_CMP_LEQ,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ '197001', '197002' ]
			]
		];

		#7 Date, Greater Equal, Less Equal
		$provider[] = [
			[
				'1970011' => new DITime( 1, 1970, 01, 01, 1, 1 ),
				'1970012' => new DITime( 1, 1970, 01, 02, 1, 1 ),
				'1970013' => new DITime( 1, 1970, 01, 03, 1, 1 ),
			],
			[
				'lower' => new DITime( 1, 1970, 01, 01, 2 ),
				'upper' => new DITime( 1, 1970, 01, 02, 2 ),
				'lowerComp' => SMW_CMP_GRTR,
				'upperComp' => SMW_CMP_LESS,
				'property'  => $property,
			],
			[
				'count'    => 1,
				'subjects' => [ '1970012' ]
			]
		];

		return $provider;
	}

}
