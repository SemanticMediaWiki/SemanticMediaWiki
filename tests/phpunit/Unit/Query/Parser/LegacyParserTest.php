<?php

namespace SMW\Tests\Unit\Query\Parser;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Query\DescriptionFactory;
use SMW\Query\Parser;
use SMW\Query\Parser\DescriptionProcessor;
use SMW\Query\Parser\LegacyParser as QueryParser;
use SMW\Query\Parser\Tokenizer;
use SMW\Query\QueryToken;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\Parser\LegacyParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class LegacyParserTest extends TestCase {

	private $testEnvironment;
	private $descriptionFactory;
	private $queryParser;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->descriptionFactory = new DescriptionFactory();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->queryParser = new QueryParser(
			new DescriptionProcessor(),
			new Tokenizer(),
			new QueryToken()
		);
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$descriptionProcessor = $this->getMockBuilder( DescriptionProcessor::class )
			->disableOriginalConstructor()
			->getMock();

		$tokenizer = $this->getMockBuilder( Tokenizer::class )
			->disableOriginalConstructor()
			->getMock();

		$queryToken = $this->getMockBuilder( QueryToken::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			Parser::class,
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
			$this->queryParser->createCondition( new Property( 'Foo' ), 'Bar' )
		);
	}

	public function testPropertyWildardDescription() {
		$description = $this->descriptionFactory->newSomeProperty(
			Property::newFromUserLabel( 'Foo' )->setPropertyTypeId( '_wpg' ),
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
			new WikiPage( 'Foo', NS_MAIN, '' )
		);

		$this->assertEquals(
			$description,
			$this->queryParser->getQueryDescription( '[[Foo]]' )
		);
	}

	public function testPropertyNotEqualValueDescription() {
		$description = $this->descriptionFactory->newSomeProperty(
			Property::newFromUserLabel( 'Has foo' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newValueDescription(
				new WikiPage( 'Bar', NS_MAIN, '' ),
				Property::newFromUserLabel( 'Has foo' )->setPropertyTypeId( '_wpg' ),
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
			Property::newFromUserLabel( 'Has foo', true )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newValueDescription(
				new WikiPage( 'Bar', NS_MAIN, '' ),
				Property::newFromUserLabel( 'Has foo', true )->setPropertyTypeId( '_wpg' ),
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
			Property::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newValueDescription(
				new WikiPage( 'A', NS_MAIN, '' ),
				Property::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ), SMW_CMP_GEQ )
		);

		$someLessThanOrEqualProperty = $this->descriptionFactory->newSomeProperty(
			Property::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newValueDescription(
				new WikiPage( 'D', NS_MAIN, '' ),
				Property::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_LEQ )
		);

		$classDescription = $this->descriptionFactory->newClassDescription(
			new WikiPage( 'Foo', NS_CATEGORY, '' )
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
			Property::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newSomeProperty(
				Property::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
				$this->descriptionFactory->newValueDescription(
					new WikiPage( 'Bar', NS_MAIN, '' ),
					Property::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
				)
			)
		);

		$classDescription = $this->descriptionFactory->newClassDescription(
			new WikiPage( 'Foo', NS_CATEGORY, '' )
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
			Property::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newSomeProperty(
				Property::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
				$this->descriptionFactory->newValueDescription(
					new WikiPage( 'Bar', NS_MAIN, '' ),
					Property::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
				)
			)
		);

		$classDescription = $this->descriptionFactory->newClassDescription(
			new WikiPage( 'Foo', NS_CATEGORY, '' )
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
			new WikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$classBarDescription = $this->descriptionFactory->newClassDescription(
			new WikiPage( 'Bar', NS_CATEGORY, '' )
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
			Property::newFromUserLabel( 'One' )->setPropertyTypeId( '_wpg' ),
			$this->descriptionFactory->newSomeProperty(
				Property::newFromUserLabel( '_SOBJ' )->setPropertyTypeId( '__sob' ),
				$this->descriptionFactory->newSomeProperty(
					Property::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ),
					$this->descriptionFactory->newValueDescription(
						new WikiPage( 'Bar', NS_MAIN, '' ),
						Property::newFromUserLabel( 'Two' )->setPropertyTypeId( '_wpg' ), SMW_CMP_EQ
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
		$property = new Property( 'HasSomeProperty' );
		$property->setPropertyTypeId( '_wpg' );

		$disjunction = $this->descriptionFactory->newDisjunction( [
			$this->descriptionFactory->newValueDescription( new WikiPage( 'Foo', NS_MAIN ), $property ),
			$this->descriptionFactory->newValueDescription( new WikiPage( 'Bar', NS_MAIN ), $property )
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
		$property = Property::newFromUserLabel( 'Born in' );
		$property->setPropertyTypeId( '_wpg' );

		$conjunction = $this->descriptionFactory->newConjunction( [
			$this->descriptionFactory->newClassDescription( new WikiPage( 'City', NS_CATEGORY ) ),
			$this->descriptionFactory->newSomeProperty(
				Property::newFromUserLabel( 'Located in' )->setPropertyTypeId( '_wpg' ),
				$this->descriptionFactory->newValueDescription(
					new WikiPage( 'Outback', NS_MAIN ),
					Property::newFromUserLabel( 'Located in' )->setPropertyTypeId( '_wpg' ) )
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
		$property = Property::newFromUserLabel( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = $this->descriptionFactory->newSomeProperty(
			$property,
			$this->descriptionFactory->newValueDescription( new WikiPage( 'Bar', NS_MAIN ), $property )
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
