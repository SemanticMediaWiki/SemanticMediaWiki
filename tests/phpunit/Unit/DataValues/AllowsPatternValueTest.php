<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValues\AllowsPatternValue;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\AllowsPatternValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $dataValueServiceFactory;
	private $constraintValueValidator;

	protected function setUp() {

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'MediaWikiNsContentReader', $this->mediaWikiNsContentReader );

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $propertySpecificationLookup );

		$this->constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->will( $this->returnValue( new AllowsPatternValueParser( $this->mediaWikiNsContentReader ) ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $this->constraintValueValidator ) );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\AllowsPatternValue',
			new AllowsPatternValue()
		);
	}

	public function testHasErrorForMissingValue() {

		$instance = new AllowsPatternValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setOption( 'smwgDVFeatures', SMW_DV_PVAP );

		$instance->setUserValue( '' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testHasErrorForNonMatchingContent() {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( " \nFoo|Bar\n" ) );

		$instance = new AllowsPatternValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setOption( 'smwgDVFeatures', SMW_DV_PVAP );

		$instance->setUserValue( 'NotMatchable' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testHasNoErrorOnMatchableContent() {

		$this->mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( " \nFoo|Bar\n" ) );

		$instance = new AllowsPatternValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setOption( 'smwgDVFeatures', SMW_DV_PVAP );

		$instance->setUserValue( 'Foo' );

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testErrorOnNotEnabledFeatureWhenUserValueIsNotEmpty() {

		$instance = new AllowsPatternValue();

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'abc/e' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testGetShortWikiText() {

		$allowsPatternValueParser = $this->getMockBuilder( '\SMW\DataValues\ValueParsers\AllowsPatternValueParser' )
			->disableOriginalConstructor()
			->getMock();

		$allowsPatternValueParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( 'Foo' ) );

		$dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->will( $this->returnValue( $allowsPatternValueParser ) );

		$dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $this->constraintValueValidator ) );

		$instance = new AllowsPatternValue();
		$instance->setOption( 'smwgDVFeatures', SMW_DV_PVAP );

		$instance->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance->setUserValue( 'abc/e' );

		$this->assertContains(
			'Smw_allows_pattern',
			$instance->getShortWikiText()
		);
	}

	public function testGetShortHtmlText() {

		$allowsPatternValueParser = $this->getMockBuilder( '\SMW\DataValues\ValueParsers\AllowsPatternValueParser' )
			->disableOriginalConstructor()
			->getMock();

		$allowsPatternValueParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( 'Foo' ) );

		$dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->will( $this->returnValue( $allowsPatternValueParser ) );

		$dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $this->constraintValueValidator ) );

		$instance = new AllowsPatternValue();
		$instance->setOption( 'smwgDVFeatures', SMW_DV_PVAP );

		$instance->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance->setUserValue( 'abc/e' );

		$this->assertContains(
			'Smw_allows_pattern',
			$instance->getShortHtmlText()
		);
	}

}
