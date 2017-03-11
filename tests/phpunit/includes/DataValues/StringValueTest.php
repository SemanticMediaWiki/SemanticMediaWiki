<?php

namespace SMW\Tests\DataValues;

use SMWStringValue as StringValue;
use SMW\DataValues\ValueFormatters\StringValueFormatter;

/**
 * @covers \SMWStringValue
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
			'\SMWStringValue',
			new StringValue( '_txt' )
		);
	}

	public function testGetWikiValueForLengthOf() {

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
			'abcdefあい',
			$instance->getWikiValueByLengthOf( 8 )
		);

		$this->assertEquals(
			'abcdefあいうエオ',
			$instance->getWikiValueByLengthOf( 14 )
		);
	}

}
