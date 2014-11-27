<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\SPARQLStore\QueryEngine\ConditionBuilder\ClassConditionBuilder;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;

use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\ClassDescription;

use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\ConditionBuilder\ClassConditionBuilder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ClassConditionBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\ConditionBuilder\ClassConditionBuilder',
			new ClassConditionBuilder( $compoundConditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\ClassDescription' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ClassConditionBuilder( $compoundConditionBuilder );

		$this->assertTrue(
			$instance->canBuildConditionFor( $description )
		);
	}

	/**
	 * @dataProvider categoryProvider
	 */
	public function testClassConditionForCategories( $description, $orderByProperty, $expectedConditionType, $expectedConditionString ) {

		$resultVariable = 'result';

		$compoundConditionBuilder = new CompoundConditionBuilder();
		$compoundConditionBuilder->setResultVariable( $resultVariable );

		$instance = new ClassConditionBuilder();
		$instance->setCompoundConditionBuilder( $compoundConditionBuilder );

		$condition = $instance->buildCondition( $description, $resultVariable, $orderByProperty );

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
			$compoundConditionBuilder->convertConditionToString( $condition )
		);
	}

	public function categoryProvider() {

		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description = new ClassDescription( array() );
		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( '<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		);

		# 1
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new ClassDescription( new DIWikiPage( 'Foo', NS_CATEGORY ) );
		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( '{ ?result rdf:type wiki:Category-3AFoo . }' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$conditionType,
			$expected,
		);

		# 2
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new ClassDescription( array(
			new DIWikiPage( 'Foo', NS_CATEGORY ),
			new DIWikiPage( 'Bar', NS_CATEGORY )
		) );

		$orderByProperty = null;

		$expected = $stringBuilder
			->addString( '{ ?result rdf:type wiki:Category-3AFoo . }' )->addNewLine()
			->addString( 'UNION' )->addNewLine()
			->addString( '{ ?result rdf:type wiki:Category-3ABar . }' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		);

		# 3
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new ClassDescription( array(
			new DIWikiPage( 'Foo', NS_CATEGORY ),
			new DIWikiPage( 'Bar', NS_CATEGORY )
		) );

		$orderByProperty = new DIProperty( 'Foo' );

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '{ ?result rdf:type wiki:Category-3AFoo . }' )->addNewLine()
			->addString( 'UNION' )->addNewLine()
			->addString( '{ ?result rdf:type wiki:Category-3ABar . }' )->addNewLine()
			->getString();

		$provider[] = array(
			$description,
			$orderByProperty,
			$conditionType,
			$expected
		);

		return $provider;
	}

}
