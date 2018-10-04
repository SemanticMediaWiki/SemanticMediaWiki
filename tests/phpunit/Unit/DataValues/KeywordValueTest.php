<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\DataValues\KeywordValue;
use SMW\PropertySpecificationLookup;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\KeywordValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class KeywordValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $dataValueServiceFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( PropertySpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$externalFormatterUriValue = $this->getMockBuilder( '\SMW\DataValues\ExternalFormatterUriValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getPropertySpecificationLookup' )
			->will( $this->returnValue( $this->propertySpecificationLookup ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $constraintValueValidator ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getDataValueFactory' )
			->will( $this->returnValue( DataValueFactory::getInstance() ) );

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			KeywordValue::class,
			new KeywordValue()
		);
	}

	public function testErrorWhenLengthExceedsLimit() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->will( $this->returnValue( [] ) );

		$instance = new KeywordValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );

		$instance->setUserValue( 'LTVCLTVCSGFzLTIwa2V5d29yZC0yMHRlc3Q6OnRFc3QtMjAyLTVELTVEL2Zvcm1hdD1jYXRlZ29yeS8tM0ZIYXMtMjBkZXNjcmlwdGlvbgLTVCLTVCSGFzLTIwa2V5d29yZC0yMHRlc3Q6OnRFc3QtMjAyLTVELTVEL2Zvcm1hdD1jYXRlZ29yeS8tM0ZIYXMtMjBkZXNjcmlwdGlvbgLTVCLTVCSGFzLTIwaLTVCLTVCSGFzLTIwa..........' );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			'',
			$instance->getShortWikiText()
		);

		$this->assertContains(
			'smw-datavalue-keyword-maximum-length',
			implode( ', ',  $instance->getErrors() )
		);
	}

	public function testGetShortWikiText_WithoutLink() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->will( $this->returnValue( [] ) );

		$instance = new KeywordValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );

		$instance->setUserValue( 'foo' );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			'foo',
			$instance->getShortWikiText()
		);

		$this->assertEquals(
			'foo',
			$instance->getShortWikiText( 'linker' )
		);
	}

	public function testGetShortWikiText_WithLink() {

		$data = json_encode(
			[
				'type' => 'LINK_FORMAT_SCHEMA',
				'rule' => [ 'link_to' => 'SPECIAL_SEARCH_BY_PROPERTY' ]
			]
		);

		$this->propertySpecificationLookup->expects( $this->at( 0 ) )
			->method( 'getSpecification' )
			->with(
				$this->anything(),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_FORMAT_SCHEMA' ) ) )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIWikiPage( 'Bar', SMW_NS_SCHEMA ) ] ) );

		$this->propertySpecificationLookup->expects( $this->at( 1 ) )
			->method( 'getSpecification' )
			->with(
				$this->anything(),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_SCHEMA_DEF' ) ) )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBlob( $data ) ] ) );

		$instance = new KeywordValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );
		$instance->setOption( KeywordValue::OPT_COMPACT_INFOLINKS, true );

		$instance->setUserValue( 'foo' );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			'foo',
			$instance->getShortWikiText()
		);

		$this->assertContains(
			'[[:Special:SearchByProperty/cl:OkJhci9mb28|foo]]',
			$instance->getShortWikiText( 'linker' )
		);
	}

}
