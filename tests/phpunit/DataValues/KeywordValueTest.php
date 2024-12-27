<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\DataValues\KeywordValue;
use SMW\PropertySpecificationLookup;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataValues\KeywordValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class KeywordValueTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $dataValueServiceFactory;

	protected function setUp(): void {
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
			->willReturn( $this->propertySpecificationLookup );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->willReturn( $constraintValueValidator );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getDataValueFactory' )
			->willReturn( DataValueFactory::getInstance() );

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown(): void {
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
			->willReturn( [] );

		$instance = new KeywordValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );

		$instance->setUserValue( 'LTVCLTVCSGFzLTIwa2V5d29yZC0yMHRlc3Q6OnRFc3QtMjAyLTVELTVEL2Zvcm1hdD1jYXRlZ29yeS8tM0ZIYXMtMjBkZXNjcmlwdGlvbgLTVCLTVCSGFzLTIwa2V5d29yZC0yMHRlc3Q6OnRFc3QtMjAyLTVELTVEL2Zvcm1hdD1jYXRlZ29yeS8tM0ZIYXMtMjBkZXNjcmlwdGlvbgLTVCLTVCSGFzLTIwaLTVCLTVCSGFzLTIwa..........' );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertSame(
			'',
			$instance->getShortWikiText()
		);

		$this->assertContains(
			'smw-datavalue-keyword-maximum-length',
			implode( ', ', $instance->getErrors() )
		);
	}

	public function testGetShortWikiText_WithoutLink() {
		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->willReturn( [] );

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

		$this->propertySpecificationLookup->expects( $this->exactly( 2 ) )
			->method( 'getSpecification' )
			->withConsecutive(
				[
					$this->anything(),
					$this->equalTo( $this->dataItemFactory->newDIProperty( '_FORMAT_SCHEMA' ) )
				],
				[
					$this->anything(),
					$this->equalTo( $this->dataItemFactory->newDIProperty( '_SCHEMA_DEF' ) )
				]
			)
			->willReturnOnConsecutiveCalls(
				[ $this->dataItemFactory->newDIWikiPage( 'Bar', SMW_NS_SCHEMA ) ],
				[ $this->dataItemFactory->newDIBlob( $data ) ]
			);

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

	public function testGetShortWikiText_WithoutLinkFormatting() {
		// inverse testing - this test ensures that when link formatting is disabled, the output is not formatted as a link
		$data = json_encode(
			[
				'type' => 'LINK_FORMAT_SCHEMA',
				'rule' => [ 'link_to' => 'SPECIAL_SEARCH_BY_PROPERTY' ]
			]
		);

		$this->propertySpecificationLookup->expects( $this->exactly( 2 ) )
			->method( 'getSpecification' )
			->withConsecutive(
				[
					$this->anything(),
					$this->equalTo( $this->dataItemFactory->newDIProperty( '_FORMAT_SCHEMA' ) )
				],
				[
					$this->anything(),
					$this->equalTo( $this->dataItemFactory->newDIProperty( '_SCHEMA_DEF' ) )
				]
			)
			->willReturnOnConsecutiveCalls(
				[ $this->dataItemFactory->newDIWikiPage( 'Bar', SMW_NS_SCHEMA ) ],
				[ $this->dataItemFactory->newDIBlob( $data ) ]
			);

		$instance = new KeywordValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );
		$instance->setOption( KeywordValue::OPT_COMPACT_INFOLINKS, false );

		$instance->setUserValue( 'foo' );
		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );

		$this->assertEquals(
			'foo',
			$instance->getShortWikiText()
		);

		$this->assertNotContains(
			'[[:Special:SearchByProperty/cl:OkJhci9mb28|foo]]',
			$instance->getShortWikiText( 'linker' )
		);
	}
}
