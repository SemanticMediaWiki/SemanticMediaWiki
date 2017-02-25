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
	private $propertySpecificationLookup;
	private $dataValueServiceFactory;

	protected function setUp() {

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'MediaWikiNsContentReader', $this->mediaWikiNsContentReader );

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	//	$this->testEnvironment->resetPoolCacheFor( 'pvap.no.pattern.cache' );

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
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
			->will( $this->returnValue( $constraintValueValidator ) );
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

}
