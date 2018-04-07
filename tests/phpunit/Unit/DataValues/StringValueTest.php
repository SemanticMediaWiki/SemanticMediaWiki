<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\StringValue;
use SMW\DataValues\ValueFormatters\StringValueFormatter;

/**
 * @covers \SMW\DataValues\StringValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class StringValueTest extends \PHPUnit_Framework_TestCase {

	private $dataValueServiceFactory;

	protected function setUp() {
		parent::setUp();

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $constraintValueValidator ) );
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
			->will( $this->returnValue( $stringValueFormatter ) );

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
