<?php

namespace SMW\Tests\Query\Parser;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\DescriptionFactory;
use SMW\Query\Parser\DescriptionProcessor;
use SMW\Query\Parser\LegacyParser as QueryParser;
use SMW\Query\Parser\Tokenizer;
use SMW\Query\QueryToken;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\Parser\LegacyParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class LegacyParserTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $descriptionFactory;
	private $queryParser;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->descriptionFactory = new DescriptionFactory();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->queryParser = new QueryParser(
			new DescriptionProcessor(),
			new Tokenizer(),
			new QueryToken()
		);
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$descriptionProcessor = $this->getMockBuilder( '\SMW\Query\Parser\DescriptionProcessor' )
			->disableOriginalConstructor()
			->getMock();

		$tokenizer = $this->getMockBuilder( '\SMW\Query\Parser\Tokenizer' )
			->disableOriginalConstructor()
			->getMock();

		$queryToken = $this->getMockBuilder( '\SMW\Query\QueryToken' )
			->disableOriginalConstructor()
			->getMock();

		// Legacy class match
		$this->assertInstanceOf(
			'\SMWQueryParser',
			new QueryParser( $descriptionProcessor, $tokenizer, $queryToken )
		);

		$this->assertInstanceOf(
			'\SMW\Query\Parser',
			new QueryParser( $descriptionProcessor, $tokenizer, $queryToken )
		);
	}

	public function testCreateCondition() {

		$this->assertEquals(
			'[[Foo::Bar]]',
			$this->queryParser->createCondition( 'Foo', 'Bar' )
		);

		$this->assertEquals(
			'[[Foo::Bar]]',
			$this->queryParser->createCondition( new DIProperty( 'Foo' ), 'Bar' )
		);
	}

	public function testPropertyWildardDescription() {

		$description = $this->descriptionFactory->newSomeProperty(
			DIProperty::newFromUserLabel( 'Foo' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newThingDescription()
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Foo::+]]' )
		);
	}

	public function testNamespaceWildardDescription() {

		$description = $this->descriptionFactory->newNamespaceDescription(
			NS_MAIN
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[:+]]' )
		);
	}

	public function testPageDescription() {

		$description = $this->descriptionFactory->newValueDescription(
			new DIWikiPage( 'Foo', NS_MAIN, '' )
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Foo]]' )
		);
	}

	public function testPropertyNotEqualValueDescription() {

		$description = $this->descriptionFactory->newSomeProperty(
			DIProperty::newFromUserLabel( 'Has foo' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newValueDescription(
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

		$description = $this->descriptionFactory->newSomeProperty(
			DIProperty::newFromUserLabel( 'Has foo', true )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newValueDescription(
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

		$someGreaterThanOrEqualProperty = $this->descriptionFactory->newSomeProperty(
			DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newValueDescription(
				new DIWikiPage( 'A', NS_MAIN, '' ),
				DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ), SMW_CMP_GEQ	)
		);

		$someLessThanOrEqualProperty = $this->descriptionFactory->newSomeProperty(
			DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newValueDescription(
				new DIWikiPage( 'D', NS_MAIN, '' ),
				DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_LEQ	)
		);

		$classDescription = $this->descriptionFactory->newClassDescription(
			new DIWikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$description = $this->descriptionFactory->newConjunction();
		$description->addDescription( $classDescription );
		$description->addDescription( $someGreaterThanOrEqualProperty );
		$description->addDescription( $someLessThanOrEqualProperty );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Category:Foo]] [[One::>A]] [[Two::<D]]' )
		);
	}

	public function testConjunctionForCategoryPropertyChainDescription() {

		$someProperty = $this->descriptionFactory->newSomeProperty(
			DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newSomeProperty(
				DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
				$this->descriptionFactory->newValueDescription(
					new DIWikiPage( 'Bar', NS_MAIN, '' ),
					DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
				)
			)
		);

		$classDescription = $this->descriptionFactory->newClassDescription(
			new DIWikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$description = $this->descriptionFactory->newConjunction();
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

		$someProperty = $this->descriptionFactory->newSomeProperty(
			DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newSomeProperty(
				DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
				$this->descriptionFactory->newValueDescription(
					new DIWikiPage( 'Bar', NS_MAIN, '' ),
					DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
				)
			)
		);

		$classDescription = $this->descriptionFactory->newClassDescription(
			new DIWikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$description = $this->descriptionFactory->newDisjunction();
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

		$classFooDescription = $this->descriptionFactory->newClassDescription(
			new DIWikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$classBarDescription = $this->descriptionFactory->newClassDescription(
			new DIWikiPage( 'Bar', NS_CATEGORY, '' )
		);

		$description = $this->descriptionFactory->newDisjunction();
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

		$description = $this->descriptionFactory->newSomeProperty(
			DIProperty::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newSomeProperty(
				DIProperty::newFromUserLabel( '_SOBJ' )->setPropertyTypeId( '__sob' ),
				$this->descriptionFactory->newSomeProperty(
					DIProperty::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
					$this->descriptionFactory->newValueDescription(
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

		$disjunction = $this->descriptionFactory->newDisjunction( [
			$this->descriptionFactory->newValueDescription( new DIWikiPage( 'Foo', NS_MAIN ), $property ),
			$this->descriptionFactory->newValueDescription( new DIWikiPage( 'Bar', NS_MAIN ), $property )
		] );

		$description = $this->descriptionFactory->newSomeProperty(
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

		$conjunction = $this->descriptionFactory->newConjunction( [
			$this->descriptionFactory->newClassDescription( new DIWikiPage( 'City', NS_CATEGORY ) ),
			$this->descriptionFactory->newSomeProperty(
				DIProperty::newFromUserLabel( 'Located in' )->setPropertyTypeId( '_wpg' ),
				$this->descriptionFactory->newValueDescription(
					new DIWikiPage( 'Outback', NS_MAIN ),
					DIProperty::newFromUserLabel( 'Located in' )->setPropertyTypeId( '_wpg' ) )
				)
			]
		);

		$description = $this->descriptionFactory->newSomeProperty(
			$property,
			$conjunction
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[born in::<q>[[Category:City]] [[located in::Outback]]</q>]]' )
		);
	}

	public function testRestrictedDefaultNamespace() {

		$property = DIProperty::newFromUserLabel( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = $this->descriptionFactory->newSomeProperty(
			$property,
			$this->descriptionFactory->newValueDescription( new DIWikiPage( 'Bar', NS_MAIN ), $property )
		);

		$description = $this->descriptionFactory->newConjunction( [
			$description,
			$this->descriptionFactory->newNamespaceDescription( NS_MAIN )
		] );

		$this->queryParser->setDefaultNamespaces( [ NS_MAIN ] );

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '<q>[[Foo::Bar]]</q>[[:+]]' )
		);

		$this->assertEmpty(
			$this->queryParser->getErrors()
		);
	}

}
