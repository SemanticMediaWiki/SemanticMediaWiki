<?php

namespace SMW\Tests\Unit\SPARQLStore\QueryEngine;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\WikiPage;
use SMW\Export\Exporter;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\ConditionBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ConditionBuilderTest extends TestCase {

	private $stringBuilder;
	private $descriptionInterpreterFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->stringBuilder = UtilityFactory::getInstance()->newStringBuilder();
		$this->descriptionInterpreterFactory = new DescriptionInterpreterFactory();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConditionBuilder::class,
			new ConditionBuilder( $this->descriptionInterpreterFactory )
		);
	}

	public function testQueryForSingleProperty() {
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQuerySomeProperty_ForKnownSortPropertyKey() {
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance
			->setSortKeys( [ 'Foo' => 'DESC' ] )
			->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( '{ ?v1 swivt:wikiPageSortKey ?v1sk .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQuerySomeProperty_ForUnknownSortPropertyKey() {
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance
			->setSortKeys( [ 'Bar' => 'DESC' ] )
			->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->addString( '{ ?v2 swivt:wikiPageSortKey ?v2sk .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o3 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o3 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQuerySomeProperty_ForEmptySortPropertyKey() {
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance
			->setSortKeys( [ '' => 'DESC' ] )
			->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQuerySomeProperty_OnInvalidSortKeyThrowsException() {
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$instance->setSortKeys( [ 'Foo', 'ASC' ] );

		$this->expectException( 'RuntimeException' );
		$instance->getConditionFrom( $description );
	}

	public function testQueryForSinglePropertyWithValue() {
		$description = new ValueDescription(
			new Blob( 'SomePropertyValue' ),
			new Property( 'Foo' )
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			SingletonCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '"SomePropertyValue" swivt:page ?url .' )->addNewLine()
			->addString( ' OPTIONAL { "SomePropertyValue" swivt:redirectsTo ?o1 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o1 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSomePropertyWithValue() {
		$property = new Property( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new Blob( 'SomePropertyBlobValue' ) )
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo "SomePropertyBlobValue" .' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSinglePageTypePropertyWithValueComparator() {
		$property = new Property( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new WikiPage( 'SomePropertyPageValue', NS_MAIN ), null, SMW_CMP_LEQ )
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1sk <= "SomePropertyPageValue" )' )->addNewLine()
			->addString( '?v1 swivt:wikiPageSortKey ?v1sk .' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSingleBlobTypePropertyWithNotLikeComparator() {
		$property = new Property( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new Blob( 'SomePropertyBlobValue' ), null, SMW_CMP_NLKE )
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( !regex( ?v1, "^SomePropertyBlobValue$", "s") )' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSingleCategory() {
		$category = new WikiPage( 'Foo', NS_CATEGORY, '' );

		$categoryName = TurtleSerializer::getTurtleNameForExpElement(
			Exporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$description = new ClassDescription(
			$category
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( "{ ?result rdf:type $categoryName . }" )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o1 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o1 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSingleNamespace() {
		$description = new NamespaceDescription( NS_HELP );

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$this->assertSame( 12, NS_HELP );

		$expectedConditionString = $this->stringBuilder
			->addString( '{ ?result swivt:wikiNamespace "12"^^xsd:integer . }' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o1 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o1 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyConjunction() {
		$conjunction = new Conjunction( [
			new SomeProperty(
				new Property( 'Foo' ), new ValueDescription( new Blob( 'SomePropertyValue' ) ) ),
			new SomeProperty(
				new Property( 'Bar' ), new ThingDescription() ),
		] );

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $conjunction );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo "SomePropertyValue" .' )->addNewLine()
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o3 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o3 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyConjunctionWithGreaterLessEqualFilter() {
		$conjunction = new Conjunction( [
			new SomeProperty(
				new Property( 'Foo' ),
				new ValueDescription( new Number( 1 ), null, SMW_CMP_GEQ ) ),
			new SomeProperty(
				new Property( 'Bar' ),
				new ValueDescription( new Number( 9 ), null, SMW_CMP_LEQ ) ),
		] );

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $conjunction );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 >= "1"^^xsd:double )' )->addNewLine()
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->addString( 'FILTER( ?v2 <= "9"^^xsd:double )' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o3 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o3 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyDisjunction() {
		$conjunction = new Disjunction( [
			new SomeProperty( new Property( 'Foo' ), new ThingDescription() ),
			new SomeProperty( new Property( 'Bar' ), new ThingDescription() )
		] );

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $conjunction );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '{' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( '} UNION {' )->addNewLine()
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->addString( '}' )
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o3 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o3 ) ) .' )->addNewLine()

			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyDisjunctionWithLikeNotLikeFilter() {
		$conjunction = new Disjunction( [
			new SomeProperty(
				new Property( 'Foo' ),
				new ValueDescription( new Blob( "AA*" ), null, SMW_CMP_LIKE ) ),
			new SomeProperty(
				new Property( 'Bar' ),
				new ValueDescription( new Blob( "BB?" ), null, SMW_CMP_NLKE ) )
		] );

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $conjunction );

		$this->assertInstanceOf(
			WhereCondition::class,
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
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o3 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o3 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testSingleDatePropertyWithGreaterEqualConstraint() {
		$property = new Property( 'SomeDateProperty' );
		$property->setPropertyTypeId( '_dat' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new Time( 1, 1970, 01, 01, 1, 1 ), null, SMW_CMP_GEQ )
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:SomeDateProperty-23aux ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 >= "2440587.5423611"^^xsd:double )' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testSingleSubobjectBuildAsAuxiliaryProperty() {
		$property = new Property( '_SOBJ' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Has_subobject ?v1 .' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
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
		$property = new Property( 'HasSomeProperty' );
		$property->setPropertyTypeId( '_wpg' );

		$disjunction = new Disjunction( [
			new ValueDescription( new WikiPage( 'Foo', NS_MAIN ), $property ),
			new ValueDescription( new WikiPage( 'Bar', NS_MAIN ), $property )
		] );

		$description = new SomeProperty(
			$property,
			$disjunction
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:HasSomeProperty ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 = wiki:Foo || ?v1 = wiki:Bar )' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
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
		$property = Property::newFromUserLabel( 'Born in' );
		$property->setPropertyTypeId( '_wpg' );

		$category = new WikiPage( 'City', NS_CATEGORY );

		$categoryName = TurtleSerializer::getTurtleNameForExpElement(
			Exporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$conjunction = new Conjunction( [
			new ClassDescription( $category ),
			new SomeProperty(
				Property::newFromUserLabel( 'Located in' ),
				new ValueDescription(
					new WikiPage( 'Outback', NS_MAIN ),
					Property::newFromUserLabel( 'Located in' ) )
				)
			]
		);

		$description = new SomeProperty(
			$property,
			$conjunction
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Born_in ?v1 .' )->addNewLine()
			->addString( '{ ' )
			->addString( "{ ?v1 rdf:type $categoryName . }" )->addNewLine()
			->addString( '?v1 property:Located_in wiki:Outback .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o3 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o3 ) ) .' )->addNewLine()
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
			Property::newFromUserLabel( 'LocatedIn' ),
			new SomeProperty(
				Property::newFromUserLabel( 'MemberOf' ),
				new ValueDescription(
					new WikiPage( 'Wonderland', NS_MAIN, '' ),
					Property::newFromUserLabel( 'MemberOf' ), SMW_CMP_EQ
				)
			)
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:LocatedIn ?v1 .' )->addNewLine()
			->addString( '{ ?v1 property:MemberOf wiki:Wonderland .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o3 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o3 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testAddOrderByData_ForNonWikiPageType() {
		$condition = $this->getMockBuilder( Condition::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$instance->addOrderByData( $condition, 'foo', DataItem::TYPE_NUMBER );

		$this->assertEquals(
			'foo',
			$condition->orderByVariable
		);
	}

	public function testAddOrderByData_ForWikiPageType() {
		$condition = $this->getMockBuilder( Condition::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );
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
		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$this->assertIsBool(

			$instance->isSetFlag( 'Foo' )
		);
	}

	public function testTryToFindRedirectVariableForNonWpgDataItem() {
		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$this->assertNull(
			$instance->tryToFindRedirectVariableForDataItem( new Number( 1 ) )
		);
	}

	public function testExtendConditionUsingPropertyPathForWpgPropertyValueRedirect() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->willReturn( true );

		$diWikiPage = $this->getMockBuilder( WikiPage::class )
			->setConstructorArgs( [ 'Bar', NS_MAIN ] )
			->setMethods( [ 'getTitle' ] )
			->getMock();

		$diWikiPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$property = new Property( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( $diWikiPage, $property )
		);

		$instance = $this->getMockBuilder( ConditionBuilder::class )
			->setConstructorArgs( [ $this->descriptionInterpreterFactory ] )
			->setMethods( [ 'isSetFlag' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isSetFlag' )
			->willReturnCallback( static function ( $flag ) {
				if ( $flag === SMW_SPARQL_QF_NOCASE ) {
					return false;
				}
				if ( $flag === SMW_SPARQL_QF_REDI ) {
					return true;
				}
				return false;
			} );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			WhereCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?r2 ^swivt:redirectsTo wiki:Bar .' )->addNewLine()
			->addString( '?result property:Foo ?r2 .' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o3 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o3 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testExtendConditionUsingPropertyPathForWpgValueRedirect() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->willReturn( true );

		$diWikiPage = $this->getMockBuilder( WikiPage::class )
			->setConstructorArgs( [ 'Bar', NS_MAIN ] )
			->setMethods( [ 'getTitle' ] )
			->getMock();

		$diWikiPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$description = new ValueDescription( $diWikiPage, null );

		$instance = $this->getMockBuilder( ConditionBuilder::class )
			->setConstructorArgs( [ $this->descriptionInterpreterFactory ] )
			->setMethods( [ 'isSetFlag' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'isSetFlag' )
			->willReturnCallback( static function ( $flag ) {
				if ( $flag === SMW_SPARQL_QF_NOCASE ) {
					return false;
				}
				if ( $flag === SMW_SPARQL_QF_REDI ) {
					return true;
				}
				return false;
			} );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			FilterCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( '?r1 ^swivt:redirectsTo wiki:Bar .' )->addNewLine()
			->addString( 'FILTER( ?result = ?r1 )' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testSingletonLikeConditionForSolitaryWpgValue() {
		$description = new ValueDescription(
			new WikiPage( "Foo*", NS_MAIN ), null, SMW_CMP_LIKE
		);

		$instance = new ConditionBuilder( $this->descriptionInterpreterFactory );

		$condition = $instance->getConditionFrom( $description );

		$this->assertInstanceOf(
			SingletonCondition::class,
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( 'FILTER( regex( ?v1, "^Foo.*$", "s") )' )->addNewLine()
			->addString( '?result swivt:wikiPageSortKey ?v1 .' )->addNewLine()
			->addString( ' OPTIONAL { ?result swivt:redirectsTo ?o2 } .' )->addNewLine()
			->addString( ' FILTER ( !bound( ?o2 ) ) .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

}
