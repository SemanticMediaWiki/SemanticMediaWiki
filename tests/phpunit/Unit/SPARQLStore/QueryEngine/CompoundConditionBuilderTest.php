<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;

use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\SomeProperty;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Localizer;

use SMWDataItem as DataItem;
use SMWDINumber as DINumber;
use SMWDIBlob as DIBlob;
use SMWDITime as DITime;
use SMW\Query\PrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class CompoundConditionBuilderTest extends \PHPUnit_Framework_TestCase {

	private $stringBuilder;

	protected function setUp() {
		parent::setUp();

		$this->stringBuilder = UtilityFactory::getInstance()->newStringBuilder();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder',
			new CompoundConditionBuilder()
		);
	}

	public function testQueryForSingleProperty() {

		$property = new DIProperty( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .'  )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQuerySomeProperty_ForKnownSortPropertyKey() {

		$property = new DIProperty( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance
			->setSortKeys( array( 'Foo' => 'DESC' ) )
			->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .'  )->addNewLine()
			->addString( '{ ?v1 swivt:wikiPageSortKey ?v1sk .'  )->addNewLine()
			->addString( '}' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQuerySomeProperty_ForUnknownSortPropertyKey() {

		$property = new DIProperty( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance
			->setSortKeys( array( 'Bar' => 'DESC' ) )
			->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Bar ?v2 .'  )->addNewLine()
			->addString( '{ ?v2 swivt:wikiPageSortKey ?v2sk .'  )->addNewLine()
			->addString( '}' )->addNewLine()
			->addString( '?result property:Foo ?v1 .'  )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQuerySomeProperty_ForEmptySortPropertyKey() {

		$property = new DIProperty( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance
			->setSortKeys( array( '' => 'DESC' ) )
			->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .'  )->addNewLine()
			->addString( '?result property:Foo ?v1 .'  )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQuerySomeProperty_OnInvalidSortKeyThrowsException() {

		$property = new DIProperty( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new CompoundConditionBuilder();
		$instance->setSortKeys( array( 'Foo', 'ASC' ) );

		$this->setExpectedException( 'RuntimeException' );
		$instance->buildCondition( $description );
	}

	public function testQueryForSinglePropertyWithValue() {

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' )
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '"SomePropertyValue" swivt:page ?url .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSomePropertyWithValue() {

		$property = new DIProperty( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'SomePropertyBlobValue' ) )
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo "SomePropertyBlobValue" .'  )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSinglePageTypePropertyWithValueComparator() {

		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'SomePropertyPageValue', NS_MAIN ), null, SMW_CMP_LEQ )
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1sk <= "SomePropertyPageValue" )' )->addNewLine()
			->addString( '?v1 swivt:wikiPageSortKey ?v1sk .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSingleBlobTypePropertyWithNotLikeComparator() {

		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'SomePropertyBlobValue' ), null, SMW_CMP_NLKE )
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( !regex( ?v1, "^SomePropertyBlobValue$", "s") )' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSingleCategory() {

		$category = new DIWikiPage( 'Foo', NS_CATEGORY, '' );

		$categoryName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$description = new ClassDescription(
			$category
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( "{ ?result rdf:type $categoryName . }" )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSingleNamespace() {

		$description = new NamespaceDescription( NS_HELP );

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$this->assertSame( 12, NS_HELP );

		$expectedConditionString = $this->stringBuilder
			->addString( '{ ?result swivt:wikiNamespace "12"^^xsd:integer . }' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyConjunction() {

		$conjunction = new Conjunction( array(
			new SomeProperty(
				new DIProperty( 'Foo' ), new ValueDescription( new DIBlob( 'SomePropertyValue' ) ) ),
			new SomeProperty(
				new DIProperty( 'Bar' ), new ThingDescription() ),
		) );

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $conjunction );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo "SomePropertyValue" .' )->addNewLine()
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyConjunctionWithGreaterLessEqualFilter() {

		$conjunction = new Conjunction( array(
			new SomeProperty(
				new DIProperty( 'Foo' ),
				new ValueDescription( new DINumber( 1 ), null, SMW_CMP_GEQ ) ),
			new SomeProperty(
				new DIProperty( 'Bar' ),
				new ValueDescription( new DINumber( 9 ), null, SMW_CMP_LEQ ) ),
		) );

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $conjunction );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 >= "1"^^xsd:double )' )->addNewLine()
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->addString( 'FILTER( ?v2 <= "9"^^xsd:double )' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyDisjunction() {

		$conjunction = new Disjunction( array(
			new SomeProperty( new DIProperty( 'Foo' ), new ThingDescription() ),
			new SomeProperty( new DIProperty( 'Bar' ), new ThingDescription() )
		) );

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $conjunction );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '{' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( '} UNION {' )->addNewLine()
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->addString( '}' )
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyDisjunctionWithLikeNotLikeFilter() {

		$conjunction = new Disjunction( array(
			new SomeProperty(
				new DIProperty( 'Foo' ),
				new ValueDescription( new DIBlob( "AA*" ), null, SMW_CMP_LIKE ) ),
			new SomeProperty(
				new DIProperty( 'Bar' ),
				new ValueDescription( new DIBlob( "BB?" ), null, SMW_CMP_NLKE )  )
		) );

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $conjunction );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '{' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( regex( ?v1, "^AA.*$", "s") )' )->addNewLine()
			->addString( '} UNION {' )->addNewLine()
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->addString( 'FILTER( !regex( ?v2, "^BB.$", "s") )' )->addNewLine()
			->addString( '}' )
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testSingleDatePropertyWithGreaterEqualConstraint() {

		$property = new DIProperty( 'SomeDateProperty' );
		$property->setPropertyTypeId( '_dat' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DITime( 1, 1970, 01, 01, 1, 1 ), null, SMW_CMP_GEQ )
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:SomeDateProperty-23aux ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 >= "2440587.5423611"^^xsd:double )' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testSingleSubobjectBuildAsAuxiliaryProperty() {

		$property = new DIProperty( '_SOBJ' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Has_subobject ?v1 .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	/**
	 * '[[HasSomeProperty::Foo||Bar]]'
	 */
	public function testSubqueryDisjunction() {

		$property = new DIProperty( 'HasSomeProperty' );
		$property->setPropertyTypeId( '_wpg' );

		$disjunction = new Disjunction( array(
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ), $property ),
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ), $property )
		) );

		$description = new SomeProperty(
			$property,
			$disjunction
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:HasSomeProperty ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 = wiki:Foo || ?v1 = wiki:Bar )' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	/**
	 * '[[Born in::<q>[[Category:City]] [[Located in::Outback]]</q>]]'
	 */
	public function testNestedPropertyConjunction() {

		$property = DIProperty::newFromUserLabel( 'Born in' );
		$property->setPropertyTypeId( '_wpg' );

		$category = new DIWikiPage( 'City', NS_CATEGORY );

		$categoryName = \SMWTurtleSerializer::getTurtleNameForExpElement(
			\SMWExporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$conjunction = new Conjunction( array(
			new ClassDescription( $category ),
			new SomeProperty(
				DIProperty::newFromUserLabel( 'Located in' ),
				new ValueDescription(
					new DIWikiPage( 'Outback', NS_MAIN ),
					DIProperty::newFromUserLabel( 'Located in' ) )
				)
			)
		);

		$description = new SomeProperty(
			$property,
			$conjunction
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Born_in ?v1 .' )->addNewLine()
			->addString( '{ ' )
			->addString( "{ ?v1 rdf:type $categoryName . }" )->addNewLine()
			->addString( '?v1 property:Located_in wiki:Outback .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	/**
	 * '[[LocatedIn.MemberOf::Wonderland]]'
	 */
	public function testPropertyChain() {

		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'LocatedIn' ),
			new SomeProperty(
				DIProperty::newFromUserLabel( 'MemberOf' ),
				new ValueDescription(
					new DIWikiPage( 'Wonderland', NS_MAIN, '' ),
					DIProperty::newFromUserLabel( 'MemberOf' ), SMW_CMP_EQ
				)
			)
		);

		$instance = new CompoundConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:LocatedIn ?v1 .' )->addNewLine()
			->addString( '{ ?v1 property:MemberOf wiki:Wonderland .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testAddOrderByData_ForNonWikiPageType() {

		$condition = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new CompoundConditionBuilder();
		$instance->addOrderByData( $condition, 'foo', DataItem::TYPE_NUMBER );

		$this->assertEquals(
			'foo',
			$condition->orderByVariable
		);
	}

	public function testAddOrderByData_ForWikiPageType() {

		$condition = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\Condition\Condition' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new CompoundConditionBuilder();
		$instance->addOrderByData( $condition, 'foo', DataItem::TYPE_WIKIPAGE );

		$this->assertEquals(
			'foosk',
			$condition->orderByVariable
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?foo swivt:wikiPageSortKey ?foosk .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$condition->weakConditions['foosk']
		);
	}

	public function testCanUseQFeature() {

		$instance = new CompoundConditionBuilder();

		$this->assertInternalType(
			'boolean',
			$instance->canUseQFeature( 'Foo' )
		);
	}

	public function testTryToFindRedirectVariableForNonWpgDataItem() {

		$instance = new CompoundConditionBuilder();

		$this->assertNull(
			$instance->tryToFindRedirectVariableForDataItem( new DINumber( 1 ) )
		);
	}

	public function testExtendConditionUsingPropertyPathForWpgPropertyValueRedirect() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

		$diWikiPage = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->setConstructorArgs( array( 'Bar', NS_MAIN ) )
			->setMethods( array( 'getTitle' ) )
			->getMock();

		$diWikiPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( $diWikiPage, $property )
		);

		$instance = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->setMethods( array( 'canUseQFeature' ) )
			->getMock();

		$instance->expects( $this->at( 0 ) )
			->method( 'canUseQFeature' )
			->with( $this->equalTo( SMW_SPARQL_QF_REDI ) )
			->will( $this->returnValue( true ) );

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?r2 ^swivt:redirectsTo wiki:Bar .' )->addNewLine()
			->addString( '?result property:Foo ?r2 .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testExtendConditionUsingPropertyPathForWpgValueRedirect() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

		$diWikiPage = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->setConstructorArgs( array( 'Bar', NS_MAIN ) )
			->setMethods( array( 'getTitle' ) )
			->getMock();

		$diWikiPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$description = new ValueDescription( $diWikiPage, null );

		$instance = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->setMethods( array( 'canUseQFeature' ) )
			->getMock();

		$instance->expects( $this->at( 0 ) )
			->method( 'canUseQFeature' )
			->with( $this->equalTo( SMW_SPARQL_QF_REDI ) )
			->will( $this->returnValue( true ) );

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?r1 ^swivt:redirectsTo wiki:Bar .' )->addNewLine()
			->addString( 'FILTER( ?result = ?r1 )' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

}
