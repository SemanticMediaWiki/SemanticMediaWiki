<?php

namespace SMW\Tests\Query;

use SMW\DIWikiPage;
use SMW\DIProperty;

use SMWQueryParser as QueryParser;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;
use SMWQuery as Query;
use SMWSomeProperty as SomeProperty;
use SMWThingDescription as ThingDescription;
use SMWValueDescription as ValueDescription;
use SMWConjunction as Conjunction;
use SMWDisjunction as Disjunction;
use SMWClassDescription as ClassDescription;
use SMWNamespaceDescription as NamespaceDescription;

/**
 * @covers \SMWQueryParser
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class QueryParserTest extends \PHPUnit_Framework_TestCase {

	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$this->queryParser = new QueryParser();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWQueryParser',
			new QueryParser()
		);
	}

	public function testPropertyWildardDescription() {

		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'Foo' )->setPropertyTypeId( '_wpg' ),
			new ThingDescription()
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Foo::+]]' )
		);
	}

	public function testNamespaceWildardDescription() {

		$description = new NamespaceDescription( NS_MAIN );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[:+]]' )
		);
	}

	public function testPageDescription() {

		$description = new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN, '' ) );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Foo]]' )
		);
	}

	public function testPropertyNotEqualValueDescription() {

		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'Has foo' )->setPropertyTypeId( '_wpg' ),
			new ValueDescription(
				new DIWikiPage( 'Bar', NS_MAIN, '' ),
				DIProperty::newFromUserLabel( 'Has foo' )->setPropertyTypeId( '_wpg' ),
				SMW_CMP_NEQ
			)
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Has foo::!Bar]]' )
		);
	}

	public function testInversePropertyDescription() {

		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'Has foo', true )->setPropertyTypeId( '_wpg' ),
			new ValueDescription(
				new DIWikiPage( 'Bar', NS_MAIN, '' ),
				DIProperty::newFromUserLabel( 'Has foo', true )->setPropertyTypeId( '_wpg' ),
				SMW_CMP_EQ
			)
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[-Has foo::Bar]]' )
		);
	}

	public function testConjunctionForCategoryPropertyValueGreaterThanOrEqualLessThanOrEqual() {

		$someGreaterThanOrEqualProperty = new SomeProperty(
			DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			new ValueDescription(
				new DIWikiPage( 'A', NS_MAIN, '' ),
				DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ), SMW_CMP_GEQ	)
		);

		$someLessThanOrEqualProperty = new SomeProperty(
			DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
			new ValueDescription(
				new DIWikiPage( 'D', NS_MAIN, '' ),
				DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_LEQ	)
		);

		$classDescription = new ClassDescription(
			new DIWikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$description = new Conjunction();
		$description->addDescription( $classDescription );
		$description->addDescription( $someGreaterThanOrEqualProperty );
		$description->addDescription( $someLessThanOrEqualProperty );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:Foo]] [[One::>A]] [[Two::<D]]' )
		);
	}

	public function testConjunctionForCategoryPropertyChainDescription() {

		$someProperty = new SomeProperty(
			DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			new SomeProperty(
				DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
				new ValueDescription(
					new DIWikiPage( 'Bar', NS_MAIN, '' ),
					DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
				)
			)
		);

		$classDescription = new ClassDescription(
			new DIWikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$description = new Conjunction();
		$description->addDescription( $classDescription );
		$description->addDescription( $someProperty );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:Foo]] [[One.Two::Bar]]' )
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:Foo]] [[One::<q>[[Two::Bar]]</q>]]' )
		);
	}

	public function testDisjunctionForCategoryPropertyChainDescription() {

		$someProperty = new SomeProperty(
			DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			new SomeProperty(
				DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
				new ValueDescription(
					new DIWikiPage( 'Bar', NS_MAIN, '' ),
					DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
				)
			)
		);

		$classDescription = new ClassDescription(
			new DIWikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$description = new Disjunction();
		$description->addDescription( $classDescription );
		$description->addDescription( $someProperty );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:Foo]] OR [[One.Two::Bar]]' )
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:Foo]] OR [[One::<q>[[Two::Bar]]</q>]]' )
		);
	}

	public function testDisjunctionForCategoryChainDescription() {

		$classFooDescription = new ClassDescription(
			new DIWikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$classBarDescription = new ClassDescription(
			new DIWikiPage( 'Bar', NS_CATEGORY, '' )
		);

		$description = new Disjunction();
		$description->addDescription( $classFooDescription );
		$description->addDescription( $classBarDescription );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:Foo||Bar]]' )
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:Foo]] OR [[Category:Bar]]' )
		);
	}

	public function testCombinedSubobjectPropertyChainDescription() {

		$description = new SomeProperty(
			DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			new SomeProperty(
				DIProperty::newFromUserLabel( '_SOBJ' )->setPropertyTypeId( '__sob' ),
				new SomeProperty(
					DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
					new ValueDescription(
						new DIWikiPage( 'Bar', NS_MAIN, '' ),
						DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
					)
				)
			)
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[One.Has subobject.Two::Bar]]' )
		);
	}

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

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[HasSomeProperty::Foo||Bar]]' )
		);
	}

	public function testNestedPropertyConjunction() {

		$property = DIProperty::newFromUserLabel( 'Born in' );
		$property->setPropertyTypeId( '_wpg' );

		$conjunction = new Conjunction( array(
			new ClassDescription( new DIWikiPage( 'City', NS_CATEGORY ) ),
			new SomeProperty(
				DIProperty::newFromUserLabel( 'Located in' )->setPropertyTypeId( '_wpg' ),
				new ValueDescription(
					new DIWikiPage( 'Outback', NS_MAIN ),
					DIProperty::newFromUserLabel( 'Located in' )->setPropertyTypeId( '_wpg' ) )
				)
			)
		);

		$description = new SomeProperty(
			$property,
			$conjunction
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[born in::<q>[[Category:City]] [[located in::Outback]]</q>]]' )
		);
	}

}
