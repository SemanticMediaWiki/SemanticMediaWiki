<?php

namespace SMW\Tests\Integration\Query;

use SMW\DataItems\Blob;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Query;
use SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector;
use SMW\SPARQLStore\SPARQLStore;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 *
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ComparatorFilterConditionQueryDBIntegrationTest extends SMWIntegrationTestCase {

	private $subjectsToBeCleared = [];
	private $semanticDataFactory;
	private $queryResultValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
	}

	protected function tearDown(): void {
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
		if ( is_a( $this->getStore(), SPARQLStore::class )
			&& is_a( $this->getStore()->getConnection( 'sparql' ), VirtuosoRepositoryConnector::class ) ) {
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
		$property = new Property( 'SomeNumericPropertyToFilter' );
		$property->setPropertyTypeId( '_num' );

		# 0 Numeric Greater Equal, Less Equal
		$provider[] = [
			[
				1 => new Number( 1 ),
				6 => new Number( 6 ),
				10 => new Number( 10 )
			],
			[
				'lower' => new Number( 1 ),
				'upper' => new Number( 9 ),
				'lowerComp' => SMW_CMP_GEQ,
				'upperComp' => SMW_CMP_LEQ,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ 1, 6 ]
			]
		];

		# 1 Numeric Greater, Equal
		$provider[] = [
			[
				1 => new Number( 1 ),
				2 => new Number( 2 ),
				6 => new Number( 6 ),
				10 => new Number( 10 )
			],
			[
				'lower' => new Number( 1 ),
				'upper' => new Number( 10 ),
				'lowerComp' => SMW_CMP_GRTR,
				'upperComp' => SMW_CMP_LESS,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ 2, 6 ]
			]
		];

		# 2 Numeric Greater, Less
		$provider[] = [
			[
				1 => new Number( 1 ),
				2 => new Number( 2 )
			],
			[
				'lower' => new Number( 1 ),
				'upper' => new Number( 2 ),
				'lowerComp' => SMW_CMP_GRTR,
				'upperComp' => SMW_CMP_LESS,
				'property'  => $property,
			],
			[
				'count'    => 0,
				'subjects' => []
			]
		];

		# 3 Numeric Greater, Not Like
		$provider[] = [
			[
				1 => new Number( 1 ),
				2 => new Number( 2 ),
				3 => new Number( 3 )
			],
			[
				'lower' => new Number( 1 ),
				'upper' => new Number( 3 ),
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
		$property = new Property( 'SomeBlobPropertyToFilter' );
		$property->setPropertyTypeId( '_txt' );

		# 4 Text, Greater Equal, Less Equal
		$provider[] = [
			[
				'AA' => new Blob( 'AA' ),
				'BB' => new Blob( 'BB' ),
				'CC' => new Blob( 'CC' )
			],
			[
				'lower' => new Blob( 'AA' ),
				'upper' => new Blob( 'BB' ),
				'lowerComp' => SMW_CMP_GEQ,
				'upperComp' => SMW_CMP_LEQ,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ 'AA', 'BB' ]
			]
		];

		# 5 Text, Like, Like
		$provider[] = [
			[
				'A'   => new Blob( 'A' ),
				'AA'  => new Blob( 'AA' ),
				'BBA' => new Blob( 'BBA' ),
				'AAC' => new Blob( 'AAC' )
			],
			[
				'lower' => new Blob( 'A*' ),
				'upper' => new Blob( 'AA*' ),
				'lowerComp' => SMW_CMP_LIKE,
				'upperComp' => SMW_CMP_LIKE,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ 'AA', 'AAC' ]
			]
		];

		# 6 Text, Like, Not Like
		$provider[] = [
			[
				'AABA' => new Blob( 'AABA' ),
				'AACA' => new Blob( 'AACA' ),
				'AAAB' => new Blob( 'AAAB' ),
			],
			[
				'lower' => new Blob( 'AA?A' ),
				'upper' => new Blob( 'AA?B' ),
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
		$property = new Property( 'SomeDatePropertyToFilter' );
		$property->setPropertyTypeId( '_dat' );

		# 7 Date, Greater Equal, Less Equal
		$provider[] = [
			[
				'197001' => new Time( 1, 1970, 01, 01, 1, 1 ),
				'197002' => new Time( 1, 1970, 02, 01, 1, 1 ),
				'197003' => new Time( 1, 1970, 03, 01, 1, 1 ),
			],
			[
				'lower' => new Time( 1, 1970, 01, 01 ),
				'upper' => new Time( 1, 1970, 03, 01 ),
				'lowerComp' => SMW_CMP_GEQ,
				'upperComp' => SMW_CMP_LEQ,
				'property'  => $property,
			],
			[
				'count'    => 2,
				'subjects' => [ '197001', '197002' ]
			]
		];

		# 7 Date, Greater Equal, Less Equal
		$provider[] = [
			[
				'1970011' => new Time( 1, 1970, 01, 01, 1, 1 ),
				'1970012' => new Time( 1, 1970, 01, 02, 1, 1 ),
				'1970013' => new Time( 1, 1970, 01, 03, 1, 1 ),
			],
			[
				'lower' => new Time( 1, 1970, 01, 01, 2 ),
				'upper' => new Time( 1, 1970, 01, 02, 2 ),
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
