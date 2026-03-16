<?php

namespace SMW\Tests\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValues\AllowsPatternValue;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\DataValues\ValueValidators\ConstraintValueValidator;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\Property\SpecificationLookup;
use SMW\Services\DataValueServiceFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\AllowsPatternValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsPatternValueTest extends TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $mediaWikiNsContentReader;
	private $dataValueServiceFactory;
	private $constraintValueValidator;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( MediaWikiNsContentReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'MediaWikiNsContentReader', $this->mediaWikiNsContentReader );

		$propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $propertySpecificationLookup );

		$this->constraintValueValidator = $this->getMockBuilder( ConstraintValueValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->willReturn( new AllowsPatternValueParser( $this->mediaWikiNsContentReader ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->willReturn( $this->constraintValueValidator );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AllowsPatternValue::class,
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
			->willReturn( " \nFoo|Bar\n" );

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
			->willReturn( " \nFoo|Bar\n" );

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
		$allowsPatternValueParser = $this->getMockBuilder( AllowsPatternValueParser::class )
			->disableOriginalConstructor()
			->getMock();

		$allowsPatternValueParser->expects( $this->any() )
			->method( 'parse' )
			->willReturn( 'Foo' );

		$dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->willReturn( $allowsPatternValueParser );

		$dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->willReturn( $this->constraintValueValidator );

		$instance = new AllowsPatternValue();
		$instance->setOption( 'smwgDVFeatures', SMW_DV_PVAP );

		$instance->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance->setUserValue( 'abc/e' );

		$this->assertStringContainsString(
			'Smw_allows_pattern',
			$instance->getShortWikiText()
		);
	}

	public function testGetShortHtmlText() {
		$allowsPatternValueParser = $this->getMockBuilder( AllowsPatternValueParser::class )
			->disableOriginalConstructor()
			->getMock();

		$allowsPatternValueParser->expects( $this->any() )
			->method( 'parse' )
			->willReturn( 'Foo' );

		$dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->willReturn( $allowsPatternValueParser );

		$dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->willReturn( $this->constraintValueValidator );

		$instance = new AllowsPatternValue();
		$instance->setOption( 'smwgDVFeatures', SMW_DV_PVAP );

		$instance->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance->setUserValue( 'abc/e' );

		$this->assertStringContainsString(
			'Smw_allows_pattern',
			$instance->getShortHtmlText()
		);
	}

}
