<?php

namespace SMW\Tests\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\StringValue;
use SMW\DataValues\ValueFormatters\StringValueFormatter;
use SMW\DataValues\ValueValidators\ConstraintValueValidator;
use SMW\Services\DataValueServiceFactory;

/**
 * @covers \SMW\DataValues\StringValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class StringValueTest extends TestCase {

	private $dataValueServiceFactory;

	protected function setUp(): void {
		parent::setUp();

		$constraintValueValidator = $this->getMockBuilder( ConstraintValueValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->willReturn( $constraintValueValidator );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			StringValue::class,
			new StringValue( '_txt' )
		);
	}

	public function testGetLength() {
		$instance = new StringValue( '_txt' );

		$stringValueFormatter = new StringValueFormatter();
		$stringValueFormatter->setDataValue( $instance );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueFormatter' )
			->willReturn( $stringValueFormatter );

		$instance->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$instance->setUserValue( 'abcdefあいうエオ' );

		$this->assertEquals(
			11,
			$instance->getLength()
		);
	}

}
