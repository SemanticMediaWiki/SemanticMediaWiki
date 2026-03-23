<?php

namespace SMW\Tests\Unit\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\DataValues\ExternalFormatterUriValue;
use SMW\DataValues\KeywordValue;
use SMW\DataValues\ValueValidators\ConstraintValueValidator;
use SMW\Property\SpecificationLookup;
use SMW\Services\DataValueServiceFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\KeywordValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class KeywordValueTest extends TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;
	private $dataValueServiceFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$constraintValueValidator = $this->getMockBuilder( ConstraintValueValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$externalFormatterUriValue = $this->getMockBuilder( ExternalFormatterUriValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
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

		$this->assertStringContainsString(
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

		$this->assertStringContainsString(
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

		$this->assertStringNotContainsString(
			'[[:Special:SearchByProperty/cl:OkJhci9mb28|foo]]',
			$instance->getShortWikiText( 'linker' )
		);
	}
}
