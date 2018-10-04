<?php

namespace SMW\Tests\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\DIWikiPage;
use SMW\Query\DescriptionFactory;

/**
 * @covers \SMW\Elastic\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ClassDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $conditionBuilder;

	public function setUp() {

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->setMethods( [ 'getID', 'findHierarchyMembers' ] )
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ClassDescriptionInterpreter::class,
			new ClassDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	/**
	 * @dataProvider classDescriptionProvider
	 */
	public function testInterpretDescription( $description, $isConjunction, $hierarchyMembers, $expected ) {

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getID' )
			->will( $this->onConsecutiveCalls( 42, 1001, 9000, 110001 ) );

		$this->conditionBuilder->expects( $this->any() )
			->method( 'findHierarchyMembers' )
			->will( $this->returnValue( $hierarchyMembers ) );

		$instance = new ClassDescriptionInterpreter(
			$this->conditionBuilder
		);

		$condition = $instance->interpretDescription(
			$description,
			$isConjunction
		);

		$this->assertEquals(
			$expected,
			$condition
		);
	}

	public function classDescriptionProvider() {

		$descriptionFactory = new DescriptionFactory();
		$cat_foo = DIWikiPage::newFromText( 'Foo', NS_CATEGORY );
		$cat_bar = DIWikiPage::newFromText( 'Bar', NS_CATEGORY );

		yield [
			$descriptionFactory->newClassDescription( $cat_foo ),
			false,
			[],
			'{"bool":{"filter":[{"term":{"P:42.wpgID":1001}}]}}'
		];

		yield [
			$descriptionFactory->newClassDescription( $cat_foo ),
			true,
			[],
			'{"bool":{"filter":[{"term":{"P:42.wpgID":1001}}]}}'
		];

		// Categories
		$classDescription = $descriptionFactory->newClassDescription( $cat_foo );
		$classDescription->addClass( $cat_bar );

		yield [
			$classDescription,
			false,
			[],
			'{"bool":{"should":[{"term":{"P:42.wpgID":1001}},{"term":{"P:42.wpgID":9000}}]}}'
		];

		yield [
			$classDescription,
			true,
			[],
			'{"bool":{"should":[{"term":{"P:42.wpgID":1001}},{"term":{"P:42.wpgID":9000}}]}}'
		];

		// HierarchyMembers
		yield [
			$descriptionFactory->newClassDescription( $cat_foo ),
			false,
			[ 5000, 5001 ],
			'{"bool":{"filter":[{"bool":{"should":[{"term":{"P:42.wpgID":1001}},{"terms":{"P:42.wpgID":[5000,5001]}}]}}]}}'
		];

		yield [
			$descriptionFactory->newClassDescription( $cat_foo ),
			true,
			[ 5000, 5001 ],
			'{"bool":{"filter":[{"bool":{"should":[{"term":{"P:42.wpgID":1001}},{"terms":{"P:42.wpgID":[5000,5001]}}]}}]}}'
		];

		yield [
			$classDescription,
			false,
			[ 5000, 5001 ],
			'{"bool":{"should":[{"bool":{"should":[{"term":{"P:42.wpgID":1001}},{"terms":{"P:42.wpgID":[5000,5001]}}]}},{"bool":{"should":[{"term":{"P:42.wpgID":9000}},{"terms":{"P:42.wpgID":[5000,5001]}}]}}]}}'
		];

		// Negation
		$classDescription = $descriptionFactory->newClassDescription( $cat_foo );
		$classDescription->isNegation = true;

		yield [
			$classDescription,
			false,
			[],
			'{"bool":{"must_not":[{"term":{"P:42.wpgID":1001}}]}}'
		];

		yield [
			$classDescription,
			true,
			[],
			'{"bool":{"must_not":[{"term":{"P:42.wpgID":1001}}]}}'
		];
	}

}
